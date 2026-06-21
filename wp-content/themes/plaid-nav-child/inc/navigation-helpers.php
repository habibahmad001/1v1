<?php
/**
 * Navigation Helper Functions
 *
 * @package PlaidNavChild
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Check if current page is in navigation
 */
function plaid_nav_is_current_page($item_id) {
	$item = get_post($item_id);
	if (!$item || $item->post_type !== 'nav_menu_item') {
		return false;
	}

	$object_id = get_post_meta($item_id, '_menu_item_object_id', true);
	$current_object_id = get_queried_object_id();

	return $object_id === $current_object_id;
}

/**
 * Get navigation menu structure as array
 */
function plaid_nav_get_menu_structure($menu_slug) {
	$locations = get_nav_menu_locations();
	$menu_id = isset($locations[$menu_slug]) ? $locations[$menu_slug] : 0;

	if (!$menu_id) {
		return array();
	}

	$menu_items = wp_get_nav_menu_items($menu_id);
	if (!$menu_items) {
		return array();
	}

	$structure = array();
	$index_map = array();

	foreach ($menu_items as $item) {
		$structure[$item->ID] = array(
			'ID' => $item->ID,
			'title' => $item->title,
			'url' => $item->url,
			'parent' => (int) $item->menu_item_parent,
			'description' => $item->description,
			'classes' => $item->classes,
			'children' => array(),
		);
		$index_map[$item->ID] = true;
	}

	foreach ($structure as $id => &$item) {
		if ($item['parent'] && isset($structure[$item['parent']])) {
			$structure[$item['parent']]['children'][] = &$item;
		}
	}

	return array_filter($structure, function($item) {
		return $item['parent'] === 0;
	});
}

/**
 * Output navigation menu as structured JSON for JS
 */
function plaid_nav_menu_json($menu_slug = 'primary') {
	$structure = plaid_nav_get_menu_structure($menu_slug);
	return wp_json_encode($structure);
}

/**
 * Check if menu has children
 */
function plaid_nav_has_children($item_id) {
	$children = get_posts(array(
		'post_type' => 'nav_menu_item',
		'post_status' => 'publish',
		'post_parent' => $item_id,
		'posts_per_page' => 1,
		'meta_key' => '_menu_item_object_id',
	));

	return !empty($children);
}

/**
 * Get menu item depth
 */
function plaid_nav_get_item_depth($item_id) {
	$depth = 0;
	$parent_id = wp_get_post_parent_id($item_id);

	while ($parent_id && get_post_type($parent_id) === 'nav_menu_item') {
		$depth++;
		$parent_id = wp_get_post_parent_id($parent_id);
	}

	return $depth;
}

/**
 * Sanitize menu description
 */
function plaid_nav_sanitize_description($description) {
	return sanitize_text_field($description);
}

/**
 * Add description field to menu item
 */
function plaid_nav_add_description_field($item_id, $item, $depth, $args) {
	$description = get_post_meta($item_id, 'menu_item_description', true);

	?>
	<div class="description-input-wrap">
		<label for="edit-menu-item-description-<?php echo $item_id; ?>">
			<?php esc_html_e('Description', 'plaid-nav-child'); ?>
		</label>
		<input
			type="text"
			id="edit-menu-item-description-<?php echo $item_id; ?>"
			class="edit-menu-item-description"
			name="menu-item-description[<?php echo $item_id; ?>]"
			value="<?php echo esc_attr($description); ?>"
		/>
		<span class="description"><?php esc_html_e('The description will be shown in mega menus.', 'plaid-nav-child'); ?></span>
	</div>
	<?php
}
add_action('wp_nav_menu_item_custom_fields', 'plaid_nav_add_description_field', 10, 4);

/**
 * Save menu item description
 */
function plaid_nav_save_description($menu_id, $menu_item_db_id, $args) {
	if (isset($_POST['menu-item-description'][$menu_item_db_id])) {
		$description = sanitize_text_field($_POST['menu-item-description'][$menu_item_db_id]);
		update_post_meta($menu_item_db_id, 'menu_item_description', $description);
	}
}
add_action('wp_update_nav_menu_item', 'plaid_nav_save_description', 10, 3);

/**
 * Get description for menu item
 */
function plaid_nav_get_description($item_id) {
	return get_post_meta($item_id, 'menu_item_description', true);
}

/**
 * Check if mega menu should be enabled for item
 */
function plaid_nav_is_mega_menu($item_id) {
	$classes = get_post_meta($item_id, '_menu_item_classes', true);
	return in_array('mega-menu', (array) $classes);
}

/**
 * Add mega menu checkbox to menu item
 */
function plaid_nav_add_mega_menu_checkbox($item_id, $item, $depth, $args) {
	$classes = get_post_meta($item_id, '_menu_item_classes', true);
	$is_mega = in_array('mega-menu', (array) $classes);

	?>
	<div class="mega-menu-input-wrap">
		<label for="edit-menu-item-mega-menu-<?php echo $item_id; ?>" style="display: flex; align-items: center; gap: 8px;">
			<input
				type="checkbox"
				id="edit-menu-item-mega-menu-<?php echo $item_id; ?>"
				class="edit-menu-item-mega-menu"
				name="menu-item-mega-menu[<?php echo $item_id; ?>]"
				value="mega-menu"
				<?php checked($is_mega); ?>
			/>
			<?php esc_html_e('Enable mega menu', 'plaid-nav-child'); ?>
		</label>
	</div>
	<?php
}
add_action('wp_nav_menu_item_custom_fields', 'plaid_nav_add_mega_menu_checkbox', 10, 4);

/**
 * Save mega menu setting
 */
function plaid_nav_save_mega_menu($menu_id, $menu_item_db_id, $args) {
	$classes = get_post_meta($menu_item_db_id, '_menu_item_classes', true);
	if (!is_array($classes)) {
		$classes = array();
	}

	if (isset($_POST['menu-item-mega-menu'][$menu_item_db_id])) {
		if (!in_array('mega-menu', $classes)) {
			$classes[] = 'mega-menu';
		}
	} else {
		$classes = array_diff($classes, array('mega-menu'));
	}

	update_post_meta($menu_item_db_id, '_menu_item_classes', $classes);
}
add_action('wp_update_nav_menu_item', 'plaid_nav_save_mega_menu', 10, 3);

/**
 * Get mobile menu state cookie
 */
function plaid_nav_get_mobile_state() {
	if (isset($_COOKIE['plaid_mobile_menu_open'])) {
		return $_COOKIE['plaid_mobile_menu_open'] === '1';
	}
	return false;
}

/**
 * Set mobile menu state cookie
 */
function plaid_nav_set_mobile_state($is_open) {
	$expiration = $is_open ? time() + 86400 : time() - 3600;
	setcookie('plaid_mobile_menu_open', $is_open ? '1' : '0', $expiration, '/');
}
