<?php

namespace AI_Client;

class Settings_Page {
	private $options;

	public function __construct() {
		$this->options = get_option( 'ai_client_options' );
		if ( ! isset( $this->options['show_admin'] )
		|| ! $this->options['show_admin'] ) {
			return;
		}
		add_action( 'admin_menu', array( $this, 'add_settings_menu' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
	}

	public function add_settings_menu() {
		add_submenu_page(
			'options-general.php',
			'Intranet Client',
			'Intranet Client',
			'manage_options',
			'intranet-client-options',
			array( $this, 'create_settings_page' )
		);
	}

	public function create_settings_page() {
		?>
		<div class="wrap">
			<h1>Intranet Client settings</h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'intranet-client-options' );
				do_settings_sections( 'intranet-client-options' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function settings_init() {
		register_setting(
			'intranet-client-options',
			'ai_client_options',
			array( $this, 'sanitize' )
		);
		add_settings_section(
			'section_client_settings', // ID
			'Client Settings', // Title
			array( $this, 'print_client_section_info' ), // Callback
			'intranet-client-options' // Page
		);

		add_settings_field(
			'project_key', // ID
			'Project Key', // Title
			array( $this, '_ai_project_key' ), // Callback
			'intranet-client-options', // Page
			'section_client_settings' // Section
		);
	}

	public function sanitize( $input ) {
		return $input;
	}

	/*
	 *	CLIENT RELATED SETTINGS
	 */

	public function print_client_section_info() {
		print 'Enter your settings below:';
	}

	public function _ai_project_key() {
		printf(
			'<input type="text" id="project_key" name="ai_client_options[project_key]" value="%s" />',
			isset( $this->options['project_key'] ) ? esc_attr( $this->options['project_key'] ) : ''
		);
	}
}
