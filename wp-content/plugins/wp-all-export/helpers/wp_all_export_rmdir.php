<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;

function wp_all_export_rmdir($dir) {
	$scanned_files = @scandir($dir);
	if (!empty($scanned_files) and is_array($scanned_files)){
	   	$files = array_diff($scanned_files, array('.','..'));
	    if (!empty($files)){
		    foreach ($files as $file) {
		      (is_dir("$dir/$file")) ? wp_all_export_rmdir("$dir/$file") : wp_delete_file("$dir/$file");
		    }
		}
	    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- export plugin reads/writes its own files in uploads dir, WP_Filesystem not viable for streamed CSV/XML output
	    return @rmdir($dir);
	}
} 