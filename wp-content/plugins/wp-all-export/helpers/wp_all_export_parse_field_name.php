<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;


function wp_all_export_parse_field_name($name){

    if (strpos($name, "[") === 0 && strpos($name, "]") === strlen($name) - 1){
        $snippet = str_replace(array("[", "]"), "", $name);
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found -- intentional: executes saved WP_Query argument string
        $name = eval("return " . $snippet . ";");
    }

    return $name;
}