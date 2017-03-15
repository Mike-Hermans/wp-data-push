<?php

namespace AI_Client;

use Jose\Factory\JWKFactory as JWKFactory;
use Jose\Factory\JWEFactory as JWEFactory;

class Crypt {
	public static function encrypt( $data ) {
		$options = get_option( 'ai_client_options' );
		$jwk = JWKFactory::createFromValues([
			'kty' => 'oct',
			'k' => $options['project_key'],
			'use' => 'enc',
			'alg' => 'A256GCMKW',
		]);

		$jwe = JWEFactory::createJWEToCompactJSON(
			$data,
			$jwk,
			[
				'alg' => 'A256GCMKW',
				'enc' => 'A256CBC-HS512',
				'zip' => 'DEF',
			]
		);

		return $jwe;
	}

	public static function generate_key() {
		$jwk = JWKFactory::createKey([
	        'kty'  => 'oct',
	        'size' => 256,
	        'kid'  => 'KEY1',
	        'alg'  => 'HS256',
	        'use'  => 'enc',
		]);
		return $jwk->get( 'k' );
	}
}
