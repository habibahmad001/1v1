<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;


function wp_all_export_comments_clauses($obj)
{
	if ( ! empty(PMXE_Plugin::$session) and PMXE_Plugin::$session->has_session() ) 
	{
		// manual export run
		$customWhere = PMXE_Plugin::$session->get('whereclause');
		$obj['where'] .= $customWhere;

		$customJoin = PMXE_Plugin::$session->get('joinclause');

		if ( ! empty( $customJoin ) ) {		
			$obj['join'] .= implode( ' ', array_unique( $customJoin ) );	
		}
	}	
	else
	{
		// cron job execution
		if ( ! empty(XmlExportEngine::$exportOptions['whereclause']) ) $obj['where'] .= XmlExportEngine::$exportOptions['whereclause'];
		if ( ! empty(XmlExportEngine::$exportOptions['joinclause']) ) {
			$obj['join'] .= implode( ' ', array_unique( XmlExportEngine::$exportOptions['joinclause'] ) );		
		}
	}	
	return $obj;
}