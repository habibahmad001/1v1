<?php
/**
 * Template Canvas Override
 *
 * This template overrides the block theme's canvas to include custom navigation
 *
 * @package PlaidNavChild
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="plaid-skip-link" href="#primary-content"><?php esc_html_e('Skip to main content', 'plaid-nav-child'); ?></a>

<?php render_custom_navigation(); ?>

<div id="primary-content">
	<?php
	// Load block template content
	global $wp_query;
	if (have_posts()) {
		while (have_posts()) {
			the_post();
			the_content();
		}
	} else {
		echo '<div class="container"><p>' . esc_html__('Sorry, no content found.', 'plaid-nav-child') . '</p></div>';
	}
	?>
</div>

<?php wp_footer(); ?>

</body>
</html>
