import './sillage-admin.css';
import $ from 'jquery';
import select2 from 'select2';
import DataTable from 'datatables.net-dt';
import flatpickr from 'flatpickr';
import { French } from 'flatpickr/dist/l10n/fr.js';

// Select2's CJS build exports a factory; a side-effect import does not attach $.fn.select2.
if (typeof select2 === 'function' && !$.fn.select2) {
	select2(window, $);
}

const flatpickrLocales = {
	fr: French,
};

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

/**
 * Date filters: store Y-m-d, display per WordPress user locale.
 *
 * @returns {{ from: import('flatpickr').Instance, to: import('flatpickr').Instance }}
 */
function initDatePickers(datePicker) {
	const altFormat = (datePicker && datePicker.altFormat) || 'd/m/Y';
	const placeholder = (datePicker && datePicker.placeholder) || 'dd/mm/yyyy';
	const localeKey = datePicker && datePicker.locale ? datePicker.locale : 'en';
	const locale = flatpickrLocales[localeKey] || undefined;

	const shared = {
		dateFormat: 'Y-m-d',
		altInput: true,
		altFormat,
		allowInput: true,
		disableMobile: true,
		// Avoid WP-admin <select> styles crushing the month dropdown.
		monthSelectorType: 'static',
		locale,
	};

	const fromEl = document.getElementById('sillage-filter-from');
	const toEl = document.getElementById('sillage-filter-to');

	if (fromEl) {
		fromEl.setAttribute('placeholder', placeholder);
	}
	if (toEl) {
		toEl.setAttribute('placeholder', placeholder);
	}

	const fpTo = flatpickr('#sillage-filter-to', shared);
	const fpFrom = flatpickr('#sillage-filter-from', {
		...shared,
		onChange(selectedDates) {
			fpTo.set('minDate', selectedDates[0] || null);
		},
	});

	fpTo.config.onChange.push(function (selectedDates) {
		fpFrom.set('maxDate', selectedDates[0] || null);
	});

	// Flatpickr copies classes to altInput; ensure placeholder is visible there too.
	[fpFrom, fpTo].forEach((fp) => {
		if (fp.altInput) {
			fp.altInput.setAttribute('placeholder', placeholder);
		}
	});

	return { from: fpFrom, to: fpTo };
}

$(function () {
	const cfg = window.sillageAdmin;
	if (!cfg || !document.getElementById('sillage-logs')) {
		return;
	}

	const i18n = cfg.i18n || {};
	const datePickers = initDatePickers(cfg.datePicker || {});

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
					const badge =
						'<span class="sil-badge">' +
						escapeHtml(row.object_type_label) +
						'</span>';
					const title = escapeHtml(data);
					if (row.object_url) {
						const a = document.createElement('a');
						a.href = row.object_url;
						a.target = '_blank';
						a.rel = 'noopener noreferrer';
						a.textContent = data;
						a.title = i18n.viewContent || 'View content';
						return badge + a.outerHTML;
					}
					return badge + title;
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
		datePickers.from.clear();
		datePickers.to.clear();
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
