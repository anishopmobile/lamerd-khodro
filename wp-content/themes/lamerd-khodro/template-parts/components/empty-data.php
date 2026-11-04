<?php
/**
 * Empty state for missing data (no fabricated content).
 *
 * @param array $args { @type string $title, $text, $link_label, $link_url }
 * @package LamerdKhodro\Theme
 */

defined( 'ABSPATH' ) || exit;

$lk_title = $args['title'] ?? 'اطلاعات در دسترس نیست';
$lk_text  = $args['text'] ?? 'داده‌ای برای نمایش وجود ندارد. پس از ثبت اطلاعات، این بخش به‌صورت خودکار تکمیل می‌شود.';
?>
<div class="lk-empty">
	<span class="lk-empty__icon" aria-hidden="true"><?php echo lk_icon( 'car', 'lk-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
	<h3 class="lk-empty__title"><?php echo esc_html( $lk_title ); ?></h3>
	<p class="lk-empty__text"><?php echo esc_html( $lk_text ); ?></p>
	<?php if ( ! empty( $args['link_url'] ) ) : ?>
		<a class="lk-btn lk-btn--ghost" href="<?php echo esc_url( $args['link_url'] ); ?>"><?php echo esc_html( $args['link_label'] ?? 'مشاهده همه' ); ?></a>
	<?php endif; ?>
</div>
