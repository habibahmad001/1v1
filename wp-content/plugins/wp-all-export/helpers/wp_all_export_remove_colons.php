<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;


function wp_all_export_remove_colons($feed) {
                  
    // pull out colons from start tags
    // (<\w+):(\w+>)
    $pattern = '/(<\w+):(\w+[ |>]{1})/i';
    $replacement = '<$2';
    $feed = preg_replace($pattern, $replacement, $feed);
    // pull out colons from end tags
    // (<\/\w+):(\w+>)
    $pattern = '/(<\/\w+):(\w+>)/i';
    $replacement = '</$2';
    $feed = preg_replace($pattern, $replacement, $feed);
    // pull out colons from attributes
    $pattern = '/(\s+\w+):(\w+[=]{1})/i';
    $replacement = '$1_$2';
    $feed = preg_replace($pattern, $replacement, $feed);
    // pull colons from single element 
    // (<\w+):(\w+\/>)
    $pattern = '/(<\w+):(\w+\/>)/i';
    $replacement = '<$2';
    $feed = preg_replace($pattern, $replacement, $feed);
  
    return $feed;

}