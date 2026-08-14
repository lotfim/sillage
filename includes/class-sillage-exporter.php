<?php
/**
 * Filtered log exports.
 *
 * @package    Sillage
 * @subpackage Sillage/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-sillage-export-format.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Streams CSV / Excel / PDF of the currently filtered log dataset.
 *
 * @since 1.0.0
 */
class Sillage_Exporter {

	/**
	 * Rows per query batch.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private const CHUNK = 500;

	/**
	 * Stream an export and exit.
	 *
	 * @since 1.0.0
	 * @param Sillage_Export_Format $format  Format.
	 * @param array<string, mixed>  $filters Sanitized filters.
	 * @return void
	 */
	public static function stream( Sillage_Export_Format $format, array $filters ): void {
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		@set_time_limit( 120 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- shared hosting may forbid this.

		if ( Sillage_Export_Format::Csv !== $format ) {
			sillage_load_vendor();
		}

		match ( $format ) {
			Sillage_Export_Format::Csv  => self::stream_csv( $filters ),
			Sillage_Export_Format::Xlsx => self::stream_xlsx( $filters ),
			Sillage_Export_Format::Pdf  => self::stream_pdf( $filters ),
		};
	}

	/**
	 * Column headers.
	 *
	 * @since 1.0.0
	 * @return array<int, string>
	 */
	private static function headers(): array {
		return array(
			__( 'User', 'sillage' ),
			__( 'Email', 'sillage' ),
			__( 'IP address', 'sillage' ),
			__( 'Content', 'sillage' ),
			__( 'Type', 'sillage' ),
			__( 'Entry date', 'sillage' ),
			__( 'Exit date', 'sillage' ),
		);
	}

	/**
	 * Map a DB row to export cells.
	 *
	 * @since 1.0.0
	 * @param object $row Log row.
	 * @return array<int, string>
	 */
	private static function cells( object $row ): array {
		$type_obj = get_post_type_object( $row->object_type );
		$label    = $type_obj ? $type_obj->labels->singular_name : $row->object_type;

		return array(
			(string) $row->user_nicename,
			(string) $row->user_email,
			(string) $row->ip_address,
			(string) $row->object_title,
			(string) $label,
			(string) $row->entry_date,
			$row->exit_date ? (string) $row->exit_date : '',
		);
	}

	/**
	 * Stream CSV.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Filters.
	 * @return void
	 */
	private static function stream_csv( array $filters ): void {
		$filename = self::filename( 'csv' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		$out = fopen( 'php://output', 'w' );

		if ( false === $out ) {
			wp_die( esc_html__( 'Unable to start the export.', 'sillage' ) );
		}

		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- stream download.
		fputcsv( $out, self::headers() );

		$offset = 0;

		while ( true ) {
			$rows = Sillage_Query::get_rows( $filters, $offset, self::CHUNK, 'entry_date', 'DESC' );

			if ( array() === $rows ) {
				break;
			}

			foreach ( $rows as $row ) {
				fputcsv( $out, self::cells( $row ) );
			}

			$offset += self::CHUNK;
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- php://output stream.
		exit;
	}

	/**
	 * Stream Excel.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Filters.
	 * @return void
	 */
	private static function stream_xlsx( array $filters ): void {
		if ( ! class_exists( Spreadsheet::class ) ) {
			wp_die( esc_html__( 'Excel export requires Composer dependencies (PhpSpreadsheet).', 'sillage' ) );
		}

		$spreadsheet = new Spreadsheet();
		$sheet       = $spreadsheet->getActiveSheet();
		$sheet->setTitle( __( 'Visit log', 'sillage' ) );

		$col = 1;
		foreach ( self::headers() as $header ) {
			$sheet->setCellValue( Coordinate::stringFromColumnIndex( $col ) . '1', $header );
			++$col;
		}

		$excel_row = 2;
		$offset    = 0;

		while ( true ) {
			$rows = Sillage_Query::get_rows( $filters, $offset, self::CHUNK, 'entry_date', 'DESC' );

			if ( array() === $rows ) {
				break;
			}

			foreach ( $rows as $row ) {
				$col = 1;
				foreach ( self::cells( $row ) as $value ) {
					$sheet->setCellValue( Coordinate::stringFromColumnIndex( $col ) . (string) $excel_row, $value );
					++$col;
				}
				++$excel_row;
			}

			$offset += self::CHUNK;
		}

		$tmp    = wp_tempnam( 'sillage-xlsx' );
		$writer = new Xlsx( $spreadsheet );
		$writer->save( $tmp );
		$spreadsheet->disconnectWorksheets();

		$filename = self::filename( 'xlsx' );

		nocache_headers();
		header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . (string) filesize( $tmp ) );

		readfile( $tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- stream download.
		wp_delete_file( $tmp );
		exit;
	}

	/**
	 * Stream PDF.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Filters.
	 * @return void
	 */
	private static function stream_pdf( array $filters ): void {
		if ( ! class_exists( Dompdf::class ) ) {
			wp_die( esc_html__( 'PDF export requires Composer dependencies (DomPDF).', 'sillage' ) );
		}

		$html  = '<html><head><meta charset="utf-8"><style>';
		$html .= 'body{font-family:DejaVu Sans,sans-serif;font-size:10px;}';
		$html .= 'h1{font-size:16px;margin:0 0 4px;} .subtitle{font-size:12px;margin:0 0 8px;color:#444;}';
		$html .= '.meta,.filters{margin:0 0 10px;} .filters{padding:6px 8px;background:#f3f4f6;border:1px solid #e5e7eb;}';
		$html .= 'table{width:100%;border-collapse:collapse;}';
		$html .= 'th,td{border:1px solid #ccc;padding:4px;text-align:left;} th{background:#f3f4f6;}';
		$html .= '</style></head><body>';

		$company = Sillage_Settings::pdf_company_name();
		$heading = __( 'Sillage visit log', 'sillage' );

		if ( '' !== $company ) {
			$html .= '<h1>' . esc_html( $company ) . '</h1>';
			$html .= '<p class="subtitle">' . esc_html( $heading ) . '</p>';
		} else {
			$html .= '<h1>' . esc_html( $heading ) . '</h1>';
		}

		$html .= '<p class="meta">' . esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ) . '</p>';

		if ( Sillage_Settings::pdf_show_filters() ) {
			$html .= '<p class="filters"><strong>' . esc_html__( 'Filters', 'sillage' ) . '</strong><br>';
			foreach ( self::filter_labels( $filters ) as $line ) {
				$html .= esc_html( $line ) . '<br>';
			}
			$html .= '</p>';
		}

		$html .= '<table><thead><tr>';

		foreach ( self::headers() as $header ) {
			$html .= '<th>' . esc_html( $header ) . '</th>';
		}

		$html .= '</tr></thead><tbody>';

		$offset = 0;

		while ( true ) {
			$rows = Sillage_Query::get_rows( $filters, $offset, self::CHUNK, 'entry_date', 'DESC' );

			if ( array() === $rows ) {
				break;
			}

			foreach ( $rows as $row ) {
				$html .= '<tr>';
				foreach ( self::cells( $row ) as $value ) {
					$html .= '<td>' . esc_html( $value ) . '</td>';
				}
				$html .= '</tr>';
			}

			$offset += self::CHUNK;
		}

		$html .= '</tbody></table></body></html>';

		$options = new Options();
		$options->set( 'isRemoteEnabled', false );
		$options->set( 'defaultFont', 'DejaVu Sans' );

		$dompdf = new Dompdf( $options );
		$dompdf->loadHtml( $html );
		$dompdf->setPaper( 'A4', 'landscape' );
		$dompdf->render();

		$filename = self::filename( 'pdf' );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		echo $dompdf->output(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- binary PDF.
		exit;
	}

	/**
	 * Human-readable filter summary for the PDF header.
	 *
	 * @since 1.0.0
	 * @param array<string, mixed> $filters Sanitized filters.
	 * @return array<int, string>
	 */
	private static function filter_labels( array $filters ): array {
		$lines = array();

		if ( ! empty( $filters['user_id'] ) ) {
			$user    = get_userdata( (int) $filters['user_id'] );
			$label   = $user ? $user->user_nicename . ' (' . $user->user_email . ')' : (string) (int) $filters['user_id'];
			$lines[] = __( 'User', 'sillage' ) . ': ' . $label;
		}

		if ( ! empty( $filters['object_id'] ) ) {
			$title   = get_the_title( (int) $filters['object_id'] );
			$label   = '' !== $title ? $title : (string) (int) $filters['object_id'];
			$lines[] = __( 'Content', 'sillage' ) . ': ' . $label;
		}

		if ( ! empty( $filters['date_from'] ) ) {
			$lines[] = __( 'From', 'sillage' ) . ': ' . (string) $filters['date_from'];
		}

		if ( ! empty( $filters['date_to'] ) ) {
			$lines[] = __( 'To', 'sillage' ) . ': ' . (string) $filters['date_to'];
		}

		if ( array() === $lines ) {
			$lines[] = __( 'No filters applied', 'sillage' );
		}

		return $lines;
	}

	/**
	 * Download filename.
	 *
	 * @since 1.0.0
	 * @param string $ext Extension.
	 * @return string
	 */
	private static function filename( string $ext ): string {
		return 'sillage-logs-' . gmdate( 'Y-m-d' ) . '.' . $ext;
	}
}
