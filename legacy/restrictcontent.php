<?php

/*******************************************
* global variables
*******************************************/

// load the plugin options
global $rc_options;

if ( $rc_options !== FALSE ) {
	if ( $rc_options['shortcode_message'] === '' ||  $rc_options['shortcode_message'] === FALSE ) {
		$rc_options['shortcode_message'] = 'You do not have access to this post.';
	}

	if ( $rc_options['administrator_message'] === '' ||  $rc_options['administrator_message'] === FALSE ) {
		$rc_options['administrator_message'] = 'This content is for Administrator Users.';
	}

	if ( $rc_options['editor_message'] === ''  ||  $rc_options['editor_message'] === FALSE ) {
		$rc_options['editor_message'] = 'This content is for Editor Users';
	}

	if ( $rc_options['author_message'] === ''  ||  $rc_options['author_message'] === FALSE ) {
		$rc_options['author_message'] = 'This content is for Author Users';
	}

	if ( $rc_options['contributor_message'] === ''  ||  $rc_options['contributor_message'] === FALSE ) {
		$rc_options['contributor_message'] = 'This content is for Author Users';
	}

	if ( $rc_options['subscriber_message'] === ''  ||  $rc_options['subscriber_message'] === FALSE ) {
		$rc_options['subscriber_message'] = 'This content is for Subscriber Users';
	}
}

if ( ! defined( 'RC_PLUGIN_VERSION' ) ) {
	define( 'RC_PLUGIN_VERSION', '3.0.2' );
}

if ( ! defined( 'RC_PLUGIN_DIR' ) ) {
	define( 'RC_PLUGIN_DIR', dirname(__FILE__) );
}

if ( ! defined( 'RC_PLUGIN_URL' ) ) {
	define( 'RC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Load textdomain
 *
 * @return void
 */
function rc_textdomain() {

	// Set filter for plugin's languages directory
	$rc_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	$rc_lang_dir = apply_filters( 'rc_languages_directory', $rc_lang_dir );

	// Load the translations
	load_plugin_textdomain( 'restrict-content', false, $rc_lang_dir );
}
add_action( 'init', 'rc_textdomain' );



/*******************************************
* file includes
*******************************************/
require_once  RC_PLUGIN_DIR . '/includes/misc-functions.php';
require_once  RC_PLUGIN_DIR . '/includes/forms.php';
require_once  RC_PLUGIN_DIR . '/includes/scripts.php';
require_once  RC_PLUGIN_DIR . '/includes/upgrades.php';
require_once  RC_PLUGIN_DIR . '/includes/integrations.php';
include(RC_PLUGIN_DIR . '/includes/settings.php');
include(RC_PLUGIN_DIR . '/includes/shortcodes.php');
include(RC_PLUGIN_DIR . '/includes/metabox.php');
include(RC_PLUGIN_DIR . '/includes/display-functions.php');
include(RC_PLUGIN_DIR . '/includes/feed-functions.php');
include(RC_PLUGIN_DIR . '/includes/user-checks.php');


if ( is_admin() && file_exists( RC_PLUGIN_DIR . '/lib/icon-fonts/load.php' ) ) {
	require( RC_PLUGIN_DIR . "/lib/icon-fonts/load.php" );
}

register_activation_hook( __FILE__, function() {
	if ( current_user_can( 'manage_options' ) ) {
		add_option( 'Restrict_Content_Plugin_Activated', 'restrict-content' );
	}
} );

add_action( 'admin_init', 'restrict_content_plugin_activation' );

function restrict_content_plugin_activation() {
	if ( is_admin() && get_option( 'Restrict_Content_Plugin_Activated' ) === 'restrict-content' ) {
		delete_option('Restrict_Content_Plugin_Activated' );
		wp_safe_redirect( admin_url( 'admin.php?page=restrict-content-welcome' ) );
		die();
	}
}