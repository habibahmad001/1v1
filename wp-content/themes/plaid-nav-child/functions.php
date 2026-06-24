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
		$theme_version
	);

	// Navigation stylesheet - load AFTER child theme
	wp_enqueue_style(
		'plaid-navigation-css',
		get_stylesheet_directory_uri() . '/assets/css/navigation.css',
		array('plaid-nav-child-style'),
		$theme_version
	);

	// Navigation JavaScript
	wp_enqueue_script(
		'plaid-navigation-js',
		get_stylesheet_directory_uri() . '/assets/js/navigation.js',
		array(),
		$theme_version,
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
	<script>
	// Simple mobile menu toggle
	(function() {
		const mobileToggle = document.querySelector('[data-plaid-mobile-toggle]');
		const mobileMenu = document.getElementById('plaid-mobile-menu');

		if (mobileToggle && mobileMenu) {
			mobileToggle.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				const isExpanded = this.getAttribute('aria-expanded') === 'true';

				if (isExpanded) {
					// Close menu
					this.setAttribute('aria-expanded', 'false');
					mobileMenu.style.display = 'none';
					document.body.style.overflow = '';
				} else {
					// Open menu
					this.setAttribute('aria-expanded', 'true');
					mobileMenu.style.display = 'block';
					document.body.style.overflow = 'hidden';
				}
			});

			// Close menu when clicking outside
			document.addEventListener('click', function(e) {
				if (!mobileToggle.contains(e.target) && !mobileMenu.contains(e.target)) {
					if (mobileToggle.getAttribute('aria-expanded') === 'true') {
						mobileToggle.setAttribute('aria-expanded', 'false');
						mobileMenu.style.display = 'none';
						document.body.style.overflow = '';
					}
				}
			});

			// Close menu on Escape key
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && mobileToggle.getAttribute('aria-expanded') === 'true') {
					mobileToggle.setAttribute('aria-expanded', 'false');
					mobileMenu.style.display = 'none';
					document.body.style.overflow = '';
					mobileToggle.focus();
				}
			});
				// Mobile submenu toggles
			const initMobileSubmenus = function() {
				const parentItems = mobileMenu.querySelectorAll('.plaid-nav-item.has-children');

				parentItems.forEach(function(parentItem) {
					const link = parentItem.querySelector('.plaid-nav-link');
					const dropdown = parentItem.querySelector('.plaid-dropdown');

					if (link && dropdown) {
						link.addEventListener('click', function(e) {
							e.preventDefault();
							e.stopPropagation();

							const isExpanded = this.getAttribute('aria-expanded') === 'true';

							if (isExpanded) {
								this.setAttribute('aria-expanded', 'false');
								dropdown.style.display = 'none';
							} else {
								this.setAttribute('aria-expanded', 'true');
								dropdown.style.display = 'block';
							}
						});
					}
				});
			};

			initMobileSubmenus();
		}
	})();
	</script>
	<?php
}

/**
 * Render Logo
 */
function render_plaid_logo() {
	// Use the uploaded logo
	$logo_url = 'http://localhost/1v1/wp-content/uploads/2026/06/heydearwomen-logo-1080-x-1350-px.png';
	$site_title = get_bloginfo('name');
	$home_url = esc_url(home_url('/'));

	?>
	<a href="<?php echo $home_url; ?>" class="plaid-logo" aria-label="<?php echo esc_attr($site_title); ?>">
		<img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($site_title); ?>" class="plaid-logo-icon">
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
		data-plaid-mobile-toggle>
		<span class="plaid-mobile-toggle-bar"></span>
		<span class="plaid-mobile-toggle-bar"></span>
		<span class="plaid-mobile-toggle-bar"></span>
	</button>
	<?php
}

/**
 * Render Mobile Navigation
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

	?>
	<div class="plaid-mobile-menu" id="plaid-mobile-menu" style="display: none;">
		<div class="plaid-mobile-menu-inner">
			<?php
			wp_nav_menu(array(
				'menu' => $mobile_menu->term_id,
				'container' => '',
				'menu_class' => 'plaid-mobile-menu-list',
				'fallback_cb' => false,
				'walker' => new Plaid_Nav_Walker(),
				'items_wrap' => '<ul id="plaid-mobile-nav" class="%1$s">%3$s</ul>',
				'echo' => true,
			));
			?>
		</div>
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
