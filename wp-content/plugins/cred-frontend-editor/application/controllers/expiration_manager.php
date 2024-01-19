<?php

namespace OTGS\Toolset\CRED\Controller;

use OTGS\Toolset\CRED\Model\Settings;
use OTGS\Toolset\CRED\Model\Wordpress\Status as PostStatusModel;

/**
 * Main controller for the post and user expiration manager feature.
 *
 * @since 2.3
 */
class ExpirationManager {

	/**
	 * @var Settings
	 */
	private $settings_model;

	/**
	 * @var \Toolset_Date_Utils
	 */
	private $date_utils;

	/**
	 * @var OTGS\Toolset\CRED\Controller\ExpirationManager\Post
	 */
	private $post_expiration_manager;

	/**
	 * @var Status
	 */
	private $post_status_model;

	/**
	 * Manager initialization method.
	 *
	 * @since 2.3
	 */
	public function initialize() {
		$this->settings_model = Settings::get_instance();
		$this->date_utils = \Toolset_Date_Utils::get_instance();
		$this->post_status_model = PostStatusModel::get_instance();
		$this->initialize_post_expiration_manager();
	}

	/**
	 * Post expiration manager initialization method.
	 *
	 * @since 2.3
	 */
	private function initialize_post_expiration_manager() {
		$this->post_expiration_manager = new ExpirationManager\Post( $this->settings_model, $this->date_utils, $this->post_status_model );
		$this->post_expiration_manager->initialize();
	}

}
