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
	}

	function draw(payload) {
		if (!payload || payload.insufficient || !payload.labels || payload.labels.length < 2) {
			showMessage(payload && payload.note ? payload.note : (strings.chartEmpty || 'داده کافی برای رسم نمودار ثبت نشده است.'));
			return;
		}

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
