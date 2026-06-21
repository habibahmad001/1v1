<?php
if(!defined('ABSPATH')) {
    die();
}
?>
<h2>Bulk Delete Exports</h2>

<form method="post">
	<input type="hidden" name="action" value="bulk" />
	<input type="hidden" name="bulk-action" value="<?php echo esc_attr($action) ?>" />
	<?php foreach ($ids as $id): ?>
		<input type="hidden" name="items[]" value="<?php echo esc_attr($id) ?>" />
	<?php endforeach ?>
	
	<?php /* translators: 1: count, 2: singular/plural noun */ ?>
	<p><?php echo wp_kses_post(sprintf(__('Are you sure you want to delete <strong>%1$s</strong> selected %2$s?', 'wp-all-export'), intval($items->count()), _n('export', 'exports', intval($items->count()), 'wp-all-export'))) ?></p>
	
	<p class="submit">
		<?php wp_nonce_field('bulk-exports', '_wpnonce_bulk-exports') ?>
		<input type="hidden" name="is_confirmed" value="1" />
		<input type="submit" class="button-primary" value="Delete" />
	</p>
</form>