/**
 * Price history chart (Chart.js loaded locally).
 */
(function () {
	'use strict';

	var wrap = document.querySelector('[data-lk-chart-wrap]');
	if (!wrap || !window.LK) {
		return;
	}

	var canvas = wrap.querySelector('[data-lk-chart]');
	var state = wrap.querySelector('[data-lk-chart-state]');
	var summary = document.querySelector('[data-lk-chart-summary]');
	var historyBox = document.querySelector('[data-lk-chart-history]');
	var vehicle = wrap.getAttribute('data-vehicle');
	var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-lk-chart-range]'));
	var strings = window.LK.strings || {};
	var chart = null;
	var range = 30;
	var controller = null;

	if (!canvas || !window.Chart) {
		if (state) {
			state.textContent = strings.chartEmpty || 'نمودار در دسترس نیست.';
		}
		return;
	}

	function fa(value) {
		try {
			return new Intl.NumberFormat('fa-IR').format(value);
		} catch (error) {
			return String(value);
		}
	}

	function compact(value) {
		if (value == null) {
			return '—';
		}
		if (Math.abs(value) >= 1e9) {
			return fa((value / 1e9).toFixed(1)) + ' میلیارد';
		}
		if (Math.abs(value) >= 1e6) {
			return fa((value / 1e6).toFixed(0)) + ' میلیون';
		}
		return fa(value);
	}

	function showMessage(message) {
		if (state) {
			state.hidden = false;
			state.innerHTML = '<span class="lk-chart__state-text">' + message + '</span>';
		}
		canvas.hidden = true;
		if (summary) {
			summary.hidden = true;
		}
		if (historyBox) {
			historyBox.hidden = true;
		}
	}

	function escapeHtml(value) {
		return String(value).replace(/[&<>"']/g, function (character) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
		});
	}

	/**
	 * Range stats (low / high / net change) rendered above the table.
	 */
	function renderSummary(payload) {
		if (!summary) {
			return;
		}

		var market = (payload.series.market || []).filter(function (value) {
			return value !== null && value !== undefined;
		});

		if (market.length < 2) {
			summary.hidden = true;
			return;
		}

		var first = market[0];
		var last = market[market.length - 1];
		var change = last - first;
		var percent = first ? (change / first) * 100 : 0;
		var changeClass = change > 0 ? 'is-up' : (change < 0 ? 'is-down' : 'is-flat');
		var arrow = change > 0 ? '▲' : (change < 0 ? '▼' : '•');

		summary.innerHTML =
			'<div class="lk-chart__stat"><span class="lk-chart__stat-label">کمترین قیمت بازه</span>' +
			'<span class="lk-chart__stat-value">' + fa(Math.min.apply(null, market)) + ' تومان</span></div>' +
			'<div class="lk-chart__stat"><span class="lk-chart__stat-label">بیشترین قیمت بازه</span>' +
			'<span class="lk-chart__stat-value">' + fa(Math.max.apply(null, market)) + ' تومان</span></div>' +
			'<div class="lk-chart__stat"><span class="lk-chart__stat-label">تغییر در این بازه</span>' +
			'<span class="lk-change ' + changeClass + '"><span class="lk-change__arrow" aria-hidden="true">' + arrow + '</span>' +
			'<span class="lk-change__percent">' + fa(Math.abs(percent).toFixed(2)) + '٪</span>' +
			'<span class="lk-change__amount">' + fa(Math.abs(change)) + ' تومان</span></span></div>';
		summary.hidden = false;
	}

	/**
	 * Day-by-day price list (newest first).
	 */
	function renderTable(payload) {
		if (!historyBox) {
			return;
		}

		var points = (payload.points || []).slice().reverse();
		if (!points.length) {
			historyBox.hidden = true;
			return;
		}

		var rows = '';
		points.slice(0, 20).forEach(function (point, index) {
			var previous = points[index + 1] ? points[index + 1].market : null;
			var delta = null !== previous && null !== point.market ? point.market - previous : null;
			var deltaClass = delta > 0 ? 'is-up' : (delta < 0 ? 'is-down' : 'is-flat');
			var deltaArrow = delta > 0 ? '▲' : (delta < 0 ? '▼' : '•');

			rows +=
				'<tr>' +
				'<td>' + escapeHtml(point.jalali) + '</td>' +
				'<td>' + (null !== point.market ? fa(point.market) + ' تومان' : '—') + '</td>' +
				'<td>' + (null === delta
					? '<span class="lk-muted">—</span>'
					: '<span class="lk-change ' + deltaClass + '"><span class="lk-change__arrow" aria-hidden="true">' + deltaArrow +
						'</span><span class="lk-change__amount">' + fa(Math.abs(delta)) + ' تومان</span></span>') +
				'</td>' +
				'</tr>';
		});

		var more = points.length > 20
			? '<p class="lk-chart__history-note">' + fa(points.length - 20) + ' روز قدیمی‌تر در نمودار و بازه‌های زمانی دیگر قابل مشاهده است.</p>'
			: '';

		historyBox.innerHTML =
			'<div class="lk-table-wrap"><table class="lk-table lk-history-table">' +
			'<caption class="lk-history-table__caption">قیمت بازار روزهای گذشته</caption>' +
			'<thead><tr><th scope="col">تاریخ</th><th scope="col">قیمت بازار</th><th scope="col">تغییر نسبت به روز قبل</th></tr></thead>' +
			'<tbody>' + rows + '</tbody></table></div>' + more;
		historyBox.hidden = false;
	}

	function draw(payload) {
		if (!payload || payload.insufficient || !payload.labels || payload.labels.length < 2) {
			showMessage(payload && payload.note ? payload.note : (strings.chartEmpty || 'داده کافی برای رسم نمودار ثبت نشده است.'));
			return;
		}

		renderSummary(payload);
		renderTable(payload);

		if (state) {
			state.hidden = true;
		}
		canvas.hidden = false;

		var ctx = canvas.getContext('2d');
		var gradient = ctx.createLinearGradient(0, 0, 0, canvas.clientHeight || 300);
		gradient.addColorStop(0, 'rgba(242, 101, 34, 0.22)');
		gradient.addColorStop(1, 'rgba(242, 101, 34, 0.01)');

		if (chart) {
			chart.destroy();
		}

		var hasFactory = (payload.series.factory || []).some(function (value) {
			return value !== null;
		});

		var datasets = [{
			label: 'قیمت بازار',
			data: payload.series.market,
			borderColor: '#F26522',
			backgroundColor: gradient,
			borderWidth: 2.5,
			fill: true,
			tension: 0.32,
			pointRadius: 0,
			pointHoverRadius: 4,
			pointBackgroundColor: '#F26522',
			spanGaps: true
		}];

		if (hasFactory) {
			datasets.push({
				label: 'قیمت کارخانه',
				data: payload.series.factory,
				borderColor: '#0A1F33',
				borderDash: [6, 5],
				borderWidth: 1.75,
				fill: false,
				tension: 0.32,
				pointRadius: 0,
				pointHoverRadius: 4,
				pointBackgroundColor: '#0A1F33',
				spanGaps: true
			});
		}

		chart = new window.Chart(ctx, {
			type: 'line',
			data: {
				labels: payload.labels,
				datasets: datasets
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				layout: { padding: { top: 8, right: 4, left: 4, bottom: 0 } },
				plugins: {
					legend: {
						position: 'top',
						align: 'end',
						rtl: true,
						textDirection: 'rtl',
						labels: {
							boxWidth: 10,
							boxHeight: 10,
							usePointStyle: true,
							font: { family: 'Vazirmatn, system-ui, sans-serif', size: 12 },
							color: '#3B4A57'
						}
					},
					tooltip: {
						rtl: true,
						textDirection: 'rtl',
						backgroundColor: '#0A1F33',
						titleFont: { family: 'Vazirmatn, system-ui, sans-serif', size: 12 },
						bodyFont: { family: 'Vazirmatn, system-ui, sans-serif', size: 12 },
						padding: 10,
						displayColors: true,
						callbacks: {
							label: function (context) {
								if (context.parsed.y == null) {
									return '';
								}
								return context.dataset.label + ': ' + fa(context.parsed.y) + ' تومان';
							}
						}
					}
				},
				scales: {
					x: {
						reverse: true,
						grid: { display: false },
						ticks: {
							maxTicksLimit: 7,
							font: { family: 'Vazirmatn, system-ui, sans-serif', size: 11 },
							color: '#7A8794'
						}
					},
					y: {
						beginAtZero: false,
						grid: { color: 'rgba(10, 31, 51, 0.07)' },
						ticks: {
							callback: function (value) {
								return compact(value);
							},
							font: { family: 'Vazirmatn, system-ui, sans-serif', size: 11 },
							color: '#7A8794'
						}
					}
				}
			}
		});
	}

	function load(nextRange) {
		range = nextRange || 30;

		buttons.forEach(function (btn) {
			btn.classList.toggle('is-active', btn.getAttribute('data-lk-chart-range') === String(range));
		});

		if (controller) {
			controller.abort();
		}
		controller = new AbortController();

		if (state) {
			state.hidden = false;
			state.innerHTML = '<span class="lk-chart__spinner" aria-hidden="true"></span><span>در حال بارگذاری نمودار…</span>';
		}

		fetch(window.LK.restUrl + 'vehicles/' + encodeURIComponent(vehicle) + '/history?range=' + range, {
			headers: { Accept: 'application/json' },
			signal: controller.signal
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				// REST responses are wrapped as { data, meta }.
				return payload && payload.data ? payload.data : payload;
			})
			.then(draw)
			.catch(function (error) {
				if (error.name !== 'AbortError') {
					showMessage(strings.error || 'خطا در دریافت تاریخچه قیمت.');
				}
			});
	}

	buttons.forEach(function (btn) {
		btn.addEventListener('click', function () {
			load(btn.getAttribute('data-lk-chart-range'));
		});
	});

	load(30);
})();
