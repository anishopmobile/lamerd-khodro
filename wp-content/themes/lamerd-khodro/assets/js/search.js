/**
 * Instant vehicle search in the header panel.
 */
(function () {
	'use strict';

	var input = document.querySelector('[data-lk-search-input]');
	var results = document.querySelector('[data-lk-search-results]');

	if (!input || !results || !window.LK) {
		return;
	}

	var strings = window.LK.strings || {};
	var controller = null;
	var timer = null;
	var lastTerm = '';

	function clear() {
		results.innerHTML = '';
		results.classList.remove('is-loading');
	}

	function render(items, term) {
		if (!items.length) {
			results.innerHTML = '<p class="lk-search-results__empty">' + (strings.noResults || 'نتیجه‌ای یافت نشد.') + '</p>';
			return;
		}

		var html = '<ul class="lk-search-results__list">';
		items.forEach(function (item) {
			var price = item.market_price
				? '<span class="lk-search-results__price">' + window.LKUI.toFa(item.market_price) + ' تومان</span>'
				: '<span class="lk-search-results__price is-empty">بدون قیمت</span>';

			html += '<li><a href="' + item.url + '">' +
				'<span class="lk-search-results__main">' +
				'<span class="lk-search-results__title">' + escapeHtml(item.title) + '</span>' +
				'<span class="lk-search-results__brand">' + escapeHtml(item.brand || '') + '</span>' +
				'</span>' + price + '</a></li>';
		});
		html += '</ul>';
		html += '<a class="lk-search-results__all" href="' + window.LK.homeUrl + '?s=' + encodeURIComponent(term) + '">همه نتایج برای «' + escapeHtml(term) + '»</a>';

		results.innerHTML = html;
	}

	function escapeHtml(text) {
		return String(text).replace(/[&<>"']/g, function (char) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
		});
	}

	function search(term) {
		if (term === lastTerm) {
			return;
		}
		lastTerm = term;

		if (term.length < 2) {
			clear();
			return;
		}

		if (controller) {
			controller.abort();
		}
		controller = new AbortController();

		results.classList.add('is-loading');
		results.innerHTML = '<p class="lk-search-results__empty">' + (strings.searching || 'در حال جستجو…') + '</p>';

		fetch(window.LK.restUrl + 'vehicles?search=' + encodeURIComponent(term) + '&per_page=6', {
			headers: { Accept: 'application/json' },
			signal: controller.signal
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('bad response');
				}
				return response.json();
			})
			.then(function (payload) {
				var items = (payload && payload.items) || [];
				render(items, term);
			})
			.catch(function (error) {
				if (error.name === 'AbortError') {
					return;
				}
				results.innerHTML = '<p class="lk-search-results__empty">' + (strings.error || 'خطا در دریافت اطلاعات.') + '</p>';
			})
			.finally(function () {
				results.classList.remove('is-loading');
			});
	}

	input.addEventListener('input', function () {
		var term = input.value.trim();
		window.clearTimeout(timer);
		timer = window.setTimeout(function () {
			search(term);
		}, 280);
	});

	input.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			clear();
		}
	});
})();
