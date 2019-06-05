<?php

namespace QMPersist;

use QM_Collector;

class Collector extends QM_Collector {
	public $id = 'qmpersist-collector';

	public function name() {
		// unsure where this is used.
		return __( 'QMPersist Collector????', 'qmpersist' );
	}

	public function process() {
		$request = [
			'id' => substr( hash( 'sha256', uniqid( mt_rand(), true ) ), 0, 6 ),
			'ip' => $_SERVER['REMOTE_ADDR'],
			'method' => strtoupper( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ),
			'scheme' => is_ssl() ? 'https' : 'http',
			'host' => wp_unslash( $_SERVER['HTTP_HOST'] ),
			'path' => wp_unslash( $_SERVER['REQUEST_URI'] ) ?? '/',
			'query' => wp_unslash( $_SERVER['QUERY_STRING'] ),
		];

		$request['url'] = sprintf( '%s://%s%s', $request['scheme'], $request['host'], $request['path'] );
		if ( ! empty( $request['query'] ) ) {
			$request['url'] .= '?' . $request['query'];
		}

		$this->data['request'] = $request;

		$response = [
			'status' => http_response_code(),
		];
		$this->data['response'] = $response;
	}
}
