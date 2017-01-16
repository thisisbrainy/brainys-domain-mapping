<?php

/*
Plugin Name: Brainy's Domain Mapping
Plugin URI: https://github.com/thisisbrainy/brainys-domain-mapping
Description: Domain mapping plugin for WordPress Multisite
Version: 0.1
Author: Brainy
Author URI: https://brainy.blog
Network: true
*/

/* Prevent non-multisite usage or reloading the plugin, if it has already been load_text_domain
if(!is_multisite() || class_exists('Domainmap_Plugin', false)) {

  return;

}*/

/* Require main classes */
require_once 'classes/class.domainmap.php';

/* Autoload classes */
function domainmap_autoloader($class) {

  $basedir = dirname(__FILE__);
  $namespaces = ['Domainmap', 'Vendor'];

  foreach($namespaces as $namespace) {

    if(substr($class, 0, strlen($namespace)) == $namespace) {

      $filename = $basedir . str_replace('_', DIRECTORY_SEPARATOR, '_classes_' . $class . '.php');

      if(is_readable($filename)) {

        require $filename;

        return true;

      }

    }

    if($namespace === 'Vendor') {

      $filename = $basedir . str_replace('_', DIRECTORY_SEPARATOR, '_classes_Vendor_' . $class . '.php');

      if(is_readable($filename)) {

        require $filename;

        return true;

      }

    }

  }

  return false;

}

/**
 * Setups domain mapping constants.
 *
 * @since 4.1.2
 *
 * @global wpdb $wpdb The instance of database connection.
 */
function domainmap_setup_constants() {
	global $wpdb;

	// setup environment
	define( 'DOMAINMAP_BASEFILE', __FILE__ );
	define( 'DOMAINMAP_ABSURL',   plugins_url( '/', __FILE__ ) );
	define( 'DOMAINMAP_ABSPATH',  dirname( __FILE__ ) );

	// setup db tables
	$prefix = isset( $wpdb->base_prefix ) ? $wpdb->base_prefix : $wpdb->prefix;
	define( 'DOMAINMAP_TABLE_MAP',          "{$prefix}domain_mapping" );
	define( 'DOMAINMAP_TABLE_RESELLER_LOG', "{$prefix}domain_mapping_reseller_log" );

	// MultiDB compatibility, register global tables
	if ( defined( 'MULTI_DB_VERSION' ) && function_exists( 'add_global_table' ) ) {
		add_global_table( 'domain_mapping' );
		add_global_table( 'domain_mapping_reseller_log' );
	}
}

/**
 * Instantiates the plugin and setup all modules.
 *
 * @since 4.0.0
 *
 * @global domain_map $dm_map The instance of domain_map class.
 */
function domainmap_launch() {
	global $dm_map;

	domainmap_setup_constants();

	// set up the plugin core class
	$dm_map = new domain_map();

	// instantiate the plugin
	$plugin = Domainmap_Plugin::instance();

	// set general modules
	$plugin->set_module( Domainmap_Module_System::NAME );
	$plugin->set_module( Domainmap_Module_Setup::NAME );
	$plugin->set_module( Domainmap_Module_Mapping::NAME );

	// CDSSO module
	if ( defined( 'SUNRISE' ) && filter_var( SUNRISE, FILTER_VALIDATE_BOOLEAN ) && $plugin->get_option( 'map_crossautologin' ) ) {
		$plugin->set_module( Domainmap_Module_Cdsso::NAME );
	}

	// conditional modules
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		// suppresses errors rendering to prevent unexpected issues
		set_error_handler( '__return_true' );
		set_exception_handler( '__return_true' );

		// set ajax modules
		$plugin->set_module( Domainmap_Module_Ajax_Map::NAME );
		$plugin->set_module( Domainmap_Module_Ajax_Purchase::NAME );
		$plugin->set_module( Domainmap_Module_Ajax_Register::NAME );
	} else {
		if ( is_admin() ) {
			// set admin modules
			$plugin->set_module( Domainmap_Module_Pages::NAME );
			$plugin->set_module( Domainmap_Module_Admin::NAME );
		}
	}


}

// register autoloader function
spl_autoload_register( 'domainmap_autoloader' );

// launch the plugin
domainmap_launch();

function domainmap_plugin_activate() {
	do_action("domainmap_plugin_activated");
}
register_activation_hook( __FILE__, 'domainmap_plugin_activate' );

function domainmap_plugin_deactivate() {
	do_action("domainmap_plugin_deactivated");
}
register_deactivation_hook( __FILE__, 'domainmap_plugin_deactivate' );
/*================== Global Functions =======================*/

/* Retrieves respective site url with original domain for current site checking */
function dm_site_url($path = '', $scheme = null) {

  return domain_map::utils()->unswap_url(site_url($path, $scheme), false, (bool) $path);

}

/* Retrieves respective home url with original domain for current site checking */
function dm_home_url($path = '', $scheme = null) {

  return domain_map::utils()->unswap_url(home_url($path, $scheme), false, (bool) $path);

}
