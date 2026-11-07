<?php
/**
 * Plans section markup (above the sale listings).
 *
 * @package LamerdKhodro\Plans
 *
 * @var array $cards Plan card data.
 */

defined( 'ABSPATH' ) || exit;

$lkp_live = 0;
foreach ( $cards as $lkp_card ) {
	if ( 'live' === $lkp_card['state'] ) {
		$lkp_live++;
	}
}

$lkp_wrap = ! isset( $wrap ) || $wrap;
?>
<section class="lkp-section" id="lkp-plans" aria-labelledby="lkp-plans-title" data-lkp-section>
	<?php if ( $lkp_wrap ) : ?>
	<div class="lk-container">
	<?php endif; ?>
	<div class="lkp-section__head">
		<div>
			<span class="lkp-eyebrow">طرح‌های فروش خودرو</span>
			<h2 class="lkp-section__title" id="lkp-plans-title">طرح‌های ثبت‌نام خودرو</h2>
			<p class="lkp-section__lead">طرح‌های فعال و غیرفعال را ببینید؛ برای طرح‌هایی که ثبت‌نامشان باز است، درخواست را آنلاین و بدون تازه‌سازی صفحه ثبت کنید.</p>
		</div>
		<div class="lkp-section__meta">
			<span class="lkp-count"><?php echo esc_html( lkp_fa( count( $cards ) ) . ' طرح' ); ?></span>
			<span class="lkp-count lkp-count--live"><?php echo esc_html( lkp_fa( $lkp_live ) . ' در حال ثبت‌نام' ); ?></span>
		</div>
	</div>

	<?php if ( empty( $cards ) ) : ?>
		<div class="lkp-empty-state">
			<span aria-hidden="true">⏱</span>
			<h3>در حال حاضر طرحی برای نمایش وجود ندارد</h3>
			<p>طرح‌های جدید پس از انتشار در همین بخش نمایش داده می‌شوند.</p>
		</div>
	<?php else : ?>
		<div class="lkp-grid">
			<?php
			foreach ( $cards as $lkp_card ) :
				$lkp_images = $lkp_card['images'];
				$lkp_primary = $lkp_images ? $lkp_images[0]['url'] : LKP_URL . 'assets/img/no-image.svg';
				$lkp_urls = wp_list_pluck( $lkp_images, 'url' );
				$lkp_state = $lkp_card['state'];
				$lkp_desc = $lkp_card['description'];
				if ( mb_strlen( $lkp_desc ) > 220 ) {
					$lkp_desc = mb_substr( $lkp_desc, 0, 220 ) . '…';
				}
				?>
				<article class="lkp-plan lkp-plan--<?php echo esc_attr( $lkp_state ); ?>" data-lkp-card data-state="<?php echo esc_attr( $lkp_state ); ?>">
					<div class="lkp-plan__media">
						<img src="<?php echo esc_url( $lkp_primary ); ?>" alt="<?php echo esc_attr( $lkp_card['title'] ); ?>" loading="lazy" decoding="async" width="640" height="400">
						<span class="lkp-status lkp-status--<?php echo esc_attr( $lkp_state ); ?>"><?php echo esc_html( $lkp_card['label'] ); ?></span>
						<?php if ( count( $lkp_images ) > 1 ) : ?>
							<button
								type="button"
								class="lkp-gallery-btn"
								data-lkp-gallery
								data-images="<?php echo esc_attr( (string) wp_json_encode( $lkp_urls ) ); ?>"
								aria-label="<?php echo esc_attr( 'مشاهده گالری ' . lkp_fa( count( $lkp_images ) ) . ' تصویر' ); ?>"
							>
								گالری · <?php echo esc_html( lkp_fa( count( $lkp_images ) ) ); ?> تصویر
							</button>
						<?php endif; ?>
					</div>

					<div class="lkp-plan__body">
						<?php if ( $lkp_card['company'] ) : ?>
							<span class="lkp-plan__company"><?php echo esc_html( $lkp_card['company'] ); ?></span>
						<?php endif; ?>
						<h3 class="lkp-plan__title"><?php echo esc_html( $lkp_card['title'] ); ?></h3>
						<p class="lkp-plan__desc"><?php echo esc_html( $lkp_desc ? $lkp_desc : 'شرایط ثبت‌نام در توضیحات طرح اعلام شده است.' ); ?></p>

						<dl class="lkp-plan__meta">
							<div>
								<dt>زمان فعال شدن طرح</dt>
								<dd><?php echo esc_html( LKP_Jalali::format( $lkp_card['dates']['active'] ) ); ?></dd>
							</div>
							<div>
								<dt>مدت ثبت‌نام</dt>
								<dd>
									<?php
									echo esc_html(
										LKP_Jalali::format( $lkp_card['dates']['start'] ) . ' تا ' . LKP_Jalali::format( $lkp_card['dates']['end'] )
									);
									?>
								</dd>
							</div>
						</dl>

						<div class="lkp-plan__timer" data-lkp-timer>
							<span>
								<?php
								if ( 'live' === $lkp_state ) {
									esc_html_e( 'زمان باقی‌ماندهٔ ثبت‌نام', 'lamerd-khodro-plans' );
								} elseif ( 'future' === $lkp_state ) {
									esc_html_e( 'مانده تا شروع ثبت‌نام', 'lamerd-khodro-plans' );
								} else {
									esc_html_e( 'مهلت ثبت‌نام به پایان رسیده', 'lamerd-khodro-plans' );
								}
								?>
							</span>
							<?php if ( $lkp_card['deadline'] > 0 ) : ?>
								<strong data-lkp-countdown data-deadline="<?php echo esc_attr( (string) $lkp_card['deadline'] ); ?>">
									<?php echo esc_html( lkp_countdown_text( $lkp_card['deadline'] ) ); ?>
								</strong>
							<?php else : ?>
								<strong><?php echo esc_html( 'disabled' === $lkp_state ? 'طرح غیرفعال است' : 'پایان‌یافته' ); ?></strong>
							<?php endif; ?>
						</div>

						<?php if ( ! empty( $lkp_card['files'] ) ) : ?>
							<div class="lkp-plan__files">
								<?php foreach ( $lkp_card['files'] as $lkp_file ) : ?>
									<a
										class="lkp-file-link"
										href="<?php echo esc_url( $lkp_file['url'] ); ?>"
										target="_blank"
										rel="noopener"
										download
									>
										<span class="lkp-file-link__icon" aria-hidden="true">⇩</span>
										<span class="lkp-file-link__text">
											<b><?php echo esc_html( $lkp_file['label'] ); ?></b>
											<?php if ( $lkp_file['size'] > 0 ) : ?>
												<small><?php echo esc_html( size_format( $lkp_file['size'] ) ); ?></small>
											<?php endif; ?>
										</span>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<?php if ( $lkp_card['can_request'] ) : ?>
							<button
								type="button"
								class="lkp-btn lkp-btn--primary"
								data-lkp-open
								data-plan-id="<?php echo esc_attr( (string) $lkp_card['id'] ); ?>"
								data-plan-title="<?php echo esc_attr( $lkp_card['title'] ); ?>"
								data-plan-company="<?php echo esc_attr( $lkp_card['company'] ); ?>"
							>
								ثبت درخواست ثبت‌نام
							</button>
						<?php else : ?>
							<button type="button" class="lkp-btn lkp-btn--muted" disabled>
								<?php echo esc_html( 'future' === $lkp_state ? 'ثبت‌نام هنوز شروع نشده' : ( 'disabled' === $lkp_state ? 'طرح غیرفعال است' : 'ثبت‌نام پایان یافته' ) ); ?>
							</button>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<noscript>
		<p class="lkp-note">برای ثبت درخواست، لطفاً جاوااسکریپت مرورگر را فعال کنید یا با شمارهٔ دفتر تماس بگیرید.</p>
	</noscript>

	<div class="lkp-overlay" data-lkp-modal hidden>
		<div class="lkp-dialog" role="dialog" aria-modal="true" aria-labelledby="lkp-modal-title" tabindex="-1">
			<button type="button" class="lkp-dialog__close" data-lkp-close aria-label="بستن">&times;</button>

			<div class="lkp-dialog__head">
				<span class="lkp-eyebrow">ثبت درخواست</span>
				<h2 id="lkp-modal-title">درخواست ثبت‌نام خودرو</h2>
				<p data-lkp-plan-label></p>
			</div>

			<form class="lkp-form" data-lkp-form enctype="multipart/form-data" novalidate>
				<input type="hidden" name="plan_id" value="">
				<input type="hidden" name="opened_at" value="0" data-lkp-opened>

				<div class="lkp-form__row lkp-form__row--two">
					<label class="lkp-field">
						<span>نام</span>
						<input type="text" name="name" required maxlength="120" autocomplete="given-name">
					</label>
					<label class="lkp-field">
						<span>نام خانوادگی</span>
						<input type="text" name="lastname" required maxlength="120" autocomplete="family-name">
					</label>
				</div>

				<div class="lkp-form__row lkp-form__row--two">
					<label class="lkp-field">
						<span>شماره تماس</span>
						<input type="tel" name="phone" required inputmode="tel" dir="ltr" placeholder="0912…" maxlength="15" autocomplete="tel">
					</label>
					<label class="lkp-field">
						<span>موبایل به نام متقاضی</span>
						<input type="tel" name="mobile_owner" required inputmode="tel" dir="ltr" placeholder="0912…" maxlength="15">
					</label>
				</div>

				<label class="lkp-field">
					<span>آدرس کامل</span>
					<textarea name="address" required maxlength="2000" rows="3" placeholder="استان، شهر، خیابان، پلاک…"></textarea>
				</label>

				<div class="lkp-form__row lkp-form__row--two">
					<label class="lkp-field">
						<span>کد پستی</span>
						<input type="text" name="postal" required inputmode="numeric" dir="ltr" maxlength="10" placeholder="۱۰ رقم">
					</label>
					<label class="lkp-field">
						<span>شماره شبای بانکی</span>
						<input type="text" name="shaba" required dir="ltr" maxlength="26" placeholder="IR…">
					</label>
				</div>

				<div class="lkp-panel">
					<div class="lkp-panel__head">
						<div>
							<h3>بارگذاری مدارک</h3>
							<p>شناسنامه صفحهٔ اول و دوم و کارت ملی پشت و رو. حداکثر ۸ تصویر، هر کدام تا ۵ مگابایت (JPG یا PNG).</p>
						</div>
						<label class="lkp-btn lkp-btn--ghost">
							انتخاب تصاویر
							<input type="file" name="documents[]" accept="image/jpeg,image/png" multiple hidden data-lkp-files>
						</label>
					</div>
					<div class="lkp-docs-grid" data-lkp-preview>
						<p class="lkp-docs-empty">تصاویر مدارک را انتخاب کنید</p>
					</div>
				</div>

				<label class="lkp-honeypot" aria-hidden="true">
					<span>وب‌سایت</span>
					<input type="text" name="website" tabindex="-1" autocomplete="off">
				</label>

				<p class="lkp-form__error" data-lkp-error role="alert" hidden></p>

				<div class="lkp-form__actions">
					<button type="submit" class="lkp-btn lkp-btn--primary" data-lkp-submit>تأیید و ارسال اطلاعات</button>
					<button type="button" class="lkp-btn lkp-btn--ghost" data-lkp-close>انصراف</button>
				</div>
				<p class="lkp-form__privacy">با ارسال اطلاعات، درخواست شما برای بررسی کارشناسان ثبت می‌شود و مدارک فقط در اختیار افراد مجاز قرار می‌گیرد.</p>
			</form>
		</div>
	</div>

	<div class="lkp-overlay" data-lkp-success hidden>
		<div class="lkp-dialog lkp-dialog--success" role="dialog" aria-modal="true" aria-labelledby="lkp-success-title" tabindex="-1">
			<div class="lkp-dialog__mark" aria-hidden="true">✓</div>
			<h2 id="lkp-success-title">درخواست شما با موفقیت ثبت شد</h2>
			<p>اطلاعات و مدارک شما دریافت شد. کارشناسان ما در اولین فرصت برای ادامهٔ مراحل با شما تماس خواهند گرفت.</p>
			<p class="lkp-tracking">کد پیگیری: <strong data-lkp-tracking>—</strong></p>
			<div class="lkp-follow">
				<b>برای اطلاع از طرح‌های جدید، ما را دنبال کنید</b>
				<div class="lkp-follow__links">
					<a href="https://ble.ir/lamerd_khodro" target="_blank" rel="noopener">کانال بله</a>
					<a href="https://t.me/lamerd_khodro" target="_blank" rel="noopener">تلگرام</a>
					<a href="https://B2n.ir/Lamerd_khodro" target="_blank" rel="noopener">واتس‌اپ</a>
				</div>
			</div>
			<button type="button" class="lkp-btn lkp-btn--ghost" data-lkp-close>بازگشت به طرح‌ها</button>
		</div>
	</div>

	<div class="lkp-overlay" data-lkp-gallery-modal hidden>
		<div class="lkp-dialog lkp-dialog--gallery" role="dialog" aria-modal="true" aria-label="گالری تصاویر طرح" tabindex="-1">
			<button type="button" class="lkp-dialog__close" data-lkp-close aria-label="بستن">&times;</button>
			<div class="lkp-gallery">
				<button type="button" class="lkp-gallery__nav" data-lkp-gallery-prev aria-label="تصویر قبلی">‹</button>
				<img src="" alt="" data-lkp-gallery-image>
				<button type="button" class="lkp-gallery__nav" data-lkp-gallery-next aria-label="تصویر بعدی">›</button>
			</div>
			<p class="lkp-gallery__index" data-lkp-gallery-index></p>
		</div>
	</div>
	<?php if ( $lkp_wrap ) : ?>
	</div>
	<?php endif; ?>
</section>
