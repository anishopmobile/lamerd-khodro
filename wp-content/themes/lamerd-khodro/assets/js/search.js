/**
 * Instant search.
 *
 * One component powers every `[data-lk-hsearch]` block: the header field (and
 * its mobile sheet) plus the home hero field. It renders grouped results
 * (vehicles, brands) as you type, with full keyboard navigation and the
 * accessible combobox/listbox pattern.
 */
(function () {
	'use strict';

	var config = window.LK || {};
	var strings = config.strings || {};
	var wraps = document.querySelectorAll('[data-lk-hsearch]');

	if (!wraps.length) {
		return;
	}

	var instances = [];
	var MIN_CHARS = 2;

	function esc(text) {
		return String(text == null ? '' : text).replace(/[&<>"']/g, function (char) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
		});
	}

	/* Wrap the first match of the typed term so results read at a glance. */
	function highlight(text, term) {
		var raw = String(text == null ? '' : text);

		if (!term) {
			return esc(raw);
		}

		var index = raw.toLowerCase().indexOf(String(term).toLowerCase());

		if (index < 0) {
			return esc(raw);
		}

		return esc(raw.slice(0, index)) +
			'<mark class="lk-hs__mark">' + esc(raw.slice(index, index + String(term).length)) + '</mark>' +
			esc(raw.slice(index + String(term).length));
	}

	function rowHtml(item, groupType, term) {
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
			'<span class="lk-hs__title">' + highlight(item.title, term) + '</span>' +
			'<span class="lk-hs__meta">' + esc(item.meta || '') + '</span>' +
			'</span>' +
			price +
			'</a></li>';
	}

	function createSearch(wrap) {
		var form = wrap.querySelector('form');
		var input = wrap.querySelector('[data-lk-search-input]');
		var panel = wrap.querySelector('[data-lk-search-results]');
		var head = document.querySelector('.lk-head');
		var toggle = document.querySelector('[data-lk-search-toggle]');
		var isSheet = wrap.classList.contains('lk-hsearch');
		/* The home hero clips its decorations; the dropdown needs an escape. */
		var hero = wrap.closest('.lk-hero');

		if (!form || !input || !panel) {
			return;
		}

		var controller = null;
		var timer = null;
		var lastTerm = '';
		var results = [];
		var active = -1;
		var cache = {};

		function openPanel() {
			panel.hidden = false;
			input.setAttribute('aria-expanded', 'true');

			if (hero) {
				hero.classList.add('is-search-open');
			}

			if (isSheet && toggle) {
				toggle.setAttribute('aria-expanded', 'true');
			}
		}

		function closePanel() {
			panel.hidden = true;
			input.setAttribute('aria-expanded', 'false');

			if (hero) {
				hero.classList.remove('is-search-open');
			}

			if (isSheet && toggle) {
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

		function render(payload, term) {
			var groups = payload.groups || [];
			var html = '';

			if (!groups.length) {
				panel.innerHTML = '<p class="lk-hs__empty">' + esc(strings.noResults || 'نتیجه‌ای یافت نشد.') + '</p>' +
					'<p class="lk-hs__hint">املای عبارت را بررسی کنید یا نام برند را جستجو کنید.</p>';
				results = [];
				openPanel();
				return;
			}

			groups.forEach(function (group) {
				html += '<div class="lk-hs__group">';
				html += '<p class="lk-hs__label">' + esc(group.label) + '</p><ul role="group" aria-label="' + esc(group.label) + '">';
				group.items.forEach(function (item) {
					html += rowHtml(item, group.type, term);
				});
				html += '</ul>';

				if (group.all) {
					html += '<a class="lk-hs__all" href="' + esc(group.all.url) + '">' + esc(group.all.label) + ' ←</a>';
				}

				html += '</div>';
			});

			html += '<a class="lk-hs__footer" role="option" aria-selected="false" data-lk-result href="' + esc(payload.allUrl) + '">' +
				'همه نتایج برای «' + esc(payload.term || term) + '»' + '</a>';

			panel.innerHTML = html;
			results = Array.prototype.slice.call(panel.querySelectorAll('[data-lk-result]'));
			results.forEach(function (row, i) {
				row.id = wrap.id ? wrap.id + '-result-' + i : 'lk-hs-result-' + i;
			});
			setActive(-1);
			openPanel();
		}

		function search(term) {
			if (term === lastTerm) {
				return;
			}
			lastTerm = term;

			if (term.length < MIN_CHARS) {
				panel.innerHTML = '';
				results = [];
				closePanel();
				return;
			}

			if (cache[term]) {
				render(cache[term], term);
				return;
			}

			if (controller) {
				controller.abort();
			}
			controller = new AbortController();

			openPanel();
			panel.innerHTML = '<p class="lk-hs__empty is-loading">' + esc(strings.searching || 'در حال جستجو…') + '</p>';

			var url = config.ajaxUrl + '?action=lk_live_search' +
				'&nonce=' + encodeURIComponent(config.nonce || '') +
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
					cache[term] = payload.data || {};
					render(payload.data || {}, term);
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

		function openSheet() {
			if (!isSheet || !head) {
				return;
			}

			head.classList.add('is-search-open');

			if (toggle) {
				toggle.setAttribute('aria-expanded', 'true');
			}

			window.setTimeout(function () {
				input.focus();
			}, 30);
		}

		function closeSheet() {
			if (!isSheet || !head) {
				return;
			}

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
			}, 180);
		});

		input.addEventListener('focus', function () {
			if (input.value.trim().length >= MIN_CHARS && panel.innerHTML) {
				openPanel();
			}
		});

		input.addEventListener('keydown', function (event) {
			if (event.key === 'ArrowDown' && results.length) {
				event.preventDefault();
				openPanel();
				setActive(active + 1 >= results.length ? 0 : active + 1);
				return;
			}

			if (event.key === 'ArrowUp' && results.length) {
				event.preventDefault();
				openPanel();
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
				closeSheet();
				input.blur();
			}
		});

		if (isSheet && toggle) {
			toggle.addEventListener('click', function () {
				if (head && head.classList.contains('is-search-open')) {
					closeSheet();
					return;
				}
				openSheet();
			});
		}

		form.addEventListener('submit', function (event) {
			if (active > -1 && results[active]) {
				event.preventDefault();
				window.location.href = results[active].href;
			}
		});

		document.addEventListener('click', function (event) {
			if (wrap.contains(event.target) || (isSheet && toggle && toggle.contains(event.target))) {
				return;
			}
			closePanel();
			closeSheet();
		});

		instances.push({
			wrap: wrap,
			input: input,
			closePanel: closePanel,
			openSheet: openSheet,
			isSheet: isSheet
		});
	}

	Array.prototype.forEach.call(wraps, createSearch);

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			instances.forEach(function (instance) {
				instance.closePanel();
			});
		}

		if (event.key !== '/' || !instances.length) {
			return;
		}

		var target = instances[0].input;

		if (document.activeElement === target) {
			return;
		}

		var tag = (document.activeElement.tagName || '').toLowerCase();

		if (tag === 'input' || tag === 'textarea' || tag === 'select') {
			return;
		}

		event.preventDefault();

		if (window.matchMedia('(max-width: 899px)').matches && instances[0].isSheet) {
			instances[0].openSheet();
		} else {
			target.focus();
		}
	});
})();
