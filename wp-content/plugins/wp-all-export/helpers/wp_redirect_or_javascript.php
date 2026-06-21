<?php

// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound,WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- legitimate plugin prefixes (pmxe/PMXE/wpae/Wpae/wp_all_export/wpallexport/XmlExport/CdataStrategy/VariableProductTitle/Soflyy/GF_Export); Plugin Check does not honor phpcs.xml prefix declaration
defined( 'ABSPATH' ) || exit;

if ( ! function_exists('wp_redirect_or_javascript')):
/**
 * For AJAX request outputs javascript specified, otherwise acts like wp_redirect 
 * @param string $location
 * @param string[optional] $javascript
 * @param int[optional] $status
 */
function wp_redirect_or_javascript($location, $javascript = NULL, $status = 302) {
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUESTED_WITH'] ) ) ) === 'xmlhttprequest') {
		is_null($javascript) and $javascript = 'location.href="' . addslashes($location) . '";';
		echo '<script type="text/javascript">' . esc_js($javascript) . '</script>';
	} else {
		return wp_safe_redirect($location, $status);
	}
}
endif;