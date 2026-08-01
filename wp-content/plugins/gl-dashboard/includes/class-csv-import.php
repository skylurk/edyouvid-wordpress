<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GLD_CSV_Import {

	/**
	 * Parse an uploaded CSV or XLSX file into an array of normalised rows.
	 * Each row: [ 'email' => string, 'first' => string, 'last' => string ]
	 *
	 * @return array|WP_Error
	 */
	public static function parse( string $file_path, string $mime_type ) {
		$ext = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );

		$is_xlsx = $ext === 'xlsx'
			|| $mime_type === 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
			|| $mime_type === 'application/zip';   // some browsers send zip mime for xlsx

		if ( $is_xlsx ) {
			return self::parse_xlsx( $file_path );
		}

		// Treat everything else as CSV (text/csv, text/plain, application/octet-stream).
		return self::parse_csv( $file_path );
	}

	// ── CSV ──────────────────────────────────────────────────────────────────

	private static function parse_csv( string $path ) {
		$handle = fopen( $path, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'read_error', 'Could not open the uploaded file.' );
		}

		// Strip UTF-8 BOM if present.
		$bom = fread( $handle, 3 );
		if ( $bom !== "\xEF\xBB\xBF" ) {
			rewind( $handle );
		}

		$headers = null;
		$rows    = [];

		while ( ( $line = fgetcsv( $handle ) ) !== false ) {
			if ( array_filter( $line, fn( $c ) => $c !== null && $c !== '' ) === [] ) {
				continue; // skip blank rows
			}

			if ( $headers === null ) {
				$headers = array_map( fn( $h ) => strtolower( trim( (string) $h ) ), $line );
				continue;
			}

			if ( count( $line ) < count( $headers ) ) {
				$line = array_pad( $line, count( $headers ), '' );
			}

			$row    = array_combine( $headers, array_slice( $line, 0, count( $headers ) ) );
			$rows[] = self::normalise_row( $row );
		}

		fclose( $handle );
		return $rows;
	}

	// ── XLSX ─────────────────────────────────────────────────────────────────

	private static function parse_xlsx( string $path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'missing_ext', 'The ZipArchive PHP extension is required to parse XLSX files. Please upload a CSV instead.' );
		}

		$zip = new ZipArchive();
		if ( $zip->open( $path ) !== true ) {
			return new WP_Error( 'read_error', 'Could not open the XLSX file. It may be corrupt or not a valid Excel file.' );
		}

		// Load shared strings (all string cell values live here).
		$shared_strings = [];
		$ss_xml         = $zip->getFromName( 'xl/sharedStrings.xml' );
		if ( $ss_xml ) {
			$ss = @simplexml_load_string( $ss_xml );
			if ( $ss ) {
				foreach ( $ss->si as $si ) {
					if ( isset( $si->t ) ) {
						$shared_strings[] = (string) $si->t;
					} elseif ( isset( $si->r ) ) {
						$text = '';
						foreach ( $si->r as $r ) {
							$text .= (string) $r->t;
						}
						$shared_strings[] = $text;
					} else {
						$shared_strings[] = '';
					}
				}
			}
		}

		// Find the first worksheet (may be sheet1.xml or referenced differently).
		$sheet_xml = $zip->getFromName( 'xl/worksheets/sheet1.xml' );
		if ( ! $sheet_xml ) {
			// Try to resolve the actual sheet path from the workbook relationships.
			$rels_xml = $zip->getFromName( 'xl/_rels/workbook.xml.rels' );
			if ( $rels_xml ) {
				$rels = @simplexml_load_string( $rels_xml );
				if ( $rels ) {
					foreach ( $rels->Relationship as $rel ) {
						if ( strpos( (string) $rel['Type'], 'worksheet' ) !== false ) {
							$target   = (string) $rel['Target'];
							$sheet_xml = $zip->getFromName( 'xl/' . ltrim( $target, '/' ) );
							if ( $sheet_xml ) break;
						}
					}
				}
			}
		}

		$zip->close();

		if ( ! $sheet_xml ) {
			return new WP_Error( 'read_error', 'Could not locate a worksheet in the XLSX file.' );
		}

		$sheet = @simplexml_load_string( $sheet_xml );
		if ( ! $sheet ) {
			return new WP_Error( 'parse_error', 'Could not parse the XLSX worksheet.' );
		}

		// Build a 2-D grid from the sparse cell references.
		$grid    = [];
		$max_col = 0;

		foreach ( $sheet->sheetData->row as $xlsx_row ) {
			$row_idx = (int) $xlsx_row['r'] - 1;
			$cells   = [];

			foreach ( $xlsx_row->c as $cell ) {
				$ref     = (string) $cell['r'];
				$col_ref = rtrim( $ref, '0123456789' );
				$col_idx = self::col_letter_to_index( $col_ref );
				$type    = (string) $cell['t'];
				$value   = isset( $cell->v ) ? (string) $cell->v : '';

				if ( $type === 's' ) {
					$value = $shared_strings[ (int) $value ] ?? '';
				}

				$cells[ $col_idx ] = $value;
				if ( $col_idx > $max_col ) {
					$max_col = $col_idx;
				}
			}

			for ( $i = 0; $i <= $max_col; $i++ ) {
				if ( ! isset( $cells[ $i ] ) ) {
					$cells[ $i ] = '';
				}
			}
			ksort( $cells );
			$grid[ $row_idx ] = array_values( $cells );
		}

		if ( empty( $grid ) || ! isset( $grid[0] ) ) {
			return [];
		}

		$header_row = array_map( fn( $h ) => strtolower( trim( (string) $h ) ), $grid[0] );
		$rows       = [];

		for ( $i = 1, $max = max( array_keys( $grid ) ); $i <= $max; $i++ ) {
			if ( ! isset( $grid[ $i ] ) ) {
				continue;
			}
			$line = $grid[ $i ];
			if ( count( $line ) < count( $header_row ) ) {
				$line = array_pad( $line, count( $header_row ), '' );
			}
			$row    = array_combine( $header_row, array_slice( $line, 0, count( $header_row ) ) );
			$rows[] = self::normalise_row( $row );
		}

		return $rows;
	}

	// ── Helpers ──────────────────────────────────────────────────────────────

	/**
	 * Accept flexible column names and return a standard [ email, first, last ] shape.
	 * Supported column names: email | first_name / firstname / first name | last_name / lastname / last name / surname | name
	 */
	private static function normalise_row( array $row ): array {
		$email = trim( $row['email'] ?? '' );

		$first = trim(
			$row['first_name'] ??
			$row['firstname']  ??
			$row['first name'] ??
			''
		);
		$last = trim(
			$row['last_name']  ??
			$row['lastname']   ??
			$row['last name']  ??
			$row['surname']    ??
			''
		);

		// If a combined "name" column exists and first/last are both empty, split it.
		if ( $first === '' && $last === '' && ! empty( $row['name'] ) ) {
			$parts = explode( ' ', trim( (string) $row['name'] ), 2 );
			$first = $parts[0] ?? '';
			$last  = $parts[1] ?? '';
		}

		return compact( 'email', 'first', 'last' );
	}

	/** Convert an Excel column letter (A, B, …, Z, AA, …) to a 0-based index. */
	private static function col_letter_to_index( string $col ): int {
		$col   = strtoupper( $col );
		$index = 0;
		$len   = strlen( $col );
		for ( $i = 0; $i < $len; $i++ ) {
			$index = $index * 26 + ( ord( $col[ $i ] ) - ord( 'A' ) + 1 );
		}
		return $index - 1;
	}
}
