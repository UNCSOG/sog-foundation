<?php
/**
 * Upgrade routine to set default editors for CTs and WPAs.
 *
 * @package Toolset Views
 * @since 3.0
 */

namespace OTGS\Toolset\Views\Controller\Upgrade;

/**
 * Existing sites should get a default user editor for Content Templates and WordPress ARchives.
 *
 * @since 3.0
 */
class DefaultEditors implements IRoutine {

	/**
	 * @var \WPV_Settings
	 */
	private $settings;

	/**
	 * @var \Wpdb
	 */
	private $wpdb;

	/**
	 * @var string
	 */
	private $toolset_views_flavour = 'classic';

	/**
	 * Constructor.
	 *
	 * @param \WPV_Settings $settings
	 * @param \OTGS\Toolset\Views\Model\Wordpress\Wpdb $wpdb_wrapper
	 */
	public function __construct(
		\WPV_Settings $settings,
		\OTGS\Toolset\Views\Model\Wordpress\Wpdb $wpdb_wrapper
	) {
		$this->settings = $settings;
		$this->wpdb = $wpdb_wrapper->get_wpdb();
	}

	/**
	 * Execute database upgrade up to 3.0
	 *
	 * @param array $args
	 * @since 3.0
	 */
	public function execute_routine( $args = array() ) {// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$this->toolset_views_flavour = wpv_get_views_flavour();
		$this->set_default_user_editor();
		$this->set_default_archive_editor();
		add_action( 'init', array( $this, 'maybe_set_editor_max_width' ) );
		$this->maybe_set_mixed_editing_experience();
		$this->settings->save();
	}

	/**
	 * Default user editor for Content Templates.
	 *
	 * Upgrading sites should get a default user editor for Content Templates,
	 * if they did not set one yet using our betas or previous versions.
	 * Note that new sites get one assigned in the Setup routine,
	 * so this only applies to existing sites without an user editor set.
	 *
	 * In this case, we should default to the legacy editor,
	 * or the installed compatible page builder.,
	 * and suggest to swich to use the Blocks editor
	 * from the welcome page and the legacy editor.
	 *
	 * @since 3.0
	 */
	private function set_default_user_editor() {
		$existing_default_user_editor = $this->settings->get_raw_value( 'default_user_editor', false );
		if ( null !== $existing_default_user_editor ) {
			return;
		}

		$default_editor = 'basic';

		switch ( $this->toolset_views_flavour ) {
			case 'blocks':
				$default_editor = 'gutenberg';
				break;
		}

		$this->settings->set( 'default_user_editor', $default_editor );
	}

	/**
	 * Default user editor for WordPress Archives.
	 *
	 * Upgrading sites should get a default editor for WPAs.
	 * New sires get one assigned in the Setup routine,
	 * so this only applies to existing sites without an user editor set.
	 *
	 * In this case, we should not enforce the Blocks editor,
	 * but suggest to swich to use it from the welcome page and the legacy editor.
	 *
	 * @since 3.0
	 */
	private function set_default_archive_editor() {
		$existing_default_wpa_editor = $this->settings->get_raw_value( 'default_wpa_editor', false );
		if ( null !== $existing_default_wpa_editor ) {
			return;
		}

		$default_editor = 'basic';

		switch ( $this->toolset_views_flavour ) {
			case 'blocks':
				$default_editor = 'gutenberg';
				break;
		}

		$this->settings->set( 'default_wpa_editor', $default_editor );
	}

	/**
	 * Set the editor max width setting in case of using Toolset Blocks.
	 *
	 * This needs to run at init because this is where Toolset Common ES dependencies are
	 * fully available, including \ToolsetCommonEs\Utils\SettingsStorage.
	 *
	 * @since 3.0
	 */
	public function maybe_set_editor_max_width() {
		if ( 'blocks' !== $this->toolset_views_flavour ) {
			return;
		}

		// Setup the editor max width setting.
		$dic = apply_filters( 'toolset_dic', false );

		if ( $dic ) {
			$settings = $dic->make( '\ToolsetCommonEs\Utils\SettingsStorage' );
			$settings->update_setting(
				\ToolsetBlocks\Plugin\EditorMaxWidth\EditorMaxWidth::SETTING_KEY_IS_APPLIED_DEFAULT,
				true
			);
		}
	}

	/**
	 * Maybe set the editing experience to mixed when the site has already some content.
	 *
	 * @since 3.0
	 */
	private function maybe_set_mixed_editing_experience() {
		if ( 'classic' == $this->toolset_views_flavour ) {
			// If there is at least one View or WPA created with blocks,
			// set the variation to extended.
			$has_block_item = $this->wpdb->get_col(
				$this->wpdb->prepare(
					"SELECT meta_id
					FROM {$this->wpdb->postmeta}
					WHERE meta_key = %s
					LIMIT 1",
					'_wpv_view_data'
				)
			);

			if ( ! empty( $has_block_item ) ) {
				$this->settings->set( 'editing_experience', 'mixed' );
			}
		}

		if ( 'blocks' === $this->toolset_views_flavour ) {
			// If there is one View or WPA already, set the variation to extended.
			$has_view_or_wpa = $this->wpdb->get_col(
				$this->wpdb->prepare(
					"SELECT ID
					FROM {$this->wpdb->posts}
					WHERE post_type = %s
					LIMIT 1",
					'view'
				)
			);

			if ( ! empty( $has_view_or_wpa ) ) {
				$this->settings->set( 'editing_experience', 'mixed' );
			}
		}
	}

}
