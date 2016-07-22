<?php

/**
 * Register default function when plugin not activated
 */
 
// Check done for buddypress activation, might be good for other plugins too
if ( empty( $_GET['plugin'] ) ) { 
	add_action( 'wp_loaded', 'avada_plugins_loaded' );
}
function avada_plugins_loaded() {

	if ( ! function_exists( 'is_woocommerce' ) ) {
		function is_woocommerce() { return false; }
	}

	if ( ! function_exists( 'is_bbpress' ) ) {
		function is_bbpress() { return false; }
	}

	if ( ! function_exists( 'is_buddypress' ) ) {
		function is_buddypress() { return false; }
	}

	if ( ! function_exists( 'bbp_is_forum_archive' ) ) {
		function bbp_is_forum_archive() { return false; }
	}

	if ( ! function_exists( 'bbp_is_topic_archive' ) ) {
		function bbp_is_topic_archive() { return false; }
	}

	if ( ! function_exists( 'bbp_is_user_home' ) ) {
		function bbp_is_user_home() { return false; }
	}

	if ( ! function_exists( 'bbp_is_search' ) ) {
		function bbp_is_search() { return false; }
	}

	if ( ! function_exists( 'tribe_is_event' ) ) {
		function tribe_is_event() { return false; }
	}

}

// Omit closing PHP tag to avoid "Headers already sent" issues.
