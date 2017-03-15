<?php
/**
 * Plugin Name: Automated Intranet Client
 * Plugin URI: http://mikehermans.nl
 * Description: Plugin that helps you kickstart your own intranet environment.
 * Version: 1.0
 * Author: Mike Hermans
 * Author URI: http://mikehermans.nl
 * License: GPL2
 *
 * @package Intranet_Client
 */

require __DIR__ . '/vendor/autoload.php';

// Load client plugin
$intranet_client = new AI_Client\System();
register_activation_hook( __FILE__, array( $intranet_client, 'activation' ) );
register_deactivation_hook( __FILE__, array( $intranet_client, 'deactivation' ) );

// if ( is_admin() ) {
// 	$settings = new AI_Client\Settings_Page();
// }

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'ai-client', 'AI_Client\CLI' );
}
