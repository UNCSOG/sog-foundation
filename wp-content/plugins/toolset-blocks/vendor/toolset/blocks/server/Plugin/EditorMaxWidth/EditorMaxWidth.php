<?php
namespace ToolsetBlocks\Plugin\EditorMaxWidth;

use ToolsetBlocks\Plugin\IPlugin;
use ToolsetCommonEs\Utils\ScriptData;
use ToolsetCommonEs\Utils\SettingsStorage;

class EditorMaxWidth implements IPlugin {
	/** DO NOT CHANGE THIS VALUE */
	const SETTING_KEY_IS_APPLIED_DEFAULT = 'editor-max-width-is-applied-default';

	/** @var ScriptData */
	private $script_data;

	/** @var SettingsStorage */
	private $settings_storage;

	/**
	 * EditorMaxWidth constructor.
	 *
	 * @param ScriptData $script_data
	 * @param SettingsStorage $settings_storage
	 */
	public function __construct( ScriptData $script_data, SettingsStorage $settings_storage ) {
		$this->script_data = $script_data;
		$this->settings_storage = $settings_storage;
	}


	public function load() {
		$this->expose_is_applied_default_to_script();
	}

	/**
	 * Expose the 'editor-max-width-is-applied-default' value to scripts.
	 */
	private function expose_is_applied_default_to_script() {
		$is_applied_default = $this->settings_storage->get_setting( self::SETTING_KEY_IS_APPLIED_DEFAULT, false );
		$this->script_data->add_data( self::SETTING_KEY_IS_APPLIED_DEFAULT, $is_applied_default ? true : false );
	}
}
