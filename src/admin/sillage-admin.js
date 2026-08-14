import './sillage-admin.css';
import $ from 'jquery';
import 'select2';
import DataTable from 'datatables.net-dt';

function escapeHtml(value) {
	const div = document.createElement('div');
	div.textContent = value == null ? '' : String(value);
	return div.innerHTML;
}

function currentFilters() {
	return {
		user_id: $('#sillage-filter-user').val() || '',
		object_id: $('#sillage-filter-content').val() || '',
		date_from: $('#sillage-filter-from').val() || '',
		date_to: $('#sillage-filter-to').val() || '',
	};
}

function initSelect2(selector, endpoint, placeholder) {
	const cfg = window.sillageAdmin;

	$(selector).select2({
		allowClear: true,
		placeholder,
		width: 'style',
		ajax: {
			url: cfg.restUrl + endpoint,
			dataType: 'json',
			delay: 250,
			headers: {
				'X-WP-Nonce': cfg.restNonce,
			},
			data(params) {
				return { search: params.term || '' };
			},
			processResults(data) {
				return { results: data.results || [] };
			},
		},
		minimumInputLength: 2,
	});
}

$(function () {
	const cfg = window.sillageAdmin;
	if (!cfg || !document.getElementById('sillage-logs')) {
		return;
	}

	const i18n = cfg.i18n || {};

	initSelect2('#sillage-filter-user', 'autocomplete/users', i18n.placeholderUser);
	initSelect2('#sillage-filter-content', 'autocomplete/pages', i18n.placeholderContent);

	const table = new DataTable('#sillage-logs', {
		serverSide: true,
		processing: true,
		searching: false,
		ordering: true,
		pageLength: 25,
		order: [[4, 'desc']],
		ajax(data, callback) {
			const url = new URL(cfg.restUrl + 'logs');
			url.searchParams.set('draw', String(data.draw));
			url.searchParams.set('start', String(data.start));
			url.searchParams.set('length', String(data.length));

			if (data.order && data.order[0]) {
				url.searchParams.set('order_column', String(data.order[0].column));
				url.searchParams.set('order_dir', data.order[0].dir);
			}

			const filters = currentFilters();
			Object.keys(filters).forEach((key) => {
				if (filters[key]) {
					url.searchParams.set(key, filters[key]);
				}
			});

			fetch(url.toString(), {
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': cfg.restNonce,
				},
			})
				.then((response) => response.json())
				.then((json) => callback(json))
				.catch(() => {
					callback({
						draw: data.draw,
						recordsTotal: 0,
						recordsFiltered: 0,
						data: [],
					});
				});
		},
		columns: [
			{ data: 'user_nicename' },
			{ data: 'user_email' },
			{
				data: 'ip_address',
				render(data, type, row) {
					if (type !== 'display') {
						return data;
					}
					const a = document.createElement('a');
					a.href = row.ip_lookup_url;
					a.target = '_blank';
					a.rel = 'noopener noreferrer';
					a.textContent = data;
					return a.outerHTML;
				},
			},
			{
				data: 'object_title',
				render(data, type, row) {
					if (type !== 'display') {
						return data;
					}
					return (
						'<span class="sil-badge">' +
						escapeHtml(row.object_type_label) +
						'</span>' +
						escapeHtml(data)
					);
				},
			},
			{ data: 'entry_date_display' },
			{
				data: 'exit_date_display',
				render(data, type) {
					if (type !== 'display') {
						return data;
					}
					return data ? escapeHtml(data) : escapeHtml(i18n.inProgress || '—');
				},
			},
		],
		language: {
			processing: i18n.processing,
			zeroRecords: i18n.zeroRecords,
			emptyTable: i18n.emptyTable,
			lengthMenu: i18n.lengthMenu,
			info: i18n.info,
			infoEmpty: i18n.infoEmpty,
			infoFiltered: i18n.infoFiltered,
			paginate: {
				first: i18n.paginateFirst,
				last: i18n.paginateLast,
				next: i18n.paginateNext,
				previous: i18n.paginatePrev,
			},
		},
	});

	$('#sillage-filter-apply').on('click', function () {
		table.ajax.reload();
	});

	$('#sillage-filter-reset').on('click', function () {
		$('#sillage-filter-user').val(null).trigger('change');
		$('#sillage-filter-content').val(null).trigger('change');
		$('#sillage-filter-from').val('');
		$('#sillage-filter-to').val('');
		table.ajax.reload();
	});

	$('.sillage-export').on('click', function () {
		const format = $(this).data('format');
		const params = new URLSearchParams({
			action: 'sillage_export',
			_wpnonce: cfg.exportNonce,
			format,
		});
		const filters = currentFilters();
		Object.keys(filters).forEach((key) => {
			if (filters[key]) {
				params.set(key, filters[key]);
			}
		});
		window.location.href = cfg.exportUrl + '?' + params.toString();
	});
});
