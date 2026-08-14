<?php
/**
 * Visit log admin view (DataTables + filters).
 *
 * @package    Sillage
 * @subpackage Sillage/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sillage-wrap">
	<h1><?php echo esc_html__( 'Sillage visit log', 'sillage' ); ?></h1>
	<p class="description">
		<?php echo esc_html__( 'Front-office visits by logged-in users. Exit dates are approximate: they are sent when the browser tab is hidden or closed, and may be missing.', 'sillage' ); ?>
	</p>

	<div class="sil-flex sil-flex-wrap sil-items-end sil-gap-3 sil-mb-4 sil-mt-4">
		<div>
			<label for="sillage-filter-user" class="sil-block sil-mb-1 sil-font-medium"><?php echo esc_html__( 'User', 'sillage' ); ?></label>
			<select id="sillage-filter-user" class="sil-min-w-64"><option></option></select>
		</div>
		<div>
			<label for="sillage-filter-content" class="sil-block sil-mb-1 sil-font-medium"><?php echo esc_html__( 'Content', 'sillage' ); ?></label>
			<select id="sillage-filter-content" class="sil-min-w-64"><option></option></select>
		</div>
		<div>
			<label for="sillage-filter-from" class="sil-block sil-mb-1 sil-font-medium"><?php echo esc_html__( 'From', 'sillage' ); ?></label>
			<input type="text" id="sillage-filter-from" class="sillage-datepicker sil-border sil-border-gray-300 sil-rounded sil-px-2 sil-py-1 sil-w-36" autocomplete="off" />
		</div>
		<div>
			<label for="sillage-filter-to" class="sil-block sil-mb-1 sil-font-medium"><?php echo esc_html__( 'To', 'sillage' ); ?></label>
			<input type="text" id="sillage-filter-to" class="sillage-datepicker sil-border sil-border-gray-300 sil-rounded sil-px-2 sil-py-1 sil-w-36" autocomplete="off" />
		</div>
		<div class="sil-flex sil-gap-2">
			<button type="button" id="sillage-filter-apply" class="button button-primary"><?php echo esc_html__( 'Apply filters', 'sillage' ); ?></button>
			<button type="button" id="sillage-filter-reset" class="button"><?php echo esc_html__( 'Reset', 'sillage' ); ?></button>
		</div>
	</div>

	<div class="sil-flex sil-gap-2 sil-mb-4">
		<button type="button" class="button sillage-export" data-format="csv"><?php echo esc_html__( 'Export CSV', 'sillage' ); ?></button>
		<button type="button" class="button sillage-export" data-format="xlsx"><?php echo esc_html__( 'Export Excel', 'sillage' ); ?></button>
		<button type="button" class="button sillage-export" data-format="pdf"><?php echo esc_html__( 'Export PDF', 'sillage' ); ?></button>
	</div>

	<table id="sillage-logs" class="display" style="width:100%">
		<thead>
			<tr>
				<th><?php echo esc_html__( 'User', 'sillage' ); ?></th>
				<th><?php echo esc_html__( 'Email', 'sillage' ); ?></th>
				<th><?php echo esc_html__( 'IP address', 'sillage' ); ?></th>
				<th><?php echo esc_html__( 'Content', 'sillage' ); ?></th>
				<th><?php echo esc_html__( 'Entry date', 'sillage' ); ?></th>
				<th><?php echo esc_html__( 'Exit date', 'sillage' ); ?></th>
			</tr>
		</thead>
	</table>
</div>
