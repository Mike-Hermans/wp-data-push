<?php

namespace AI_Client;

class Endpoints {
	public function init() {
		$prefix = 'ai-client/v1';

		register_rest_route( $prefix, '/status', array(
			'methods' => 'GET',
			'callback' => array( $this, 'display_status' ),
		) );
	}

	// Show all new data
	public function display_status( \WP_REST_Request $request ) {
		$valid_parameters = array(
			'versions',
			'plugins',
			'database',
			'database_tables',
			'server_usage',
			'server'
		);
		if ( isset( $request['categories'] ) ) {
			$categories = $request['categories'];
			if ( strlen( $categories ) <= strlen( implode( ',', $valid_parameters ) ) ) {
				$categories = array_intersect( $valid_parameters, explode( ',', $categories ) );

				$wpinfo = new WP_Info( $categories );
				$response = new \WP_REST_Response( array(
						'data' => Crypt::encrypt( $wpinfo->get() )
				) );
				$response->set_status( 200 );
				return $response;
			}
		}
		$response = new \WP_REST_Response( array(
				'error' => 'invalid_parameters',
		) );
		$response->set_status( 400 );
		return $response;
	}
}
