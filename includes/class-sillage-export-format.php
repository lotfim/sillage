<?php
/**
 * Export format enum.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Supported export formats.
 *
 * @since 1.0.0
 */
enum Sillage_Export_Format: string {
	case Csv  = 'csv';
	case Xlsx = 'xlsx';
	case Pdf  = 'pdf';
}
