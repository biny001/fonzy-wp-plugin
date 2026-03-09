<?php
/**
 * Plugin Name: Fonzy - AI Content Publisher
 * Plugin URI: https://fonzy.ai/wordpress
 * Description: Connects your WordPress site to Fonzy.ai for automated article publishing with SEO meta support.
 * Version: 1.0.0
 * Author: Fonzy.ai
 * Author URI: https://fonzy.ai
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fonzy-ai-content-publisher
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 *
 * @package Fonzy
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FONZY_VERSION', '1.0.0' );
define( 'FONZY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FONZY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FONZY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once FONZY_PLUGIN_DIR . 'includes/class-fonzy-rest-api.php';
require_once FONZY_PLUGIN_DIR . 'includes/class-fonzy-publisher.php';
require_once FONZY_PLUGIN_DIR . 'includes/class-fonzy-settings.php';

/**
 * Initialize the plugin.
 *
 * @since 1.0.0
 */
function fonzy_init() {
	$settings = new Fonzy_Settings();
	$settings->init();

	$publisher = new Fonzy_Publisher();
	$rest_api  = new Fonzy_REST_API( $publisher );
	$rest_api->init();
}
add_action( 'plugins_loaded', 'fonzy_init' );

/**
 * Activation hook — flush rewrite rules so REST routes register immediately.
 *
 * @since 1.0.0
 */
function fonzy_activate() {
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'fonzy_activate' );

/**
 * Deactivation hook.
 *
 * @since 1.0.0
 */
function fonzy_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'fonzy_deactivate' );
