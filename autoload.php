<?php
spl_autoload_register( 'intranet_autoloader' );
function intranet_autoloader( $class_name ) {
	$classes_dir = trailingslashit( realpath( plugin_dir_path( __FILE__ ) ) );
	$class_file = strtolower(
		str_replace(
			array( '_', '\\' ),
			array( '-', DIRECTORY_SEPARATOR ),
			$class_name
		)
	) . '.php';
	if ( file_exists( $classes_dir . $class_file ) ) {
		require_once( $classes_dir . $class_file );
	}
}
