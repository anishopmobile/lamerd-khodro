<?php
/**
 * Inner page hero.
 *
 * @param array $args { @type string $title, $text, $meta }
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_title = $args['title'] ?? get_the_title();
$lk_text  = $args['text'] ?? '';
$lk_meta  = $args['meta'] ?? '';
?>
<header class="lk-pagehero">
	<div class="lk-pagehero__grid" aria-hidden="true"></div>
	<div class="lk-container">
		<?php lk_breadcrumb(); ?>
		<div class="lk-pagehero__row">
			<div class="lk-pagehero__main">
				<span class="lk-pagehero__rule" aria-hidden="true"></span>
				<h1 class="lk-pagehero__title"><?php echo esc_html( $lk_title ); ?></h1>
				<?php if ( $lk_text ) : ?>
					<p class="lk-pagehero__text"><?php echo esc_html( $lk_text ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $lk_meta ) : ?>
				<div class="lk-pagehero__meta"><?php echo wp_kses_post( $lk_meta ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</header>
