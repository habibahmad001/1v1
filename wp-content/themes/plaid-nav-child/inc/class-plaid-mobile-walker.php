<?php
/**
 * Plaid Mobile Navigation Walker
 *
 * Custom walker for generating mobile navigation with accordion submenus
 *
 * @package PlaidNavChild
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Plaid_Mobile_Walker extends Walker_Nav_Menu {

	/**
	 * Starts the list before the elements are added.
	 */
	public function start_lvl(&$output, $depth = 0, $args = array()) {
		if ($depth === 0) {
			$submenu_id = 'mobile-submenu-' . uniqid();
			$output .= '<div class="plaid-mobile-submenu" id="' . esc_attr($submenu_id) . '" data-plaid-mobile-submenu>';
		} else {
			$output .= '<ul class="plaid-mobile-list">';
		}
	}

	/**
	 * Ends the list of after the elements are added.
	 */
	public function end_lvl(&$output, $depth = 0, $args = array()) {
		if ($depth === 0) {
			$output .= '</div>';
		} else {
			$output .= '</ul>';
		}
	}

	/**
	 * Start the element output.
	 */
	public function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
		$classes = empty($item->classes) ? array() : (array) $item->classes;

		if ($depth === 0) {
			$classes[] = 'plaid-mobile-item';
		} else {
			$classes[] = 'plaid-mobile-submenu-item';
		}

		$has_children = in_array('menu-item-has-children', $classes) || $this->has_children;
		$has_description = !empty($item->description);

		if ($has_children && $depth === 0) {
			$classes[] = 'has-children';
		}

		$is_active = in_array('current-menu-item', $classes) ||
		             in_array('current-menu-parent', $classes) ||
		             in_array('current-menu-ancestor', $classes);

		$class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
		$class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

		$item_id = 'mobile-menu-item-' . $item->ID;
		$item_id = apply_filters('nav_menu_item_id', $item_id, $item, $args, $depth);
		$item_id_attr = $item_id ? ' id="' . esc_attr($item_id) . '"' : '';

		$output .= '<li' . $item_id_attr . $class_names . '>';

		$atts = array();
		$atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';
		$atts['target'] = !empty($item->target) ? $item->target : '';
		$atts['rel'] = !empty($item->xfn) ? $item->xfn : '';
		$atts['href'] = !empty($item->url) ? $item->url : '';

		if ($depth === 0) {
			$atts['class'] = 'plaid-mobile-link';
		} else {
			$atts['class'] = 'plaid-mobile-submenu-link';
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
			$item_output .= '<div class="plaid-mobile-link-wrapper">';

			// Add toggle button for items with children
			if ($has_children) {
				$submenu_id = 'mobile-submenu-' . $item->ID;
				$item_output .= '<button type="button" class="plaid-mobile-toggle-btn" aria-expanded="' . ($is_active ? 'true' : 'false') . '" aria-controls="' . esc_attr($submenu_id) . '" data-plaid-mobile-toggle-btn data-target="' . esc_attr($submenu_id) . '">';
				$item_output .= $this->get_arrow_icon();
				$item_output .= '</button>';
			}

			$item_output .= '<a' . $attributes . '>';
			$item_output .= esc_html($item->title);
			$item_output .= '</a>';

			$item_output .= '</div>';
		} else {
			$item_output .= '<a' . $attributes . '>';
			$item_output .= esc_html($item->title);
			$item_output .= '</a>';

			if ($has_description) {
				$description = esc_html($item->description);
				$item_output .= '<span class="plaid-mobile-submenu-link-description">' . $description . '</span>';
			}
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
	 * Get the arrow icon for accordion
	 */
	private function get_arrow_icon() {
		return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="16" height="16">
			<path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>';
	}
}
