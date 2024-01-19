<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain;

/**
 * Class ContentLines
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain
 *
 * @since TB 1.3
 */
class ContentLines {
	/** @var string[] */
	private $lines;

	/** @var integer */
	private $iterator = -1;

	public function __construct( $content ) {
		if( ! is_array( $content ) ) {
			throw new \InvalidArgumentException( '$content must be a string.' );
		}

		foreach( $content as $line ) {
			if( ! empty( trim( $line ) ) ) {
				$this->lines[] = $line;
			}
		}
	}

	/**
	 * Get next line.
	 *
	 * @return bool|string
	 */
	public function next() {
		// Increase iterator to get next item.
		$this->iterator++;

		// Check for no more lines.
		if( count( $this->lines ) <= $this->iterator ) {
			// Reset iterator.
			$this->iterator = -1;

			// No more lines.
			return false;
		}

		// Return line.
		return $this->current_line();
	}

	private function current_line() {
		return (string) $this->lines[ $this->iterator ];
	}
}
