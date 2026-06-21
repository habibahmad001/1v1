<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;

if ( ! function_exists('wp_all_export_secure_file') ){

	function wp_all_export_secure_file( $targetDir, $ID = false){

		$is_secure_import = PMXE_Plugin::getInstance()->getOption('secure');

		if ( $is_secure_import ){

			$dir = $targetDir . DIRECTORY_SEPARATOR . ( ( $ID ) ? md5( $ID . wp_salt( 'nonce' ) ) : md5( time() . wp_salt( 'nonce' ) ) );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- export plugin reads/writes its own files in uploads dir, WP_Filesystem not viable for streamed CSV/XML output
			if ( ! is_dir($dir) ) @mkdir($dir, 0755);

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable -- export plugin reads/writes its own files in uploads dir, WP_Filesystem not viable for streamed CSV/XML output
			if (@is_writable($dir) and @is_dir($dir)){
				$targetDir = $dir;
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_touch -- export plugin reads/writes its own files in uploads dir, WP_Filesystem not viable for streamed CSV/XML output
				@touch( $dir . DIRECTORY_SEPARATOR . 'index.php' );
			}

		}

		return $targetDir;
	}
}	