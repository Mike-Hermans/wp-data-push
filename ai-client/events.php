<?php

namespace AI_Client;

class Events {
    private $old_status;
    private $new_status;
    public $status;

    public function __construct() {
        if ( false === ( $old_status = get_option( 'ai_status' ) ) ) {
            $this->status = false;
            $this->save_current_status( $this->get_status() );
            return;
        }
        $this->status = true;
        $this->old_status = $old_status;
        $this->new_status = $this->get_status();
    }

    public function get_events() {
        if ( ! $this->status ) {
            return array();
        }
        $events = array();

        foreach ( $this->old_status['versions'] as $product => $version ) {
            if ( version_compare( $version, $this->new_status['versions'][ $product ], '<' ) ) {
                $events[] = 'Updated ' . $product . ' from ' . $version . ' to ' . $this->new_status['versions'][ $product ] . '.';
            } else if ( version_compare( $version, $this->new_status['versions'][ $product ], '>' ) ) {
                $events[] = 'Downgraded ' . $product . ' from ' . $version . ' to ' . $this->new_status['versions'][ $product ] . '.';
            }
        }

        $events = array_merge(
            $events,
            $this->difference(
                $this->old_status['tables'],
                $this->new_status['tables'],
                'Removed %s table.'
                )
            );

        $events = array_merge(
            $events,
            $this->difference(
                $this->new_status['tables'],
                $this->old_status['tables'],
                'Added %s table.'
                )
            );

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
                } else if ( version_compare( $data['version'], $this->new_status['plugins'][ $plugin ]['version'], '>' ) ) {
                    $events[] = 'Downgraded ' . $plugin . ' from version ' . $data['version'] . ' to ' . $this->new_status['plugins'][ $plugin ]['version'] . '.';
                }
            }
        }

        if ( $this->new_status['theme']['name'] != $this->old_status['theme']['name'] ) {
            $events[] = 'Switched to ' . $this->new_status['theme']['name'] . ' theme';
        } else if ( $this->new_status['theme']['version'] != $this->old_status['theme']['version'] ) {
            if ( version_compare( $this->old_status['theme']['version'], $this->new_status['theme']['version'], '<' ) ) {
                $events[] = 'Updated ' . $this->new_status['theme']['name'] . ' from ' . $this->old_status['theme']['version'] . ' to ' . $this->new_status['theme']['version'];
            } else {
                $events[] = 'Downgraded ' . $this->new_status['theme']['name'] . ' from ' . $this->old_status['theme']['version'] . ' to ' . $this->new_status['theme']['version'];
            }
        }

        $this->save_current_status( $this->new_status );

        return $events;
    }

    public function get_updated() {
        if ( ! empty( $this->updated ) ) {
            return $this->updated;
        }
        return false;
    }

    private function difference( $one, $two, $string ) {
        $result = array();

        $diff = array_diff( $one, $two );

        foreach ( $diff as $d ) {
            $result[] = sprintf( $string, $d );
        }

        return $result;
    }

    private function difference_plugin( $one, $two, $string ) {
        $result = array();

        $diff = array_diff_key( $one, $two );

        foreach ( $diff as $name => $data ) {
            $result[] = sprintf( $string, $data['name'] . ' (' . $data['version'] . ')' );
        }

        return $result;
    }

    private function get_status() {
        global $wp_version;
        $status = array(
            'versions' => array(
                'wordpress' => $wp_version,
                'php' => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
            ),
            'tables' => $this->database_tables(),
            'plugins' => $this->active_plugins(),
            'theme' => $this->current_theme(),
        );
        return $status;
    }

    private function database_tables() {
        global $wpdb;
        $tables = $wpdb->get_results(
			'SELECT table_name
			FROM information_schema.tables
			WHERE table_schema = DATABASE()'
		);
        $array = array();
        foreach ( $tables as $table ) {
            $array[] = $table->table_name;
        }
        return $array;
    }

    private function active_plugins() {
        if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins_active = get_option( 'active_plugins' );

		$plugins = array();
		foreach ( get_plugins() as $fullname => $plugin ) {
			if ( in_array( $fullname, $plugins_active ) ) {
                $plugins[$plugin['TextDomain']] = array(
    				'name' => $plugin['Name'],
    				'version' => $plugin['Version'],
    			);
            }
		}

		return $plugins;
    }

    private function current_theme() {
        $theme = wp_get_theme();
        return array(
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
        );
    }

    private function save_current_status( $status ) {
        if ( get_option( 'ai_status' ) ) {
            update_option( 'ai_status', $status );
        } else {
            add_option( 'ai_status', $status, '', false );
        }
    }
}
