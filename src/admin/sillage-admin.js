import './sillage-admin.css';
import $ from 'jquery';
import select2 from 'select2';
import DataTable from 'datatables.net-dt';

// Select2's CJS build exports a factory; a side-effect import does not attach $.fn.select2.
if (typeof select2 === 'function' && !$.fn.select2) {
	select2(window, $);
}

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

function select2Language(i18n) {
	return {
		errorLoading() {
			return i18n.errorLoading || 'The results could not be loaded.';
		},
		inputTooShort(args) {
			const remaining = args.minimum - args.input.length;
			return (i18n.inputTooShort || 'Please enter %d or more characters.').replace(
				'%d',
				String(remaining)
			);
		},
		loadingMore() {
			return i18n.loadingMore || 'Loading more results…';
		},
		noResults() {
			return i18n.noResults || 'No results found';
		},
		searching() {
			return i18n.searching || 'Searching…';
		},
		removeAllItems() {
			return i18n.removeAllItems || 'Remove all items';
		},
	};
}

function initSelect2(selector, endpoint, placeholder) {
	const cfg = window.sillageAdmin;
	const i18n = (cfg && cfg.i18n) || {};
	let xhr;

	$(selector).select2({
		allowClear: true,
		placeholder,
		width: '100%',
		dropdownParent: $(document.body),
		language: select2Language(i18n),
		ajax: {
			url: cfg.restUrl + endpoint,
			dataType: 'json',
			delay: 80,
			cache: true,
			headers: {
				'X-WP-Nonce': cfg.restNonce,
			},
			data(params) {
				return { search: params.term || '' };
			},
			processResults(data) {
				return { results: (data && data.results) || [] };
			},
			transport(params, success, failure) {
				if (xhr && xhr.readyState !== 4) {
					xhr.abort();
				}
				xhr = $.ajax(params);
				xhr.then(success);
				xhr.fail(function (jqXHR, textStatus) {
					if (textStatus === 'abort') {
						return;
					}
					failure(jqXHR, textStatus);
				});
				return xhr;
			},
		},
		minimumInputLength: 0,
	});
}

$(function () {
	const cfg = window.sillageAdmin;
	if (!cfg || !document.getElementById('sillage-logs')) {
		return;
	}

	const i18n = cfg.i18n || {};

	if ($.fn.select2) {
		initSelect2('#sillage-filter-user', 'autocomplete/users', i18n.placeholderUser);
		initSelect2('#sillage-filter-content', 'autocomplete/pages', i18n.placeholderContent);
	}

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
