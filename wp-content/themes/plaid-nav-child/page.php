<?php
/**
 * Page Template
 *
 * @package PlaidNavChild
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

get_header();
?>

<main class="site-main" role="main">
	<div class="container">
		<?php while (have_posts()) : the_post(); ?>
			<article <?php post_class(); ?>>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</main>

<?php get_footer(); ?>
