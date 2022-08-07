<?php

if ( ! class_exists( 'QM_Collectors' ) ) {
	
	class QM_Collectors implements IteratorAggregate {
		private static $instance = null;

		private $items     = array();
		private $processed = false;

		public function getIterator() {
			return new ArrayIterator( $this->items );
		}

		public static function add( QM_Collector $collector ) {
			$collectors = self::init();

			$collectors->items[ $collector->id ] = $collector;
		}

		public static function get( $id ) {
			$collectors = self::init();
			if ( isset( $collectors->items[ $id ] ) ) {
				return $collectors->items[ $id ];
			}
			return false;
		}

		/* added/modified: */
		public static function init() {
			if ( ! static::$instance ) {
				static::$instance = new QM_Collectors();
			}

			return static::$instance;

		}

		public static function replace_instance( QM_Collectors $instance ) {
			static::$instance = $instance;
		}
		/* end added/modified */

		public function process() {
			if ( $this->processed ) {
				return;
			}

			foreach ( $this as $collector ) {
				$collector->tear_down();

				$timer = new QM_Timer();
				$timer->start();

				$collector->process();
				$collector->process_concerns();

				$collector->set_timer( $timer->stop() );
			}

			foreach ( $this as $collector ) {
				$collector->post_process();
			}

			$this->processed = true;
		}

	}

}
