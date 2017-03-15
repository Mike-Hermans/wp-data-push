<?php

namespace AI_Client;

class Endpoints {
	public function init() {
		$prefix = 'ai-client/v1';

		register_rest_route( $prefix, '/status', array(
			'methods' => 'POST',
			'callback' => array( $this, 'display_status' ),
		) );
	}

	// Show all new data
	public function display_status( $level = 'default' ) {
		$response = new \WP_REST_Response( array(
				'data' => Crypt::encrypt( WP_Info::get_all() ),
				//'data' => WP_Info::get_all(),
		) );
		$response->set_status( 200 );
		return $response;
	}
}
