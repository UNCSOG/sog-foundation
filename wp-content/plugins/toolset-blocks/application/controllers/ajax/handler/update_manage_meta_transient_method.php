<?php

/**
 * Handle saving the transient invalidation method for meta keys cache.
 *
 * @since 2.8.1
 */
class WPV_Ajax_Handler_Update_Manage_Meta_Transient_Method extends Toolset_Ajax_Handler_Abstract {

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 */
	public function process_call( $arguments ) {
		$this->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_UPDATE_MANAGE_META_TRANSIENT_METHOD,
		) );

		$settings = WPV_Settings::get_instance();

		$settings->manage_meta_transient_method = toolset_getpost(
			'wpv_manage_meta_transient_method',
			\OTGS\Toolset\Views\Controller\Cache\Meta\Gui::CRON,
			array(
				\OTGS\Toolset\Views\Controller\Cache\Meta\Gui::CRON,
				\OTGS\Toolset\Views\Controller\Cache\Meta\Gui::MANUAL,
			)
		);
		$settings->save();

		$this->ajax_finish();
	}

}
