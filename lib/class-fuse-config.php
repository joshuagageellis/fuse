<?php
/**
 * Standard config object.
 * Strictly typed object.
 *
 * @package fuse
 */

/**
 * Config object.
 * Define the post type, API endpoint, and field mapping.
 * Optionally define batch size, parent interval, and child interval.
 */
class Fuse_Config {
	/**
	 * Post type.
	 *
	 * @var string
	 */
	public $post_type;

	/**
	 * API endpoint.
	 *
	 * @var string
	 */
	public $api_endpoint;

	/**
	 * API
	 * Base API url.
	 *
	 * @var string
	 */
	public $api;

	/**
	 * Postarr map.
	 *
	 * @var array
	 */
	public $postarr_map = array();

	/**
	 * Meta fields to map.
	 *
	 * @var array
	 */
	public $meta_input_map = array();

	/**
	 * Taxonomy fields to map.
	 *
	 * @var array
	 */
	public $tax_input_map = array();

	/**
	 * Batch size.
	 *
	 * @var int
	 */
	public $batch_size;

	/**
	 * Parent interval.
	 * When the parent cron job should run.
	 *
	 * @var int
	 */
	public $parent_interval;

	/**
	 * Child interval.
	 * When the child cron job should run.
	 *
	 * @var int
	 */
	public $child_interval;

	/**
	 * Constructor.
	 *
	 * @param string $post_type       The post type.
	 * @param string $api_endpoint    The API endpoint.
	 * @param string $api             The API.
	 * @param int    $batch_size      The batch size. Optional.
	 * @param int    $parent_interval The parent interval. Optional.
	 * @param int    $child_interval  The child interval. Optional.
	 */
	public function __construct(
		string $post_type,
		string $api_endpoint,
		string $api,
		int $batch_size = 10,
		int $parent_interval = 86400,
		int $child_interval = 600
	) {
		$this->post_type       = $post_type;
		$this->api_endpoint    = $api_endpoint;
		$this->api             = $api;
		$this->batch_size      = $batch_size;
		$this->parent_interval = $parent_interval;
		$this->child_interval  = $child_interval;
	}

	/**
	 * Get full API endpoint.
	 */
	public function get_api_url() {
		return $this->api . $this->api_endpoint;
	}

	/**
	 * Define meta map field.
	 *
	 * @param string $meta_key The meta key.
	 * @param array  $config {
	 *   Array of field mapping configurations, allows either api JSON parsing or manual values.
	 *
	 *   @type string $meta_type 'insert'|'tax'|'meta' The insert post arg being updated.
	 *   @type string $data_type 'string'|'number'|'date' Appropriate snaitization and data normalization.
	 *   @type string $api_field_key Supports nested values via . notation ('field.subfield')
	 *   @type bool $manual Is manual input?
	 *   @type string $manual_value The manual value. Ignored if not explicitly a manual update.
	 * }
	 */
	public function define_field_map(
		string $meta_key,
		array $config
	) {
		$default = array(
			'meta_type'     => 'insert',
			'data_type'     => '',
			'api_field_key' => '',
			'manual'        => false,
			'manual_value'  => '',
		);

		$config = wp_parse_args( $config, $default );

		$meta_type = $config['meta_type'];
		if ( 'insert' === $meta_type ) {
			$this->postarr_map[ $meta_key ] = $config;
		} elseif ( 'meta' === $meta_type ) {
			$this->meta_input_map[ $meta_key ] = $config;
		} elseif ( 'tax' === $meta_type || 'taxonomy' === $meta_type ) {
			$this->tax_input_map[ $meta_key ] = $config;
		}
	}
}
