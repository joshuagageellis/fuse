<?php
/**
 * Plugin Name:     Plugin Name
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     PLUGIN DESCRIPTION HERE
 * Author:          YOUR NAME HERE
 * Author URI:      YOUR SITE HERE
 * Text Domain:     plugin-name
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'plugin_DIR', plugin_dir_path( __FILE__ ) );
define( 'plugin_URL', plugin_dir_url( __FILE__ ) );

// Your code starts here.