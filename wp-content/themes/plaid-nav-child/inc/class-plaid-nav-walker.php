<?php
/**
 * Plaid Navigation Walker
 *
 * Custom walker for generating desktop navigation with mega menu support
 *
 * @package PlaidNavChild
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Plaid_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Current item being processed
	 */
	private $current_item = null;

	/**
	 * Child items cache
	 */
	private $children_cache = array();

	/**
	 * Starts the list before the elements are added.
	 */
	public function start_lvl(&$output, $depth = 0, $args = array()) {
		if ($depth === 0) {
			// This is a mega menu - determine column count
			$child_count = $this->get_child_count($this->current_item);
			$columns = $this->get_column_count($child_count);

			$output .= '<div class="plaid-mega-menu plaid-mega-menu--multi-column plaid-mega-menu--' . $columns . '-columns" role="group" aria-label="' . esc_attr__('Submenu', 'plaid-nav-child') . '">';
			$output .= '<div class="plaid-mega-menu-inner">';
		} else {
			// Regular submenu within mega menu
			$output .= '<ul class="plaid-mega-menu-list">';
		}
	}

	/**
	 * Ends the list of after the elements are added.
	 */
	public function end_lvl(&$output, $depth = 0, $args = array()) {
		if ($depth === 0) {
			$output .= '</div></div>';
		} else {
			$output .= '</ul>';
		}
	}

	/**
	 * Start the element output.
	 */
	public function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
		$this->current_item = $item;

		$classes = empty($item->classes) ? array() : (array) $item->classes;
		$classes[] = 'plaid-nav-item';

		$has_children = in_array('menu-item-has-children', $classes) || $this->has_children;
		$has_description = !empty($item->description);

		if ($has_children && $depth === 0) {
			$classes[] = 'has-children';
		}

		// Check if current page
		$is_current = in_array('current-menu-item', $classes) ||
		             in_array('current-menu-parent', $classes) ||
		             in_array('current-menu-ancestor', $classes);

		if ($is_current) {
			$classes[] = 'current';
		}

		$class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
		$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

		$item_id = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
		$item_id = $item_id ? ' id="' . esc_attr($item_id) . '"' : '';

		$output .= '<li' . $item_id . $class_names . '>';

		$atts = array();
		$atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';
		$atts['target'] = !empty($item->target) ? $item->target : '';
		$atts['rel'] = !empty($item->xfn) ? $item->xfn : '';
		$atts['href'] = !empty($item->url) ? $item->url : '';
		$atts['class'] = 'plaid-nav-link';

		if ($has_children && $depth === 0) {
			$atts['aria-haspopup'] = 'true';
			$atts['aria-expanded'] = 'false';
			$atts['data-plaid-menu-toggle'] = 'true';
		}

		$atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

		$attributes = '';
		foreach ($atts as $attr => $value) {
			if (!empty($value)) {
				$value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$item_output = isset($args->before) ? $args->before : '';
		$item_output .= '<a' . $attributes . '>';

		$title = apply_filters('the_title', $item->title, $item->ID);
		$title = esc_html($title);

		$item_output .= '<span class="plaid-nav-link-text">' . $title . '</span>';

		if ($has_children) {
			$item_output .= $this->get_arrow_icon();
		}

		$item_output .= '</a>';

		// Add description for items with descriptions (depth > 0)
		if ($has_description && $depth > 0) {
			$description = esc_html($item->description);
			$item_output .= '<span class="plaid-mega-menu-link-description">' . $description . '</span>';
		}

		$item_output .= isset($args->after) ? $args->after : '';

		$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
	}

	/**
	 * Ends the element output, if needed.
	 */
	public function end_el(&$output, $item, $depth = 0, $args = array()) {
		$output .= '</li>';
	}

	/**
	 * Get the arrow icon for dropdown
	 */
	private function get_arrow_icon() {
		return '<span class="plaid-nav-arrow">
			<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M2.5 4.5L6 8L9.5 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</span>';
	}

	/**
	 * Get child count for menu item
	 */
	private function get_child_count($item) {
		if (isset($this->children_cache[$item->ID])) {
			return $this->children_cache[$item->ID];
		}

		global $wpdb;

		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}posts
			WHERE post_type = 'nav_menu_item'
			AND post_status = 'publish'
			AND post_parent = %d",
			$item->ID
		));

		$this->children_cache[$item->ID] = (int) $count;
		return (int) $count;
	}

	/**
	 * Get column count based on child items
	 */
	private function get_column_count($child_count) {
		if ($child_count <= 3) {
			return '1';
		} elseif ($child_count <= 6) {
			return '2';
		} elseif ($child_count <= 9) {
			return '3';
		} else {
			return '4';
		}
	}

}
