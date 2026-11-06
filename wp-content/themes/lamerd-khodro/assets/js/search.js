/**
 * Header instant search.
 *
 * One component powers the desktop field and the mobile search sheet:
 * grouped results (vehicles, brands, listings), full keyboard navigation and
 * an accessible combobox/listbox pattern.
 */
(function () {
	'use strict';

	var head = document.querySelector('.lk-head');
	var wrap = document.querySelector('[data-lk-hsearch]');
	var form = wrap ? wrap.querySelector('form') : null;
	var input = wrap ? wrap.querySelector('[data-lk-search-input]') : null;
	var panel = wrap ? wrap.querySelector('[data-lk-search-results]') : null;
	var toggle = document.querySelector('[data-lk-search-toggle]');

	if (!head || !wrap || !form || !input || !panel || !window.LK) {
		return;
	}

	var strings = window.LK.strings || {};
	var controller = null;
	var timer = null;
	var lastTerm = '';
	var results = [];
	var active = -1;

	function esc(text) {
		return String(text == null ? '' : text).replace(/[&<>"']/g, function (char) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
		});
	}

	function openPanel() {
		panel.hidden = false;
		input.setAttribute('aria-expanded', 'true');
		if (toggle) {
			toggle.setAttribute('aria-expanded', 'true');
		}
	}

	function closePanel() {
		panel.hidden = true;
		input.setAttribute('aria-expanded', 'false');
		if (toggle) {
			toggle.setAttribute('aria-expanded', 'false');
		}
		setActive(-1);
	}

	function setActive(index) {
		results.forEach(function (row, i) {
			row.classList.toggle('is-active', i === index);
			row.setAttribute('aria-selected', i === index ? 'true' : 'false');
		});
		active = index;

		if (index > -1 && results[index]) {
			input.setAttribute('aria-activedescendant', results[index].id);
			results[index].scrollIntoView({ block: 'nearest' });
		} else {
			input.removeAttribute('aria-activedescendant');
		}
	}

	function rowHtml(item, groupType) {
		var media = item.thumb
			? '<img src="' + esc(item.thumb) + '" alt="" loading="lazy" decoding="async" />'
			: '<span class="lk-hs__initial" aria-hidden="true">' + esc(item.initial || '') + '</span>';

		var price = item.price
			? '<span class="lk-hs__price">' + esc(item.price) + '<span class="lk-hs__unit">تومان</span></span>'
			: '';

		return '<li role="none">' +
			'<a class="lk-hs__row" role="option" aria-selected="false" data-lk-result data-group="' + esc(groupType) + '" href="' + esc(item.url) + '">' +
			'<span class="lk-hs__media">' + media + '</span>' +
			'<span class="lk-hs__text">' +
			'<span class="lk-hs__title">' + esc(item.title) + '</span>' +
			'<span class="lk-hs__meta">' + esc(item.meta || '') + '</span>' +
			'</span>' +
			price +
			'</a></li>';
	}

	function render(payload) {
		var groups = payload.groups || [];
		var html = '';

		if (!groups.length) {
			html = '<p class="lk-hs__empty">' + esc(strings.noResults || 'نتیجه‌ای یافت نشد.') + '</p>' +
				'<p class="lk-hs__hint">املای عبارت را بررسی کنید یا نام برند را جستجو کنید.</p>';
			panel.innerHTML = html;
			results = [];
			openPanel();
			return;
		}

		groups.forEach(function (group) {
			html += '<div class="lk-hs__group">';
			html += '<p class="lk-hs__label">' + esc(group.label) + '</p><ul role="group">';
			group.items.forEach(function (item) {
				html += rowHtml(item, group.type);
			});
			html += '</ul>';
			if (group.all) {
				html += '<a class="lk-hs__all" href="' + esc(group.all.url) + '">' + esc(group.all.label) + ' ←</a>';
			}
			html += '</div>';
		});

		html += '<a class="lk-hs__footer" role="option" aria-selected="false" data-lk-result href="' + esc(payload.allUrl) + '">' +
			'همه نتایج برای «' + esc(payload.term) + '»</a>';

		panel.innerHTML = html;
		results = Array.prototype.slice.call(panel.querySelectorAll('[data-lk-result]'));
		results.forEach(function (row, i) {
			row.id = 'lk-hs-result-' + i;
		});
		setActive(-1);
		openPanel();
	}

	function search(term) {
		if (term === lastTerm) {
			return;
		}
		lastTerm = term;

		if (term.length < 2) {
			panel.innerHTML = '';
			results = [];
			closePanel();
			return;
		}

		if (controller) {
			controller.abort();
		}
		controller = new AbortController();

		openPanel();
		panel.innerHTML = '<p class="lk-hs__empty is-loading">' + esc(strings.searching || 'در حال جستجو…') + '</p>';

		var url = window.LK.ajaxUrl + '?action=lk_live_search' +
			'&nonce=' + encodeURIComponent(window.LK.nonce || '') +
			'&term=' + encodeURIComponent(term);

		fetch(url, { headers: { Accept: 'application/json' }, signal: controller.signal })
			.then(function (response) {
				if (!response.ok) {
					throw new Error('bad response');
				}
				return response.json();
			})
			.then(function (payload) {
				if (!payload || !payload.success) {
					throw new Error('bad payload');
				}
				if (input.value.trim() !== term) {
					return;
				}
				render(payload.data || {});
			})
			.catch(function (error) {
				if (error.name === 'AbortError') {
					return;
				}
				panel.innerHTML = '<p class="lk-hs__empty">' + esc(strings.error || 'خطا در دریافت اطلاعات.') + '</p>';
				results = [];
				openPanel();
			});
	}

	function openMobileSearch() {
		head.classList.add('is-search-open');
		if (toggle) {
			toggle.setAttribute('aria-expanded', 'true');
		}
		window.setTimeout(function () {
			input.focus();
		}, 30);
	}

	function closeMobileSearch() {
		head.classList.remove('is-search-open');
		if (toggle) {
			toggle.setAttribute('aria-expanded', 'false');
		}
		closePanel();
	}

	input.addEventListener('input', function () {
		var term = input.value.trim();
		window.clearTimeout(timer);
		timer = window.setTimeout(function () {
			search(term);
		}, 220);
	});

	input.addEventListener('focus', function () {
		if (input.value.trim().length >= 2 && panel.innerHTML) {
			openPanel();
		}
	});

	input.addEventListener('keydown', function (event) {
		if (event.key === 'ArrowDown' && results.length) {
			event.preventDefault();
			openPanel();
			setActive((active + 1) % results.length);
			return;
		}

		if (event.key === 'ArrowUp' && results.length) {
			event.preventDefault();
			setActive(active <= 0 ? results.length - 1 : active - 1);
			return;
		}

		if (event.key === 'Enter' && active > -1 && results[active]) {
			event.preventDefault();
			window.location.href = results[active].href;
			return;
		}

		if (event.key === 'Escape') {
			event.preventDefault();
			closePanel();
			closeMobileSearch();
			input.blur();
		}
	});

	if (toggle) {
		toggle.addEventListener('click', function () {
			if (head.classList.contains('is-search-open')) {
				closeMobileSearch();
				return;
			}
			openMobileSearch();
		});
	}

	form.addEventListener('submit', function (event) {
		if (active > -1 && results[active]) {
			event.preventDefault();
			window.location.href = results[active].href;
		}
	});

	document.addEventListener('click', function (event) {
		if (wrap.contains(event.target) || (toggle && toggle.contains(event.target))) {
			return;
		}
		closePanel();
		if (head.classList.contains('is-search-open')) {
			closeMobileSearch();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			closePanel();
		}

		if (event.key === '/' && document.activeElement !== input) {
			var tag = (document.activeElement.tagName || '').toLowerCase();
			if (tag === 'input' || tag === 'textarea' || tag === 'select') {
				return;
			}
			event.preventDefault();
			if (window.matchMedia('(max-width: 899px)').matches) {
				openMobileSearch();
			} else {
				input.focus();
			}
		}
	});
})();
