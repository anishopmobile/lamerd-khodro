/**
 * Featured-listing countdown timers.
 *
 * Elements carry data-until as a unix timestamp in seconds, so the browser
 * clock alone decides when the featured window ends; the server value is the
 * single source of truth.
 */
(function () {
	'use strict';

	var nodes = Array.prototype.slice.call(document.querySelectorAll('[data-lk-countdown]'));
	if (!nodes.length) {
		return;
	}

	var fa = new Intl.NumberFormat('fa-IR', { minimumIntegerDigits: 2, useGrouping: false });
	var faPlain = new Intl.NumberFormat('fa-IR');

	function pad(value) {
		return fa.format(value);
	}

	function expire(el) {
		el.classList.add('is-expired');
		el.hidden = true;

		var card = el.closest('.lk-lcard');
		if (card) {
			card.classList.remove('is-featured');
			var ribbon = card.querySelector('.lk-lcard__ribbon');
			if (ribbon) {
				ribbon.hidden = true;
			}
			return;
		}

		var showcase = el.closest('.lk-hsale__lead');
		if (showcase) {
			showcase.classList.remove('is-featured');
			var tag = showcase.querySelector('.lk-hsale__ribbon');
			if (tag) {
				tag.hidden = true;
			}
			return;
		}

		var meta = el.parentElement;
		if (meta) {
			var badge = meta.querySelector('.lk-badge-featured');
			if (badge) {
				badge.hidden = true;
			}
		}
	}

	function tick() {
		var now = Math.floor(Date.now() / 1000);

		nodes.forEach(function (el) {
			var until = parseInt(el.getAttribute('data-until'), 10) || 0;
			var left = until - now;

			if (left <= 0) {
				expire(el);
				return;
			}

			var days = Math.floor(left / 86400);
			var hours = Math.floor((left % 86400) / 3600);
			var minutes = Math.floor((left % 3600) / 60);
			var seconds = left % 60;
			var text = (days > 0 ? faPlain.format(days) + ' روز و ' : '') + pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);

			var target = el.querySelector('[data-lk-countdown-text]');
			if (target) {
				target.textContent = text;
			}
		});
	}

	tick();
	window.setInterval(tick, 1000);
})();
