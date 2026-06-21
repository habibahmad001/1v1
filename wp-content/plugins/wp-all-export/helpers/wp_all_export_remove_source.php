<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;

if ( ! function_exists('wp_all_export_remove_source')){
	function wp_all_export_remove_source($file, $remove_dir = true){
		
		wp_delete_file($file);
        
        $path_parts = pathinfo($file);
        if ( ! empty($path_parts['dirname'])){
            $path_all_parts = explode('/', $path_parts['dirname']);
            $dirname = array_pop($path_all_parts);

            if ( wp_all_export_isValidMd5($dirname)){                              
            	if ($remove_dir){
            		wp_delete_file($path_parts['dirname'] . DIRECTORY_SEPARATOR . 'index.php' );
            	}
                if ($remove_dir or count(@scandir($path_parts['dirname'])) == 2) 
                	wp_all_export_rmdir($path_parts['dirname']);                    
            }
        }
        
	}
}