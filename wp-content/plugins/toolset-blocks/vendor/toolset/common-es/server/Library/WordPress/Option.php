<?php

namespace ToolsetCommonEs\Library\WordPress;

class Option {
	/**
	 * @param string $option
	 * @param bool $default
	 *
	 * @return mixed|void
	 */
	public function get_option( $option, $default = false ) {
		return get_option( $option, $default );
	}

	/**
	 * @param string$option
	 * @param mixed $value
	 * @param null $autoload
	 *
	 * @return bool
	 */
	public function update_option( $option, $value, $autoload = null ) {
		return update_option( $option, $value, $autoload );
	}
}


