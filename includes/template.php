<?php
/**
 * Template functions
 *
 * @package bpmsgat
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Load a template file.
 *
 * @param string $template template file name.
 * @param string $variation template file variation, if any.
 *
 * @return void
 */
function bp_msgat_load_template( $template, $variation = '' ) {
	$file = $template;

	if ( $variation ) {
		$file .= '-' . $variation;
	}
	$file .= '.php';

	$file_found = false;
	// First, try to load template-variation.php.
	if ( file_exists( get_stylesheet_directory() . '/buddypress/members/single/messages/attachments/' . $file ) ) {
		include get_stylesheet_directory() . '/buddypress/members/single/messages/attachments/' . $file;
		$file_found = true;
	} elseif ( file_exists( get_template_directory() . '/buddypress/members/single/messages/attachments/' . $file ) ) {
		include get_template_directory() . '/buddypress/members/single/messages/attachments/' . $file;
		$file_found = true;
	} elseif ( file_exists( BPMSGAT_PLUGIN_DIR . 'templates/' . $file ) ) {
		include BPMSGAT_PLUGIN_DIR . 'templates/' . $file;
		$file_found = true;
	}

	if ( ! $file_found && '' !== $variation ) {
		// Then, try to load template.php.
		$file = $template . '.php';
		if ( file_exists( get_stylesheet_directory() . '/buddypress/members/single/messages/attachments/' . $file ) ) {
			include get_stylesheet_directory() . '/buddypress/members/single/messages/attachments/' . $file;
		} elseif ( file_exists( get_template_directory() . '/buddypress/members/single/messages/attachments/' . $file ) ) {
			include get_template_directory() . '/buddypress/members/single/messages/attachments/' . $file;
		} elseif ( file_exists( BPMSGAT_PLUGIN_DIR . 'templates/' . $file ) ) {
			include BPMSGAT_PLUGIN_DIR . 'templates/' . $file;
		}
	}
}

/**
 * Buffer template part
 *
 * @param string  $template template file name.
 * @param string  $variation template file variation, if any.
 * @param boolean $paint Whether to echo the output or just return.
 * @return mixed
 */
function bp_msgat_buffer_template_part( $template, $variation = '', $paint = true ) {
	ob_start();

	bp_msgat_load_template( $template, $variation );
	// Get the output buffer contents.
	$output = ob_get_clean();

	// Echo or return the output buffer contents.
	if ( true === $paint ) {
		echo $output;//phpcs:ignore
	} else {
		return $output;
	}
}

/**
 * Update the global attachment details to current attachment details.
 *
 * @param array $file_info Information about current attachment.
 * @return void
 */
function msgat_the_attachment( $file_info ) {
	global $msgat_current_file;
	$msgat_current_file = $file_info;
}

/**
 * Prints the css classes for current attachment.
 *
 * @return void
 */
function msgat_file_cssclass() {
	echo esc_html( msgat_get_file_cssclass() );
}

/**
 * Get the css classes for current attachment.
 *
 * @return string
 */
function msgat_get_file_cssclass() {
	global $msgat_current_file;
	$classes = array( 'attachment' );

	$classes[] = $msgat_current_file['file_type_group'];
	$classes[] = $msgat_current_file['subtype'];
	$classes[] = 'file-' . $msgat_current_file['id'];

	return apply_filters( 'msgat_get_file_cssclass', implode( ' ', $classes ) );
}

/**
 * Prints the thumbnail url for current attachment.
 *
 * @return void
 */
function msgat_file_thumbnail_url() {
	echo esc_url( msgat_get_file_thumbnail_url() );
}

/**
 * Get the thumbnail url for current attachment.
 *
 * @return string
 */
function msgat_get_file_thumbnail_url() {
	global $msgat_current_file;

	$url = $msgat_current_file['icon'];
	if ( 'image' === $msgat_current_file['type'] ) {
		if ( isset( $msgat_current_file['sizes'] ) && isset( $msgat_current_file['sizes']['thumbnail'] ) ) {
			$url = $msgat_current_file['sizes']['thumbnail']['url'];
		}
	}

	return apply_filters( 'msgat_get_file_thumbnail_url', $url );
}

/**
 * Prints the download url for current attachment.
 *
 * @return void
 */
function msgat_file_download_url() {
	echo esc_url( msgat_get_file_download_url() );
}

/**
 * Get the download url for current attachment.
 *
 * @return string
 */
function msgat_get_file_download_url() {
	global $msgat_current_file;
	$url = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/attachment/' . $msgat_current_file['id'] . '/' . bp_get_the_thread_id() );
	return apply_filters( 'msgat_get_file_download_url', $url );
}

/**
 * Prints the name of current attachment.
 *
 * @return void
 */
function msgat_file_name() {
	echo esc_html( msgat_get_file_name() );
}

/**
 * Get the name of current attachment.
 *
 * @return string
 */
function msgat_get_file_name() {
	global $msgat_current_file;
	$name = $msgat_current_file['title'];
	return apply_filters( 'msgat_get_file_name', $name );
}
