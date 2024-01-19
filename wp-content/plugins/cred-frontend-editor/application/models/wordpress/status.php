<?php

namespace OTGS\Toolset\CRED\Model\Wordpress;


/**
 * Wrapper for WordPress status functions
 *
 * @package OTGS\Toolset\CRED\Model\Wordpress
 * @since 2.3
 */
class Status {

	/**
	 * @var OTGS\Toolset\CRED\Model\Wordpress\Status
	 * @since 2.3
	 */
	private static $instance = null;

	/**
	 * Get an instance of this object.
	 *
	 * @return OTGS\Toolset\CRED\Model\Wordpress\Status
	 * @since 2.3
	 */
	public static function get_instance() {
		if ( null === Status::$instance ) {
			Status::$instance = new Status();
		}
		return Status::$instance;
	}

	/**
	 * Get a list of custom basic values for post stati  used by this plugin.
	 *
	 * @return array
	 * @since 2.3
	 */
	public function get_basic_stati() {
		return array(
			/* translators: Label of the action to keep a post in its current status after performing an action, like editing or expiring it */
			'original' => __( 'Keep original status', 'wp-cred' ),
		);
	}

	/**
	 * Label for the native post status group.
	 *
	 * @return string
	 * @since 2.3
	 */
	public function get_native_stati_group_label() {
		/* translators: Label of the group of options offering to set one of the native post status values for an action */
		return __( 'Native post status', 'wp-cred' );
	}

	/**
	 * Get a list of valid native post stati that a post can be set to in the native editor.
	 *
	 * @return array
	 * @since 2.3
	 */
	public function get_native_stati() {
		return get_post_statuses();
	}

	/**
	 * Get a list of valid native post stati that a post can be set to in the native editor,
	 * plus the 'trash' status.
	 *
	 * @return array
	 * @since 2.3
	 */
	public function get_native_stati_with_trash() {
		$native_stati = $this->get_native_stati();
		$native_stati['trash'] = __( 'Trash', 'wp-cred' );
		return $native_stati;
	}

	/**
	 * Label for the custom post status group.
	 *
	 * @return string
	 * @since 2.3
	 */
	public function get_custom_stati_group_label() {
		/* translators: Label of the group of options offering to set one of the custom post status values, created by a third party theme or plugin, for an action */
		return __( 'Additional post status', 'wp-cred' );
	}

	/**
	 * Get a list of valid post stati registered by third parties,
	 * as long as they are set to be publicly visible.
	 *
	 * Note that it might be possible to use \OTGS\Toolset\Common\PostStatus
	 * instead to gather post status values. Keeping it separated for now.
	 *
	 * @return array
	 * @since 2.3
	 */
	public function get_custom_stati() {
		$custom_post_stati = array();
		$custom_post_stati_objects = get_post_stati( array( 'show_in_admin_status_list' => true, '_builtin' => false ), 'objects' );

		foreach( $custom_post_stati_objects as $custom_post_stati_object ) {
			$custom_post_stati[ $custom_post_stati_object->name ] = $custom_post_stati_object->label;
		}

		return $custom_post_stati;
	}
}
