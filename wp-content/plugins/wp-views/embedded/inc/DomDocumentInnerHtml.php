<?php

/**
 * // TODO Can be removed when views-3260 is implemented.
 * It's only used on ./wpv.class.php to re-apply blocks meta data on rendering (for styles).
 *
 * @author Keyvan Minoukadeh - http://www.keyvan.net - keyvan@keyvan.net
 * @see http://fivefilters.org (the project this was written for)
 */
class JSLikeHTMLElement extends DOMElement {
	/**
	 * Used for getting innerHTML like it's done in JavaScript:
	 * @code
	 * $string = $div->innerHTML;
	 * @endcode
	 */
	public function __get( $name ) {
		if ( $name == 'innerHTML' ) {
			$inner = '';
			foreach ( $this->childNodes as $child ) {
				$inner .= $this->ownerDocument->saveHTML( $child );
			}

			return $inner;
		}

		return null;
	}

	public function __toString() {
		return '[' . $this->tagName . ']';
	}
}
