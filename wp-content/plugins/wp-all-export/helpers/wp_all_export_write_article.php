<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;


function wp_all_export_write_article( &$article, &$element_name, $element_value ) 
{
	$base_name = $element_name;

	if ( ! isset($article[$element_name]) ) 
	{
		$article[$element_name] = $element_value;
	}
	else
	{
		$is_added = false;
		$i = 0;
		do
		{
			$element_name = $base_name . '_' . md5($i);

			if ( ! isset($article[$element_name]))
			{
				$article[$element_name] = $element_value;
				$is_added = true;
			}

			$i++;
		}
		while ( ! $is_added );		
	}
}