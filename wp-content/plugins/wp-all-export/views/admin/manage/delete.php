<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<h2><?php esc_html_e('Delete Export', 'wp-all-export') ?></h2>

<form method="post">
	<?php /* translators: %s: export friendly name */ ?>
	<p><?php echo wp_kses_post(sprintf(__('Are you sure you want to delete <strong>%s</strong> export?', 'wp-all-export'), wp_all_export_clear_xss(esc_html($item->friendly_name)))); ?></p>
	<p class="submit">
		<?php wp_nonce_field('delete-export', '_wpnonce_delete-export') ?>
		<input type="hidden" name="is_confirmed" value="1" />
		<input type="submit" class="button-primary" value="Delete" />
	</p>
	
</form>