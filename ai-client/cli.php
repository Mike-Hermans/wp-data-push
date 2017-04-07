<?php

namespace AI_Client;

class CLI extends \WP_CLI {
	private $options;

	public function __construct() {
		$options = get_option( 'ai_client_options' );
	}

	public function regenerate() {
		$newkey = Crypt::generate_key();
		$this->set_option( 'project_key', $newkey );
		\WP_CLI::line( 'New client key: ' );
		\WP_CLI::line( $newkey );
		\WP_CLI::success( 'New key has been saved to the database' );
	}

	public function hide_admin() {
		$this->set_option( 'show_admin', false );
	}

	public function show_admin() {
		$this->set_option( 'show_admin', true );
	}

	public function status() {
		$status = new Events();
		\WP_CLI::line( json_encode( $status->get_events() ) );
	}

	private function set_option( $option, $value ) {
		$options = get_option( 'ai_client_options' );
		$options[ $option ] = $value;
		update_option( 'ai_client_options', $options );
	}
}
