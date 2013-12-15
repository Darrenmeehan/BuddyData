<?php

/**
 * Plugin Name: BuddyData
 * Plugin URI:  http://github.com/modemlooper/buddydata
 * Description: JSON API for buddyPress
 * Author:      modemlooper
 * Author URI:  http://twitter.com/modemlooper
 * Version:     1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !defined( 'BD_PLUGIN_DIR' ) )
	define( 'BD_PLUGIN_DIR', trailingslashit( WP_PLUGIN_DIR . '/buddydata' ) );

if ( !defined( 'BD_PLUGIN_URL' ) ) {
	$plugin_url = plugin_dir_url( __FILE__ );

	// If we're using https, update the protocol.
	if ( is_ssl() )
		$plugin_url = str_replace( 'http://', 'https://', $plugin_url );

	define( 'BD_PLUGIN_URL', $plugin_url );
}

require( BD_PLUGIN_DIR . '/includes/bd-core.php'  );
require( BD_PLUGIN_DIR . '/includes/bd-class.php'  );


function buddydata_textdomain_init() {
	$mofile        = sprintf( 'buddydata-%s.mo', get_locale() );
	$mofile_local  = dirname( __FILE__ )  . '/languages/' . $mofile;

	if ( file_exists( $mofile_local ) )
	return load_textdomain( 'buddydata', $mofile_local );
}
add_action( 'plugins_loaded', 'buddydata_textdomain_init' );