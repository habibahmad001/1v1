<?php
/**
 * Plaid Navigation Walker - Desktop
 * Generates exact HTML structure matching Plaid.com navigation
 *
 * @package PlaidNavChild
 * @version 2.0.0
 */

defined('ABSPATH') || exit;

class Plaid_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Current item being processed
	 */
	private $current_item = null;

	/**
	 * Starts the list before the elements are added.
	 */
	public function start_lvl(&$output, $depth = 0, $args = array()) {
		// Use the current_item stored from start_el
		// For depth > 0, we need to get the parent item

		if ($depth === 0) {
			// Determine menu type based on child count
			$child_count = $this->current_item ? $this->get_child_count($this->current_item) : 0;

			if ($child_count <= 6) {
				// Simple dropdown for few items
				$output .= '<div class="plaid-dropdown" role="menu">';
				$output .= '<div class="plaid-dropdown-inner">';
			} else {
				// Mega menu for many items
				$columns = $this->get_column_count($child_count);
				$output .= '<div class="plaid-mega-menu plaid-mega-menu--' . $columns . '-columns" role="menu">';
				$output .= '<div class="plaid-mega-menu-inner">';
			}
		} else {
			// Section list in mega menu - use parent item title
			$parent_title = $this->current_item ? esc_html($this->current_item->title) : '';
			$output .= '<div class="plaid-mega-section">';
			if ($parent_title) {
				$output .= '<h3 class="plaid-mega-section-title">' . $parent_title . '</h3>';
			}
			$output .= '<div class="plaid-mega-section-list">';
		}
	}

	/**
	 * Ends the list of after the elements are added.
	 */
	public function end_lvl(&$output, $depth = 0, $args = array()) {
		if ($depth === 0) {
			$output .= '</div></div>';
		} else {
			$output .= '</div></div>';
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

		$is_current = in_array('current-menu-item', $classes) ||
		             in_array('current-menu-parent', $classes) ||
		             in_array('current-menu-ancestor', $classes);

		if ($is_current) {
			$classes[] = 'current';
		}

		$class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
		$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

		$item_id = 'menu-item-' . $item->ID;
		$item_id = apply_filters('nav_menu_item_id', $item_id, $item, $args, $depth);
		$item_id_attr = $item_id ? ' id="' . esc_attr($item_id) . '"' : '';

		if ($depth === 0) {
			$output .= '<li' . $item_id_attr . $class_names . '>';
		} else {
			$output .= '<div class="plaid-mega-item">';
		}

		$atts = array();
		$atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';
		$atts['target'] = !empty($item->target) ? $item->target : '';
		$atts['rel'] = !empty($item->xfn) ? $item->xfn : '';
		$atts['href'] = !empty($item->url) ? $item->url : '';

		if ($depth === 0) {
			$atts['class'] = 'plaid-nav-link';
		} else {
			$atts['class'] = 'plaid-mega-link';
		}

		if ($has_children && $depth === 0) {
			$atts['aria-haspopup'] = 'true';
			$atts['aria-expanded'] = 'false';
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

		if ($depth === 0) {
			$item_output .= '<a' . $attributes . '>';
			$item_output .= '<span class="plaid-nav-link-text">' . esc_html($item->title) . '</span>';

			if ($has_children) {
				$item_output .= $this->get_arrow_icon();
			}

			$item_output .= '</a>';
		} else {
			// Mega menu item
			$item_output .= '<a' . $attributes . '>';
			$item_output .= '<h4 class="plaid-mega-link-title">' . esc_html($item->title) . '</h4>';

			if ($has_description) {
				$item_output .= '<p class="plaid-mega-link-description">' . esc_html($item->description) . '</p>';
			}

			$item_output .= '</a>';
		}

		$item_output .= isset($args->after) ? $args->after : '';

		$output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
	}

	/**
	 * Ends the element output, if needed.
	 */
	public function end_el(&$output, $item, $depth = 0, $args = array()) {
		if ($depth === 0) {
			$output .= '</li>';
		} else {
			$output .= '</div>';
		}
	}

	/**
	 * Get the arrow icon for dropdown
	 */
	private function get_arrow_icon() {
		return '<span class="plaid-nav-arrow" aria-hidden="true">
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M4 6L8 10L12 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			</svg>
		</span>';
	}

	/**
	 * Get child count for menu item
	 */
	private function get_child_count($item) {
		global $wpdb;

		$count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}posts
			WHERE post_type = 'nav_menu_item'
			AND post_status = 'publish'
			AND post_parent = %d",
			$item->ID
		));

		return (int) $count;
	}

	/**
	 * Get column count based on child items
	 */
	private function get_column_count($child_count) {
		if ($child_count <= 3) {
			return '2';
		} elseif ($child_count <= 6) {
			return '2';
		} elseif ($child_count <= 9) {
			return '3';
		} else {
			return '4';
		}
	}
}
