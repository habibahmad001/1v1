<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;


function wp_all_export_posts_join($join){

	// cron job execution
	if ( ! empty(PMXE_Plugin::$session) and PMXE_Plugin::$session->has_session() ) 
	{		
		$customJoin = PMXE_Plugin::$session->get('joinClause');
		if ( ! empty( $customJoin ) ) {
			$join .= implode( ' ', array_unique( $customJoin ) );		
		}			
	}
	else
	{
		if ( ! empty(XmlExportEngine::$exportOptions['joinclause']) ) {
			$join .= implode( ' ', array_unique( XmlExportEngine::$exportOptions['joinclause'] ) );		
		}
	}		

	return $join;
}