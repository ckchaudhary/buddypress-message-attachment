<?php
/**
 * Plugin Name: BuddyPress Message Attachment
 * Plugin URI: http://webdeveloperswall.com/buddypress/buddypress-message-attachment
 * Description: Extend BuddyPress' private message feature by enabling attachments. This plugin enables users to send attachments in private messages.
 * Version: 3.0.0
 * Author: ckchaudhary
 * Author URI: http://webdeveloperswall.com/author/ckchaudhary
 * Text Domain: bp-msgat
 * Domain Path: /languages
 *
 * @package bpmsgat
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Directory.
if ( ! defined( 'BPMSGAT_PLUGIN_DIR' ) ) {
	define( 'BPMSGAT_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

// Url.
if ( ! defined( 'BPMSGAT_PLUGIN_URL' ) ) {
	$plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );

	// If we're using https, update the protocol.
	if ( is_ssl() ) {
		$plugin_url = str_replace( 'http://', 'https://', $plugin_url );
	}

	define( 'BPMSGAT_PLUGIN_URL', $plugin_url );
}

/**
 * Instantiate the main plugin class
 *
 * @return void
 */
function bp_msgat_init() {
	require BPMSGAT_PLUGIN_DIR . 'includes/class-bp-msgat-plugin.php';
	bp_message_attachment();
}
add_action( 'plugins_loaded', 'bp_msgat_init' );

/**
 * Returns plugins instance.
 * Must be called after plugins_loaded.
 *
 * @since 2.0
 * @return \BP_Msgat_Plugin plugin object
 */
function bp_message_attachment() {
	return BP_Msgat_Plugin::instance();
}
