<?php

namespace ToolsetCommonEs\Utils\Config;

use \ToolsetCommonEs\Utils\Data\Factory as FactoryData;

class Factory {
	/** @var FactoryData  */
	private $factory_data;

	/**
	 * Factory constructor.
	 *
	 * @param FactoryData $factory_data
	 */
	public function __construct( FactoryData $factory_data ) {
		$this->factory_data = $factory_data;
	}

	/**
	 * @param array $config
	 *
	 * @return Block
	 */
	public function get_block( $config ) {
		return new Block( $this->factory_data->get_static( $config ) );
	}
}
