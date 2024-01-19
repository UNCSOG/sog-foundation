<?php

namespace ToolsetCommonEs\Utils\Data;

class Factory {

	public function get_static( $raw_data ) {
		if( ! is_array( $raw_data ) ) {
			throw new \InvalidArgumentException( '$data must be an array.' );
		}

		return new StaticData( $raw_data );
	}

}
