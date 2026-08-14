<?php
/**
 * Settings admin view.
 *
 * @package    Sillage
 * @subpackage Sillage/admin/views
 *
 * @var array<string, mixed> $settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sillage_ip_anon   = ! empty( $settings['ip_anonymization'] );
$sillage_retention = isset( $settings['retention_days'] ) ? (int) $settings['retention_days'] : 90;
$sillage_geoip     = isset( $settings['geoip_base_url'] ) ? (string) $settings['geoip_base_url'] : 'https://ipinfo.io/';
$sillage_company   = isset( $settings['pdf_company_name'] ) ? (string) $settings['pdf_company_name'] : '';
$sillage_pdf_filt  = ! empty( $settings['pdf_show_filters'] );
?>
<div class="wrap sillage-wrap">
	<h1><?php echo esc_html__( 'Sillage settings', 'sillage' ); ?></h1>

	<div class="notice notice-info inline sil-mt-4">
		<p>
			<?php echo esc_html__( 'Sillage stores personal data (IP address, nicename, and email) for logged-in users who visit public content. Update your site privacy policy to disclose this collection. Uninstalling the plugin does not delete existing logs.', 'sillage' ); ?>
		</p>
	</div>

	<form method="post" action="options.php" class="sil-mt-4 sil-max-w-2xl">
		<?php settings_fields( 'sillage_settings_group' ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'IP anonymization', 'sillage' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( Sillage_Settings::OPTION_KEY ); ?>[ip_anonymization]" value="1" <?php checked( $sillage_ip_anon ); ?> />
						<?php echo esc_html__( 'Mask the last octet of IPv4 addresses and the trailing bits of IPv6 addresses (keep /48) before storing.', 'sillage' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="sillage-retention"><?php echo esc_html__( 'Data retention (days)', 'sillage' ); ?></label>
				</th>
				<td>
					<input type="number" min="0" step="1" id="sillage-retention" class="small-text" name="<?php echo esc_attr( Sillage_Settings::OPTION_KEY ); ?>[retention_days]" value="<?php echo esc_attr( (string) $sillage_retention ); ?>" />
					<p class="description">
						<?php echo esc_html__( 'Log rows older than this are deleted daily. Use 0 to never auto-purge.', 'sillage' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="sillage-geoip"><?php echo esc_html__( 'IP geolocation link base URL', 'sillage' ); ?></label>
				</th>
				<td>
					<input type="url" id="sillage-geoip" class="regular-text" name="<?php echo esc_attr( Sillage_Settings::OPTION_KEY ); ?>[geoip_base_url]" value="<?php echo esc_attr( $sillage_geoip ); ?>" />
					<p class="description">
						<?php echo esc_html__( 'Used to build the “locate IP” link in the visit log (opened in a new tab). Default: https://ipinfo.io/', 'sillage' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="sillage-pdf-company"><?php echo esc_html__( 'PDF company name', 'sillage' ); ?></label>
				</th>
				<td>
					<input type="text" id="sillage-pdf-company" class="regular-text" maxlength="191" name="<?php echo esc_attr( Sillage_Settings::OPTION_KEY ); ?>[pdf_company_name]" value="<?php echo esc_attr( $sillage_company ); ?>" />
					<p class="description">
						<?php echo esc_html__( 'Shown as the PDF title. Leave empty to keep the default “Sillage visit log” heading.', 'sillage' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php echo esc_html__( 'PDF filters', 'sillage' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( Sillage_Settings::OPTION_KEY ); ?>[pdf_show_filters]" value="1" <?php checked( $sillage_pdf_filt ); ?> />
						<?php echo esc_html__( 'Print the active filters (user, content, date range) at the top of the PDF.', 'sillage' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
