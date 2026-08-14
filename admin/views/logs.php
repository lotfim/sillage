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

	<?php
	$sillage_show_export = true;
	require SILLAGE_PLUGIN_DIR . 'admin/views/partials/filters.php';
	?>

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
