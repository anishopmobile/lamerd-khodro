<?php
/**
 * Single article.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lk_categories = get_the_category();
	$lk_category   = $lk_categories ? $lk_categories[0] : null;
	?>

	<article class="lk-article">
		<header class="lk-article__head">
			<div class="lk-article__pattern" aria-hidden="true"></div>
			<div class="lk-container lk-article__headinner">
				<?php lk_breadcrumb(); ?>

				<?php if ( $lk_category ) : ?>
					<a class="lk-article__cat" href="<?php echo esc_url( get_category_link( $lk_category ) ); ?>"><?php echo esc_html( $lk_category->name ); ?></a>
				<?php endif; ?>

				<h1 class="lk-article__title"><?php the_title(); ?></h1>

				<div class="lk-article__meta">
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( lk_jalali( get_post_timestamp(), 'long' ) ); ?></time>
					<span class="lk-acard__dot" aria-hidden="true"></span>
					<span><?php echo esc_html( lk_num( max( 1, (int) ceil( mb_strlen( wp_strip_all_tags( get_the_content() ), 'UTF-8' ) / 1200 ) ) ) ); ?> دقیقه مطالعه</span>
					<?php if ( get_the_modified_date() !== get_the_date() ) : ?>
						<span class="lk-acard__dot" aria-hidden="true"></span>
						<span>به‌روزرسانی: <?php echo esc_html( lk_jalali( get_post_modified_time(), 'long' ) ); ?></span>
					<?php endif; ?>
				</div>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="lk-article__cover">
						<?php the_post_thumbnail( 'lk-wide', array( 'loading' => 'eager' ) ); ?>
					</figure>
				<?php endif; ?>
			</div>
		</header>

		<div class="lk-container lk-article__body">
			<div class="lk-article__content lk-prose">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
			</div>

			<?php if ( has_tag() ) : ?>
				<div class="lk-article__tags">
					<?php the_tags( '<span class="lk-article__tags-label">برچسب‌ها:</span> ', ' ' ); ?>
				</div>
			<?php endif; ?>

			<?php
			$lk_related = new WP_Query(
				array(
					'post_type'           => 'post',
					'posts_per_page'      => 3,
					'post__not_in'        => array( get_the_ID() ),
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
					'category__in'        => $lk_category ? array( $lk_category->term_id ) : array(),
				)
			);

			if ( $lk_related->have_posts() ) :
				?>
				<section class="lk-related-articles">
					<h2 class="lk-panel__title">مطالب مرتبط</h2>
					<div class="lk-news-grid lk-news-grid--pair">
						<?php
						while ( $lk_related->have_posts() ) :
							$lk_related->the_post();
							get_template_part( 'template-parts/news/card', null, array( 'post' => get_post() ) );
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</article>

	<?php
endwhile;

get_footer();
