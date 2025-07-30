<?php

namespace OTGS\Toolset\Views\Controller\Compatibility;

use Kadence\Theme;
use Kadence\Theme_Meta;

use OTGS\Toolset\Views\Controller\Compatibility\BlockEditorWPA;


/**
 * Handles the compatibility between Views and the Kadence Theme.
 *
 * @since 3.2.0
 */
class KadenceTheme extends Base {
	/** @var Theme */
	private $theme_instance;

	/** @var Theme_Meta */
	private $theme_meta_instance;

	/** @var \Toolset_Condition_Woocommerce_Active */
	private $woocommerce_active_condition;

	/**
	 * Constructor for the Kadence Theme compatibility class.
	 *
	 * @param Theme $theme_instance
	 * @param Theme_Meta $theme_meta_instance
	 * @param \Toolset_Condition_Woocommerce_Active $woocommerce_active_condition
	 */
	public function __construct(
		Theme $theme_instance,
		Theme_Meta $theme_meta_instance,
		\Toolset_Condition_Woocommerce_Active $woocommerce_active_condition
	) {
		$this->theme_instance = $theme_instance;
		$this->theme_meta_instance = $theme_meta_instance;
		$this->woocommerce_active_condition = $woocommerce_active_condition;
	}

	/**
	 * Initializes the Kadence Theme compatibility layer.
	 */
	public function initialize() {
		$this->init_hooks();
	}

	/**
	 * Initializes the hooks for the Kadence Theme compatibility.
	 */
	private function init_hooks() {
		add_action( 'wpv_action_after_archive_set', array( $this, 'disable_grid_on_assigned_archive' ) );

		// The "problematic" product loop filters need to be removed only for the case a Toolset Views WordPress Archive for products is rendered.
		// If no such WordPress Archive is assigned, the rendering process should continue uninterrupted.
		if ( $this->woocommerce_active_condition->is_met() ) {
			add_action( 'wpv_action_before_initialize_archive_loop', array( $this, 'maybe_remove_problematic_product_loop_filters' ) );
		}

		add_action( 'enqueue_block_editor_assets', array( $this, 'maybe_disable_block_editor_tab' ), 9 );
	}

	/**
	 * Disable the grid CSS classes in archives which have a WPA assigned.
	 *
	 * @param null|int $wpa_id
	 */
	public function disable_grid_on_assigned_archive( $wpa_id ) {
		if ( null === $wpa_id ) {
			return;
		}

		if ( 0 === $wpa_id ) {
			return;
		}

		add_filter( 'kadence_archive_container_classes', array( $this, 'filter_out_grid_classes' ) );
	}

	/**
	 * Remove classes starting with grid- prefixes.
	 *
	 * @param string[] $classnames
	 * @return string[]
	 */
	public function filter_out_grid_classes( $classnames ) {
		$filtered_classnames = array_filter( $classnames, function( $v ) {
			return substr( $v, 0, 5 ) !== 'grid-';
		});
		return $filtered_classnames;
	}


	/**
	 * Handles the removal of the "problematic" product loop filters that prevents Toolset Views designed WordPress Archives
	 * to be rendered properly on the frontend because the actual theme is taking over, injecting a grid-like layout.
	 */
	public function maybe_remove_problematic_product_loop_filters() {
		if ( null === $this->theme_instance ) {
			return;
		}

		if ( ! $this->woocommerce_active_condition->is_met() ) {
			return;
		}

		if ( ! method_exists( $this->theme_instance, 'component' ) ) {
			return;
		}

		$woocommerce_component_instance = $this->theme_instance->component( 'woocommerce' );

		// Removes the modification of the WordPress Archive loop start, the default of the theme for which is a grid-like styled unordered list.
		remove_filter( 'woocommerce_product_loop_start', [ $woocommerce_component_instance, 'product_loop_start' ], 5 );

		// Removes the modification of th "Add to cart" link in the each of the loop items.
		remove_filter( 'woocommerce_product_loop_start', [ $woocommerce_component_instance, 'add_filter_for_add_to_cart_link' ] );
		remove_filter( 'woocommerce_product_loop_end', [ $woocommerce_component_instance, 'remove_filter_for_add_to_cart_link' ] );
	}

	/**
	 * Remove the Kadence theme options tab from the blocks editor on our own objects.
	 */
	public function maybe_disable_block_editor_tab() {
		if ( null === $this->theme_meta_instance ) {
			return;
		}

		$post_type = get_post_type();

		if ( ! in_array( $post_type, array(
			\WPV_Content_Template_Embedded::POST_TYPE,
			\WPV_View_Base::POST_TYPE,
			BlockEditorWPA::WPA_HELPER_POST_TYPE,
		), true ) ) {
			return;
		}

		remove_action( 'enqueue_block_editor_assets', array( $this->theme_meta_instance, 'script_enqueue' ) );
	}

}
