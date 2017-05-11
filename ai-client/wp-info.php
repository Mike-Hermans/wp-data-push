<?php

namespace AI_Client;

class WP_Info {
	private $categories;
	private $db;

	public function get() {
		$events = new Events();

		$categories = array('usage', 'network', 'tables');
		if ( ! empty( $events->get_events() ) || ! $events->status ) {
			$categories = array_merge( $categories, array( 'plugins', 'wp_version', 'status' ) );
		}

		$info = array();
		foreach ( $categories as $category ) {
			if ( method_exists( $this, $category ) ) {
				$info[ $category ] = call_user_func( array( $this, $category ) );
			}
		}
		$info['events'] = $events->get_events();
		return $info;
	}

	private function wp_version() {
		include_once( ABSPATH . 'wp-includes' . DIRECTORY_SEPARATOR . 'version.php' );
		global $wp_version;
		return array(
			'wp' => $wp_version
		);
	}

	/*
		Server specific information that won't change a lot, such as PHP Version
		and time since last boot
	*/
	private function status() {
		$uptime = '';
		if ( function_exists('shell_exec') ) {
			$uptime = shell_exec('uptime -s');
			$uptime = strtotime($uptime);
		}

		$mem = $this->get_memory();

		return array(
			'php' => PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION . "." . PHP_RELEASE_VERSION,
			'os' => implode(' ', $this->get_os_version()),
			'disk' => disk_total_space( '/' ),
			'mem' => $mem[0],
			'up' => $uptime
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
			$new_version = null;

			if ( in_array( $fullname, $plugins_active ) ) {
				$active = true;
			}
			if ( isset( $plugins_updates->response[ $fullname ] ) ) {
				$new_version = $plugins_updates->response[ $fullname ]->new_version;
			}
			$plugins[] = array(
				'name' => $plugin['Name'],
				'version' => $plugin['Version'],
				'slug' => sanitize_title($plugin['Name']),
				'active' => $active,
				'uri' => $plugin['PluginURI'],
				'new_version' => $new_version
			);
		}

		return $plugins;
	}

	// Returns RAM and HDD usage in percentage
	private function usage() {
		// HDD
		$hdd_free = round( disk_free_space( '/' ) );
		$hdd_total = round( disk_total_space( '/' ) );
		$hdd_used = $hdd_total - $hdd_free;

		$mem = $this->get_memory();

		$usage = array();
		$usage['ram'] = round( sprintf( '%.2f', $mem[1] / $mem[0] * 100 ), 2 );
		$usage['hdd'] = round( sprintf( '%.2f', $hdd_used / $hdd_total * 100 ), 2 );
		$usage['rx'] = round( trim( file_get_contents( '/sys/class/net/eth0/statistics/rx_bytes' ) ) / 1024 / 1024 / 1024, 2 );
		$usage['tx'] = round( trim( file_get_contents( '/sys/class/net/eth0/statistics/tx_bytes' ) ) / 1024 / 1024 / 1024, 2 );
		$usage['cpu'] = $this->get_cpu();

		return $usage;
	}

	// Returns database tables and their size
	private function tables() {
		global $wpdb;

		$tables = $wpdb->get_results(
			'SELECT table_name, index_length, data_length
			FROM information_schema.tables
			WHERE table_schema = DATABASE()
			ORDER BY ( index_length + data_length ) DESC'
		);

		$db = array();
		$db['total'] = 0;
		foreach ( $tables as $table ) {
			$size = round( ( ( $table->data_length + $table->index_length ) / 1024 / 1024 ), 3 );
			$db[$table->table_name] = $size;
			$db['total'] += $size;
		}

		return $db;
	}

	/*
	Expanded from
	http://stackoverflow.com/a/42397673

	Try to get a nice name for the current OS, for example: Ubuntu 14.04.5 LTS
	*/
	private function get_os_version($onlyversion = false) {
		if ( function_exists("shell_exec") || is_readable("/etc/os-release")) {
			$os         = shell_exec('cat /etc/os-release');
			$listIds    = preg_match_all('/.*=/', $os, $matchListIds);
			$listIds    = $matchListIds[0];

			$listVal    = preg_match_all('/=.*/', $os, $matchListVal);
			$listVal    = $matchListVal[0];

			array_walk($listIds, function(&$v, $k){
				$v = strtolower(str_replace('=', '', $v));
			});

			array_walk($listVal, function(&$v, $k){
				$v = preg_replace('/=|"/', '', $v);
			});

			$os = array_combine($listIds, $listVal);

			if ($onlyversion) {
				return $os['version_id'];
			}

			if ( isset( $os['pretty_name'] ) ) {
				return $os['pretty_name'];
			} else if ( isset( $os['name'] ) && isset( $os['version'] ) ) {
				return $os['name'] . ' ' . $os['version'];
			}
		}
		return php_uname('n') . ' ' . php_uname('r') . ' ' . php_uname('m');
	}

	/*
		Returns an array of memory stats returned by the 'free' shell command.
		['mem', <total>, <used>, <free>, <shared>, <buffers>, <cached>]
	*/
	private function get_memory() {
		$os = $this->get_os_version(true);

		$free_exec = explode( "\n", trim( shell_exec( 'free' ) ) );
		$firstline = preg_split("/[\s]+/", $free_exec[1]);
		$total = $firstline[1];
		var_dump($os);
		// Ubuntu 16.xx and higher handle the 'free' command differently
		if (version_compare( $os, '16', '>=' )) {
			$free = $firstline[2];
		} else {
			// This returns the free memory where cache and buffers are excluded
			$secondline = preg_split("/[\s]+/", $free_exec[2]);
			$free = $secondline[2];
		}
		return array($total, $free);
	}

	/*
		Returns CPU usage
	*/
	private function get_cpu() {
		$exec_loads = sys_getloadavg();
		$exec_cores = trim(shell_exec("grep -P '^processor' /proc/cpuinfo|wc -l"));
		return round($exec_loads[1] / ($exec_cores + 1) * 100, 2);
	}
}
