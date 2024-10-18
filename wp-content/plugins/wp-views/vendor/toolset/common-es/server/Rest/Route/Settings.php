<?php

namespace ToolsetCommonEs\Rest\Route;


use ToolsetCommonEs\Library\WordPress\User;
use ToolsetCommonEs\Utils\SettingsStorage;

class Settings extends ARoute {
	/** @var SettingsStorage */
	private $settings_storage;

	protected $name = 'Settings';

	protected $version = 1;

	/**
	 * Settings constructor.
	 *
	 * @param User $wp_user
	 * @param SettingsStorage $settings_storage
	 */
	public function __construct( User $wp_user, SettingsStorage $settings_storage ) {
		parent::__construct( $wp_user );

		$this->settings_storage = $settings_storage;
	}

	public function callback( \WP_REST_Request $rest_request ) {
		$params = $rest_request->get_json_params();

		$fail_response = array( 'error' => 'Something went wrong.' );
		
		if( ! is_array( $params ) ||
			! isset( $params['action'] ) ||
			! isset( $params['key'] ) ||
			! isset( $params['value'] )
		) {
			return $fail_response;
		}

		switch( $params['action'] ) {
			case 'persist':
				$this->settings_storage->update_setting( $params['key'], $params['value'] );
				return 1;
		}

		return $fail_response;
	}

	protected function get_media_object_by_url( $url ) {
		if( $attachment_id = attachment_url_to_postid( $url ) ) {
			return wp_prepare_attachment_for_js( $attachment_id );
		}

		return array();
	}

	public function permission_callback() {
		// @todo check for Toolset Access permissions
		return $this->wp_user->current_user_can( 'edit_posts' );
	}
}
