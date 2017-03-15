<?php
/*
 TODO:
 Database size (Total & wp_option);
*/
namespace AI_Client;

class WP_Info {
	public static function get_all() {
		$info = array(
			'timestamp' => date( DATE_ISO8601 ),
			'versions' => array(
				'wp' => self::wp_version(),
				'php' => phpversion(),
			),
			'plugins' => self::plugins(),
			'database' => self::database(),
			'server' => self::server_info( 'variable' ),
			'server_static' => self::server_info( 'static' ),
		);
		return $info;
	}

	public static function get_default() {
		$info = array(
			'versions' => array(
				'wp' => self::wp_version(),
				'php' => phpversion(),
			),
			'plugins' => self::plugins(),
			'database' => self::database(),
			'server' => self::server_info( 'variable' ),
		);
		return $info;
	}

	public static function get_serverinfo() {
		$info = array(
			'serverinfo' => self::facter(),
		);
		return $info;
	}

	public static function wp_version() {
		include_once( ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php' );
		global $wp_version;
		return $wp_version;
	}

	public static function plugins() {
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

	public static function server_info( $return = '' ) {
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
			'memoryfree' => 0,
		);

		foreach ( $filter as $f ) {
			$serverinfo = array_diff_key( $serverinfo, $filter );
		}

		$variable_items = array(
			'uptime_days' => 0,
			'uptime_hours' => 0,
			'uptime_seconds' => 0,
			'memorysize_mb' => 0,
			'memoryfree_mb' => 0,
		);

		if ( 'static' == $return ) {
			return array_diff_key( $serverinfo, $variable_items );
		}

		if ( 'variable' == $return ) {
			$serverinfo = array_intersect_key( $serverinfo, $variable_items );
		}

		// Add missing items (HDD Size, network activity)
		//hdd stat
		$serverinfo['hdd_free'] = round( disk_free_space( '/' ) / 1024 / 1024 / 1024, 2 );
		$serverinfo['hdd_total'] = round( disk_total_space( '/' ) / 1024 / 1024 / 1024, 2 );
		$serverinfo['hdd_used'] = $serverinfo['hdd_total'] - $serverinfo['hdd_free'];
		$serverinfo['hdd_percent'] = round( sprintf( '%.2f', ( $serverinfo['hdd_used'] / $serverinfo['hdd_total'] ) * 100 ), 2 );
		//network stat
		$serverinfo['network_rx'] = round( trim( file_get_contents( '/sys/class/net/eth0/statistics/rx_bytes' ) ) / 1024 / 1024 / 1024, 2 );
		$serverinfo['network_tx'] = round( trim( file_get_contents( '/sys/class/net/eth0/statistics/tx_bytes' ) ) / 1024 / 1024 / 1024, 2 );

		return $serverinfo;
	}

	public static function database() {
		global $wpdb;

		$tables = $wpdb->get_results(
			'SELECT table_name, table_rows, index_length, data_length
			FROM information_schema.tables
			WHERE table_schema = DATABASE()
			ORDER BY ( index_length + data_length ) DESC'
		);

		$db = array();
		$db['size_in_mb'] = 0;
		$db['table_count'] = count( $tables );
		$db['tables'] = array();

		foreach ( $tables as $table ) {
			$table_info = array(
				'name' => $table->table_name,
				'row_count' => $table->table_rows,
				'size_in_mb' => round( ( ( $table->data_length + $table->index_length ) / 1024 / 1024 ), 3 ),
			);
			$db['size_in_mb'] += $table_info['size_in_mb'];
			$db['tables'][] = $table_info;
		}

		return $db;
	}
}
