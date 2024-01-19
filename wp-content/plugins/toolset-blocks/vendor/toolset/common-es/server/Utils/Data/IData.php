<?php

namespace ToolsetCommonEs\Utils\Data;

interface IData {
	/**
	 * @param mixed $needle
	 *
	 * @param bool $return_on_false
	 *
	 * @return mixed
	 */
	public function find( $needle, $return_on_false = false );

	/**
	 * Not really related to the object itself, but a good place for this helper function.
	 *
	 * @param mixed $needle
	 * @param mixed $values
	 * @param bool $return_on_false
	 *
	 * @return mixed
	 */
	public function find_in( $needle, $values, $return_on_false = false );
}
