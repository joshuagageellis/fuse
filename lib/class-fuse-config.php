<?php
/**
 * Standard config object.
 * Strictly typed object.
 *
 * @package fuse
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
	 * Constructor.
	 */
	public function __construct(
		string $post_type,
		string $api_endpoint,
		string $api
	) {
		$this->post_type    = $post_type;
		$this->api_endpoint = $api_endpoint;
		$this->api          = $api;
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
		} elseif ( 'tax' === $meta_type ) {
			$this->tax_input_map[ $meta_key ] = $config;
		}
	}
}
