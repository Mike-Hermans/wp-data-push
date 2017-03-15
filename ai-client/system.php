<?php

namespace AI_Client;

class System {
	public static $version = '1.0';

	public function __construct() {
		add_action( 'rest_api_init', array( new Endpoints, 'init' ) );
	}

	public function activation() {
		$default_options = array(
			'project_key' => Crypt::generate_key(),
			'show_admin' => true,
		);
		add_option( 'ai_client_options', $default_options );
	}

	public function deactivate() {
		delete_option( 'ai_client_options' );
	}
}
