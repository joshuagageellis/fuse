<?php
/**
 * Post updater.
 *
 * @package fuse
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
	 */
	private $meta_identifier_key = 'fuse_id';

	/**
	 * Constructor.
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
	 */
	public function update( $data ) {
		$post_type = $this->config->post_type;

		foreach ( $data as $key => $insertable_post ) {
			/* Extract meta and insert mapping from config */
			$args = array_merge(
				array( 'post_type' => $post_type ),
				$this->get_meta_map( $this->config->postarr_map, $insertable_post ),
				array( 'meta_input' => $this->get_meta_map( $this->config->meta_input_map, $insertable_post ) ),
				array( 'tax_input' => $this->get_meta_map( $this->config->tax_input_map, $insertable_post ) ),
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

			if ( $existing_post ) {
				$args['ID'] = $existing_post[0];
			} else {
				unset( $args['ID'] );
			}

			$post_id = wp_insert_post( $args );
			if ( is_wp_error( $post_id ) || 0 === $post_id ) {
				throw new Exception( sprintf( 'Error on post %1$s', $post_id ), 500 );
			}
		}
	}

	/**
	 * Get field value.
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
	 */
	public function get_meta_map( array $map, stdClass $post ) {
		$output = array();

		foreach ( $map as $meta_key => $field ) {
			$output[ $meta_key ] = $this->get_field_value( $field, $post );
		}

		return $output;
	}
}
