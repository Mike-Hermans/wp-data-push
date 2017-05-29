<?php

namespace AI_Client;

class Events {

	private $old_status;
	private $new_status;
	public $status;

	public function __construct() {
        $old_status = get_option( 'ai_status' )
		if ( false === ( $old_status ) ) {
			$this->status = false;
			$this->save_current_status( $this->get_status() );
			return;
		}
		$this->status = true;
		$this->old_status = $old_status;
		$this->new_status = $this->get_status();
	}

	/**
	 * Check the difference between the old and the new status, and
	 * return the result as an array of readable strings.
	 *
	 * @return array
	 */
	public function get_events() {
		if ( ! $this->status ) {
			return array();
		}
		$events = array();

		foreach ( $this->old_status['versions'] as $product => $version ) {
			if ( version_compare( $version, $this->new_status['versions'][ $product ], '<' ) ) {
				$events[] = 'Updated ' . $product . ' from ' . $version . ' to ' . $this->new_status['versions'][ $product ] . '.';
			} elseif ( version_compare( $version, $this->new_status['versions'][ $product ], '>' ) ) {
				$events[] = 'Downgraded ' . $product . ' from ' . $version . ' to ' . $this->new_status['versions'][ $product ] . '.';
			}
		}

		$events = array_merge(
			$events,
			$this->difference_plugin(
				$this->old_status['plugins'],
				$this->new_status['plugins'],
				'Deactivated %s.'
			)
		);

		$events = array_merge(
			$events,
			$this->difference_plugin(
				$this->new_status['plugins'],
				$this->old_status['plugins'],
				'Activated %s.'
			)
		);

		foreach ( $this->old_status['plugins'] as $plugin => $data ) {
			if ( isset( $this->new_status['plugins'][ $plugin ] ) ) {
				if ( version_compare( $data['version'], $this->new_status['plugins'][ $plugin ]['version'], '<' ) ) {
					$events[] = 'Updated ' . $plugin . ' from version ' . $data['version'] . ' to ' . $this->new_status['plugins'][ $plugin ]['version'] . '.';
				} elseif ( version_compare( $data['version'], $this->new_status['plugins'][ $plugin ]['version'], '>' ) ) {
					$events[] = 'Downgraded ' . $plugin . ' from version ' . $data['version'] . ' to ' . $this->new_status['plugins'][ $plugin ]['version'] . '.';
				}
			}
		}

		if ( $this->new_status['theme']['name'] != $this->old_status['theme']['name'] ) {
			$events[] = 'Switched to ' . $this->new_status['theme']['name'] . ' theme';
		} elseif ( $this->new_status['theme']['version'] != $this->old_status['theme']['version'] ) {
			if ( version_compare( $this->old_status['theme']['version'], $this->new_status['theme']['version'], '<' ) ) {
				$events[] = 'Updated ' . $this->new_status['theme']['name'] . ' from ' . $this->old_status['theme']['version'] . ' to ' . $this->new_status['theme']['version'];
			} else {
				$events[] = 'Downgraded ' . $this->new_status['theme']['name'] . ' from ' . $this->old_status['theme']['version'] . ' to ' . $this->new_status['theme']['version'];
			}
		}

		$this->save_current_status( $this->new_status );

		return $events;
	}

	/**
	 * Calculates the difference between two arrays and returns the result
	 * in the given string
	 *
	 * @param  array  $one    Set of plugins
	 * @param  array  $two    Set of plugins
	 * @param  string $string String, where %s will be replaced with the plugin name and version
	 * @return array of event strings
	 */
	private function difference_plugin( $one, $two, $string ) {
		$result = array();

		$diff = array_diff_key( $one, $two );

		foreach ( $diff as $name => $data ) {
			$result[] = sprintf( $string, $data['name'] . ' (' . $data['version'] . ')' );
		}

		return $result;
	}

	/**
	 * Generate a snapshot of the current status
	 *
	 * @return array
	 */
	private function get_status() {
		global $wp_version;
		$status = array(
			'versions' => array(
				'wordpress' => $wp_version,
				'php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
			),
			'plugins' => $this->active_plugins(),
			'theme' => $this->current_theme(),
		);
		return $status;
	}

	/**
	 * Return a list of the plugins that are currently active
	 *
	 * @return array
	 */
	private function active_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins_active = get_option( 'active_plugins' );

		$plugins = array();
		foreach ( get_plugins() as $fullname => $plugin ) {
			if ( in_array( $fullname, $plugins_active ) ) {
				$plugins[ $plugin['TextDomain'] ] = array(
					'name' => $plugin['Name'],
					'version' => $plugin['Version'],
				);
			}
		}

		return $plugins;
	}

	/**
	 * Returns the theme that is currently active
	 *
	 * @return array
	 */
	private function current_theme() {
		$theme = wp_get_theme();
		return array(
			'name' => $theme->get( 'Name' ),
			'version' => $theme->get( 'Version' ),
		);
	}

	/**
	 * Save a new snapshot of the status
	 *
	 * @param array $status
	 */
	private function save_current_status( $status ) {
		if ( get_option( 'ai_status' ) ) {
			update_option( 'ai_status', $status );
		} else {
			add_option( 'ai_status', $status, '', false );
		}
	}
}
