<?php
/**
 * Plugin Name:     Fuse
 * Plugin URI:      https://wideeye.co/
 * Description:     Sync an arbitrary API to a WordPress post type.
 * Author:          Joshua Gage Ellis
 * Author URI:      https://wideeye.co/
 * Text Domain:     fuse
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         fuse
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FUSE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FUSE_URL', plugin_dir_url( __FILE__ ) );
define( 'FUSE_DEACTIVATE_HOOK', __FILE__ );

require FUSE_DIR . 'lib/class-fuse-config.php';
require FUSE_DIR . 'lib/class-fuse.php';
require FUSE_DIR . 'lib/class-fuse-cron.php';
require FUSE_DIR . 'lib/class-fuse-updater.php';
require FUSE_DIR . 'lib/class-fuse-api-request.php';

/**
 * Post type config registration.
 */
require FUSE_DIR . 'lib/configs/post.php';
