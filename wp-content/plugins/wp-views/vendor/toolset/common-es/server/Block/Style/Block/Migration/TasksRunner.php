<?php

namespace ToolsetCommonEs\Block\Style\Block\Migration;

use ToolsetCommonEs\Block\Style\Attribute\Factory as FactoryStyleAttribute;
use ToolsetCommonEs\Block\Style\Block\ABlock;

/**
 * Class TasksRunner
 *
 * @package ToolsetBlocks\Block\Style\Block\Migrate
 */
class TasksRunner implements ITask {

	/** @var array ITask[]   */
	private $tasks = [];

	public function add( ITask $task ) {
		$this->tasks[] = $task;
	}


	/**
	 * Loop over all registered Migration Tasks.
	 *
	 * @param ABlock $block
	 * @param FactoryStyleAttribute $factory_style_attribute
	 */
	public function migrate( ABlock $block, FactoryStyleAttribute $factory_style_attribute ) {
		foreach( $this->tasks as $task ) {
			$task->migrate( $block, $factory_style_attribute );
		}
	}
}
