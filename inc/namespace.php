<?php

namespace QMPersist;

use QM_Collectors;
use QM_Dispatchers;
use QM_Plugin;

const COLLECTOR_ID = 'qmpersist-collector';

function bootstrap() {
	maybe_render_screen();

	add_filter( 'qm/collectors', __NAMESPACE__ . '\\register_collector' );
	add_filter( 'qm/dispatchers', __NAMESPACE__ . '\\register_dispatcher', 100, 2 );
	add_filter( 'qm/outputter/html', __NAMESPACE__ . '\\register_browse_output' );
}

/**
 * Get the dispatcher instance.
 *
 * @return Dispatcher
 */
function get_dispatcher() {
	return QM_Dispatchers::get( Dispatcher::ID );
}

function get_storage() {
	return new Storage\FileStorage( '/tmp/qmpersist' );
}

function is_qmpersist_request() {
	return isset( $GLOBALS['is_qmpersist_request'] );
}

/**
 * Register our collector with Query Monitor.
 */
function register_collector( array $collectors ) {
	require __DIR__ . '/class-collector.php';

	$collectors[ COLLECTOR_ID ] = new Collector();
	return $collectors;
}

/**
 * Register our dispatcher with Query Monitor.
 *
 * @param \QM_Dispatcher[] $dispatchers Registered dispatchers.
 * @return \QM_Dispatcher[]
 */
function register_dispatcher( array $dispatchers, QM_Plugin $qm ) {
	require __DIR__ . '/class-dispatcher.php';

	if ( ! is_qmpersist_request() ) {
		$dispatchers[ Dispatcher::ID ] = new Dispatcher( $qm, get_storage() );
	}

	return $dispatchers;
}

function register_browse_output( array $output ) {
	require __DIR__ . '/class-browse-output.php';

	$collector = QM_Collectors::get( COLLECTOR_ID );
	if ( $collector ) {
		$output['browse'] = new Browse_Output( $collector );
	}
	return $output;
}

function maybe_render_screen() {
	if ( ! isset( $_GET['qm_id'] ) ) {
		return;
	}

	$GLOBALS['is_qmpersist_request'] = $_GET['qm_id'];

	// Disable all collectors for this request.
	add_filter( 'qm/collectors', __NAMESPACE__ . '\\replace_collectors', 10000 );
	add_action( 'template_redirect', __NAMESPACE__ . '\\render_panel_only' );
}

function replace_collectors() {
	$storage = get_storage();
	$profile = $storage->load( wp_unslash( $_GET['qm_id'] ) );

	// Replace QM's data with the profile's.
	QM_Collectors::replace_instance( $profile->get_data() );

	// Disable all collectors for this request.
	return [];
}

function render_panel_only() {
	// Force HTML output to render.
	$html_dispatcher = QM_Dispatchers::get( 'html' );
	$html_dispatcher->did_footer = true;

	// Render basic page.
	echo '<!doctype html><html><head>';
	echo '<style>
		#qmpersist #query-monitor-main.qm-show {
			height: 100% !important;
			top: 0;
		}
		#qmpersist #query-monitor-main #qm-title {
			display: none !important;
		}
	</style>';
	wp_head();

	echo '<body id="qmpersist">';

	exit;
}
