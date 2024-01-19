<?php

namespace OTGS\Toolset\CRED\Controller;

use OTGS\Toolset\CRED\Model\Settings;
use OTGS\Toolset\CRED\Model\Wordpress\Status as PostStatusModel;

/**
 * Main controller for the post and user expiration manager feature.
 *
 * @since 2.3
 */
class DraftsManager {

	/**
	 * @var Settings
	 */
	private $settings_model;

	/**
	 * @var OTGS\Toolset\CRED\Controller\DraftsManager\Post
	 */
	private $post_drafts_manager;

	/**
	 * Manager initialization method.
	 *
	 * @since 2.3
	 */
	public function initialize() {
		$this->settings_model = Settings::get_instance();
		$this->initialize_post_drafts_manager();
	}

	/**
	 * Post expiration manager initialization method.
	 *
	 * @since 2.3
	 */
	private function initialize_post_drafts_manager() {
		$dic = apply_filters( 'toolset_common_es_dic', false );

		$dic->define( '\OTGS\Toolset\CRED\Controller\DraftsManager\Post', [ ':settings_model' => $this->settings_model ] );
		$this->post_drafts_manager = $dic->make( '\OTGS\Toolset\CRED\Controller\DraftsManager\Post' );
		$this->post_drafts_manager->initialize();
	}

}
