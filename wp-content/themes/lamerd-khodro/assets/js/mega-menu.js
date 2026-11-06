/**
 * Header brand mega menu.
 *
 * Hover opens on pointer devices, click/keyboard toggles everywhere, and only
 * one panel stays open at a time. Panels are server-rendered, so nothing is
 * fetched when the menu opens.
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-lk-mega]');
	if (!root) {
		return;
	}

	var panelsWrap = root.querySelector('[data-lk-mega-panels]');
	var triggers = Array.prototype.slice.call(root.querySelectorAll('[data-lk-mega-trigger]'));
	var panels = panelsWrap ? Array.prototype.slice.call(panelsWrap.querySelectorAll('[data-lk-mega-panel]')) : [];

	if (!panelsWrap || !triggers.length || !panels.length) {
		return;
	}

	/**
	 * Sub-brand tabs inside a panel. Panels stay open across tab switches, the
	 * markup already carries role/aria state, and without JavaScript every tab
	 * panel simply stays visible.
	 */
	function initTabs(scope) {
		var boxes = Array.prototype.slice.call(scope.querySelectorAll('[data-lk-tabs]'));

		boxes.forEach(function (box) {
			var tabs = Array.prototype.slice.call(box.querySelectorAll('[role="tab"]'));
			var panes = Array.prototype.slice.call(box.querySelectorAll('[role="tabpanel"]'));

			if (tabs.length < 2 || tabs.length !== panes.length) {
				return;
			}

			function select(index, moveFocus) {
				tabs.forEach(function (tab, position) {
					var active = position === index;
					tab.setAttribute('aria-selected', active ? 'true' : 'false');
					tab.tabIndex = active ? 0 : -1;
				});
				panes.forEach(function (pane, position) {
					pane.hidden = position !== index;
				});
				if (moveFocus) {
					tabs[index].focus();
				}
			}

			var rtl = window.getComputedStyle(box).direction === 'rtl';

			tabs.forEach(function (tab, index) {
				tab.addEventListener('click', function () {
					select(index, false);
				});

				tab.addEventListener('keydown', function (event) {
					var next = null;

					if (event.key === 'ArrowRight') {
						next = rtl ? index - 1 : index + 1;
					} else if (event.key === 'ArrowLeft') {
						next = rtl ? index + 1 : index - 1;
					} else if (event.key === 'Home') {
						next = 0;
					} else if (event.key === 'End') {
						next = tabs.length - 1;
					}

					if (next === null) {
						return;
					}

					event.preventDefault();
					select((next + tabs.length) % tabs.length, true);
				});
			});

			select(0, false);
		});
	}

	initTabs(root);

	var canHover = window.matchMedia('(hover: hover) and (pointer: fine)');
	var current = null;
	var closeTimer = null;

	function panelFor(key) {
		for (var i = 0; i < panels.length; i += 1) {
			if (panels[i].getAttribute('data-lk-mega-panel') === key) {
				return panels[i];
			}
		}
		return null;
	}

	function hide() {
		window.clearTimeout(closeTimer);
		panels.forEach(function (panel) {
			panel.hidden = true;
		});
		triggers.forEach(function (trigger) {
			trigger.setAttribute('aria-expanded', 'false');
		});
		panelsWrap.hidden = true;
		root.classList.remove('is-mega-open');
		current = null;
	}

	function open(key, trigger) {
		if (current === key) {
			return;
		}

		window.clearTimeout(closeTimer);

		panels.forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-lk-mega-panel') !== key;
		});
		triggers.forEach(function (item) {
			item.setAttribute('aria-expanded', item === trigger ? 'true' : 'false');
		});

		panelsWrap.hidden = false;
		root.classList.add('is-mega-open');
		current = key;
	}

	function scheduleClose() {
		window.clearTimeout(closeTimer);
		closeTimer = window.setTimeout(hide, 160);
	}

	triggers.forEach(function (trigger) {
		var key = trigger.getAttribute('data-lk-mega-trigger');

		trigger.addEventListener('click', function (event) {
			event.preventDefault();
			if (current === key) {
				hide();
			} else {
				open(key, trigger);
			}
		});

		trigger.addEventListener('pointerenter', function () {
			if (!canHover.matches) {
				return;
			}
			open(key, trigger);
		});

		trigger.addEventListener('pointerleave', function () {
			if (!canHover.matches) {
				return;
			}
			scheduleClose();
		});
	});

	panelsWrap.addEventListener('pointerenter', function () {
		window.clearTimeout(closeTimer);
	});

	panelsWrap.addEventListener('pointerleave', function () {
		if (canHover.matches) {
			scheduleClose();
		}
	});

	document.addEventListener('click', function (event) {
		if (!root.contains(event.target)) {
			hide();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape' || !current) {
			return;
		}
		var owner = triggers.filter(function (trigger) {
			return trigger.getAttribute('data-lk-mega-trigger') === current;
		})[0];
		hide();
		if (owner) {
			owner.focus();
		}
	});

	root.addEventListener('focusout', function (event) {
		if (!root.contains(event.relatedTarget)) {
			hide();
		}
	});
})();
