<?php

namespace QMPersist\Storage;

use QMPersist\Profile;
use WP_Error;

class FileStorage {
	public function __construct( string $directory ) {
		$this->directory = $directory;
	}

	/**
	 * Search for matching profiles.
	 */
	public function find( $ip = null, $url = null, $limit = 10, $method = null, $start = null, $end = null, $status_code = null ) {
		$file = $this->get_index_path();

		if ( ! file_exists( $file ) ) {
			return [];
		}

		$file = fopen( $file, 'r' );
		fseek( $file, 0, SEEK_END );

		$result = [];
		while ( count( $result ) < $limit ) {
			$line = $this->get_next_row( $file );
			if ( empty( $line ) ) {
				break;
			}

			$values = str_getcsv( $line );
			list( $row_id, $row_ip, $row_method, $row_url, $row_time, $row_status ) = $values;
			$row_time = (int) $row_time;

			$conds = [
				$ip && false === strpos( $row_ip, $ip ),
				$url && false === strpos( $row_url, $url ),
				$method && false === strpos( $row_method, $method ),
				$status_code && false === strpos( $row_status, $status_code ),
				! empty( $start ) && $row_time < $start,
				! empty( $end ) && $row_time > $end,
			];
			if ( count( array_filter( $conds ) ) > 0 ) {
				continue;
			}

			$result[ $row_id ] = [
				'id' => $row_id,
				'ip' => $row_ip,
				'method' => $row_method,
				'url' => $row_url,
				'time' => $row_time,
				'status_code' => $row_status,
			];
		}

		fclose( $file );

		return array_values( $result );
	}

	/**
	 * Reads a line in the file, backward.
	 *
	 * This function automatically skips the empty lines and do not include the line return in result value.
	 *
	 * @param resource $file The file resource, with the pointer placed at the end of the line to read
	 *
	 * @return mixed A string representing the line or null if beginning of file is reached
	 */
	protected function get_next_row( $file ) {
		$line = '';
		$position = ftell( $file );

		if ( 0 === $position ) {
			return;
		}

		while ( true ) {
			$chunk = min( $position, 1024 );
			$position -= $chunk;
			fseek( $file, $position );

			if ( $chunk === 0 ) {
				// bof reached
				break;
			}

			$buffer = fread( $file, $chunk );

			$rest_length = strrpos( $buffer, "\n" );
			if ( $rest_length === false ) {
				$line = $buffer . $line;
				continue;
			}

			$position += $rest_length;
			$line = substr( $buffer, $rest_length + 1 ) . $line;
			fseek( $file, max( 0, $position ), SEEK_SET );

			if ( $line !== '' ) {
				break;
			}
		}

		if ( $line === '' ) {
			return null;
		}

		return $line;
	}

	/**
	 * Load a profile by ID.
	 *
	 * @param string $id
	 * @return Profile|null
	 */
	public function load( string $id ) : ?Profile {
		$path = $this->directory . DIRECTORY_SEPARATOR . $id;
		if ( ! file_exists( $path ) ) {
			return null;
		}

		$contents = file_get_contents( $path );
		return unserialize( $contents );
	}

	/**
	 * Save a profile.
	 *
	 * @param Profile $profile
	 */
	public function save( Profile $profile ) {
		if ( ! is_dir( $this->directory ) ) {
			$status = @mkdir( $this->directory, 0777, true );
			if ( $status === false && ! is_dir( $this->directory ) ) {
				return new WP_Error(
					'qmpersist.storage.cannot_create',
					sprintf( 'Cannot create the storage directory (%s)', $this->directory )
				);
			}
		}

		$id = $profile->get_id();
		$filename = wp_unique_filename( $this->directory, $id );
		$path = $this->directory . DIRECTORY_SEPARATOR . $filename;

		$result = file_put_contents( $path, serialize( $profile ) );
		if ( $result === false ) {
			var_dump( $result );
			return false;
		}

		$this->save_to_index( $profile );

		return $result;
	}

	/**
	 * Get path to the index file.
	 *
	 * @return string Path for the index CSV file
	 */
	protected function get_index_path() : string {
		return $this->directory . DIRECTORY_SEPARATOR . 'index.csv';
	}

	/**
	 * Add a profile to the index.
	 *
	 * @param Profile $profile Profile to add.
	 */
	protected function save_to_index( Profile $profile ) {
		$file = fopen( $this->get_index_path(), 'a' );
		if ( $file === false ) {
			return false;
		}

		$data = [
			$profile->get_id(),
			$profile->ip,
			$profile->method,
			$profile->url,
			$profile->time,
			$profile->status_code,
		];
		fputcsv( $file, $data );

		fclose( $file );
	}
}
