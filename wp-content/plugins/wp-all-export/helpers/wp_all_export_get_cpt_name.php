<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;


function wp_all_export_get_cpt_name($cpt = array(), $count = 2, $post = array())
{
	$cptName = '';
	if ( ! empty($cpt))
	{
		if (in_array('users', $cpt))
		{
			$cptName = ($count > 1) ? __('Users', 'wp-all-export') : __('User', 'wp-all-export');
		}
		elseif (in_array('shop_customer', $cpt))
		{
			$cptName = ($count > 1) ? __('Customers', 'wp-all-export') : __('Customer', 'wp-all-export');
		}
		elseif (in_array('comments', $cpt))
		{
			$cptName = ($count > 1) ? __('Comments', 'wp-all-export') : __('Comment', 'wp-all-export');
		}
        elseif (in_array('taxonomies', $cpt))
        {
            if (!empty($post['taxonomy_to_export'])){
                $tx = get_taxonomy( $post['taxonomy_to_export'] );
                $cptName = ($count > 1) ? $tx->labels->name : $tx->labels->singular_name;
            }
            else{
                $cptName = ($count > 1) ? __('Taxonomy Terms', 'wp-all-export') : __('Taxonomy Term', 'wp-all-export');
            }
        }
        elseif (in_array('custom_wpae-gf-addon', $cpt)) {
            $cptName = ($count > 1) ? __('Entries', 'wp-all-export') : __('Entry', 'wp-all-export');
        }
        else
		{
			if (count($cpt) === 1 and in_array('product_variation', $cpt) and class_exists('WooCommerce')){
				$cptName = ($count > 1) ? 'Variations' : 'Variation';
			}
			else
			{
				$post_type_details = get_post_type_object( $cpt[0] );				
				if ($post_type_details)
				{
					$cptName = ($count > 1) ? $post_type_details->labels->name : $post_type_details->labels->singular_name;
				}				
			}			
		}
	}
	if (empty($cptName))
	{
		$cptName = ($count > 1) ? __('Records', 'wp-all-export') : __('Record', 'wp-all-export');
	}

	return $cptName;
}