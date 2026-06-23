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
	 * Current menu type (dropdown or mega)
	 */
	private $current_menu_type = 'dropdown';

	/**
	 * Track item count for current submenu
	 */
	private $submenu_item_count = 0;

	/**
	 * Track current parent item for submenu
	 */
	private $current_parent_item = null;

	/**
	 * Starts the list before the elements are added.
	 */
	public function start_lvl(&$output, $depth = 0, $args = array()) {
		// Use the current_item stored from start_el
		// For depth > 0, we need to get the parent item

		if ($depth === 0) {
			// Store parent item and reset counter
			$this->current_parent_item = $this->current_item;
			$this->submenu_item_count = 0;

			// Determine menu type based on child count
			$child_count = $this->current_item ? $this->get_child_count($this->current_item) : 0;

			// Use dropdown for all menus with our new layout (5 items per column, max 10 visible)
			$this->current_menu_type = 'dropdown';
			$output .= '<div class="plaid-dropdown" role="menu">';
			$output .= '<div class="plaid-dropdown-inner plaid-dropdown-inner--split">';

			// Add 30% sidebar with parent menu info
			if ($this->current_item) {
				$parent_title = esc_html($this->current_item->title);
				$parent_description = !empty($this->current_item->description) ? esc_html($this->current_item->description) : $this->get_default_description($this->current_item->title);

				$output .= '<div class="plaid-dropdown-sidebar">';
				$output .= '<h2 class="plaid-dropdown-sidebar-title">' . $parent_title . '</h2>';
				$output .= '<p class="plaid-dropdown-sidebar-description">' . $parent_description . '</p>';
				$output .= '</div>';
			}

			// Add 70% content wrapper for current items
			$output .= '<div class="plaid-dropdown-content">';
			$output .= '<div class="plaid-dropdown-grid">';

			// Store total child count for "More+" logic
			$this->total_child_count = $child_count;
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
			// Close grid, content wrapper, inner wrapper, and dropdown
			$output .= '</div></div></div></div>';
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
			// Use dropdown-link for simple dropdowns, mega-link for mega menus
			$link_class = ($this->current_menu_type === 'dropdown') ? 'plaid-dropdown-link' : 'plaid-mega-link';
			$atts['class'] = $link_class;
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
			// Increment item counter
			$this->submenu_item_count++;

			// Skip rendering if we're beyond 10 items (they'll be in "More+")
			if ($this->submenu_item_count > 10) {
				$item_output = '';
			} else {
				// Submenu item with icon, title, and description
				$icon = $this->get_item_icon($item);

				// Get description or use a default based on title
				$description = '';
				if (!empty($item->description)) {
					$description = esc_html($item->description);
				} else {
					// Generate a default description based on the title
					$description = $this->get_default_description($item->title);
				}

				$item_output .= '<a' . $attributes . '>';
				$item_output .= $icon;
				$item_output .= '<div class="plaid-dropdown-link-content">';
				$item_output .= '<span class="plaid-dropdown-link-title">' . esc_html($item->title) . '</span>';
				$item_output .= '<span class="plaid-dropdown-link-description">' . $description . '</span>';
				$item_output .= '</div>';
				$item_output .= '</a>';

				// Add "More+" link after 10th item if there are more
				if ($this->submenu_item_count === 10 && isset($this->total_child_count) && $this->total_child_count > 10) {
					$remaining = $this->total_child_count - 10;
					$item_output .= '<a href="#" class="plaid-dropdown-link plaid-dropdown-link--more">';
					$item_output .= '<span class="plaid-dropdown-link-icon"><i class="fa-solid fa-ellipsis"></i></span>';
					$item_output .= '<div class="plaid-dropdown-link-content">';
					$item_output .= '<span class="plaid-dropdown-link-title">More+</span>';
					$item_output .= '<span class="plaid-dropdown-link-description">View ' . $remaining . ' more items</span>';
					$item_output .= '</div>';
					$item_output .= '</a>';
				}
			}
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
	 * Get icon for menu item based on CSS classes, then title
	 */
	private function get_item_icon($item) {
		$icon_class = ($this->current_menu_type === 'dropdown') ? 'plaid-dropdown-link-icon' : 'plaid-mega-link-icon';

		// First, check if Font Awesome icon class is present in CSS classes
		$classes = empty($item->classes) ? array() : (array) $item->classes;

		// Look for Font Awesome icon classes (fa-*, fas fa-*, far fa-*, fab fa-*, etc.)
		$fa_icon_class = null;
		$fa_style_prefix = 'fa-solid'; // Default to solid if not specified

		foreach ($classes as $class) {
			$class_lower = strtolower(trim($class));

			// Check for style prefixes
			if (in_array($class_lower, array('fas', 'fa-solid'))) {
				$fa_style_prefix = 'fa-solid';
				continue;
			}
			if (in_array($class_lower, array('far', 'fa-regular'))) {
				$fa_style_prefix = 'fa-regular';
				continue;
			}
			if (in_array($class_lower, array('fab', 'fa-brands'))) {
				$fa_style_prefix = 'fa-brands';
				continue;
			}
			if (in_array($class_lower, array('fal', 'fa-light'))) {
				$fa_style_prefix = 'fa-light';
				continue;
			}
			if (in_array($class_lower, array('fad', 'fa-duotone'))) {
				$fa_style_prefix = 'fa-duotone';
				continue;
			}
			if (in_array($class_lower, array('fass', 'fa-sharp'))) {
				$fa_style_prefix = 'fa-sharp';
				continue;
			}

			// Check for icon class (starts with fa-)
			if (strpos($class_lower, 'fa-') === 0 && $class_lower !== 'fa-solid' && $class_lower !== 'fa-regular' && $class_lower !== 'fa-brands' && $class_lower !== 'fa-light' && $class_lower !== 'fa-duotone' && $class_lower !== 'fa-sharp') {
				$fa_icon_class = $class_lower;
				break;
			}
		}

		// If Font Awesome icon class found, use it
		if ($fa_icon_class) {
			return '<span class="' . $icon_class . '"><i class="' . $fa_style_prefix . ' ' . $fa_icon_class . '"></i></span>';
		}

		// Fallback: Check title for keyword matches (legacy behavior)
		$icon_map = array(
			'products' => 'fa-box',
			'solutions' => 'fa-lightbulb',
			'developers' => 'fa-code',
			'resources' => 'fa-book',
			'pricing' => 'fa-tag',
			'payments' => 'fa-credit-card',
			'auth' => 'fa-shield-halved',
			'identity' => 'fa-user-check',
			'balance' => 'fa-scale-balanced',
			'fraud' => 'fa-user-secret',
			'risk' => 'fa-triangle-exclamation',
			'transactions' => 'fa-receipt',
			'investments' => 'fa-chart-line',
			'transfer' => 'fa-money-bill-transfer',
			'link' => 'fa-link',
			'apis' => 'fa-api',
			'documentation' => 'fa-file-code',
			'support' => 'fa-headset',
			'contact' => 'fa-envelope',
			'login' => 'fa-right-to-bracket',
			'about' => 'fa-info-circle',
			'blog' => 'fa-newspaper',
			'careers' => 'fa-briefcase',
			'security' => 'fa-lock',
			// Domestic & Medical
			'domestic' => 'fa-house-chimney',
			'tools' => 'fa-screwdriver-wrench',
			'medical' => 'fa-stethoscope',
			'equipment' => 'fa-kit-medical',
			'hospital' => 'fa-hospital',
			'health' => 'fa-heart-pulse',
			'doctor' => 'fa-user-doctor',
			'nurse' => 'fa-user-nurse',
			'pharmacy' => 'fa-pills',
			// More common icons
			'services' => 'fa-concierge-bell',
			'features' => 'fa-star',
			'benefits' => 'fa-gift',
			'company' => 'fa-building',
			'portfolio' => 'fa-briefcase',
			'team' => 'fa-users',
			'partners' => 'fa-handshake',
			'customers' => 'fa-users-gear',
			'help' => 'fa-circle-question',
			'faq' => 'fa-circle-question',
			'guides' => 'fa-book-open',
			'tutorials' => 'fa-video',
			'reviews' => 'fa-star-half-stroke',
			'testimonials' => 'fa-quote-left',
			'news' => 'fa-newspaper',
			'events' => 'fa-calendar-days',
			'webinar' => 'fa-desktop',
			'community' => 'fa-people-group',
			'forum' => 'fa-comments',
		);

		$title_lower = strtolower($item->title);
		foreach ($icon_map as $keyword => $icon) {
			if (strpos($title_lower, $keyword) !== false) {
				return '<span class="' . $icon_class . '"><i class="fa-solid ' . $icon . '"></i></span>';
			}
		}

		// Default icon
		return '<span class="' . $icon_class . '"><i class="fa-solid fa-chevron-right"></i></span>';
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

	/**
	 * Get default description for menu item based on title
	 */
	private function get_default_description($title) {
		$title_lower = strtolower($title);

		// Generate contextual descriptions based on keywords
		$description_map = array(
			'domestic' => 'Tools and resources for domestic markets',
			'medical' => 'Healthcare and medical equipment solutions',
			'tools' => 'Essential tools and utilities',
			'equipment' => 'Professional equipment and supplies',
			'hospital' => 'Hospital and healthcare services',
			'health' => 'Health and wellness products',
			'doctor' => 'Connect with medical professionals',
			'nurse' => 'Nursing and caregiver resources',
			'pharmacy' => 'Pharmaceutical and pharmacy services',
			'products' => 'Explore our product offerings',
			'services' => 'Professional services and solutions',
			'solutions' => 'Comprehensive solutions for your needs',
			'features' => 'Discover key features and benefits',
			'pricing' => 'View pricing plans and options',
			'about' => 'Learn more about us',
			'contact' => 'Get in touch with our team',
			'support' => 'Help and support resources',
			'help' => 'FAQs and help documentation',
			'blog' => 'Latest news and articles',
			'careers' => 'Join our growing team',
			'partners' => 'Partner with us',
			'community' => 'Join our community',
		);

		foreach ($description_map as $keyword => $description) {
			if (strpos($title_lower, $keyword) !== false) {
				return $description;
			}
		}

		// Default fallback description
		return 'Explore this section';
	}
}
