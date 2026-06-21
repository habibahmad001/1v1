<?php
namespace TaxoCSIE;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class ImportPreview {

    public static function init() {
        add_action( 'wp_ajax_academy_preview_import', [ __CLASS__, 'handle_preview' ] );
    }

    public static function handle_preview() {
        if ( empty( $_FILES['file'] ) ) {
            wp_send_json_error( 'No file uploaded' );
        }

        $file = $_FILES['file']['tmp_name'];
        $rows = \TaxoCSIE\Helper::taxo_parse_csv( $file );

        $taxonomy = sanitize_text_field( $_POST['taxonomy'] ?? 'category' );

        $preview = [];

        foreach ( $rows as $row ) {
            $check = \TaxoCSIE\Helper::taxo_validate_row( $row, $taxonomy );

            $preview[] = [
                'name'   => $row['name'] ?? '',
                'status' => $check['status'],
                'errors' => $check['errors'],
            ];
        }

        wp_send_json_success( $preview );
    }
}