<?php

namespace QMPersist;

use QM_Collectors;
use QM_Dispatcher;
use QM_Plugin;

class Dispatcher extends QM_Dispatcher {
	/**
	 * Dispatcher ID (required by QM).
	 */
	const ID = 'qmpersist';

	/**
	 * Dispatcher ID (required by QM).
	 *
	 * @var string
	 */
	public $id = self::ID;

	/**
	 * Randomly generated request ID.
	 *
	 * @var string
	 */
	protected $request_id;

	public function __construct( QM_Plugin $qm, $storage ) {
		parent::__construct( $qm );

		$this->storage = $storage;

		add_action( 'shutdown', [ $this, 'dispatch' ], 100 );
	}

	/**
	 * Mark the dispatcher as always active.
	 */
	public function is_active() {
		return true;
	}

	/**
	 * Dispatch.
	 */
	public function dispatch() {
		$collectors = QM_Collectors::init();
		$overview = QM_Collectors::get( 'overview' )->get_data();
		$data = QM_Collectors::get( COLLECTOR_ID )->get_data();

		// header( sprintf( 'X-QMPersist-ID: %s', $data['request']['id'] ) );

		$profile = new Profile( $data['request']['id'] );
		$profile->set_data( $collectors );
		$profile->ip = $data['request']['ip'];
		$profile->method = $data['request']['method'];
		$profile->url = $data['request']['url'];
		$profile->time = $overview['time_start'];
		$profile->status_code = $data['response']['status'];

		$this->storage->save( $profile );
	}
}
