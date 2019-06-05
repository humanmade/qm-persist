<?php

namespace QMPersist;

use QM_Collectors;

class Profile {
	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var QM_Collectors
	 */
	protected $data;

	/**
	 * @var string
	 */
	public $ip;

	/**
	 * @var string
	 */
	public $method;

	/**
	 * @var string
	 */
	public $url;

	/**
	 * @var float
	 */
	public $time;

	/**
	 * @var int
	 */
	public $status_code;

	public function __construct( string $id ) {
		$this->id = $id;
	}

	public function get_id() : string {
		return $this->id;
	}

	public function get_data() : QM_Collectors {
		return $this->data;
	}

	public function set_data( QM_Collectors $data ) {
		$this->data = $data;
	}
}
