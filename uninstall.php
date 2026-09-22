<?php
/**
 * Uninstall cleanup.
 *
 * @package CssLiteForElementor
 */

// Stop direct file access unless WordPress is uninstalling the plugin.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$eccl_settings = get_option( 'eccl_settings', array() );

// Keep user data by default; only delete settings when the admin explicitly opted in.
if ( is_array( $eccl_settings ) && ! empty( $eccl_settings['delete_on_uninstall'] ) ) {
	delete_option( 'eccl_settings' );
}
