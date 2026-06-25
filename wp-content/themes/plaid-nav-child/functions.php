<?php
/**
 * Plaid Navigation Child Theme Functions
 *
 * @package PlaidNavChild
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Theme Setup
 */
function plaid_nav_child_setup() {
	// Add theme support
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('html5', array(
		'search-form',
		'comment-form',
		'comment-list',
		'gallery',
		'caption',
	));

	// Register navigation menus
	register_nav_menus(array(
		'primary' => __('Primary Navigation', 'plaid-nav-child'),
		'mobile' => __('Mobile Navigation', 'plaid-nav-child'),
		'footer' => __('Footer Navigation', 'plaid-nav-child'),
	));

	// Add custom menu support for descriptions
	add_filter('wp_nav_menu_item_custom_fields', function() {
		return array('description');
	}, 10, 0);
}
add_action('after_setup_theme', 'plaid_nav_child_setup');

/**
 * Enqueue Scripts and Styles
 */
function plaid_nav_child_enqueue_assets() {
	$theme_version = wp_get_theme()->get('Version');

	// Dynamic version based on current date/time for cache busting
	$current_datetime = date('YmdHi'); // Format: YYYYMMDDHHMM (e.g., 202412241530)

	// Get file modification times for cache busting
	$child_style_path = get_stylesheet_directory() . '/style.css';
	$nav_css_path = get_stylesheet_directory() . '/assets/css/navigation.css';
	$nav_js_path = get_stylesheet_directory() . '/assets/js/navigation.js';

	$child_style_version = file_exists($child_style_path) ? $current_datetime : $theme_version;
	$nav_css_version = file_exists($nav_css_path) ? $current_datetime : $theme_version;
	$nav_js_version = file_exists($nav_js_path) ? $current_datetime : $theme_version;

	// Parent theme stylesheet
	wp_enqueue_style(
		'twentytwentyfive-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme(get_template())->get('Version')
	);

	// Font Awesome for icons
	wp_enqueue_style(
		'font-awesome',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
		array(),
		'6.5.1'
	);

	// Child theme stylesheet - load AFTER parent
	wp_enqueue_style(
		'plaid-nav-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array('twentytwentyfive-style'),
		$child_style_version
	);

	// Navigation stylesheet - load AFTER child theme
	wp_enqueue_style(
		'plaid-navigation-css',
		get_stylesheet_directory_uri() . '/assets/css/navigation.css',
		array('plaid-nav-child-style'),
		$nav_css_version
	);

	// Navigation JavaScript
	$nav_js_path = get_stylesheet_directory() . '/assets/js/navigation.js';
	if (file_exists($nav_js_path)) {
		wp_enqueue_script(
			'plaid-navigation-js',
			get_stylesheet_directory_uri() . '/assets/js/navigation.js',
			array(),
			$nav_js_version,
			true
		);

		// Localize script for PHP data
		wp_localize_script('plaid-navigation-js', 'plaidNavData', array(
			'nonce' => wp_create_nonce('plaid-nav-nonce'),
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'siteUrl' => site_url(),
			'themeUrl' => get_stylesheet_directory_uri(),
			'hoverDelay' => 150,
			'closeDelay' => 300,
			'desktopBreakpoint' => 768
		));
	} else {
		// Fallback: add error logging
		error_log('plaid-nav-child: navigation.js not found at ' . $nav_js_path);
	}
}
add_action('wp_enqueue_scripts', 'plaid_nav_child_enqueue_assets', 20);

/**
 * Add skip link for accessibility
 */
function plaid_nav_child_skip_link() {
	?>
	<a class="plaid-skip-link" href="#primary-content"><?php esc_html_e('Skip to main content', 'plaid-nav-child'); ?></a>
	<?php
}
add_action('wp_body_open', 'plaid_nav_child_skip_link');

/**
 * Inject navigation after body open (for block themes)
 */
function plaid_nav_child_inject_navigation() {
	render_custom_navigation();
}
add_action('wp_body_open', 'plaid_nav_child_inject_navigation', 5);

/**
 * Add body classes for navigation state
 */
function plaid_nav_child_body_classes($classes) {
	// Add class if mobile menu is open
	if (isset($_COOKIE['plaid_mobile_menu_open']) && $_COOKIE['plaid_mobile_menu_open'] === '1') {
		$classes[] = 'mobile-menu-open';
	}

	return $classes;
}
add_filter('body_class', 'plaid_nav_child_body_classes');

/**
 * Render custom navigation
 * Central function for rendering the complete navigation system
 */
function render_custom_navigation() {
	$mobile_menu_open = isset($_COOKIE['plaid_mobile_menu_open']) && $_COOKIE['plaid_mobile_menu_open'] === '1';

	?>
	<header class="plaid-header<?php echo $mobile_menu_open ? ' mobile-menu-active' : ''; ?>" role="banner">
		<div class="plaid-header-container">
			<?php render_plaid_logo(); ?>

			<?php render_desktop_navigation(); ?>

			<?php render_mobile_navigation_toggle(); ?>
		</div>
	</header>

	<?php render_mobile_navigation(); ?>
	<?php
}

/**
 * Inline JavaScript functions for mobile navigation
 * These are added directly in the HTML for reliability
 */
function plaid_navigation_inline_js() {
	?>
	<script>
	// Mobile navigation state
	var plaidMobileState = {
		isOpen: false,
		panelStack: [],
		currentPanel: null
	};

	// Toggle mobile menu
	function plaidToggleMobileMenu(e) {
		console.log('plaidToggleMobileMenu called');
		e.preventDefault();
		e.stopPropagation();

		var menu = document.getElementById('plaid-mobile-menu');
		var backdrop = document.getElementById('plaid-mobile-backdrop');
		var toggle = document.querySelector('.plaid-mobile-toggle');
		var header = document.querySelector('.plaid-header');

		if (!menu) return;

		if (plaidMobileState.isOpen) {
			// Close menu
			menu.style.display = 'none';
			menu.classList.remove('active');
			backdrop.style.display = 'none';
			backdrop.classList.remove('active');
			header.classList.remove('mobile-menu-active');
			document.body.style.overflow = '';
			toggle.setAttribute('aria-expanded', 'false');
			plaidMobileState.isOpen = false;
			plaidResetPanels();
		} else {
			// Open menu
			menu.style.display = 'flex';
			menu.classList.add('active');
			backdrop.style.display = 'block';
			backdrop.classList.add('active');
			header.classList.add('mobile-menu-active');
			document.body.style.overflow = 'hidden';
			toggle.setAttribute('aria-expanded', 'true');
			plaidMobileState.isOpen = true;
			plaidResetPanels();
		}
	}

	// Close mobile menu
	function plaidCloseMobileMenu(e) {
		console.log('plaidCloseMobileMenu called');
		if (e) {
			e.preventDefault();
			e.stopPropagation();
		}

		var menu = document.getElementById('plaid-mobile-menu');
		var backdrop = document.getElementById('plaid-mobile-backdrop');
		var toggle = document.querySelector('.plaid-mobile-toggle');
		var header = document.querySelector('.plaid-header');

		if (!menu) return;

		menu.style.display = 'none';
		menu.classList.remove('active');
		backdrop.style.display = 'none';
		backdrop.classList.remove('active');
		header.classList.remove('mobile-menu-active');
		document.body.style.overflow = '';
		if (toggle) toggle.setAttribute('aria-expanded', 'false');
		plaidMobileState.isOpen = false;
		plaidResetPanels();
	}

	// Navigate to panel
	function plaidNavigateToPanel(e) {
		e.preventDefault();
		e.stopPropagation();

		var targetId = e.currentTarget.getAttribute('data-panel-target');
		console.log('plaidNavigateToPanel: ' + targetId);

		var targetPanel = document.getElementById('plaid-mobile-panel-' + targetId);
		if (!targetPanel) {
			console.error('Panel not found: plaid-mobile-panel-' + targetId);
			return;
		}

		var currentPanel = plaidMobileState.currentPanel;
		if (!currentPanel) {
			currentPanel = document.getElementById('plaid-mobile-panel-root');
		}

		if (currentPanel) {
			plaidMobileState.panelStack.push(currentPanel);
			currentPanel.classList.remove('active');
		}

		targetPanel.classList.add('active');
		plaidMobileState.currentPanel = targetPanel;

		// Update header
		plaidUpdateMobileHeader(true);

		// Focus first item
		var firstLink = targetPanel.querySelector('a, button');
		if (firstLink) firstLink.focus();
	}

	// Navigate back
	function plaidNavigateBack(e) {
		console.log('plaidNavigateBack called');
		if (e) {
			e.preventDefault();
			e.stopPropagation();
		}

		if (plaidMobileState.panelStack.length === 0) return;

		var currentPanel = plaidMobileState.currentPanel;
		var previousPanel = plaidMobileState.panelStack.pop();

		if (currentPanel) currentPanel.classList.remove('active');
		if (previousPanel) previousPanel.classList.add('active');

		plaidMobileState.currentPanel = previousPanel;
		plaidUpdateMobileHeader(plaidMobileState.panelStack.length > 0);

		if (previousPanel) {
			var firstLink = previousPanel.querySelector('a, button');
			if (firstLink) firstLink.focus();
		}
	}

	// Update mobile header
	function plaidUpdateMobileHeader(showBack) {
		var logo = document.getElementById('plaid-mobile-logo');
		var backBtn = document.getElementById('plaid-mobile-back-btn');

		if (!logo || !backBtn) return;

		if (showBack) {
			backBtn.style.display = 'flex';
			logo.style.display = 'none';
		} else {
			logo.style.display = 'flex';
			backBtn.style.display = 'none';
		}
	}

	// Reset panels
	function plaidResetPanels() {
		plaidMobileState.panelStack = [];
		var rootPanel = document.getElementById('plaid-mobile-panel-root');
		plaidMobileState.currentPanel = rootPanel;

		var allPanels = document.querySelectorAll('.plaid-mobile-panel');
		allPanels.forEach(function(panel) {
			panel.classList.remove('active');
		});

		if (rootPanel) rootPanel.classList.add('active');
		plaidUpdateMobileHeader(false);
	}

	// Initialize panel navigation
	document.addEventListener('DOMContentLoaded', function() {
		console.log('Plaid nav inline JS loaded');

		// Bind panel arrow clicks
		var mobileMenu = document.getElementById('plaid-mobile-menu');
		if (mobileMenu) {
			mobileMenu.addEventListener('click', function(e) {
				var arrowBtn = e.target.closest('.plaid-mobile-panel-arrow');
				if (arrowBtn) {
					plaidNavigateToPanel(e);
				}
			});
		}

		// Set initial panel
		plaidResetPanels();
	});
	</script>
	<?php
}
add_action('wp_footer', 'plaid_navigation_inline_js');

/**
 * Render Logo
 */
function render_plaid_logo() {
	// Use relative path for logo from theme assets
	$logo_path = get_stylesheet_directory_uri() . '/assets/images/heydearwomen-logo-1080-x-1350-px.png';
	$site_title = get_bloginfo('name');
	$home_url = esc_url(home_url('/'));

	?>
	<a href="<?php echo $home_url; ?>" class="plaid-logo" aria-label="<?php echo esc_attr($site_title); ?>">
		<img src="<?php echo esc_url($logo_path); ?>" alt="<?php echo esc_attr($site_title); ?>" class="plaid-logo-icon">
		<span class="plaid-logo-text"><?php echo esc_html($site_title); ?></span>
	</a>
	<?php
}

/**
 * Render Desktop Navigation
 */
function render_desktop_navigation() {
	$locations = get_nav_menu_locations();
	$primary_menu = isset($locations['primary']) ? wp_get_nav_menu_object($locations['primary']) : null;

	if (!$primary_menu) {
		return;
	}

	?>
	<nav class="plaid-nav-desktop" role="navigation" aria-label="<?php esc_attr_e('Primary navigation', 'plaid-nav-child'); ?>">
		<?php
		wp_nav_menu(array(
			'menu' => $primary_menu->term_id,
			'container' => '',
			'menu_class' => 'plaid-nav-list',
			'fallback_cb' => false,
			'walker' => new Plaid_Nav_Walker(),
			'items_wrap' => '<ul id="plaid-nav-menu" class="%1$s">%3$s</ul>',
			'echo' => true,
		));
		?>

		<div class="plaid-nav-cta">
			<?php render_navigation_ctas(); ?>
		</div>
	</nav>
	<?php
}

/**
 * Render Navigation CTAs
 */
function render_navigation_ctas() {
	$login_url = get_option('plaid_nav_login_url', '#login');
	$contact_url = get_option('plaid_nav_contact_url', '#contact');
	$login_text = get_option('plaid_nav_login_text', __('Log in', 'plaid-nav-child'));
	$contact_text = get_option('plaid_nav_contact_text', __('Donate', 'plaid-nav-child'));

	?>
	<a href="<?php echo esc_url($login_url); ?>" class="plaid-nav-button plaid-nav-button--ghost">
		<?php echo esc_html($login_text); ?>
	</a>
	<a href="<?php echo esc_url($contact_url); ?>" class="plaid-nav-button plaid-nav-button--primary">
		<?php echo esc_html($contact_text); ?>
	</a>
	<?php
}

/**
 * Render Mobile Navigation Toggle
 */
function render_mobile_navigation_toggle() {
	?>
	<button
		class="plaid-mobile-toggle"
		type="button"
		aria-label="<?php esc_attr_e('Toggle menu', 'plaid-nav-child'); ?>"
		aria-expanded="false"
		aria-controls="plaid-mobile-menu"
		onclick="plaidToggleMobileMenu(event)">
		<span class="plaid-mobile-toggle-bar"></span>
		<span class="plaid-mobile-toggle-bar"></span>
		<span class="plaid-mobile-toggle-bar"></span>
	</button>
	<?php
}

/**
 * Render Mobile Navigation
 * Panel-based sliding navigation like Plaid.com mobile menu
 */
function render_mobile_navigation() {
	$locations = get_nav_menu_locations();
	$mobile_menu = isset($locations['mobile']) ? wp_get_nav_menu_object($locations['mobile']) : null;

	// Fallback to primary menu if mobile menu not set
	if (!$mobile_menu) {
		$primary_menu = isset($locations['primary']) ? wp_get_nav_menu_object($locations['primary']) : null;
		$mobile_menu = $primary_menu;
	}

	// Simple mobile menu - just output the primary menu
	if (!$mobile_menu) {
		return;
	}

	$logo_path = get_stylesheet_directory_uri() . '/assets/images/heydearwomen-logo-1080-x-1350-px.png';
	$site_title = get_bloginfo('name');
	$home_url = esc_url(home_url('/'));

	?>
	<div class="plaid-mobile-backdrop" id="plaid-mobile-backdrop" style="display: none;"></div>
	<div class="plaid-mobile-menu" id="plaid-mobile-menu" style="display: none;">
		<!-- Mobile Menu Header -->
		<div class="plaid-mobile-menu-header">
			<div class="plaid-mobile-menu-header-left">
				<!-- Logo (shown on root level) -->
				<a href="<?php echo $home_url; ?>" class="plaid-mobile-menu-logo" id="plaid-mobile-logo" aria-label="<?php echo esc_attr($site_title); ?>">
					<img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_title); ?>" class="plaid-mobile-logo-icon">
				</a>
				<!-- Back button (hidden on root level) -->
				<button type="button" class="plaid-mobile-back-btn" id="plaid-mobile-back-btn" style="display: none;" onclick="plaidNavigateBack(event)" aria-label="<?php esc_attr_e('Go back', 'plaid-nav-child'); ?>">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
						<path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<span><?php esc_html_e('Back', 'plaid-nav-child'); ?></span>
				</button>
			</div>
			<!-- Close button -->
			<button type="button" class="plaid-mobile-close-btn" onclick="plaidCloseMobileMenu(event)" aria-label="<?php esc_attr_e('Close menu', 'plaid-nav-child'); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
					<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>

		<!-- Panel Container for sliding navigation -->
		<div class="plaid-mobile-panels" id="plaid-mobile-panels">
			<?php
			// Render the root menu panel
			render_mobile_menu_panel($mobile_menu->term_id, 0);
			?>
		</div>
	</div>
	<?php
}

/**
 * Render a single mobile menu panel
 * This function recursively renders panels for each menu level
 */
function render_mobile_menu_panel($menu_id, $depth = 0) {
	$menu_items = wp_get_nav_menu_items($menu_id);
	if (!$menu_items) return;

	// Organize items by parent
	$root_items = array();
	$child_items = array();

	foreach ($menu_items as $item) {
		if (empty($item->menu_item_parent)) {
			$root_items[] = $item;
		} else {
			if (!isset($child_items[$item->menu_item_parent])) {
				$child_items[$item->menu_item_parent] = array();
			}
			$child_items[$item->menu_item_parent][] = $item;
		}
	}

	// Panel class based on depth
	$panel_class = $depth === 0 ? 'plaid-mobile-panel plaid-mobile-panel--root' : 'plaid-mobile-panel plaid-mobile-panel--submenu';
	$panel_id = $depth === 0 ? 'plaid-mobile-panel-root' : 'plaid-mobile-panel-' . $menu_id;

	?>
	<div class="<?php echo esc_attr($panel_class); ?>" id="<?php echo esc_attr($panel_id); ?>" data-panel-depth="<?php echo esc_attr($depth); ?>">
		<ul class="plaid-mobile-panel-list">
			<?php foreach ($root_items as $item) :
				$classes = empty($item->classes) ? array() : (array) $item->classes;
				$has_children = isset($child_items[$item->ID]) && !empty($child_items[$item->ID]);
				$is_current = in_array('current-menu-item', $classes) ||
				             in_array('current-menu-parent', $classes) ||
				             in_array('current-menu-ancestor', $classes);

				$item_classes = array('plaid-mobile-panel-item');
				if ($has_children) {
					$item_classes[] = 'has-children';
				}
				if ($is_current) {
					$item_classes[] = 'current';
				}
			?>
				<li class="<?php echo esc_attr(implode(' ', $item_classes)); ?>">
					<?php if ($has_children) : ?>
						<!-- Item with children - clickable arrow to navigate to panel -->
						<div class="plaid-mobile-panel-item-wrapper">
							<span class="plaid-mobile-panel-item-text"><?php echo esc_html($item->title); ?></span>
							<button type="button" class="plaid-mobile-panel-arrow" data-panel-target="<?php echo esc_attr($item->ID); ?>" aria-label="<?php echo esc_attr(sprintf(__('Open %s submenu', 'plaid-nav-child'), $item->title)); ?>">
								<i class="fa-solid fa-arrow-right-long"></i>
							</button>
						</div>
					<?php else : ?>
						<!-- Item without children - direct link -->
						<a href="<?php echo esc_url($item->url); ?>" class="plaid-mobile-panel-item-link">
							<?php echo esc_html($item->title); ?>
						</a>
					<?php endif; ?>

					<?php if ($has_children) : ?>
						<!-- Render submenu panel (hidden by default, slides in) -->
						<div class="plaid-mobile-submenu-panels" data-parent-id="<?php echo esc_attr($item->ID); ?>">
							<?php
							// Create a temporary menu structure for the submenu
							render_submenu_panel($item->ID, $child_items[$item->ID], $item->title, $depth + 1);
							?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>

		<!-- CTA Buttons at bottom of root panel -->
		<?php if ($depth === 0) : ?>
			<div class="plaid-mobile-panel-footer">
				<?php render_navigation_ctas(); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render a submenu panel
 */
function render_submenu_panel($parent_id, $children, $parent_title, $depth) {
	$panel_id = 'plaid-mobile-panel-' . $parent_id;

	?>
	<div class="plaid-mobile-panel plaid-mobile-panel--submenu" id="<?php echo esc_attr($panel_id); ?>" data-panel-depth="<?php echo esc_attr($depth); ?>" data-parent-panel-id="<?php echo $depth === 1 ? 'plaid-mobile-panel-root' : ''; ?>">
		<ul class="plaid-mobile-panel-list">
			<?php foreach ($children as $item) :
				// Get grandchildren for this item
				$grandchildren = get_posts(array(
					'post_type' => 'nav_menu_item',
					'post_status' => 'publish',
					'post_parent' => $item->ID,
					'posts_per_page' => -1,
					'orderby' => 'menu_order',
					'order' => 'ASC',
				));

				$has_children = !empty($grandchildren);
				$classes = empty($item->classes) ? array() : (array) $item->classes;
				$is_current = in_array('current-menu-item', $classes) ||
				             in_array('current-menu-parent', $classes) ||
				             in_array('current-menu-ancestor', $classes);

				$item_classes = array('plaid-mobile-panel-item');
				if ($has_children) {
					$item_classes[] = 'has-children';
				}
				if ($is_current) {
					$item_classes[] = 'current';
				}
			?>
				<li class="<?php echo esc_attr(implode(' ', $item_classes)); ?>">
					<?php if ($has_children) : ?>
						<!-- Item with children - clickable arrow to navigate to panel -->
						<div class="plaid-mobile-panel-item-wrapper">
							<span class="plaid-mobile-panel-item-text"><?php echo esc_html($item->title); ?></span>
							<button type="button" class="plaid-mobile-panel-arrow" data-panel-target="<?php echo esc_attr($item->ID); ?>" aria-label="<?php echo esc_attr(sprintf(__('Open %s submenu', 'plaid-nav-child'), $item->title)); ?>">
								<i class="fa-solid fa-arrow-right-long"></i>
							</button>
						</div>
					<?php else : ?>
						<!-- Item without children - direct link -->
						<a href="<?php echo esc_url($item->url); ?>" class="plaid-mobile-panel-item-link">
							<?php echo esc_html($item->title); ?>
						</a>
					<?php endif; ?>

					<?php if ($has_children) : ?>
						<!-- Recursively render nested submenu -->
						<div class="plaid-mobile-submenu-panels" data-parent-id="<?php echo esc_attr($item->ID); ?>">
							<?php
							render_submenu_panel($item->ID, $grandchildren, $item->title, $depth + 1);
							?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * Include required files
 */
require_once get_stylesheet_directory() . '/inc/class-plaid-nav-walker.php';
require_once get_stylesheet_directory() . '/inc/class-plaid-mobile-walker.php';
require_once get_stylesheet_directory() . '/inc/navigation-helpers.php';

/**
 * Add customizer settings for navigation
 */
function plaid_nav_child_customize_register($wp_customize) {
	// Login URL
	$wp_customize->add_setting('plaid_nav_login_url', array(
		'default' => '#login',
		'sanitize_callback' => 'esc_url_raw',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('plaid_nav_login_url', array(
		'label' => __('Login URL', 'plaid-nav-child'),
		'section' => 'title_tagline',
		'type' => 'url',
		'priority' => 100,
	));

	// Login Text
	$wp_customize->add_setting('plaid_nav_login_text', array(
		'default' => __('Log in', 'plaid-nav-child'),
		'sanitize_callback' => 'sanitize_text_field',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('plaid_nav_login_text', array(
		'label' => __('Login Button Text', 'plaid-nav-child'),
		'section' => 'title_tagline',
		'type' => 'text',
		'priority' => 101,
	));

	// Contact URL
	$wp_customize->add_setting('plaid_nav_contact_url', array(
		'default' => '#contact',
		'sanitize_callback' => 'esc_url_raw',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('plaid_nav_contact_url', array(
		'label' => __('Contact URL', 'plaid-nav-child'),
		'section' => 'title_tagline',
		'type' => 'url',
		'priority' => 102,
	));

	// Contact Text
	$wp_customize->add_setting('plaid_nav_contact_text', array(
		'default' => __('Contact sales', 'plaid-nav-child'),
		'sanitize_callback' => 'sanitize_text_field',
		'transport' => 'refresh',
	));

	$wp_customize->add_control('plaid_nav_contact_text', array(
		'label' => __('Contact Button Text', 'plaid-nav-child'),
		'section' => 'title_tagline',
		'type' => 'text',
		'priority' => 103,
	));
}
add_action('customize_register', 'plaid_nav_child_customize_register');
