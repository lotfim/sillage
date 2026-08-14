import $ from 'jquery';
import {
	ArcElement,
	CategoryScale,
	Chart,
	DoughnutController,
	Filler,
	Legend,
	LinearScale,
	LineController,
	LineElement,
	PointElement,
	Tooltip,
} from 'chart.js';

Chart.register(
	ArcElement,
	CategoryScale,
	DoughnutController,
	Filler,
	Legend,
	LinearScale,
	LineController,
	LineElement,
	PointElement,
	Tooltip
);

const TYPE_COLORS = [
	'#2563eb',
	'#0ea5e9',
	'#8b5cf6',
	'#10b981',
	'#f59e0b',
	'#ef4444',
	'#14b8a6',
	'#6366f1',
];

function formatNumber(value, locale) {
	try {
		return new Intl.NumberFormat(locale || undefined).format(value);
	} catch (e) {
		return String(value);
	}
}

function formatDuration(seconds, i18n) {
	if (seconds == null || seconds < 0) {
		return '—';
	}
	const total = Math.round(seconds);
	const h = Math.floor(total / 3600);
	const m = Math.floor((total % 3600) / 60);
	const s = total % 60;
	const parts = [];
	if (h) {
		parts.push(h + ' ' + (i18n.durationHours || 'hr'));
	}
	if (m || h) {
		parts.push(m + ' ' + (i18n.durationMinutes || 'min'));
	}
	if (!h && !m) {
		parts.push(s + ' ' + (i18n.durationSeconds || 'sec'));
	} else if (!h && s) {
		parts.push(s + ' ' + (i18n.durationSeconds || 'sec'));
	}
	return parts.join(' ');
}

function formatBucket(bucket, granularity, locale) {
	const iso = granularity === 'hour' ? bucket.replace(' ', 'T') : bucket + 'T00:00:00';
	const date = new Date(iso);
	if (Number.isNaN(date.getTime())) {
		return bucket;
	}
	const opts =
		granularity === 'hour'
			? { month: 'short', day: 'numeric', hour: '2-digit' }
			: { month: 'short', day: 'numeric' };
	try {
		return new Intl.DateTimeFormat(locale || undefined, opts).format(date);
	} catch (e) {
		return bucket;
	}
}

function setHidden(el, hidden) {
	if (!el) {
		return;
	}
	el.hidden = hidden;
}

function maxVisits(items) {
	return items.reduce((max, item) => Math.max(max, item.visits || 0), 0) || 1;
}

function renderRankList(root, items, kind, i18n) {
	root.innerHTML = '';
	if (!items.length) {
		const li = document.createElement('li');
		li.className = 'sillage-rank__empty';
		li.textContent = i18n.noData || 'No data';
		root.appendChild(li);
		return;
	}
	const peak = maxVisits(items);
	items.forEach((item, index) => {
		const li = document.createElement('li');
		li.className = 'sillage-rank__item';
		li.style.setProperty('--sillage-rank', Math.max(6, (item.visits / peak) * 100) + '%');

		const rank = document.createElement('span');
		rank.className = 'sillage-rank__n';
		rank.textContent = String(index + 1);

		const main = document.createElement('span');
		main.className = 'sillage-rank__main';

		if (kind === 'content') {
			const badge = document.createElement('span');
			badge.className = 'sil-badge';
			badge.textContent = item.object_type_label || item.object_type;
			const title = item.object_url
				? Object.assign(document.createElement('a'), {
						href: item.object_url,
						target: '_blank',
						rel: 'noopener noreferrer',
						title: i18n.viewContent || 'View content',
						textContent: item.object_title,
					})
				: Object.assign(document.createElement('span'), { textContent: item.object_title });
			title.className = 'sillage-rank__title';
			main.append(badge, title);
		} else {
			const name = document.createElement('span');
			name.className = 'sillage-rank__title';
			name.textContent = item.user_nicename;
			const email = document.createElement('span');
			email.className = 'sillage-rank__sub';
			email.textContent = item.user_email;
			main.append(name, email);
		}

		const count = document.createElement('span');
		count.className = 'sillage-rank__count';
		count.textContent = formatNumber(item.visits, document.documentElement.lang);

		li.append(rank, main, count);
		root.appendChild(li);
	});
}

function chartDefaults() {
	Chart.defaults.font.family =
		'-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
	Chart.defaults.font.size = 12;
	Chart.defaults.color = '#64748b';
	Chart.defaults.plugins.legend.labels.boxWidth = 10;
	Chart.defaults.plugins.legend.labels.boxHeight = 10;
	Chart.defaults.plugins.legend.labels.usePointStyle = true;
	Chart.defaults.plugins.legend.labels.pointStyle = 'circle';
}

/**
 * @param {object} cfg window.sillageAdmin
 * @param {{ from: import('flatpickr').Instance, to: import('flatpickr').Instance }} datePickers
 * @param {() => { user_id: string, object_id: string, date_from: string, date_to: string }} currentFilters
 */
export function initAnalytics(cfg, datePickers, currentFilters) {
	const i18n = cfg.i18n || {};
	const locale = cfg.locale || undefined;
	const root = document.getElementById('sillage-analytics');
	if (!root) {
		return;
	}

	chartDefaults();

	const loading = document.getElementById('sillage-analytics-loading');
	const empty = document.getElementById('sillage-analytics-empty');
	const error = document.getElementById('sillage-analytics-error');
	const body = document.getElementById('sillage-analytics-body');
	const seriesCanvas = document.getElementById('sillage-chart-series');
	const typesCanvas = document.getElementById('sillage-chart-types');
	const seriesMeta = root.querySelector('[data-series-meta]');

	let seriesChart;
	let typesChart;
	let loadSeq = 0;

	function applyDefaultDates() {
		const from = (cfg.defaults && cfg.defaults.date_from) || '';
		const to = (cfg.defaults && cfg.defaults.date_to) || '';
		if (from) {
			datePickers.from.setDate(from, false, 'Y-m-d');
		}
		if (to) {
			datePickers.to.setDate(to, false, 'Y-m-d');
		}
	}

	function applyPreset(days) {
		const to = (cfg.defaults && cfg.defaults.date_to) || '';
		if (!to) {
			return;
		}
		const parts = to.split('-').map(Number);
		const dt = new Date(Date.UTC(parts[0], parts[1] - 1, parts[2]));
		dt.setUTCDate(dt.getUTCDate() - (days - 1));
		const from =
			dt.getUTCFullYear() +
			'-' +
			String(dt.getUTCMonth() + 1).padStart(2, '0') +
			'-' +
			String(dt.getUTCDate()).padStart(2, '0');
		datePickers.from.setDate(from, false, 'Y-m-d');
		datePickers.to.setDate(to, false, 'Y-m-d');
		load();
	}

	function paintSeries(payload) {
		const buckets = payload.series.buckets || [];
		const labels = buckets.map((b) => formatBucket(b.bucket, payload.series.granularity, locale));
		const visits = buckets.map((b) => b.visits);
		const users = buckets.map((b) => b.unique_users);
		const ctx = seriesCanvas.getContext('2d');
		const gradient = ctx.createLinearGradient(0, 0, 0, 280);
		gradient.addColorStop(0, 'rgba(37, 99, 235, 0.28)');
		gradient.addColorStop(1, 'rgba(37, 99, 235, 0.02)');

		if (seriesMeta) {
			seriesMeta.textContent =
				payload.series.granularity === 'hour' ? i18n.byHour || 'Hourly' : i18n.byDay || 'Daily';
		}

		const data = {
			labels,
			datasets: [
				{
					label: i18n.visits || 'Visits',
					data: visits,
					borderColor: '#2563eb',
					backgroundColor: gradient,
					fill: true,
					tension: 0.35,
					borderWidth: 2.25,
					pointRadius: buckets.length > 40 ? 0 : 3,
					pointHoverRadius: 5,
					pointBackgroundColor: '#fff',
					pointBorderColor: '#2563eb',
					pointBorderWidth: 2,
					yAxisID: 'y',
				},
				{
					label: i18n.uniqueUsers || 'Unique users',
					data: users,
					borderColor: '#7c3aed',
					backgroundColor: 'transparent',
					fill: false,
					tension: 0.35,
					borderWidth: 2,
					borderDash: [4, 4],
					pointRadius: buckets.length > 40 ? 0 : 2.5,
					pointHoverRadius: 5,
					pointBackgroundColor: '#fff',
					pointBorderColor: '#7c3aed',
					pointBorderWidth: 2,
					yAxisID: 'y',
				},
			],
		};

		const options = {
			responsive: true,
			maintainAspectRatio: false,
			interaction: { mode: 'index', intersect: false },
			plugins: {
				legend: { position: 'top', align: 'end' },
				tooltip: {
					backgroundColor: '#0f172a',
					titleColor: '#f8fafc',
					bodyColor: '#e2e8f0',
					padding: 10,
					cornerRadius: 8,
					displayColors: true,
				},
			},
			scales: {
				x: {
					grid: { display: false },
					ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 },
					border: { display: false },
				},
				y: {
					beginAtZero: true,
					grace: '8%',
					ticks: { precision: 0 },
					grid: { color: 'rgba(15, 23, 42, 0.06)' },
					border: { display: false },
				},
			},
		};

		if (seriesChart) {
			seriesChart.data = data;
			seriesChart.options = options;
			seriesChart.update();
			return;
		}

		seriesChart = new Chart(seriesCanvas, { type: 'line', data, options });
	}

	function paintTypes(payload) {
		const rows = payload.by_type || [];
		const data = {
			labels: rows.map((r) => r.label),
			datasets: [
				{
					data: rows.map((r) => r.visits),
					backgroundColor: rows.map((_, i) => TYPE_COLORS[i % TYPE_COLORS.length]),
					borderWidth: 0,
					hoverOffset: 6,
				},
			],
		};
		const options = {
			responsive: true,
			maintainAspectRatio: false,
			cutout: '62%',
			plugins: {
				legend: {
					position: 'bottom',
					labels: { padding: 14 },
				},
				tooltip: {
					backgroundColor: '#0f172a',
					padding: 10,
					cornerRadius: 8,
				},
			},
		};

		if (typesChart) {
			typesChart.data = data;
			typesChart.update();
			return;
		}

		typesChart = new Chart(typesCanvas, { type: 'doughnut', data, options });
	}

	function paintKpis(kpis) {
		root.querySelector('[data-kpi="visits"]').textContent = formatNumber(kpis.visits, locale);
		root.querySelector('[data-kpi="unique_users"]').textContent = formatNumber(
			kpis.unique_users,
			locale
		);
		root.querySelector('[data-kpi="unique_contents"]').textContent = formatNumber(
			kpis.unique_contents,
			locale
		);
		root.querySelector('[data-kpi="avg_duration"]').textContent = formatDuration(
			kpis.avg_duration_seconds,
			i18n
		);
	}

	function load() {
		const seq = ++loadSeq;
		setHidden(error, true);
		setHidden(empty, true);
		setHidden(body, true);
		setHidden(loading, false);

		const url = new URL(cfg.restUrl + 'stats');
		const filters = currentFilters();
		Object.keys(filters).forEach((key) => {
			if (filters[key]) {
				url.searchParams.set(key, filters[key]);
			}
		});

		fetch(url.toString(), {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': cfg.restNonce },
		})
			.then((response) => {
				if (!response.ok) {
					throw new Error('bad status');
				}
				return response.json();
			})
			.then((payload) => {
				if (seq !== loadSeq) {
					return;
				}
				setHidden(loading, true);
				if (!payload.kpis || payload.kpis.visits === 0) {
					setHidden(empty, false);
					return;
				}
				paintKpis(payload.kpis);
				paintSeries(payload);
				paintTypes(payload);
				renderRankList(
					document.getElementById('sillage-top-contents'),
					payload.top_contents || [],
					'content',
					i18n
				);
				renderRankList(
					document.getElementById('sillage-top-users'),
					payload.top_users || [],
					'user',
					i18n
				);
				setHidden(body, false);
			})
			.catch(() => {
				if (seq !== loadSeq) {
					return;
				}
				setHidden(loading, true);
				setHidden(error, false);
			});
	}

	applyDefaultDates();

	$('#sillage-filter-apply').on('click', load);
	$('#sillage-filter-reset').on('click', function () {
		$('#sillage-filter-user').val(null).trigger('change');
		$('#sillage-filter-content').val(null).trigger('change');
		applyDefaultDates();
		load();
	});
	root.querySelectorAll('.sillage-preset').forEach((btn) => {
		btn.addEventListener('click', function () {
			applyPreset(parseInt(btn.getAttribute('data-days'), 10) || 30);
		});
	});

	load();
}
