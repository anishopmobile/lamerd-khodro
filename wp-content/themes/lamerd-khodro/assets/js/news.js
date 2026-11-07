/**
 * News sidebar: live article search.
 *
 * The form still submits normally to /news/?s=… when JavaScript is missing;
 * here we only replace the grid with fresh results while typing.
 */
(function () {
	'use strict';

	var root = document.querySelector('[data-lk-news-region]');
	var form = document.querySelector('[data-lk-news-search]');
	if (!root || !form || !window.LK) {
		return;
	}

	var input = form.querySelector('[data-lk-news-input]');
	var grid = root.querySelector('[data-lk-news-grid]');
	var status = root.querySelector('[data-lk-news-status]');
	var statusText = root.querySelector('[data-lk-news-status-text]');
	var clearBtn = root.querySelector('[data-lk-news-clear]');
	var pagination = root.querySelector('[data-lk-news-pagination]');
	var empty = root.querySelector('[data-lk-news-empty]');
	var spinner = form.querySelector('.lk-newssearch__spinner');

	if (!input || !grid || !status || !statusText) {
		return;
	}

	var defaultGrid = grid.innerHTML;
	var defaultPagination = pagination ? pagination.innerHTML : '';
	var defaultPaginationHidden = pagination ? pagination.hidden : true;
	var defaultEmptyHidden = empty ? empty.hidden : true;

	var timer = null;
	var controller = null;
	var activeTerm = '';

	function setLoading(isLoading) {
		form.classList.toggle('is-loading', isLoading);
		form.setAttribute('aria-busy', isLoading ? 'true' : 'false');
		if (spinner) {
			spinner.hidden = !isLoading;
		}
	}

	function setUrl(term) {
		if (!window.history || !window.history.replaceState) {
			return;
		}
		var url = term ? form.action + '?s=' + encodeURIComponent(term) : form.action;
		window.history.replaceState({}, '', url);
	}

	function showStatus(label) {
		statusText.textContent = label || '';
		status.classList.toggle('is-visible', !!label);
	}

	function restoreDefault() {
		if (controller) {
			controller.abort();
			controller = null;
		}
		activeTerm = '';
		setLoading(false);
		grid.innerHTML = defaultGrid;
		if (pagination) {
			pagination.innerHTML = defaultPagination;
			pagination.hidden = defaultPaginationHidden;
		}
		if (empty) {
			empty.hidden = defaultEmptyHidden;
		}
		showStatus('');
		setUrl('');
	}

	function run(term) {
		if (controller) {
			controller.abort();
		}
		controller = new AbortController();

		var body = new FormData();
		body.append('action', 'lk_news_search');
		body.append('nonce', LK.nonce || '');
		body.append('term', term);

		setLoading(true);
		activeTerm = term;

		fetch(LK.ajaxUrl, {
			method: 'POST',
			body: body,
			credentials: 'same-origin',
			signal: controller.signal
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (activeTerm !== term) {
					return;
				}
				setLoading(false);

				if (!payload || !payload.success) {
					showStatus((payload && payload.data && payload.data.message) || LK.strings.error);
					return;
				}

				var data = payload.data;
				grid.innerHTML = data.html || '';
				if (pagination) {
					pagination.hidden = true;
				}
				if (empty) {
					empty.hidden = data.count > 0;
				}
				showStatus(data.label);
				setUrl(term);
			})
			.catch(function (error) {
				if (error && error.name === 'AbortError') {
					return;
				}
				setLoading(false);
				showStatus(LK.strings.error);
			});
	}

	input.addEventListener('input', function () {
		var term = input.value.trim();
		window.clearTimeout(timer);

		if (term.length === 0) {
			restoreDefault();
			return;
		}
		if (term.length < 2) {
			return;
		}

		timer = window.setTimeout(function () {
			run(term);
		}, 320);
	});

	input.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') {
			input.value = '';
			restoreDefault();
		}
	});

	form.addEventListener('submit', function (event) {
		var term = input.value.trim();
		if (!term) {
			return;
		}
		event.preventDefault();
		window.clearTimeout(timer);
		if (term.length < 2) {
			showStatus('برای جستجو حداقل دو حرف وارد کنید.');
			return;
		}
		run(term);
	});

	if (clearBtn) {
		clearBtn.addEventListener('click', function () {
			input.value = '';
			restoreDefault();
			input.focus();
		});
	}
})();
