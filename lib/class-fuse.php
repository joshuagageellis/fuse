<?php
/**
 * Main class.
 *
 * @package fuse
 */

/**
 * Primary class.
 * Expects a config object.
 */
class Fuse {
	/**
	 * Config.
	 *
	 * @var Fuse_Config;
	 */
	private $config;

	/**
	 * Updater.
	 *
	 * @var Fuse_Updater;
	 */
	private $updater;

	/**
	 * API request.
	 *
	 * @var Fuse_API_Request;
	 */
	private $api_request;

	/**
	 * Schedule options meta key.
	 *
	 * @var string
	 */
	private $options_meta_key = 'fuse_schedule_';

	/**
	 * CRON hook.
	 *
	 * @var string
	 */
	private $cron_key = 'fuse_cron_';

	/**
	 * Parent cron.
	 *
	 * @var Fuse_Cron
	 */
	private $parent_cron;

	/**
	 * Child cron.
	 *
	 * @var Fuse_Cron
	 */
	private $child_cron;

	/**
	 * Constructor.
	 *
	 * @param Fuse_Config $config The config.
	 */
	public function __construct( Fuse_Config $config ) {
		$this->config           = $config;
		$this->updater          = new Fuse_Updater( $config );
		$this->api_request      = new Fuse_API_Request( $config );
		$this->options_meta_key = $this->options_meta_key . $config->post_type;
		$this->cron_key         = $this->cron_key . $config->post_type . '_';
	}

	/**
	 * Add Actions.
	 */
	public function register() {
		/**
		 * Register hooks.
		 */
		add_action( 'wp_loaded', array( $this, 'register_parent_schedule' ) );
		add_action( 'wp_loaded', array( $this, 'register_child_schedule' ) );
		add_action( $this->cron_key . 'cleanup', array( $this, 'cleanup_action' ) );

		/**
		 * Register deactivation hook.
		 */
		register_deactivation_hook( FUSE_DEACTIVATE_HOOK, array( $this, 'fuse_unschedule' ) );
	}

	/**
	 * Deactivation hook.
	 */
	public function fuse_unschedule() {
		$this->unschedule_cleanup();
		$this->parent_cron->unschedule_event();
		$this->child_cron->unschedule_event();
	}

	/**
	 * Get the config.
	 *
	 * @return array
	 */
	public function get_config() {
		return $this->config;
	}

	/**
	 * Get the API request.
	 *
	 * @return Fuse_API_Request
	 */
	public function get_api_request() {
		return $this->api_request;
	}

	/**
	 * Get the updater.
	 *
	 * @return Fuse_Updater
	 */
	public function get_updater() {
		return $this->updater;
	}

	/**
	 * Parent process.
	 * Runs on parent schedule.
	 * Sets up the schedule tracker if not already running.
	 */
	public function parent_process() {
		// Ensure we're not already running a batch.
		$in_progress = $this->get_schedule_tracker();
		if ( $in_progress && $in_progress['pointer'] < $in_progress['total'] ) {
			return;
		}

		$api_request = $this->api_request;
		$data        = $api_request->get_api_data();

		if ( ! $data ) {
			throw new Exception( 'API failure', 500 );
		}

		/**
		 * Set up batch.
		 */
		$count = count( $data );
		$this->set_schedule_tracker( time(), $count, 0 );

		/**
		 * Run clean up on delay
		 */
		$this->schedule_cleanup();
	}

	/**
	 * Individual batch process.
	 * Runs on child schedule.
	 */
	public function child_process() {
		$in_progress = $this->get_schedule_tracker();

		// No batch in progress.
		if ( ! $in_progress ) {
			return;
		}

		// Pointer exceeds or matches total.
		if ( $in_progress['pointer'] >= $in_progress['total'] ) {
			$this->delete_schedule_tracker();
			return;
		}

		// Get data and run batch.
		$api_request = $this->api_request;
		$data        = $api_request->get_api_data();

		if ( ! $data ) {
			throw new Exception( 'API failure', 500 );
		}

		// Update.
		$batch   = array_slice( $data, $in_progress['pointer'], $this->config->batch_size );
		$updater = $this->updater;
		$updater->update( $batch );

		// Update pointer.
		$this->update_schedule_tracker( $in_progress['pointer'] + $this->config->batch_size );
	}

	/**
	 * Register parent cron schedule.
	 */
	public function register_parent_schedule() {
		$this->parent_cron = Fuse_Cron::init(
			$this->cron_key . 'parent',
			$this->config->parent_interval,
			array( $this, 'parent_process' )
		);
	}

	/**
	 * Register sub schedule.
	 * If posts need to be updated.
	 */
	public function register_child_schedule() {
		$this->child_cron = Fuse_Cron::init(
			$this->cron_key . 'child',
			$this->config->child_interval,
			array( $this, 'child_process' )
		);
	}

	/**
	 * Schedule cleanup.
	 */
	public function schedule_cleanup() {
		if ( ! wp_next_scheduled( $this->cron_key . 'cleanup' ) ) {
			wp_schedule_event( time(), 'one_minute', $this->cron_key . 'cleanup' );
		}
	}

	/**
	 * Schedule cleanup.
	 */
	public function unschedule_cleanup() {
		$timestamp = wp_next_scheduled( $this->cron_key . 'cleanup' );
		wp_unschedule_event( $timestamp, $this->cron_key . 'cleanup' );
	}

	/**
	 * Cleanup action.
	 * Runs immeidately after parent schedule.
	 * Performs a full query against the DB to check if posts need to be deleted.
	 */
	public function cleanup_action() {
		$api_request = $this->api_request;
		$data        = $api_request->get_api_data();

		if ( ! $data ) {
			throw new Exception( 'API failure', 500 );
		}

		// Update.
		$updater = $this->updater;
		$updater->cleanup( $data );

		// Unschedule self.
		$this->unschedule_cleanup();
	}

	/**
	 * Get schedule tracker.
	 */
	private function get_schedule_tracker() {
		return get_option( $this->options_meta_key );
	}

	/**
	 * Update schedule tracker.
	 *
	 * - timestamp of parent schedule call
	 * - total number of posts to update
	 * - current pointer
	 */
	private function set_schedule_tracker( $timestamp = null, $total, $pointer ) {
		if ( ! $timestamp ) {
			$timestamp = time();
		}

		$tracker = array(
			'timestamp' => $timestamp,
			'total'     => $total,
			'pointer'   => $pointer,
		);

		update_option( $this->options_meta_key, $tracker, false );
	}

	/**
	 * Update schedule tracker.
	 * Handles pointer increment and removing tracker on completion.
	 */
	private function update_schedule_tracker( $pointer ) {
		$tracker = get_option( $this->options_meta_key );
		if ( ! $tracker ) {
			return false;
		}

		if ( $tracker['total'] === $pointer ) {
			$this->delete_schedule_tracker();
			return false;
		}

		$tracker['pointer'] = $pointer;
		update_option( $this->options_meta_key, $tracker, false );
		return true;
	}

	/**
	 * Delete schedule tracker.
	 */
	private function delete_schedule_tracker() {
		delete_option( $this->options_meta_key );
	}
}
