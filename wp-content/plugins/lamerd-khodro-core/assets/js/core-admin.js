/**
 * Lamerd Khodro Core — admin interactions.
 * Tabs, gallery picker (vehicle meta box), and single image picker (brand terms).
 */
(function ($) {
	'use strict';

	$(function () {
		// Meta box tabs.
		$('.lk-metabox').each(function () {
			var $box = $(this);
			$box.find('.lk-metabox__tab').on('click', function () {
				var key = $(this).data('lk-tab');
				$box.find('.lk-metabox__tab').removeClass('is-active').attr('aria-selected', 'false');
				$(this).addClass('is-active').attr('aria-selected', 'true');
				$box.find('.lk-metabox__panel').removeClass('is-active');
				$box.find('[data-lk-panel="' + key + '"]').addClass('is-active');
			});
		});

		// Vehicle gallery.
		$('[data-lk-gallery]').each(function () {
			var $wrap = $(this);
			var $input = $wrap.find('input[type="hidden"]');
			var $preview = $wrap.find('[data-lk-gallery-preview]');
			var frame;

			$wrap.find('[data-lk-gallery-add]').on('click', function (event) {
				event.preventDefault();

				if (!frame) {
					frame = wp.media({
						title: 'انتخاب تصاویر گالری',
						button: { text: 'افزودن به گالری' },
						library: { type: 'image' },
						multiple: true
					});

					frame.on('select', function () {
						var ids = ($input.val() || '').split(',').filter(Boolean);
						var html = '';
						frame.state().get('selection').each(function (attachment) {
							var data = attachment.toJSON();
							if (ids.indexOf(String(data.id)) === -1) {
								ids.push(String(data.id));
							}
							var thumb = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
							html += '<span class="lk-media__item" data-id="' + data.id + '"><img src="' + thumb + '" alt="" /></span>';
						});
						$input.val(ids.join(','));
						$preview.append(html);
					});
				}

				frame.open();
			});

			$wrap.find('[data-lk-gallery-clear]').on('click', function (event) {
				event.preventDefault();
				$input.val('');
				$preview.empty();
			});

			$preview.on('click', '.lk-media__item', function () {
				var id = String($(this).data('id'));
				var ids = ($input.val() || '').split(',').filter(function (value) {
					return value && value !== id;
				});
				$input.val(ids.join(','));
				$(this).remove();
			});
		});

		// Brand logo picker.
		var brandFrame;

		$(document).on('click', '[data-lk-media]', function (event) {
			event.preventDefault();
			var target = $(this).data('lk-media');
			var $input = $('#' + target);
			var $preview = $('[data-preview="' + target + '"]');

			if (!brandFrame) {
				brandFrame = wp.media({
					title: 'انتخاب لوگوی برند',
					button: { text: 'استفاده از تصویر' },
					library: { type: 'image' },
					multiple: false
				});

				brandFrame.on('select', function () {
					var attachment = brandFrame.state().get('selection').first().toJSON();
					$('#' + brandFrame.lkTarget).val(attachment.id);
					$('[data-preview="' + brandFrame.lkTarget + '"]').html('<img src="' + attachment.url + '" alt="" />');
				});
			}

			brandFrame.lkTarget = target;
			$input.val($input.val());
			brandFrame.open();
		});

		$(document).on('click', '[data-lk-media-clear]', function (event) {
			event.preventDefault();
			var target = $(this).data('lk-media-clear');
			$('#' + target).val('');
			$('[data-preview="' + target + '"]').empty();
		});
	});
})(jQuery);
