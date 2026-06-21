<?php
/**
 * Index Template
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
		<?php if (have_posts()) : ?>
			<div class="post-list">
				<?php while (have_posts()) : the_post(); ?>
					<article <?php post_class(); ?>>
						<h2 class="entry-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<div class="entry-content">
							<?php the_excerpt(); ?>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
		<?php else : ?>
			<p><?php esc_html_e('Sorry, no posts found.', 'plaid-nav-child'); ?></p>
		<?php endif; ?>
	</div>
</main>

<?php get_footer(); ?>
