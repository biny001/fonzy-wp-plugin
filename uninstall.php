<?php
/**
 * Fonzy Uninstall
 *
 * Fired when the plugin is deleted via the WordPress admin.
 *
 * @package Fonzy
 * @since   1.0.0
 */

// If uninstall not called from WordPress, abort.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

// The Fonzy plugin does not store any options or custom database tables,
// so there is nothing to clean up on uninstall. If future versions add
// plugin-specific options, delete them here:
//
// delete_option( 'fonzy_option_name' );
//
// For multisite:
// $sites = get_sites();
// foreach ( $sites as $site ) {
//     switch_to_blog( $site->blog_id );
//     delete_option( 'fonzy_option_name' );
//     restore_current_blog();
// }
