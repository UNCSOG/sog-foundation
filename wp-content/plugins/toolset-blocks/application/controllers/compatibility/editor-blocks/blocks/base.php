<?php
namespace OTGS\Toolset\Views\Controller\Compatibility\EditorBlocks;

/**
 * Class Base
 */
abstract class Base extends \Toolset_Gutenberg_Block {
	/** @var false|\WPV_Ajax */
	protected $toolset_ajax_manager;

	/**
	 * Base constructor.
	 *
	 * @param \Toolset_Constants|null $constants
	 * @param \Toolset_Assets_Manager|null $toolset_assets_manager
	 * @param \Toolset_Ajax|null $toolset_ajax_manager
	 * @param \Toolset_Condition_Plugin_Types_Active|null $types_active
	 * @param \Toolset_Condition_Plugin_Views_Active|null $views_active
	 * @param \Toolset_Condition_Plugin_Cred_Active|null $cred_active
	 */
	public function __construct( \Toolset_Constants $constants = null,
		\Toolset_Assets_Manager $toolset_assets_manager = null,
		\Toolset_Ajax $toolset_ajax_manager = null,
		\Toolset_Condition_Plugin_Types_Active $types_active = null,
		\Toolset_Condition_Plugin_Views_Active $views_active = null,
		\Toolset_Condition_Plugin_Cred_Active $cred_active = null
	) {
		$this->toolset_ajax_manager = $toolset_ajax_manager ?: \WPV_Ajax::get_instance();

		parent::__construct( $constants, $toolset_assets_manager, $this->toolset_ajax_manager, $types_active, $views_active, $cred_active );
	}
}
