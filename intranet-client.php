<?php
/**
 * Plugin Name: WP Data Push
 * Plugin URI: http://mikehermans.nl
 * Description: Pushes information about the WP installation and server to a remote server
 * Version: 2.1
 * Author: Mike Hermans
 * Author URI: http://mikehermans.nl
 * License: GPL2
 *
 * @package Intranet_Client
 */

 spl_autoload_register( 'intranet_autoloader' );
 function intranet_autoloader( $class_name ) {
 	$classes_dir = trailingslashit( realpath( plugin_dir_path( __FILE__ ) ) );
 	$class_file = strtolower(
 		str_replace(
 			array( '_', '\\' ),
 			array( '-', DIRECTORY_SEPARATOR ),
 			$class_name
 		)
 	) . '.php';
 	if ( file_exists( $classes_dir . $class_file ) ) {
 		require_once( $classes_dir . $class_file );
 	}
 }

// Load client plugin
$intranet_client = new AI_Client\System();
register_activation_hook( __FILE__, array( $intranet_client, 'activation' ) );
register_deactivation_hook( __FILE__, array( $intranet_client, 'deactivation' ) );

if ( is_admin() ) {
	$settings = new AI_Client\Settings_Page();
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'ai-client', 'AI_Client\CLI' );
}
