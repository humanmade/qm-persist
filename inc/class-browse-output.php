<?php

namespace QMPersist;

use QM_Collector;
use QM_Collectors;
use QM_Output_Html;

class Browse_Output extends QM_Output_Html {
	/**
	 * Constructor.
	 *
	 * @param QM_Collector $collector
	 */
	public function __construct( QM_Collector $collector ) {
		parent::__construct( $collector );

		add_filter( 'qm/output/menus', array( $this, 'register_menu_item' ), 20 );
		add_filter( 'qm/output/panel_menus', array( $this, 'panel_menu' ), 20 );
	}

	/**
	 * Register additional menu items.
	 *
	 * Adds the "Browse" entry to the menu.
	 *
	 * @param array $menu Menu items for QM
	 * @return array
	 */
	public function register_menu_item( array $menu ) {
		$id          = $this->collector->id() . '-browse';
		$menu[ $id ] = $this->menu( array(
			'title' => esc_html__( 'History', 'qmpersist' ),
			'href'  => esc_attr( '#' . $id ),
		) );

		return $menu;

	}

	/**
	 * Move menu item under "Overview"
	 */
	public function panel_menu( array $menu ) {
		$id = $this->collector->id() . '-browse';
		if ( isset( $menu[ $id ] ) ) {
			$menu[ $id ]['title'] = '└ ' . $menu[ $id ]['title'];
		}

		return $menu;
	}

	/**
	 * Get the URL for a request.
	 *
	 * @param string $id Request ID
	 * @return string URL to view the request
	 */
	protected function get_url( string $id ) : string {
		return add_query_arg( 'qm_id', urlencode( $id ), home_url() );
	}

	/**
	 * Output the browse panel.
	 */
	public function output() {
		$id = $this->collector->id() . '-browse';
		$this->before_tabular_output( $id, __( 'History', 'qmpersist' ) );

		$data = $this->collector->get_data();
		$overview_data = QM_Collectors::get( 'overview' )->get_data();

		echo '<thead>';
		echo '<tr>';
		echo '<th scope="col">' . esc_html__( 'Status', 'qmpersist' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Method', 'qmpersist' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'URL', 'qmpersist' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'IP', 'qmpersist' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Time', 'qmpersist' ) . '</th>';
		echo '<th scope="col">' . esc_html__( 'Request ID', 'qmpersist' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$rows = get_storage()->find();

		// Add current request to the rows.
		if ( ! is_qmpersist_request() ) {
			array_unshift( $rows, [
				'status_code' => $data['response']['status'],
				'method' => $data['request']['method'],
				'url' => $data['request']['url'],
				'ip' => $data['request']['ip'],
				'time' => $overview_data['time_start'],
				'id' => $data['request']['id'],
			] );
		}

		foreach ( $rows as $row ) {
			echo '<tr>';
			echo '<td>' . esc_html( $row['status_code'] ) . '</td>';
			echo '<td>' . esc_html( $row['method'] ) . '</td>';
			echo '<td>' . esc_html( $row['url'] ) . '</td>';
			echo '<td>' . esc_html( $row['ip'] ) . '</td>';
			echo '<td>' . esc_html( date( 'r', $row['time'] ) );
			if ( $row['id'] === $data['request']['id'] ) {
				echo ' <span class="qm-info">(Current request)</span>';
			}
			echo '</td>';
			printf(
				'<td><a href="%s">%s</a></td>',
				esc_url( $this->get_url( $row['id'] ) ),
				esc_html( $row['id'] )
			);
			echo '</tr>';
		}
		echo '</tbody>';

		$this->after_tabular_output();
	}
}
