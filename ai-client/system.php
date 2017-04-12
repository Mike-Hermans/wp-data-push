<?php

namespace AI_Client;

class System {
	public static $version = '1.0';

	public function activation() {
		new Events(); // Create status snapshot
	}

	public function deactivate() {
		delete_option( 'ai_status' );
		delete_option( 'ai_client_options' );
	}
}
