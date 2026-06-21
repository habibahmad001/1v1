<?php

namespace TaxoCSIE;

if (!defined('ABSPATH')) {
	exit;
}


class Assets
{
	public static function init()
	{
		$self = new self();
		add_action('admin_enqueue_scripts', array($self, 'enqueue_taxocsie_taxonomy_scripts'));
	}

	public function enqueue_taxocsie_taxonomy_scripts()
	{
		wp_enqueue_script('taxocsie-scripts', TAXOCSIE_ASSETS_URI . '/dev-js/ajax.js', array('jquery'), TAXOCSIE_VERSION, true);
		wp_localize_script('taxocsie-scripts', 'taxocsieData', array(
			'previewExportNonce'   => wp_create_nonce('taxocsie_preview_export'),
			'getTermsNonce'        => wp_create_nonce('taxocsie_get_terms'),
			'ajaxCreateTermNonce'  => wp_create_nonce('taxocsie_ajax_create_term'),
			'saveTaxonomyNonce'    => wp_create_nonce('taxocsie_save_taxonomy'),
			'deleteTaxonomyNonce'  => wp_create_nonce('taxocsie_delete_taxonomy'),
		));
		wp_enqueue_style('taxocsie-style', TAXOCSIE_ASSETS_URI . '/css/menu.css', array('wp-components'), filemtime( TAXOCSIE_ASSETS_DIR_PATH . '/css/menu.css' ), 'all');
	}
}
