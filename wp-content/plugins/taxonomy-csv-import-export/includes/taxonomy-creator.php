<?php

namespace TaxoCSIE;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TaxonomyCreator {

	const OPTION_KEY = 'taxocsie_custom_taxonomies';

	public static function init() {
		$self = new self();
		add_action( 'init', array( $self, 'register_saved_taxonomies' ), 5 );
		add_action( 'wp_ajax_taxocsie_save_taxonomy',   array( $self, 'handle_ajax_save_taxonomy' ) );
		add_action( 'wp_ajax_taxocsie_delete_taxonomy', array( $self, 'handle_ajax_delete_taxonomy' ) );
	}

	public function register_saved_taxonomies() {
		$taxonomies = get_option( self::OPTION_KEY, array() );
		foreach ( $taxonomies as $slug => $data ) {
			if ( taxonomy_exists( $slug ) ) {
				continue;
			}
			register_taxonomy(
				$slug,
				$data['post_types'],
				array(
					'labels'       => array(
						'name'          => $data['label'],
						'singular_name' => $data['singular_label'],
					),
					'description'  => $data['description'] ?? '',
					'hierarchical' => (bool) $data['hierarchical'],
					'public'       => true,
					'show_ui'      => true,
					'show_in_menu' => true,
					'show_in_rest' => true,
					'rewrite'      => array( 'slug' => $slug ),
				)
			);
		}
	}

	public function handle_ajax_save_taxonomy() {
		check_ajax_referer( 'taxocsie_save_taxonomy', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$label          = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );
		$singular_label = sanitize_text_field( wp_unslash( $_POST['singular_label'] ?? '' ) );
		$slug           = sanitize_key( wp_unslash( $_POST['slug'] ?? '' ) );
		$description    = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );
		$hierarchical   = ! empty( $_POST['hierarchical'] ) && $_POST['hierarchical'] === '1';
		$post_types     = isset( $_POST['post_types'] )
			? array_values( array_filter( array_map( 'sanitize_key', (array) wp_unslash( $_POST['post_types'] ) ) ) )
			: array();

		if ( empty( $label ) ) {
			wp_send_json_error( 'Label is required.' );
		}
		if ( empty( $slug ) ) {
			wp_send_json_error( 'Slug is required.' );
		}
		if ( empty( $post_types ) ) {
			wp_send_json_error( 'Please select at least one post type.' );
		}

		$saved = get_option( self::OPTION_KEY, array() );

		if ( isset( $saved[ $slug ] ) ) {
			wp_send_json_error( 'A custom taxonomy with this slug already exists.' );
		}
		if ( taxonomy_exists( $slug ) ) {
			wp_send_json_error( 'This slug is already used by WordPress or another plugin.' );
		}

		$saved[ $slug ] = array(
			'label'          => $label,
			'singular_label' => $singular_label ?: $label,
			'description'    => $description,
			'hierarchical'   => $hierarchical,
			'post_types'     => $post_types,
		);

		update_option( self::OPTION_KEY, $saved );

		wp_send_json_success( array(
			'slug'         => $slug,
			'label'        => $label,
			'hierarchical' => $hierarchical,
			'post_types'   => $post_types,
		) );
	}

	public function handle_ajax_delete_taxonomy() {
		check_ajax_referer( 'taxocsie_delete_taxonomy', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$slug = sanitize_key( wp_unslash( $_POST['slug'] ?? '' ) );
		if ( empty( $slug ) ) {
			wp_send_json_error( 'Invalid slug.' );
		}

		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! isset( $saved[ $slug ] ) ) {
			wp_send_json_error( 'Taxonomy not found.' );
		}

		unset( $saved[ $slug ] );
		update_option( self::OPTION_KEY, $saved );

		wp_send_json_success( array( 'slug' => $slug ) );
	}

	public static function get_saved() {
		return (array) get_option( self::OPTION_KEY, array() );
	}
}
