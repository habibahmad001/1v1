<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;

if ( ! function_exists('get_taxonomies_by_object_type')):
/**
 * get_taxnomies doesn't filter propery by object_type, so these function can be used when filtering by object type requied
 * @param string|array $object_type
 * @param string[optional] $output
 */
function get_taxonomies_by_object_type($object_type, $output = 'names') {
	global $wp_taxonomies;
	
	is_array($object_type) or $object_type = array($object_type);
	$field = ('names' == $output) ? 'name' : false;
	$filtered = array();
	foreach ($wp_taxonomies as $key => $obj) {
		if (array_intersect($object_type, $obj->object_type)) {
			$filtered[$key] = $obj;
		}
	}
	if ($field) {
		$filtered = wp_list_pluck($filtered, $field);
	}
	return $filtered;
}

endif;