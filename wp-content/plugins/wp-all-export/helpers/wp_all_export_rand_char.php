<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;

if ( ! function_exists('wp_all_export_rand_char')){

	function wp_all_export_rand_char($length) {
		
		$random = '';
	  
		do
		{
	  		$random .= str_replace(array('-', '_'), '', wp_all_export_url_title(chr(random_int(33, 126))));
	  	} 
	  	while (strlen($random) < $length); 

	  	return $random;
	}
}