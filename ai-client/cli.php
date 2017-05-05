<?php

namespace AI_Client;

/**
 * Manage settings for this client.
 *
 * ## EXAMPLES
 *
 *     # Show or hide admin menu entry.
 *     $ wp ai-client admin-menu show
 *     Success: Admin section visible.
 *
 *     # Set remote URL to post to
 *     $ wp ai-client set-remote <url>
 *     Success: Client will now post to <url>
 *
 *     # Generate a new key
 *     $ wp ai-client generate-key
 *     New client key:
 *	   <32 character key>
 *     Success: New key has been saved to the database.
 *
 *	   # Set the name
 *     $ wp ai-client set-name ai_client
 *     Success: Client name changed to ai_client
 *
 *     # Set the key
 *     $ wp ai-client set-key client_key
 *     Success: New key has been set
 *
 *	   # Print status
 *     $ wp ai-client status
 *     <status>
 *
 *     # Send data to remote
 *     $ wp ai-client send network,server_usage,versions
 *     Success: 200 OK
 */
class CLI extends \WP_CLI {
	/**
	 * Show or hide the admin page
	 *
	 * ## OPTIONS
	 *
	 * <flag>
	 * : Show or Hide
	 *
	 * ## EXAMPLES
	 *
	 *     # Show the menu
	 *     $ wp ai-client admin-menu show
	 *     Success: Admin section visible.
	 *
	 *	   # Hide the menu
	 *     $ wp ai-client admin-menu hide
	 *     Success: Admin section hidden.
	 *
	 * @subcommand admin-menu
	 */
	public function admin_menu( $args ) {
		if ( ! isset( $args[0] ) ) {
			return;
		}

		switch( $args[0] ) {
			case 'show':
				$this->set_option( 'show_admin', true );
				\WP_CLI::success( 'Admin section visible.' );
				break;
			case 'hide':
				$this->set_option( 'show_admin', false );
				\WP_CLI::success( 'Admin section hidden.' );
				break;
			case 'default':
				\WP_CLI::line( 'Invalid option.' );
		}
	}

	/**
	 * Set the url for this client to post its status to.
	 *
	 * ## OPTIONS
	 *
	 * <url>
	 * : Remote server URL
	 *
	 * ## EXAMPLES
	 *
	 *     # Set the url
	 *     $ wp ai-client set-remote https://remote.server.com
	 *     Success: Client will now post to https://remote.server.com
	 *
	 * @subcommand set-remote
	 */
	public function set_remote( $args ) {
		if ( ! isset( $args[0] ) ) {
			return;
		}
		$this->set_option( 'remote_url', $args[0] );
		\WP_CLI::success( 'Client will now post to ' . $args[0] );
	}

	/**
	 * Set the project name (must be the same as the AI_Project slug on remote)
	 *
	 * ## OPTIONS
	 *
	 * <name>
	 * : Project slug
	 *
	 * ## EXAMPLES
	 *
	 *     # Set the name
	 *     $ wp ai-client set-name ai_client
	 *     Success: Client name changed to ai_client
	 *
	 * @subcommand set-name
	 */
	public function set_name( $args ) {
		if ( ! isset( $args[0] ) ) {
			return;
		}
		$this->set_option( 'project_name', $args[0] );
		\WP_CLI::success( 'Client name changed to ' . $args[0] );
	}

	/**
	 * Generate a key to use for this client
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate a new key
	 *     $ wp ai-client generate-key
	 *     New client key:
	 *	   <32 character key>
	 *     Success: New key has been saved to the database.
	 *
	 * @subcommand generate-key
	 */
	public function generate_key() {
		$newkey = wp_generate_password( 32 );
		$this->set_option( 'project_key', $newkey );
		\WP_CLI::line( 'New client key: ' );
		\WP_CLI::line( $newkey );
		\WP_CLI::success( 'New key has been saved to the database.' );
	}

	/**
	 * Set a new key to use for this client
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : New key
	 *
	 * ## EXAMPLES
	 *
	 *     # Set the key
	 *     $ wp ai-client set-key client_key
	 *     Success: New key has been set
	 *
	 * @subcommand set-key
	 */
	public function set_key( $args ) {
		if ( ! isset( $args[0] ) ) {
			return;
		}
		$this->set_option( 'project_key', $args[0] );
		\WP_CLI::success( 'New key has been set.' );
	}

	/**
	 * Prints the last saved status snapshot
	 *
	 * ## EXAMPLES
	 *
	 *     # Print status
	 *     $ wp ai-client status
	 *     <status>
	 */
	public function status() {
		$status = new Events();
		\WP_CLI::line( json_encode( $status->get_events() ) );
	}

	/**
	 * Send status to remote server
	 *
	 *
	 * ## EXAMPLES
	 *
	 *     # Send data
	 *     $ wp ai-client send
	 *     Success: 200 OK
	 */
	public function send( $args ) {
		$options = get_option( 'ai_client_options' );
		$wpinfo = new WP_Info();

		$response = wp_remote_post( $options['remote_url'] . 'api/collect', array(
		    'body'    => json_encode($wpinfo->get()),
		    'headers' => array(
		        'Authorization' => 'Basic ' . base64_encode( $options['project_name'] . ':' . $options['project_key'] ),
		    ),
		) );

		if ( $response['response']['code'] != 200 ){
			\WP_CLI::line( $response['response']['code'] );
		} else {
			\WP_CLI::success( $response['response']['code'] . ' ' . $response['response']['message']);
		}
	}

	private function set_option( $option, $value ) {
		$options = get_option( 'ai_client_options' );
		$options[ $option ] = $value;
		update_option( 'ai_client_options', $options );
	}
}
