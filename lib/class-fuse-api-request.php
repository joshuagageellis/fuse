<?php
/**
 * Fuse API request.
 *
 * @package fuse
 */

class Fuse_API_Request {
	/**
	 * Config.
	 *
	 * @var Fuse_Config;
	 */
	private $config;

	/**
	 * Standard cache time.
	 *
	 * @var int
	 */
	private $cache_time = 1 * HOUR_IN_SECONDS;

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	private $cache_key;

	/**
	 * Cache prefix.
	 */
	private static $cache_prefix = 'fuse-cache_';

	/**
	 * Constructor.
	 *
	 * @param Fuse_Config $config The config.
	 */
	public function __construct( Fuse_Config $config ) {
		$this->config = $config;
		$this->cache_key = sanitize_title( self::$cache_prefix . $this->config->post_type );
	}

	/**
	 * Handle response caching.
	 */
	private function get_cachable_response() {
		$cache = get_transient( $this->cache_key );
		if ( false === $cache ) {
			$cache = $this->get_api_response();
			if ( $cache ) {
				set_transient( $this->cache_key, $cache, $this->cache_time );
			}
		}
		return $cache;
	}

	/**
	 * Get the API data.
	 *
	 * @return array|bool
	 */
	public function get_api_response() {
		$url = $this->config->get_api_url();
		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$body = wp_remote_retrieve_body( $response );
		if ( is_wp_error( $body ) || empty( $body ) ) {
			return false;
		}
		return json_decode( $body );
	}

	/**
	 * Get API data.
	 */
	public function get_api_data( $cacheable = true ) {
		if ( $cacheable ) {
			return $this->get_cachable_response();
		}
		return $this->get_api_response();
	}
}