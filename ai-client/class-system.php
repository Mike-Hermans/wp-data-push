<?php

namespace AI_Client;

class System {

	public static $version = '2.2.3';

	public function activation() {
		new Events(); // Create status snapshot
	}

	// On deactivation, remove the status snapshot
	public function deactivate() {
		delete_option( 'ai_status' );
	}

	// Remove all settings on plugin removal
	public function uninstall() {
		delete_option( 'ai_client_options' );
	}
}
