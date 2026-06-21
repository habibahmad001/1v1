<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;

function wp_all_export_check_children_assign( $parent, $taxonomy, $term_ids = array() )
{
	$is_latest_child = true;
    $children = get_term_children( $parent, $taxonomy );
    if ( count($children) > 0 ){
        foreach ($children as $child) {
            if ( in_array($child, $term_ids) ){
                $is_latest_child = false;
                break;
            }
        }
    }
    return $is_latest_child;
}