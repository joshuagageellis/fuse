<?php
/**
 * Simple cron helper class.
 * Inspired by: https://ericbusch.net/simplify-wordpress-cron/
 *
 * @package fuse
 */

class Fuse_Cron {
	/** @var string $hook A name for this cron. */
	public $hook;

	/** @var int $interval How often to run this cron in seconds. */
	public $interval;

	/** @var Closure|string|null $callback Optional. Anonymous function, function name or null to override with your own handle() method. */
	public $callback;

	/** @var array $args Optional. An array of arguments to pass into the callback. */
	public $args;

	/** @var string $recurrence How often the event should subsequently recur. See wp_get_schedules(). */
	public $recurrence;

	private function __construct( $hook, $interval, $callback = null, $args = [] ) {

		$this->hook     = trim( $hook );
		$this->interval = absint( $interval );
		$this->callback = $callback;
		$this->args     = $args;
		$this->set_recurrence();

		add_action( 'wp', [ $this, 'schedule_event' ] );
		add_filter( 'cron_schedules', [ $this, 'add_schedule' ] );
		add_action( $this->hook, [ $this, 'handle' ] );
	}

	public static function init( $hook, $interval, $callback = null, $args = [] ) {
		return new static( $hook, $interval, $callback, $args );
	}

	public function handle() {
		if ( is_callable( $this->callback ) ) {
			call_user_func_array( $this->callback, $this->args );
		}
	}

	public function schedule_event() {
		if ( ! wp_next_scheduled( $this->hook, $this->args ) ) {
			wp_schedule_event( time(), $this->recurrence, $this->hook, $this->args );
		}
	}

	public function add_schedule( $schedules ) {

		if ( in_array( $this->recurrence, $this->default_wp_recurrences() ) ) {
			return $schedules;
		}

		$schedules[ $this->recurrence ] = [
			'interval' => $this->interval,
			'display'  => __( 'Every ' . $this->interval . ' seconds' ),
		];

		return $schedules;
	}

	public function unschedule_event() {
		wp_clear_scheduled_hook( $this->hook, $this->args );
	}

	private function set_recurrence() {
		foreach ( $this->default_wp_schedules() as $recurrence => $schedule ) {
			if ( $this->interval == absint( $schedule['interval'] ) ) {
				$this->recurrence = $recurrence;

				return;
			}
		}

		$this->recurrence = 'every_' . absint( $this->interval ) . '_seconds';
	}

	private function default_wp_schedules() {
		return array_filter(
			wp_get_schedules(),
			function ( $schedule ) {
				return in_array( $schedule, $this->default_wp_recurrences() );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	private function default_wp_recurrences() {
		return [ 'hourly', 'twicedaily', 'daily' ];
	}
}
