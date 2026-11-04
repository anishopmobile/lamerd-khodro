/**
 * Lamerd Khodro Price Engine — admin interactions.
 */
(function ($) {
	'use strict';

	$(function () {
		var $buttons = $('[data-lkpe-sync]');
		if (!$buttons.length) {
			return;
		}

		$buttons.on('click', function () {
			var $button = $(this);
			var $result = $('[data-lkpe-sync-result]');

			if (!window.confirm(lkpeAdmin.strings.confirm)) {
				return;
			}

			$button.prop('disabled', true);
			$buttons.prop('disabled', true);
			$result
				.removeAttr('hidden')
				.removeClass('is-success is-error')
				.addClass('is-loading')
				.text(lkpeAdmin.strings.syncing);

			$.post(lkpeAdmin.ajaxUrl, {
				action: 'lkpe_sync_now',
				nonce: lkpeAdmin.nonce
			})
				.done(function (response) {
					if (response && response.success && response.data) {
						var data = response.data;
						var text = data.message || lkpeAdmin.strings.syncDone;
						$result.removeClass('is-loading').addClass('is-success').text(text);

						if (data.errors && data.errors.length) {
							var list = $('<ul class="lkpe-error-list"></ul>');
							data.errors.forEach(function (error) {
								list.append($('<li></li>').text(error));
							});
							$result.append(list);
						}

						window.setTimeout(function () {
							window.location.reload();
						}, 2500);
					} else {
						var message = (response && response.data && response.data.message) ? response.data.message : lkpeAdmin.strings.syncError;
						$result.removeClass('is-loading').addClass('is-error').text(message);
					}
				})
				.fail(function (xhr) {
					var message = lkpeAdmin.strings.syncError;
					if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
						message = xhr.responseJSON.data.message;
					}
					$result.removeClass('is-loading').addClass('is-error').text(message);
				})
				.always(function () {
					$button.prop('disabled', false);
					$buttons.prop('disabled', false);
				});
		});
	});
})(jQuery);
