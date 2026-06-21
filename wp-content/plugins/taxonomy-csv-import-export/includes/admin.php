<?php

namespace TaxoCSIE;

if (!defined('ABSPATH')) {
	exit;
}

use TaxoCSIE\Helper;

class Admin
{

	public static function init()
	{
		$self = new self();
		\TaxoCSIE\Admin\Preview::init();
		add_action('admin_init', array($self, 'handle_taxocsie_import_export'));
		add_action('admin_head', array($self, 'taxocsie_admin_notice_remove'));
		add_action('wp_ajax_taxocsie_preview_export', array($self, 'handle_preview_export'));
		add_action('wp_ajax_taxocsie_get_parent_terms', array($self, 'handle_get_parent_terms'));
		add_action('wp_ajax_taxocsie_ajax_create_term', array($self, 'handle_ajax_create_term'));
	}

	public function handle_taxocsie_import_export()
	{
		if (isset($_POST['taxonomy_export_csv']) ) {
			$this->taxocsie_export_csv();
		} elseif (isset($_POST['taxonomy_import_csv']) && isset($_FILES['csv_file'])) {
			$this->taxocsie_import_csv();
		} elseif (isset($_GET['taxocsie_export_users']) ) {
			$this->taxocsie_export_users();
		} elseif (isset($_POST['taxocsie_import_users']) && isset($_FILES['taxocsie_import_file'])) {
			$this->taxocsie_import_users();
		} elseif (isset($_POST['taxocsie_create_term'])) {
			$this->taxocsie_create_term();
		}

	}

	public function taxocsie_export_csv()
	{
		if (!current_user_can('manage_options') || !check_admin_referer('taxonomy_csv_export', 'taxonomy_csv_export_nonce')) {
			wp_die('Unauthorized export request.');
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$taxonomy = sanitize_text_field(wp_unslash($_POST['taxocsie_type']));
		if (empty($taxonomy) || !taxonomy_exists($taxonomy)) {
			wp_die('Invalid taxonomy.');
		}

		$terms = get_terms(
			array(
				'taxonomy' => $taxonomy,
				'hide_empty' => false,
				'orderby' => 'name',
				'order' => 'ASC',
			)
		);

		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="' . $taxonomy . '-terms.csv"');

		$output = fopen('php://output', 'w');
		fputcsv($output, array('name', 'slug', 'description', 'parent_slug'), ',', '"', '');

		foreach ($terms as $term) {
			$parent_term = $term->parent ? get_term($term->parent, $taxonomy) : null;
			$parent_slug = $parent_term ? $parent_term->slug : '';
			fputcsv($output, array($term->name, $term->slug, $term->description, $parent_slug), ',', '"', '');
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose($output);
		exit;
	}

	public function taxocsie_import_csv()
	{
		if (!current_user_can('manage_options') || !check_admin_referer('taxonomy_csv_import', 'taxonomy_csv_import_nonce')) {
			wp_die('Unauthorized import request.');
		}

		$taxonomy = isset($_POST['taxocsie_type']) ? sanitize_text_field(wp_unslash($_POST['taxocsie_type'])) : '';
		if (empty($taxonomy) || !taxonomy_exists($taxonomy) || empty($_FILES['csv_file']['tmp_name'])) {
			wp_die('Invalid import request.');
		}
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$csv = array_map(
			static function ( $line ) {
				return str_getcsv( $line, ',', '"', '' );
			},
			file( $_FILES['csv_file']['tmp_name'] ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_file
		);
		unset($csv[0]); // remove header

		foreach ($csv as $row) {
			if (count($row) < 4) {
				continue;
			}

			$name = sanitize_text_field($row[0]);
			$slug = sanitize_title($row[1]);
			$description = sanitize_text_field($row[2]);
			$parent_slug = sanitize_title($row[3]);
			$parent_id = 0;

			// Resolve parent slug to ID
			if ($parent_slug) {
				$parent_term = get_term_by('slug', $parent_slug, $taxonomy);
				if ($parent_term && !is_wp_error($parent_term)) {
					$parent_id = $parent_term->term_id;
				}
			}

			// Ensure unique slug
			$unique_slug = $slug;
			$i = 1;
			while (term_exists($unique_slug, $taxonomy)) {
				$unique_slug = $slug . '-' . $i++;
			}

			// Insert term
			wp_insert_term(
				$name,
				$taxonomy,
				array(
					'slug' => $unique_slug,
					'description' => $description,
					'parent' => $parent_id,
				)
			);
		}

		set_transient('taxoscie_csv_data', $csv, 60);


		wp_safe_redirect(admin_url('admin.php?page=taxonomy-csv-import-export&imported=1'));
		exit;
	}

	public function taxocsie_import_users() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if (
			! isset( $_POST['taxocsie_nonce'] ) ||
			! wp_verify_nonce( $_POST['taxocsie_nonce'], 'taxocsie_import_users_nonce' )
		) {
			wp_safe_redirect( admin_url( 'admin.php?page=taxonomy-csv-import-export&import=error' ) );
			exit;
		}

		if ( empty( $_FILES['taxocsie_import_file']['tmp_name'] ) ) {
			return;
		}

		$file = $_FILES['taxocsie_import_file']['tmp_name'];

		Helper::import_users( $file );

		wp_safe_redirect( admin_url( 'admin.php?page=taxonomy-csv-import-export-users&user-imported=success' ) );
		exit;
	}

	public function taxocsie_export_users() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$selected_roles = isset( $_GET['roles'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET['roles'] ) ) : [];

		Helper::export_users( $selected_roles );
	}

	public function taxocsie_create_term() {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'taxocsie_create_term', 'taxocsie_create_term_nonce' ) ) {
			wp_die( 'Unauthorized.' );
		}

		$taxonomy    = sanitize_text_field( wp_unslash( $_POST['taxocsie_create_taxonomy'] ?? '' ) );
		$name        = sanitize_text_field( wp_unslash( $_POST['term_name'] ?? '' ) );
		$slug        = sanitize_title( wp_unslash( $_POST['term_slug'] ?? '' ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['term_description'] ?? '' ) );
		$parent      = absint( $_POST['term_parent'] ?? 0 );

		$base = admin_url( 'admin.php?page=taxonomy-csv-import-export-create' );

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			wp_safe_redirect( $base . '&create_error=' . rawurlencode( 'Invalid taxonomy selected.' ) );
			exit;
		}

		if ( empty( $name ) ) {
			wp_safe_redirect( $base . '&create_error=' . rawurlencode( 'Term name is required.' ) );
			exit;
		}

		$args = array(
			'description' => $description,
			'parent'      => $parent,
		);
		if ( ! empty( $slug ) ) {
			$args['slug'] = $slug;
		}

		$result = wp_insert_term( $name, $taxonomy, $args );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( $base . '&create_error=' . rawurlencode( $result->get_error_message() ) );
			exit;
		}

		wp_safe_redirect( $base . '&term_created=1&created_taxonomy=' . rawurlencode( $taxonomy ) );
		exit;
	}

	public function handle_ajax_create_term() {
		check_ajax_referer( 'taxocsie_ajax_create_term', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$taxonomy    = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ?? '' ) );
		$name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$description = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			wp_send_json_error( 'Invalid taxonomy.' );
		}

		if ( empty( $name ) ) {
			wp_send_json_error( 'Term name is required.' );
		}

		$result = wp_insert_term( $name, $taxonomy, array( 'description' => $description ) );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		$term = get_term( $result['term_id'], $taxonomy );
		wp_send_json_success( array(
			'term_id' => $result['term_id'],
			'name'    => $term->name,
			'slug'    => $term->slug,
		) );
	}

	public function handle_get_parent_terms() {
		check_ajax_referer( 'taxocsie_get_terms', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$taxonomy = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ?? '' ) );

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			wp_send_json_error( 'Invalid taxonomy.' );
		}

		$tax_obj = get_taxonomy( $taxonomy );
		$terms   = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		$items = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$items[] = array( 'id' => $term->term_id, 'name' => $term->name );
			}
		}

		wp_send_json_success( array(
			'hierarchical' => (bool) $tax_obj->hierarchical,
			'terms'        => $items,
		) );
	}

	public function handle_preview_export() {
		check_ajax_referer( 'taxocsie_preview_export', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$taxonomy = sanitize_text_field( wp_unslash( $_POST['taxonomy'] ?? '' ) );

		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			wp_send_json_error( 'Invalid taxonomy.' );
		}

		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $terms ) ) {
			wp_send_json_error( 'Failed to fetch terms.' );
		}

		$rows = array();
		foreach ( $terms as $term ) {
			$parent_term = $term->parent ? get_term( $term->parent, $taxonomy ) : null;
			$rows[] = array(
				'name'        => $term->name,
				'slug'        => $term->slug,
				'description' => $term->description,
				'parent_slug' => ( $parent_term && ! is_wp_error( $parent_term ) ) ? $parent_term->slug : '',
			);
		}

		wp_send_json_success( $rows );
	}

	// Check if we are on your custom menu page
	public function taxocsie_admin_notice_remove()
	{
		$screen = get_current_screen();
		if ( isset( $screen->id ) && (
			$screen->id === 'toplevel_page_taxonomy-csv-import-export' ||
			$screen->id === 'import-export_page_taxonomy-csv-import-export-import' ||
			$screen->id === 'import-export_page_taxonomy-csv-import-export-users' ||
			$screen->id === 'import-export_page_taxonomy-csv-import-export-create' ||
			$screen->id === 'import-export_page_taxonomy-csv-import-export-taxonomy'
		) ) {

			// Remove default WP and plugin notices
			remove_all_actions('admin_notices');
			remove_all_actions('all_admin_notices');
		}
	}
}