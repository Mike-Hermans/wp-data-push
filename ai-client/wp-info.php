<?php

namespace AI_Client;

class WP_Info {
	private $categories;

	private $db;

	public function __construct( $categories ) {
		$this->categories = $categories;
	}

	public function get() {
		$info = array();
		foreach ( $this->categories as $category ) {
			if ( method_exists( $this, $category ) ) {
				$info[ $category ] = call_user_func( array( $this, $category ) );
			}
		}
		return $info;
	}

	private function versions() {
		include_once( ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php' );
		global $wp_version;
		return array(
			'wp' => $wp_version,
			'php' => phpversion(),
		);
	}

	private function plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins_updates = get_option( '_site_transient_update_plugins' );
		$plugins_active = get_option( 'active_plugins' );

		$plugins = array();
		foreach ( get_plugins() as $fullname => $plugin ) {
			$active = false;
			if ( in_array( $fullname, $plugins_active ) ) {
				$active = true;
			}
			$plugins[] = array(
				'name' => $plugin['Name'],
				'version' => $plugin['Version'],
				'slug' => $plugin['TextDomain'],
				'active' => $active,
				'uri' => $plugin['PluginURI'],
			);
		}

		return $plugins;
	}

	private function database() {
		if ( is_null( $this->db ) ) {
			$this->db = $this->database_info();
		}
		return $this->db['info'];
	}

	private function database_tables() {
		if ( is_null( $this->db ) ) {
			$this->db = $this->database_info();
		}
		return $this->db['tables'];
	}

	private function server() {
		$facter = shell_exec( 'facter -j' );
		$serverinfo = json_decode( $facter, true );

		// Filter keys
		$filter = array(
			'path' => 0,
			'sshdsakey' => 0,
			'sshfp_dsa' => 0,
			'sshrsakey' => 0,
			'sshfp_rsa' => 0,
			'sshecdsakey' => 0,
			'sshfp_ecdsa' => 0,
			'macaddress' => 0,
			'memorysize' => 0,
			'uptime_days' => 0,
			'uptime_hours' => 0,
			'uptime_seconds' => 0,
			'memoryfree_mb' => 0,
		);

		foreach ( $filter as $f ) {
			$serverinfo = array_diff_key( $serverinfo, $filter );
		}

		// Add HDD size (in mb)
		$serverinfo['hddsize_mb'] = round( disk_total_space( '/' ) / 1024 / 1024, 2 );

		return $serverinfo;
	}

	private function server_usage() {
		// HDD
		$hdd_free = round( disk_free_space( '/' ) );
		$hdd_total = round( disk_total_space( '/' ) );
		$hdd_used = $hdd_total - $hdd_free;

		// RAM
		$free = shell_exec('free');
		$free = (string)trim($free);
		$free_arr = explode("\n", $free);
		$mem = explode(" ", $free_arr[1]);
		$mem = array_filter($mem);
		$mem = array_merge($mem);

		$serverinfo['mem'] = round( sprintf( '%.2f', $mem[2]/$mem[1]*100 ), 2 );
		$serverinfo['hdd'] = round( sprintf( '%.2f', ( $hdd_used / $hdd_total ) * 100 ), 2 );
		$serverinfo['network_rx'] = round( trim( file_get_contents( '/sys/class/net/eth0/statistics/rx_bytes' ) ) / 1024 / 1024 / 1024, 2 );
		$serverinfo['network_tx'] = round( trim( file_get_contents( '/sys/class/net/eth0/statistics/tx_bytes' ) ) / 1024 / 1024 / 1024, 2 );

		return $serverinfo;
	}

	private function database_info() {
		global $wpdb;

		$tables = $wpdb->get_results(
			'SELECT table_name, table_rows, index_length, data_length
			FROM information_schema.tables
			WHERE table_schema = DATABASE()
			ORDER BY ( index_length + data_length ) DESC'
		);

		$db = array();
		$db['info'] = array();
		$db['info']['size_in_mb'] = 0;
		$db['info']['table_count'] = count( $tables );
		$db['tables'] = array();

		foreach ( $tables as $table ) {
			$table_info = array(
				'name' => $table->table_name,
				'row_count' => $table->table_rows,
				'size_in_mb' => round( ( ( $table->data_length + $table->index_length ) / 1024 / 1024 ), 3 ),
			);
			$db['info']['size_in_mb'] += $table_info['size_in_mb'];
			$db['tables'][] = $table_info;
		}
		return $db;
	}
}
