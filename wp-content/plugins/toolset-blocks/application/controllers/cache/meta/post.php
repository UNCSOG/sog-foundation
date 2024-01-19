<?php

namespace OTGS\Toolset\Views\Controller\Cache\Meta;

/**
 * Postmeta cache controller.
 *
 * @since 2.8.1
 */
class Post extends Base {

	const VISIBLE_KEY = 'wpv_transient_meta_keys_visible512';
	const HIDDEN_KEY = 'wpv_transient_meta_keys_hidden512';

	/**
	 * @var array
	 */
	// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
	protected $excluded_visible = array(
		// WPML
		'wpml_media_duplicate_of', 'wpml_media_lang', 'wpml_media_processed',
		// Toolset
		'dd_layouts_settings'
	);

	/**
	 * @var array
	 */
	// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
	protected $excluded_hidden = array(
		// Core
		'_edit_last', '_edit_lock', '_wp_page_template', '_wp_attachment_metadata',
		'_thumbnail_id', '_top_nav_excluded',
		// WPML
		'_icl_translator_note',	'_icl_translation',
		'_wpml_media_duplicate', '_wpml_media_featured',
		// Toolset
		'_views_template', '_wpv_settings', '_wpv_layout_settings', '_wpv_view_sync',
		'_wpv_view_template_fields', '_wpv_view_template_mode',
		// Third parties
		'_alp_processed', '_cms_nav_minihome',
	);

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\Post\Manager $manager
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\Post\Invalidator $invalidator
	 * @param \Toolset_Field_Definition_Factory_Post $post_field_definition_factory
	 * @since 2.8.1
	 * @since 2.8.2 Add the \Toolset_Field_Definition_Factory_Post dependency.
	 */
	public function __construct(
		\OTGS\Toolset\Views\Controller\Cache\Meta\Post\Manager $manager,
		\OTGS\Toolset\Views\Controller\Cache\Meta\Post\Invalidator $invalidator,
		\Toolset_Field_Definition_Factory_Post $field_definition_factory
	) {
		$this->manager = $manager;
		$this->invalidator = $invalidator;
		$this->field_definition_factory = $field_definition_factory;
	}

	/**
	 * Get hidden postmeta fields managed as visible.
	 *
	 * @return array
	 * @since 2.8.1
	 */
	public function get_hidden_turned_visible() {
		if ( null !== $this->hidden_turned_visible ) {
			return $this->hidden_turned_visible;
		}

		$plugin_settings = \WPV_settings::get_instance();
		if (
			isset( $plugin_settings->wpv_show_hidden_fields )
			&& is_string( $plugin_settings->wpv_show_hidden_fields )
		) {
			$this->hidden_turned_visible = explode( ',', $plugin_settings->wpv_show_hidden_fields );
		} else {
			$this->hidden_turned_visible = array();
		}

		// TODO add some sanitization here

		return $this->hidden_turned_visible;
	}

	/**
	 * Get Types postmeta field keys.
	 *
	 * @return array
	 * @since 2.8.1
	 */
	public function get_types_meta_keys() {
		if ( null !== $this->types_meta_keys ) {
			return $this->types_meta_keys;
		}

		$this->types_meta_keys = $this->field_definition_factory->get_types_field_meta_keys();
		return $this->types_meta_keys;
	}

}
