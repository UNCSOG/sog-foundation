<?php
/**
 * Minimal YAML parser and dumper for plugin preset files.
 *
 * @package SOGUNCRebrand
 */

declare(strict_types=1);

namespace SOG\Rebrand\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Yaml {
	/**
	 * Serialize an array as YAML.
	 *
	 * @param array<string, mixed> $data Structured data.
	 * @return string
	 */
	public static function dump( array $data ): string {
		return rtrim( self::dump_value( $data, 0 ), "\n" ) . "\n";
	}

	/**
	 * Parse YAML into an array.
	 *
	 * @param string $yaml YAML payload.
	 * @return array<string, mixed>
	 */
	public static function parse( string $yaml ): array {
		if ( function_exists( 'yaml_parse' ) ) {
			$parsed = yaml_parse( $yaml );

			return is_array( $parsed ) ? $parsed : array();
		}

		$lines = preg_split( "/\r\n|\n|\r/", $yaml );

		if ( false === $lines ) {
			return array();
		}

		$index  = 0;
		$parsed = self::parse_block( $lines, $index, 0 );

		return is_array( $parsed ) ? $parsed : array();
	}

	/**
	 * Dump a value recursively.
	 *
	 * @param mixed $value Value to serialize.
	 * @param int   $indent Current indentation.
	 * @return string
	 */
	private static function dump_value( $value, int $indent ): string {
		if ( ! is_array( $value ) ) {
			return str_repeat( ' ', $indent ) . self::dump_inline_scalar( $value ) . "\n";
		}

		if ( empty( $value ) ) {
			return str_repeat( ' ', $indent ) . "[]\n";
		}

		$output = '';

		if ( self::is_list( $value ) ) {
			foreach ( $value as $item ) {
				if ( is_array( $item ) ) {
					if ( empty( $item ) ) {
						$output .= str_repeat( ' ', $indent ) . "- []\n";
						continue;
					}

					$output .= str_repeat( ' ', $indent ) . "-\n";
					$output .= self::dump_value( $item, $indent + 2 );
					continue;
				}

				if ( is_string( $item ) && false !== strpos( $item, "\n" ) ) {
					$output .= str_repeat( ' ', $indent ) . "- |-\n";
					$output .= self::dump_multiline_string( $item, $indent + 2 );
					continue;
				}

				$output .= str_repeat( ' ', $indent ) . '- ' . self::dump_inline_scalar( $item ) . "\n";
			}

			return $output;
		}

		foreach ( $value as $key => $item ) {
			$key = (string) $key;

			if ( is_array( $item ) ) {
				if ( empty( $item ) ) {
					$output .= str_repeat( ' ', $indent ) . $key . ": []\n";
					continue;
				}

				$output .= str_repeat( ' ', $indent ) . $key . ":\n";
				$output .= self::dump_value( $item, $indent + 2 );
				continue;
			}

			if ( is_string( $item ) && false !== strpos( $item, "\n" ) ) {
				$output .= str_repeat( ' ', $indent ) . $key . ": |-\n";
				$output .= self::dump_multiline_string( $item, $indent + 2 );
				continue;
			}

			$output .= str_repeat( ' ', $indent ) . $key . ': ' . self::dump_inline_scalar( $item ) . "\n";
		}

		return $output;
	}

	/**
	 * Dump a multiline string as an indented block scalar body.
	 *
	 * @param string $value Multiline content.
	 * @param int    $indent Current indentation.
	 * @return string
	 */
	private static function dump_multiline_string( string $value, int $indent ): string {
		$lines  = explode( "\n", str_replace( array( "\r\n", "\r" ), "\n", $value ) );
		$output = '';

		foreach ( $lines as $line ) {
			$output .= str_repeat( ' ', $indent ) . $line . "\n";
		}

		return $output;
	}

	/**
	 * Dump a scalar as an inline YAML value.
	 *
	 * @param mixed $value Scalar value.
	 * @return string
	 */
	private static function dump_inline_scalar( $value ): string {
		if ( null === $value ) {
			return 'null';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return (string) $value;
		}

		$string = (string) $value;

		if ( '' === $string ) {
			return "''";
		}

		if ( self::is_plain_string_safe( $string ) ) {
			return $string;
		}

		return "'" . str_replace( "'", "''", $string ) . "'";
	}

	/**
	 * Parse the current block.
	 *
	 * @param array<int, string> $lines YAML lines.
	 * @param int                $index Cursor position.
	 * @param int                $indent Expected indentation.
	 * @return mixed
	 */
	private static function parse_block( array $lines, int &$index, int $indent ) {
		self::skip_ignored_lines( $lines, $index );

		if ( ! isset( $lines[ $index ] ) ) {
			return array();
		}

		$current_indent = self::count_indent( $lines[ $index ] );

		if ( $current_indent < $indent ) {
			return array();
		}

		$trimmed = trim( $lines[ $index ] );

		if ( 0 === strpos( $trimmed, '- ' ) || '-' === $trimmed ) {
			return self::parse_list( $lines, $index, $indent );
		}

		return self::parse_map( $lines, $index, $indent );
	}

	/**
	 * Parse a YAML map.
	 *
	 * @param array<int, string> $lines YAML lines.
	 * @param int                $index Cursor position.
	 * @param int                $indent Expected indentation.
	 * @return array<string, mixed>
	 */
	private static function parse_map( array $lines, int &$index, int $indent ): array {
		$result = array();

		while ( isset( $lines[ $index ] ) ) {
			if ( self::is_ignored_line( $lines[ $index ] ) ) {
				$index++;
				continue;
			}

			$current_indent = self::count_indent( $lines[ $index ] );

			if ( $current_indent < $indent ) {
				break;
			}

			if ( $current_indent > $indent ) {
				$index++;
				continue;
			}

			$trimmed = trim( $lines[ $index ] );

			if ( 0 === strpos( $trimmed, '- ' ) || '-' === $trimmed ) {
				break;
			}

			$parts = explode( ':', $trimmed, 2 );

			if ( 2 !== count( $parts ) ) {
				$index++;
				continue;
			}

			$key       = trim( $parts[0] );
			$value_raw = ltrim( $parts[1] );
			$index++;

			if ( '' === $value_raw ) {
				$next_indent = self::next_significant_indent( $lines, $index );

				if ( $next_indent > $current_indent ) {
					$result[ $key ] = self::parse_block( $lines, $index, $current_indent + 2 );
					continue;
				}

				$result[ $key ] = null;
				continue;
			}

			if ( self::is_block_scalar( $value_raw ) ) {
				$result[ $key ] = self::parse_block_scalar( $lines, $index, $current_indent + 2, $value_raw );
				continue;
			}

			$result[ $key ] = self::parse_inline_scalar( $value_raw );
		}

		return $result;
	}

	/**
	 * Parse a YAML list.
	 *
	 * @param array<int, string> $lines YAML lines.
	 * @param int                $index Cursor position.
	 * @param int                $indent Expected indentation.
	 * @return array<int, mixed>
	 */
	private static function parse_list( array $lines, int &$index, int $indent ): array {
		$result = array();

		while ( isset( $lines[ $index ] ) ) {
			if ( self::is_ignored_line( $lines[ $index ] ) ) {
				$index++;
				continue;
			}

			$current_indent = self::count_indent( $lines[ $index ] );

			if ( $current_indent < $indent ) {
				break;
			}

			if ( $current_indent > $indent ) {
				$index++;
				continue;
			}

			$trimmed = trim( $lines[ $index ] );

			if ( 0 !== strpos( $trimmed, '- ' ) && '-' !== $trimmed ) {
				break;
			}

			$value_raw = '-' === $trimmed ? '' : ltrim( substr( $trimmed, 1 ) );
			$index++;

			if ( '' === $value_raw ) {
				$next_indent = self::next_significant_indent( $lines, $index );

				if ( $next_indent > $current_indent ) {
					$result[] = self::parse_block( $lines, $index, $current_indent + 2 );
					continue;
				}

				$result[] = null;
				continue;
			}

			if ( self::is_block_scalar( $value_raw ) ) {
				$result[] = self::parse_block_scalar( $lines, $index, $current_indent + 2, $value_raw );
				continue;
			}

			$result[] = self::parse_inline_scalar( $value_raw );
		}

		return $result;
	}

	/**
	 * Parse an inline scalar.
	 *
	 * @param string $value Raw inline value.
	 * @return mixed
	 */
	private static function parse_inline_scalar( string $value ) {
		$value = trim( $value );

		if ( "''" === $value || '""' === $value ) {
			return '';
		}

		if ( '[]' === $value ) {
			return array();
		}

		if ( 'null' === $value || '~' === $value ) {
			return null;
		}

		if ( 'true' === $value ) {
			return true;
		}

		if ( 'false' === $value ) {
			return false;
		}

		if ( preg_match( '/^-?\d+$/', $value ) ) {
			return (int) $value;
		}

		if ( preg_match( '/^-?\d+\.\d+$/', $value ) ) {
			return (float) $value;
		}

		if ( self::is_wrapped_in_quotes( $value, "'" ) ) {
			return str_replace( "''", "'", substr( $value, 1, -1 ) );
		}

		if ( self::is_wrapped_in_quotes( $value, '"' ) ) {
			$decoded = json_decode( $value, true );

			return is_string( $decoded ) ? $decoded : stripcslashes( substr( $value, 1, -1 ) );
		}

		return $value;
	}

	/**
	 * Parse a block scalar body.
	 *
	 * @param array<int, string> $lines YAML lines.
	 * @param int                $index Cursor position.
	 * @param int                $indent Expected indentation.
	 * @param string             $indicator Scalar indicator.
	 * @return string
	 */
	private static function parse_block_scalar( array $lines, int &$index, int $indent, string $indicator ): string {
		$keep_trailing_newline = false === strpos( $indicator, '-' );
		$folded                = '>' === substr( $indicator, 0, 1 );
		$chunks                = array();

		while ( isset( $lines[ $index ] ) ) {
			if ( self::is_ignored_line( $lines[ $index ] ) && 0 === self::count_indent( $lines[ $index ] ) ) {
				$index++;
				continue;
			}

			$current_indent = self::count_indent( $lines[ $index ] );

			if ( $current_indent < $indent ) {
				break;
			}

			$line = substr( $lines[ $index ], $indent );

			if ( false === $line ) {
				$line = '';
			}

			$chunks[] = $line;
			$index++;
		}

		if ( $folded ) {
			$value = implode( ' ', array_map( 'trim', $chunks ) );
		} else {
			$value = implode( "\n", $chunks );
		}

		if ( $keep_trailing_newline ) {
			$value .= "\n";
		}

		return $value;
	}

	/**
	 * Move past blank lines and whole-line comments.
	 *
	 * @param array<int, string> $lines YAML lines.
	 * @param int                $index Cursor position.
	 * @return void
	 */
	private static function skip_ignored_lines( array $lines, int &$index ): void {
		while ( isset( $lines[ $index ] ) && self::is_ignored_line( $lines[ $index ] ) ) {
			$index++;
		}
	}

	/**
	 * Check whether a line should be skipped.
	 *
	 * @param string $line YAML line.
	 * @return bool
	 */
	private static function is_ignored_line( string $line ): bool {
		$trimmed = trim( $line );

		return '' === $trimmed || 0 === strpos( $trimmed, '#' );
	}

	/**
	 * Find the indentation of the next significant line.
	 *
	 * @param array<int, string> $lines YAML lines.
	 * @param int                $index Cursor position.
	 * @return int
	 */
	private static function next_significant_indent( array $lines, int $index ): int {
		while ( isset( $lines[ $index ] ) ) {
			if ( self::is_ignored_line( $lines[ $index ] ) ) {
				$index++;
				continue;
			}

			return self::count_indent( $lines[ $index ] );
		}

		return -1;
	}

	/**
	 * Count leading spaces in a line.
	 *
	 * @param string $line YAML line.
	 * @return int
	 */
	private static function count_indent( string $line ): int {
		return strlen( $line ) - strlen( ltrim( $line, ' ' ) );
	}

	/**
	 * Check whether the raw value is a block scalar marker.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	private static function is_block_scalar( string $value ): bool {
		return in_array( $value, array( '|', '|-', '>', '>-' ), true );
	}

	/**
	 * Check whether a string is safely emitted without quotes.
	 *
	 * @param string $value Candidate string.
	 * @return bool
	 */
	private static function is_plain_string_safe( string $value ): bool {
		if ( preg_match( '/^(true|false|null|~|-?\d+(?:\.\d+)?)$/i', $value ) ) {
			return false;
		}

		return 1 === preg_match( '/^[A-Za-z0-9_\/.@:-]+(?: [A-Za-z0-9_\/.@:-]+)*$/', $value ) && '#' !== substr( $value, 0, 1 );
	}

	/**
	 * Check whether a string is wrapped in matching quotes.
	 *
	 * @param string $value Candidate string.
	 * @param string $quote Quote character.
	 * @return bool
	 */
	private static function is_wrapped_in_quotes( string $value, string $quote ): bool {
		return strlen( $value ) >= 2 && $quote === substr( $value, 0, 1 ) && $quote === substr( $value, -1 );
	}

	/**
	 * Determine whether an array uses sequential integer keys.
	 *
	 * @param array<mixed> $value Candidate array.
	 * @return bool
	 */
	private static function is_list( array $value ): bool {
		$expected = 0;

		foreach ( array_keys( $value ) as $key ) {
			if ( $expected !== $key ) {
				return false;
			}

			$expected++;
		}

		return true;
	}
}
