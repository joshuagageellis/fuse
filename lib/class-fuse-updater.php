<?php
/**
 * Post updater.
 *
 * @package fuse
 */

/**
 * Updater.
 * Handles all post updates.
 */
class Fuse_Updater {
	/**
	 * Config.
	 *
	 * @var Fuse_Config;
	 */
	private $config;

	/**
	 * Meta identifier key.
	 * Used to identify posts to update.
	 *
	 * @var string
	 */
	private $meta_identifier_key = 'fuse_id';

	/**
	 * Constructor.
	 *
	 * @param Fuse_Config $config The config.
	 */
	public function __construct( Fuse_Config $config ) {
		$this->config = $config;
	}

	/**
	 * Sanitization.
	 *
	 * @param mixed  $value Value.
	 * @param string $type The custom type from schema.
	 */
	public static function sanitize( $value, $type ) {
		switch ( $type ) {
			case 'string':
				return sanitize_text_field( $value );
			case 'date':
				return sanitize_text_field( wp_date( 'Y-m-d g:i:s A', strtotime( $value ) ) );
			case 'number':
				return absint( $value );
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Walk parsed stdClass response from API.
	 *
	 * @param string   $prop The property to walk.
	 * @param stdClass $obj The object to walk.
	 * @return mixed The value.
	 * @throws Exception If the property doesn't exist.
	 */
	public static function walk_object( $prop, $obj ) {
		$exploded = explode( '.', $prop );
		if ( ! $exploded[0] || ! property_exists( $obj, $exploded[0] ) ) {
			return null;
		}

		$value = $obj->{ $exploded[0] };
		if ( 1 === count( $exploded ) ) {
			return $value;
		}

		array_shift( $exploded );
		foreach ( $exploded as $p ) {
			if ( ! property_exists( $value, $p ) ) {
				throw new Exception( sprintf( 'Error on field %1$s', $prop ), 500 );
			}

			if ( array_key_exists( $p, $value ) ) {
				$value = $value[ $p ];
			} else {
				throw new Exception( sprintf( 'Error on field %1$s', $prop ), 500 );
			}
		}
		return $value;
	}

	/**
	 * Esentially insert post w/ parsed meta.
	 *
	 * @param array $data The data to insert.
	 * @throws Exception If the post errors.
	 */
	public function update( $data ) {
		$post_type = $this->config->post_type;

		foreach ( $data as $key => $insertable_post ) {
			/* Extract meta and insert mapping from config */
			$args = array_merge(
				array( 'post_type' => $post_type ),
				$this->get_meta_map( $this->config->postarr_map, $insertable_post ),
				array( 'meta_input' => $this->get_meta_map( $this->config->meta_input_map, $insertable_post ) ), // @TODO: Implement meta map.
			);

			// Must have an ID.
			if ( ! array_key_exists( 'ID', $args ) ) {
				throw new Exception( sprintf( 'Error on post %1$s', $key ), 500 );
			}

			/**
			 * Get post by meta identifier, if none set remove ID.
			 */
			$args['meta_input'][ $this->meta_identifier_key ] = $args['ID'];
			$existing_post                                    = get_posts(
				array(
					'post_type'      => $post_type,
					'posts_per_page' => 1,
					'meta_key'       => $this->meta_identifier_key,
					'meta_value'     => $args['ID'],
					'fields'         => 'ids', // Returns only post ids.
				)
			);

			// If post exists, set ID and post_date.
			// If we need to retain current data we should do so here.
			if ( $existing_post ) {
				$args['ID']        = $existing_post[0];
				$args['post_date'] = get_post_datetime( $args['ID'] )->format( 'Y-m-d H:i:s' );
			} else {
				unset( $args['ID'] );
			}

			$post_id = wp_insert_post( $args );
			if ( is_wp_error( $post_id ) || 0 === $post_id ) {
				throw new Exception( sprintf( 'Error on post %1$s', $post_id ), 500 );
			}

			/**
			 * Taxonomy insert.
			 *
			 * Cron jobs do not pass the current_user can check when inserting via wp_insert_post.
			 * Taxonomies need to be handled separately and after post insertion.
			 */
			$tax_args = $this->get_taxonomy_map( $this->config->tax_input_map, $insertable_post );
			foreach ( $tax_args as $taxonomy => $terms ) {
				wp_set_object_terms( $post_id, $terms, $taxonomy, false );
			}
		}
	}

	/**
	 * Get field value.
	 *
	 * @param array    $field The field to get.
	 * @param stdClass $post The post to get the field from.
	 * @return mixed The value.
	 */
	public function get_field_value( array $field, stdClass $post ) {
		// Handle manual, no check.
		if ( array_key_exists( 'manual', $field ) && $field['manual'] ) {
			if ( array_key_exists( 'manual_value', $field ) ) {
				return $field['manual_value'];
			}
			return null;
		}
		// Standard values from response.
		return self::sanitize( self::walk_object( $field['api_field_key'], $post ), $field['data_type'] );
	}

	/**
	 * Get meta map.
	 * How the API data maps to wp_insert_post.
	 *
	 * @param array    $map The map to parse.
	 * @param stdClass $post The post to get the field from.
	 * @return array The parsed map.
	 */
	public function get_meta_map( array $map, stdClass $post ) {
		$output = array();

		foreach ( $map as $meta_key => $field ) {
			$output[ $meta_key ] = $this->get_field_value( $field, $post );
		}

		return $output;
	}

	/**
	 * Get taxonomy map.
	 * Handles registering and inserting taxonomy terms.
	 *
	 * @param array    $map The map to parse.
	 * @param stdClass $post The post to get the field from.
	 * @return array The parsed map.
	 */
	public function get_taxonomy_map( array $map, stdClass $post ) {
		$output = array();

		// Parse api field.
		foreach ( $map as $meta_key => $field ) {
			$output[ $meta_key ] = $this->get_field_value( $field, $post );
		}

		// Get term ids and register if necessary.
		foreach ( $output as $taxonomy => $term ) {
			$term_ids     = array();
			$slugify_term = sanitize_title( $term ); // Normalize term.
			$term_check   = term_exists( $slugify_term, $taxonomy );

			// If term doesn't exist, create it.
			if ( null === $term_check ) {
				$tmp_term_id = wp_insert_term( $term, $taxonomy );
				if ( is_wp_error( $tmp_term_id ) ) {
					continue;
				}
				$term_ids[] = $tmp_term_id['term_id'];
			} elseif ( is_array( $term_check ) ) {
				$term_ids[] = $term_check['term_id'];
			}
			// Ensure intval for term ids.
			$output[ $taxonomy ] = array_map( 'intval', $term_ids );
		}

		return $output;
	}

	/**
	 * Clean up update.
	 * Remove posts that are no longer in API.
	 * Expensive operation.
	 *
	 * @param array $data The data from the API.
	 * @throws Exception If error.
	 * @return void
	 */
	public function cleanup( array $data ) {
		// Query all data in post type.
		$posts = get_posts(
			array(
				'post_type'      => $this->config->post_type,
				'posts_per_page' => -1,
				'no_found_rows'  => true, // We don't need pagination.
				'fields'         => 'ids', // Returns only post ids.
				'meta_query'     => array(
					array(
						'key'     => $this->meta_identifier_key,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		// Get all IDs from API.
		try {
			// Config defined ID from api, could be anything.
			$id_key = $this->config->postarr_map['ID']['api_field_key'];
		} catch ( Exception $e ) {
			throw new Exception( 'No ID key found in postarr_map', 500 );
		}

		$api_ids = array();
		foreach ( $data as $insertable_post ) {
			$api_ids[] = (int) $insertable_post->{ $id_key };
		}

		// Delete all posts that are not in API.
		foreach ( $posts as $post_id ) {
			$meta = (int) get_post_meta( $post_id, $this->meta_identifier_key, true );
			if ( ! in_array( $meta, $api_ids, true ) ) {
				wp_delete_post( $post_id, false );
			}
		}
	}
}
