<?php
/*
Plugin Name: BuddyPress Classifieds
Plugin URI:  http://dev.benoitgreant.be/2010/02/01/buddypress-classifieds
Description: This component adds classifieds to your BuddyPress installation.
Version: 1.02
Revision Date: March 11, 2010
Requires at least: WPMU 2.9.1, BuddyPress 1.2
Tested up to: WPMU 2.9.2, BuddyPress 1.2.1
License: (Classifieds: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html)
Author: G.Breant
Author URI: http://dev.benoitgreant.be
Site Wide Only: true
*/


/*** Make sure BuddyPress is loaded ********************************/
if ( !function_exists( 'bp_core_install' ) ) {
	require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
	if ( is_plugin_active( 'buddypress/bp-loader.php' ) )
		require_once ( WP_PLUGIN_DIR . '/buddypress/bp-loader.php' );
	else
		return;
}
/*******************************************************************/

function classifieds_init() {
	/* Define the slug for the component */
	if ( !defined( 'BP_CLASSIFIEDS_SLUG' ) )
		define ( 'BP_CLASSIFIEDS_SLUG', 'classifieds' );

	/////////
	// Important Internal Constants
	// *** DO NOT MODIFY THESE ***
	define ( 'BP_CLASSIFIEDS_IS_INSTALLED', 1 );
	define ( 'BP_CLASSIFIEDS_VERSION', '1.02' );
	define ( 'BP_CLASSIFIEDS_DB_VERSION', '1100' );
	define ( 'BP_CLASSIFIEDS_PLUGIN_NAME', 'buddypress-classifieds' );
	define ( 'BP_CLASSIFIEDS_PLUGIN_DIR', WP_PLUGIN_DIR . '/' . BP_CLASSIFIEDS_PLUGIN_NAME );
	define ( 'BP_CLASSIFIEDS_PLUGIN_URL', WP_PLUGIN_URL . '/' . BP_CLASSIFIEDS_PLUGIN_NAME );


	/////////

	// lets do it
	require_once 'bp-classifieds.php';
}

if ( defined( 'BP_VERSION' ) )
	classifieds_init();
else
	add_action( 'bp_init', 'classifieds_init' );



?>