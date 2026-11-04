/**
 * Comparison builder.
 */
(function () {
	'use strict';

	var builder = document.querySelector('[data-lk-compare-builder]');
	if (!builder || !window.LKCompare || !window.LK) {
		return;
	}

	var slots = builder.querySelector('[data-lk-compare-slots]');
	var searchInput = builder.querySelector('[data-lk-compare-search]');
	var suggestBox = builder.querySelector('[data-lk-compare-suggest]');
	var spinner = builder.querySelector('[data-lk-compare-spinner]');
	var results = document.querySelector('[data-lk-compare-results]');
	var strings = window.LK.strings || {};
	var titles = {};
	var ids = window.LKCompare.get();
	var timer = null;
	var controller = null;
	var compareTimer = null;

	// Adopt ids from a shared URL when storage is empty.
	if (!ids.length) {
		try {
			var params = new URLSearchParams(window.location.search);
			var raw = params.get('ids');
			if (raw) {
				ids = raw.split(',').map(Number).filter(Boolean).slice(0, window.LKCompare.max);
				window.LKCompare.clear();
				ids.forEach(function (id) {
					window.LKCompare.add(id, '');
				});
				ids = window.LKCompare.get();
			}
		} catch (error) {
			/* ignore malformed URLs */
		}
	}

	// Seed titles from server-rendered slots (shared ?ids= links).
	Array.prototype.forEach.call(document.querySelectorAll('[data-lk-compare-slots] .lk-cmp-slot[data-id]'), function (slot) {
		var id = Number(slot.getAttribute('data-id'));
		var title = slot.getAttribute('data-title');
		if (id && title) {
			titles[id] = title;
		}
	});

	function currentIds() {
		return window.LKCompare.get();
	}

	function esc(text) {
		return String(text == null ? '' : text).replace(/[&<>"']/g, function (char) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
		});
	}

	function renderSlots() {
		var list = currentIds();
		titles = titles || {};

		if (!list.length) {
			slots.innerHTML = '<span class="lk-cmp-slot lk-cmp-slot--placeholder">هنوز خودرویی انتخاب نشده است</span>';
			return;
		}

		slots.innerHTML = list.map(function (id) {
			return '<span class="lk-cmp-slot" data-id="' + id + '">' +
				'<span class="lk-cmp-slot__name">' + esc(titles[id] || ('خودرو #' + id)) + '</span>' +
				'<button type="button" class="lk-cmp-slot__remove" data-lk-slot-remove="' + id + '" aria-label="حذف از مقایسه">×</button>' +
				'</span>';
		}).join('');
	}

	function hideSuggest() {
		if (suggestBox) {
			suggestBox.hidden = true;
			suggestBox.innerHTML = '';
		}
	}

	function renderSuggest(items) {
		if (!suggestBox) {
			return;
		}
		var selected = currentIds();

		items = items.filter(function (item) {
			return selected.indexOf(Number(item.post_id || item.id)) === -1;
		});

		if (!items.length) {
			suggestBox.innerHTML = '<p class="lk-cmp-suggest__empty">نتیجه دیگری نیست.</p>';
			suggestBox.hidden = false;
			return;
		}

		suggestBox.innerHTML = '<ul>' + items.map(function (item) {
			var id = item.post_id || item.id;
			var price = item.market_price
				? window.LKUI.toFa(item.market_price) + ' تومان'
				: 'بدون قیمت';
			return '<li><button type="button" data-lk-suggest-id="' + id + '" data-lk-suggest-title="' + esc(item.title) + '">' +
				'<span class="lk-cmp-suggest__title">' + esc(item.title) + '</span>' +
				'<span class="lk-cmp-suggest__meta">' + price + '</span>' +
				'</button></li>';
		}).join('') + '</ul>';

		suggestBox.hidden = false;
	}

	function searchVehicles(term) {
		if (term.length < 2) {
			hideSuggest();
			return;
		}

		if (controller) {
			controller.abort();
		}
		controller = new AbortController();

		if (spinner) {
			spinner.hidden = false;
		}

		fetch(window.LK.restUrl + 'vehicles?search=' + encodeURIComponent(term) + '&per_page=8', {
			headers: { Accept: 'application/json' },
			signal: controller.signal
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				renderSuggest((payload && payload.items) || []);
			})
			.catch(function (error) {
				if (error.name !== 'AbortError') {
					hideSuggest();
				}
			})
			.finally(function () {
				if (spinner) {
					spinner.hidden = true;
				}
			});
	}

	function updateUrl() {
		var list = currentIds();
		try {
			var url = window.location.pathname + (list.length ? '?ids=' + list.join(',') : '');
			window.history.replaceState({}, '', url);
		} catch (error) {
			/* ignore */
		}
	}

	function runCompare() {
		var list = currentIds();

		if (list.length < 2) {
			window.LKUI.toast(strings.compareEmpty || 'دو تا چهار خودرو برای مقایسه انتخاب کنید.');
			return;
		}

		if (!results) {
			return;
		}

		results.classList.add('is-loading');

		var data = new FormData();
		data.set('action', 'lk_compare');
		data.set('nonce', window.LK.nonce);
		list.forEach(function (id) {
			data.append('ids[]', id);
		});

		fetch(window.LK.ajaxUrl, {
			method: 'POST',
			body: data,
			credentials: 'same-origin'
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (payload && payload.success && payload.data) {
					results.innerHTML = payload.data.html;
					updateUrl();
				} else {
					var message = (payload && payload.data && payload.data.message) || strings.error;
					results.innerHTML = '<div class="lk-empty"><p class="lk-empty__text">' + esc(message) + '</p></div>';
					window.LKUI.toast(message);
				}
			})
			.catch(function () {
				window.LKUI.toast(strings.error || 'خطا در دریافت اطلاعات.');
			})
			.finally(function () {
				results.classList.remove('is-loading');
			});
	}

	if (searchInput) {
		searchInput.addEventListener('input', function () {
			var term = searchInput.value.trim();
			window.clearTimeout(timer);
			timer = window.setTimeout(function () {
				searchVehicles(term);
			}, 300);
		});

		document.addEventListener('click', function (event) {
			if (!searchInput.contains(event.target) && suggestBox && !suggestBox.contains(event.target)) {
				hideSuggest();
			}
		});
	}

	document.addEventListener('click', function (event) {
		var option = event.target.closest('[data-lk-suggest-id]');
		if (option) {
			var id = Number(option.getAttribute('data-lk-suggest-id'));
			var title = option.getAttribute('data-lk-suggest-title');
			titles[id] = title;
			window.LKCompare.add(id, title);
			hideSuggest();
			if (searchInput) {
				searchInput.value = '';
				searchInput.focus();
			}
			renderSlots();
			return;
		}

		var remove = event.target.closest('[data-lk-slot-remove]');
		if (remove) {
			window.LKCompare.remove(Number(remove.getAttribute('data-lk-slot-remove')));
			renderSlots();
			return;
		}

		if (event.target.closest('[data-lk-compare-run]')) {
			runCompare();
			return;
		}

		if (event.target.closest('[data-lk-compare-clear]')) {
			window.LKCompare.clear();
			renderSlots();
			if (results) {
				results.innerHTML = '<div class="lk-empty"><p class="lk-empty__text">' + esc(strings.compareEmpty || '') + '</p></div>';
			}
			updateUrl();
			return;
		}

		if (event.target.closest('[data-lk-compare-share]')) {
			var url = window.location.href;
			if (navigator.clipboard && navigator.clipboard.writeText) {
				navigator.clipboard.writeText(url).then(function () {
					window.LKUI.toast(strings.copyLink || 'پیوند کپی شد.');
				});
			} else {
				window.prompt('پیوند مقایسه را کپی کنید:', url);
			}
		}
	});

	document.addEventListener('lk:compare-changed', function () {
		renderSlots();
		window.clearTimeout(compareTimer);
		if (currentIds().length >= 2) {
			compareTimer = window.setTimeout(runCompare, 350);
		}
	});

	renderSlots();
	if (currentIds().length >= 2) {
		runCompare();
	}
})();
