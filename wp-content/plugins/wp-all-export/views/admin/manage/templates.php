<?php
// phpcs:ignoreFile WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- variables in template files inherited from controller render() scope
if(!defined('ABSPATH')) {
    die();
}
?>
<div class="wrap download-import-templates">
	<h2><?php esc_html_e('Download Import Templates', 'wp-all-export') ?></h2>
	<p class="description"><?php esc_html_e('Download your import templates and use them to import your exported file to a separate WordPress/WP All Import installation.', 'wp-all-export'); ?></p>
	<p class="description"><?php echo wp_kses(__('Install these import templates in your separate WP All Import installation from the <i>All Import › Settings</i> page by clicking the "Import Templates" button.', 'wp-all-export'), array('i' => array())); ?></p>
	<p class="submit-buttons">
		<a class="button-primary" href='<?php echo esc_url(add_query_arg(array('id' => $item['id'], 'action' => 'get_template', '_wpnonce' => wp_create_nonce( '_wpnonce-download_template' )), $this->baseUrl));?>'>Download</a>
	</p>
	<img src="<?php echo esc_url( PMXE_ROOT_URL ); ?>/static/img/import-templates.png" width="400px" style="border: 1px solid #aaa;">
    <div class="wpallexport-display-columns wpallexport-margin-top-forty">
		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plugin-owned filter output; callback in filters/wpallexport_footer.php returns trusted static HTML
		echo apply_filters('wpallexport_footer', ''); ?>
    </div>
</div>