/**
 * AJAX price table: filters, sorting and pagination without reloads.
 * Falls back to a normal GET request when JavaScript is unavailable.
 */
(function () {
	'use strict';

	var form = document.querySelector('[data-lk-price-filters]');
	var root = document.querySelector('[data-lk-price-results]');
	var pagination = document.querySelector('[data-lk-price-pagination]');

	if (!form || !root || !window.LK || !window.fetch) {
		return;
	}

	var rows = document.querySelector('[data-lk-price-rows]');
	var cards = document.querySelector('[data-lk-price-cards]');
	var count = document.querySelector('[data-lk-price-count]');
	var strings = window.LK.strings || {};
	var controller = null;
	var debounceTimer = null;
	var currentPage = 1;

	function syncUrl() {
		try {
			var params = new URLSearchParams();
			new FormData(form).forEach(function (value, key) {
				if (String(value).trim() !== '') {
					params.set(key, value);
				}
			});
			if (currentPage > 1) {
				params.set('pg', currentPage);
			}
			var url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
			window.history.replaceState({}, '', url);
		} catch (error) {
			/* URL sync is cosmetic */
		}
	}

	function setLoading(isLoading) {
		root.classList.toggle('is-loading', isLoading);
		root.setAttribute('aria-busy', isLoading ? 'true' : 'false');
	}

	function renderEmpty() {
		if (rows) {
			rows.innerHTML = '<tr><td colspan="7" class="lk-table__empty">خودرویی با این فیلترها پیدا نشد.</td></tr>';
		}
		if (cards) {
			cards.innerHTML = '<p class="lk-price-cards__empty">خودرویی با این فیلترها پیدا نشد.</p>';
		}
	}

	function load(page) {
		if (controller) {
			controller.abort();
		}
		controller = new AbortController();

		var data = new FormData(form);
		data.set('action', 'lk_price_results');
		data.set('nonce', window.LK.nonce);
		data.set('page', page);

		setLoading(true);

		fetch(window.LK.ajaxUrl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin',
			signal: controller.signal
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success || !payload.data) {
					throw new Error('bad payload');
				}

				var result = payload.data;
				currentPage = Number(result.page) || 1;

				if (rows) {
					rows.innerHTML = result.rows || '';
				}
				if (cards) {
					cards.innerHTML = result.cards || '';
				}
				if (!result.total) {
					renderEmpty();
				}
				if (pagination) {
					pagination.innerHTML = result.pagination || '';
				}
				if (count) {
					count.textContent = result.countLabel || '';
				}

				syncUrl();
				window.LKUI.toast(result.total ? result.countLabel : 'نتیجه‌ای یافت نشد.');
			})
			.catch(function (error) {
				if (error.name === 'AbortError') {
					return;
				}
				window.LKUI.toast(strings.error || 'خطا در دریافت اطلاعات. دوباره تلاش کنید.');
			})
			.finally(function () {
				setLoading(false);
			});
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		load(1);
	});

	Array.prototype.forEach.call(form.querySelectorAll('select'), function (select) {
		select.addEventListener('change', function () {
			load(1);
		});
	});

	var searchInput = form.querySelector('input[type="search"]');
	if (searchInput) {
		searchInput.addEventListener('input', function () {
			window.clearTimeout(debounceTimer);
			debounceTimer = window.setTimeout(function () {
				load(1);
			}, 420);
		});
	}

	Array.prototype.forEach.call(form.querySelectorAll('input[type="number"]'), function (input) {
		input.addEventListener('input', function () {
			window.clearTimeout(debounceTimer);
			debounceTimer = window.setTimeout(function () {
				load(1);
			}, 600);
		});
	});

	document.addEventListener('click', function (event) {
		var link = event.target.closest('[data-lk-price-pagination] a[data-page]');
		if (!link) {
			return;
		}
		event.preventDefault();
		var page = Number(link.getAttribute('data-page')) || 1;
		load(page);

		if (root && root.getBoundingClientRect().top < 0) {
			root.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}
	});
})();
