<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;

function wp_all_export_is_compatible(){
	return ( class_exists('PMXI_Plugin') and ( PMXI_EDITION == 'paid' and version_compare(PMXI_VERSION, '4.1.4') >= 0 or PMXI_EDITION == 'free' and version_compare(PMXI_VERSION, '3.3.0') >= 0) ) ? true : false;
}