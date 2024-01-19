<?php

namespace ToolsetBlocks\Plugin;


class Manager {
	/** @var IPlugin[] */
	private $plugins = array();

	/**
	 * @param IPlugin $plugin
	 */
	public function register_plugin( IPlugin $plugin ) {
		$this->plugins[] = $plugin;
	}

	/**
	 * Bootstrap all registered plugins.
	 */
	public function load_plugins() {
		foreach( $this->plugins as $plugin ) {
			$plugin->load();
		}
	}
}
