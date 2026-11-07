<?php
/**
 * News sidebar: AJAX search, most read, latest, tags.
 *
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_popular     = lk_popular_posts( 5 );
$lk_latest      = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 5,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
$lk_news_tags   = get_tags(
	array(
		'orderby'    => 'count',
		'order'      => 'DESC',
		'number'     => 14,
		'hide_empty' => true,
	)
);
$lk_news_tags   = is_wp_error( $lk_news_tags ) ? array() : $lk_news_tags;
$lk_search_term = get_search_query();

/* Fall back to the categories the editors already use while no tag exists. */
$lk_news_topics = array();
if ( ! $lk_news_tags ) {
	$lk_news_topics = get_categories(
		array(
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => 14,
			'hide_empty' => true,
		)
	);
	$lk_news_topics = is_wp_error( $lk_news_topics ) ? array() : $lk_news_topics;
}
?>

<div class="lk-aside-card lk-aside-card--search">
	<h2 class="lk-aside-card__title">جستجو در اخبار و مقالات</h2>
	<form class="lk-newssearch" role="search" method="get" action="<?php echo esc_url( lk_blog_url() ); ?>" data-lk-news-search>
		<label class="screen-reader-text" for="lk-newssearch-input">جستجو در اخبار و مقالات</label>
		<span class="lk-newssearch__icon" aria-hidden="true"><?php echo lk_icon( 'search', 'lk-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<input
			class="lk-newssearch__input"
			id="lk-newssearch-input"
			type="search"
			name="s"
			value="<?php echo esc_attr( $lk_search_term ); ?>"
			placeholder="عنوان یا موضوع مقاله…"
			autocomplete="off"
			data-lk-news-input
		/>
		<button class="lk-newssearch__submit" type="submit">جستجو</button>
		<span class="lk-newssearch__spinner" aria-hidden="true" hidden></span>
	</form>
	<p class="lk-newssearch__hint">نتیجه به‌صورت زنده در فهرست مطالب نمایش داده می‌شود.</p>
</div>

<?php if ( $lk_popular ) : ?>
	<div class="lk-aside-card">
		<h2 class="lk-aside-card__title">محبوب‌ترین اخبار</h2>
		<ol class="lk-aside-list lk-aside-list--ranked">
			<?php foreach ( $lk_popular as $lk_index => $lk_post ) : ?>
				<?php
				$lk_views = lk_news_views( $lk_post->ID );
				$lk_thumb = get_the_post_thumbnail_url( $lk_post, 'thumbnail' );
				?>
				<li class="lk-aside-list__item">
					<a class="lk-aside-list__link" href="<?php echo esc_url( get_permalink( $lk_post ) ); ?>">
						<span class="lk-aside-list__rank" aria-hidden="true"><?php echo esc_html( lk_num( $lk_index + 1 ) ); ?></span>
						<span class="lk-aside-list__thumb">
							<?php if ( $lk_thumb ) : ?>
								<img src="<?php echo esc_url( $lk_thumb ); ?>" alt="" loading="lazy" />
							<?php else : ?>
								<span class="lk-aside-list__mono"><?php echo esc_html( lk_monogram( $lk_post->post_title ) ); ?></span>
							<?php endif; ?>
						</span>
						<span class="lk-aside-list__text">
							<span class="lk-aside-list__title"><?php echo esc_html( get_the_title( $lk_post ) ); ?></span>
							<span class="lk-aside-list__meta">
								<?php
								echo esc_html(
									$lk_views > 0
										? sprintf( '%s بازدید', lk_num( $lk_views ) )
										: lk_jalali( get_post_timestamp( $lk_post ), 'date' )
								);
								?>
							</span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
<?php endif; ?>

<?php if ( $lk_latest->have_posts() ) : ?>
	<div class="lk-aside-card">
		<h2 class="lk-aside-card__title">آخرین اخبار</h2>
		<ul class="lk-aside-list">
			<?php
			while ( $lk_latest->have_posts() ) :
				$lk_latest->the_post();
				$lk_thumb = get_the_post_thumbnail_url( get_post(), 'thumbnail' );
				?>
				<li class="lk-aside-list__item">
					<a class="lk-aside-list__link" href="<?php the_permalink(); ?>">
						<span class="lk-aside-list__thumb">
							<?php if ( $lk_thumb ) : ?>
								<img src="<?php echo esc_url( $lk_thumb ); ?>" alt="" loading="lazy" />
							<?php else : ?>
								<span class="lk-aside-list__mono"><?php echo esc_html( lk_monogram( get_the_title() ) ); ?></span>
							<?php endif; ?>
						</span>
						<span class="lk-aside-list__text">
							<span class="lk-aside-list__title"><?php the_title(); ?></span>
							<span class="lk-aside-list__meta"><?php echo esc_html( lk_jalali( get_post_timestamp(), 'long' ) ); ?></span>
						</span>
					</a>
				</li>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</ul>
	</div>
<?php endif; ?>

<?php if ( $lk_news_tags || $lk_news_topics ) : ?>
	<div class="lk-aside-card">
		<h2 class="lk-aside-card__title"><?php echo $lk_news_tags ? 'برچسب‌ها' : 'موضوع‌ها'; ?></h2>
		<ul class="lk-tagcloud">
			<?php foreach ( $lk_news_tags as $lk_tag ) : ?>
				<li>
					<a class="lk-tagcloud__item" href="<?php echo esc_url( get_tag_link( $lk_tag ) ); ?>">
						<?php echo esc_html( $lk_tag->name ); ?>
						<span class="lk-tagcloud__count"><?php echo esc_html( lk_num( (int) $lk_tag->count ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
			<?php foreach ( $lk_news_topics as $lk_topic ) : ?>
				<li>
					<a class="lk-tagcloud__item" href="<?php echo esc_url( get_category_link( $lk_topic ) ); ?>">
						<?php echo esc_html( $lk_topic->name ); ?>
						<span class="lk-tagcloud__count"><?php echo esc_html( lk_num( (int) $lk_topic->count ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
