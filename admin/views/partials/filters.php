<?php
/**
 * Shared admin filter bar (visit log + analytics).
 *
 * @package    Sillage
 * @subpackage Sillage/admin/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sillage_show_export = ! empty( $sillage_show_export );
?>
<div class="sillage-filters sil-flex sil-flex-wrap sil-items-end sil-gap-3 sil-mb-4 sil-mt-4">
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
	<div class="sil-flex sil-gap-2 sil-flex-wrap">
		<button type="button" id="sillage-filter-apply" class="button button-primary"><?php echo esc_html__( 'Apply filters', 'sillage' ); ?></button>
		<button type="button" id="sillage-filter-reset" class="button"><?php echo esc_html__( 'Reset', 'sillage' ); ?></button>
	</div>
</div>
<?php if ( $sillage_show_export ) : ?>
	<div class="sil-flex sil-gap-2 sil-mb-4">
		<button type="button" class="button sillage-export" data-format="csv"><?php echo esc_html__( 'Export CSV', 'sillage' ); ?></button>
		<button type="button" class="button sillage-export" data-format="xlsx"><?php echo esc_html__( 'Export Excel', 'sillage' ); ?></button>
		<button type="button" class="button sillage-export" data-format="pdf"><?php echo esc_html__( 'Export PDF', 'sillage' ); ?></button>
	</div>
<?php endif; ?>
