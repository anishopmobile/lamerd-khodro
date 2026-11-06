/**
 * Lamerd Khodro — global interactions.
 *
 * No dependencies. All data requests go to the site's own REST/AJAX endpoints.
 */
(function () {
	'use strict';

	var config = window.LK || {};
	var strings = config.strings || {};

	/* ---------------------------------------------------------------- utils */
	function qs(selector, scope) {
		return (scope || document).querySelector(selector);
	}

	function qsa(selector, scope) {
		return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
	}

	function toFa(value) {
		try {
			return new Intl.NumberFormat('fa-IR').format(value);
		} catch (error) {
			return String(value);
		}
	}

	var toastTimer = null;

	function toast(message) {
		if (!message) {
			return;
		}
		var node = qs('.lk-toast');
		if (!node) {
			node = document.createElement('div');
			node.className = 'lk-toast';
			node.setAttribute('role', 'status');
			node.setAttribute('aria-live', 'polite');
			document.body.appendChild(node);
		}
		node.textContent = message;
		node.classList.add('is-visible');
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () {
			node.classList.remove('is-visible');
		}, 2600);
	}

	/* --------------------------------------------------------- header state */
	var header = qs('#lk-header');
	if (header) {
		var onScroll = function () {
			header.classList.toggle('is-stuck', window.scrollY > 12);
		};
		window.addEventListener('scroll', onScroll, { passive: true });
		onScroll();
	}

	/* -------------------------------------------------------------- drawers */
	var drawer = qs('#lk-drawer');
	var menuToggle = qs('#lk-menu-toggle');
	var lastFocus = null;

	function openDrawer() {
		if (!drawer) {
			return;
		}
		lastFocus = document.activeElement;
		drawer.hidden = false;
		// Force a reflow so the slide-in transition still runs, then set the
		// open state synchronously (rAF can be throttled in background tabs).
		void drawer.offsetWidth;
		drawer.classList.add('is-open');
		document.body.classList.add('lk-locked');
		if (menuToggle) {
			menuToggle.setAttribute('aria-expanded', 'true');
		}
		var first = qs('[data-lk-close-drawer]', drawer);
		if (first) {
			first.focus();
		}
	}

	function closeDrawer() {
		if (!drawer) {
			return;
		}
		drawer.classList.remove('is-open');
		document.body.classList.remove('lk-locked');
		if (menuToggle) {
			menuToggle.setAttribute('aria-expanded', 'false');
			menuToggle.focus();
		}
		window.setTimeout(function () {
			drawer.hidden = true;
		}, 220);
		if (lastFocus && lastFocus.focus) {
			lastFocus = null;
		}
	}

	if (menuToggle) {
		menuToggle.addEventListener('click', function () {
			if (drawer && drawer.hidden) {
				openDrawer();
			} else {
				closeDrawer();
			}
		});
	}

	qsa('[data-lk-close-drawer]').forEach(function (btn) {
		btn.addEventListener('click', closeDrawer);
	});

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') {
			return;
		}
		if (drawer && !drawer.hidden) {
			closeDrawer();
		}
	});

	/* ------------------------------------------------------- compare store */
	var STORAGE_KEY = 'lk_compare_ids';
	var MAX = 4;

	function readIds() {
		try {
			var raw = window.localStorage.getItem(STORAGE_KEY);
			var ids = raw ? JSON.parse(raw) : [];
			return Array.isArray(ids) ? ids.map(Number).filter(Boolean) : [];
		} catch (error) {
			return [];
		}
	}

	function writeIds(ids) {
		try {
			window.localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
		} catch (error) {
			/* storage may be blocked; comparison still works for this page view */
		}
		syncCompareUI();
		document.dispatchEvent(new CustomEvent('lk:compare-changed', { detail: { ids: ids } }));
	}

	function syncCompareUI() {
		var ids = readIds();
		qsa('[data-lk-compare-count]').forEach(function (node) {
			node.textContent = toFa(ids.length);
			node.hidden = ids.length === 0;
		});
		qsa('[data-lk-compare-id]').forEach(function (btn) {
			var id = Number(btn.getAttribute('data-lk-compare-id'));
			var added = ids.indexOf(id) !== -1;
			btn.classList.toggle('is-added', added);
			btn.setAttribute('aria-pressed', added ? 'true' : 'false');
		});
	}

	function addToCompare(id, title) {
		id = Number(id);
		var ids = readIds();
		if (ids.indexOf(id) !== -1) {
			toast('این خودرو قبلاً به مقایسه اضافه شده است.');
			return false;
		}
		if (ids.length >= MAX) {
			toast(strings.compareLimit || 'حداکثر چهار خودرو قابل مقایسه است.');
			return false;
		}
		ids.push(id);
		writeIds(ids);
		toast((title || 'خودرو') + ' به فهرست مقایسه اضافه شد.');
		return true;
	}

	function removeFromCompare(id) {
		id = Number(id);
		writeIds(readIds().filter(function (item) {
			return item !== id;
		}));
		toast(strings.compareRemove || 'از فهرست مقایسه حذف شد.');
	}

	document.addEventListener('click', function (event) {
		var btn = event.target.closest('[data-lk-compare-id]');
		if (!btn) {
			return;
		}
		event.preventDefault();
		var id = Number(btn.getAttribute('data-lk-compare-id'));
		var ids = readIds();
		if (ids.indexOf(id) !== -1) {
			removeFromCompare(id);
		} else {
			addToCompare(id, btn.getAttribute('data-lk-compare-title'));
		}
	});

	window.LKCompare = {
		get: readIds,
		add: addToCompare,
		remove: removeFromCompare,
		clear: function () {
			writeIds([]);
		},
		max: MAX
	};

	syncCompareUI();

	/* ------------------------------------------------------------- gallery */
	qsa('[data-lk-gallery-src]').forEach(function (thumb) {
		thumb.addEventListener('click', function () {
			var main = qs('.lk-gallery__main img');
			if (!main) {
				return;
			}
			main.src = thumb.getAttribute('data-lk-gallery-src');
			qsa('.lk-gallery__thumb').forEach(function (item) {
				item.classList.remove('is-active');
			});
			thumb.classList.add('is-active');
		});
	});

	/* --------------------------------------------------------------- print */
	qsa('[data-lk-print]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			window.print();
		});
	});

	/* -------------------------------------------------------- mobile submenu */
	qsa('.lk-drawer__list .menu-item-has-children > a').forEach(function (link) {
		link.addEventListener('click', function (event) {
			var parent = link.parentNode;
			if (!parent.classList.contains('is-open') && parent.querySelector('.sub-menu')) {
				event.preventDefault();
				parent.classList.add('is-open');
			}
		});
	});

	/* ----------------------------------------------------------- public API */
	window.LKUI = {
		toast: toast,
		toFa: toFa,
		qs: qs,
		qsa: qsa,
	};
})();
