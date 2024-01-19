<?php

namespace OTGS\Toolset\Views\Controller\Compatibility;

use OTGS\Toolset\Views\Services\Bootstrap;
use OTGS\Toolset\Views\Services\QueryFilterService;
use OTGS\Toolset\Views\Model\WooCommerce\WcQuery;

/**
 * Class WooCommerceCompatibility
 *
 * Handles the compatibility between Views and WooCommerce.
 *
 * @since Views 3.3 / Toolset Blocks 1.3
 */
class WooCommerceCompatibility extends Base {
	const QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_RELATED = 'related';

	const QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_UP_SELLS = 'up-sells';

	const QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_CROSS_SELLS = 'cross-sells';

	const WC_UP_SELLS_POST_META = '_upsell_ids';

	const WC_CROSS_SELLS_POST_META = '_crosssell_ids';

	const TOP_CURRENT_POST = 'top_current_post';

	/**
	 * Product Taxonomy Filter View Settings (Category)
	 */
	const TAX_PRODUCT_CAT_SLUG = 'product_cat';
	const VIEW_SETTINGS_TAX_FILTER_TAX_PRODUCT_CAT_RELATIONSHIP = 'tax_product_cat_relationship';
	const VIEW_SETTINGS_TAX_FILTER_TAXONOMY_PRODUCT_CAT_ATTRIBUTE_OPERATOR = 'taxonomy-product_cat-attribute-operator';
	const VIEW_SETTINGS_TAX_FILTER_TAX_INPUT_PRODUCT_CAT = 'tax_input_product_cat';

	/**
	 * Product Taxonomy Filter View Settings (Tag)
	 */
	const TAX_PRODUCT_TAG_SLUG = 'product_tag';
	const VIEW_SETTINGS_TAX_FILTER_TAX_PRODUCT_TAG_RELATIONSHIP = 'tax_product_tag_relationship';
	const VIEW_SETTINGS_TAX_FILTER_TAXONOMY_PRODUCT_TAG_ATTRIBUTE_OPERATOR = 'taxonomy-product_tag-attribute-operator';
	const VIEW_SETTINGS_TAX_FILTER_TAX_INPUT_PRODUCT_TAG = 'tax_input_product_tag';

	/**
	 * WooCommerce Views Settings
	 */

	const WC_PAGINATION_ENABLED = 'woocommerce_pagination_enabled';
	const WC_SORTING_ENABLED = 'woocommerce_sorting_enabled';

	/**
	 * Initializes the WooCommerce integration.
	 */
	public function initialize() {
		$this->init_hooks();
		$this->init_wc_query();
	}

	/**
	 * Initializes the hooks for the WooCommerce integration.
	 */
	private function init_hooks() {
		add_filter( 'wpv_filter_post_relationship_slugs_blacklist_for_automatic_query_filter_generation', array( $this, 'add_relationship_slugs_to_be_excluded' ) );

		add_filter( 'wpv_view_settings', array( $this, 'maybe_adjust_view_settings_for_query_filter_injection' ), 10, 2 );

		add_filter( 'wpv_filter_wpv_get_view_settings_for_query_filter_rendering', array( $this, 'maybe_adjust_view_settings_for_query_filter_injection' ), 10, 2 );

		add_filter( 'wpv_filter_wpv_get_view_settings', array( $this, 'maybe_adjust_view_settings_for_query_filter_injection' ), 10, 2 );

		add_action( 'wpv_action_after_archive_set', array( $this, 'woocommerce_gui_overrides' ), 10, 1 );

		add_action( 'wpv_filter_view_block_data_from_db', array( $this, 'woocommerce_global_settings' ), 10, 2 );
	}

	/**
	 * Init the compatibility layer for Views and WPAs loops.
	 *
	 * @return void
	 */
	private function init_wc_query() {
		if ( class_exists( 'WC_Query' ) ) {
			new WcQuery();
		}
	}

	/**
	 * Adds the WooCommerce post relationship slugs to the blacklist to prevent handling the WooCommerce related query slugs
	 * as normal post relationship slugs during automatic query filter generation.
	 *
	 * @param array $slugs The array of blacklisted post relationship slugs.
	 *
	 * @return array
	 */
	public function add_relationship_slugs_to_be_excluded( $slugs ) {
		return array_merge(
			$slugs,
			array(
				self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_RELATED,
				self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_UP_SELLS,
				self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_CROSS_SELLS,
			)
		);
	}

	/**
	 * Adjusts the View's settings post meta to achieve automatic query filter generation for the WooCommerce Related
	 * Query Filters.
	 *
	 * @param array    $view_settings
	 * @param null|int $view_id
	 *
	 * @return array
	 */
	public function maybe_adjust_view_settings_for_query_filter_injection( $view_settings, $view_id = null ) {
		if ( ! $view_id ) {
			return $view_settings;
		}

		$view_data = get_post_meta( $view_id, Bootstrap::BLOCK_VIEW_DATA_POST_META_KEY, true );
		$allow_multiple_post_types = toolset_getnest( $view_data, array( 'content_selection', 'allowMultiplePostTypes' ), false );
		$post_type_relationship_slug = toolset_getnest( $view_data, array( 'content_selection', 'postTypeRelationship' ), QueryFilterService::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_NOT_SET );

		if ( $allow_multiple_post_types ) {
			return $view_settings;
		}

		if ( self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_RELATED === $post_type_relationship_slug ) {
			$view_settings[ self::VIEW_SETTINGS_TAX_FILTER_TAX_PRODUCT_CAT_RELATIONSHIP ] = self::TOP_CURRENT_POST;
			$view_settings[ self::VIEW_SETTINGS_TAX_FILTER_TAXONOMY_PRODUCT_CAT_ATTRIBUTE_OPERATOR ] = 'IN';
			$view_settings[ self::VIEW_SETTINGS_TAX_FILTER_TAX_INPUT_PRODUCT_CAT ] = array();

			$view_settings[ self::VIEW_SETTINGS_TAX_FILTER_TAX_PRODUCT_TAG_RELATIONSHIP ] = self::TOP_CURRENT_POST;
			$view_settings[ self::VIEW_SETTINGS_TAX_FILTER_TAXONOMY_PRODUCT_TAG_ATTRIBUTE_OPERATOR ] = 'IN';
			$view_settings[ self::VIEW_SETTINGS_TAX_FILTER_TAX_INPUT_PRODUCT_TAG ] = array();

			$view_settings[ \WPV_View_Base::VIEW_SETTINGS_TAXONOMY_FILTER_TAXONOMY_RELATIONSHIP ] = 'OR';

			$filters = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, 'taxonomy' ), array() );
			$filters[ self::TAX_PRODUCT_CAT_SLUG ] = array(
				\WPV_Filter_Manager::EDITOR_MODE => \WPV_Filter_Manager::FILTER_MODE_FROM_CONTENT_SELECTION,
				\WPV_Filter_Manager::EDITOR_SUMMARY => __( 'Filters only products that are related (same category) with the current product.', 'wpv-views' ),
			);
			$filters[ self::TAX_PRODUCT_TAG_SLUG ] = array(
				\WPV_Filter_Manager::EDITOR_MODE => \WPV_Filter_Manager::FILTER_MODE_FROM_CONTENT_SELECTION,
				\WPV_Filter_Manager::EDITOR_SUMMARY => __( 'Filters only products that are related (same tags) with the current product.', 'wpv-views' ),
			);
			$view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ 'taxonomy' ] = $filters;
		}

		if (
			in_array(
				$post_type_relationship_slug,
				array( self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_UP_SELLS, self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_CROSS_SELLS ),
				true
			)
		) {
			$post = get_post();

			if ( ! $post ) {
				$post = get_post( toolset_getnest( $view_data, array( 'general', 'parent_post_id' ), 0 ) );
			}

			if ( ! $post ) {
				return $view_settings;
			}

			if ( \WPV_Content_Template_Embedded::POST_TYPE === $post->post_type ) {
				$ct_preview_post = absint( get_post_meta( $post->ID, \WPV_Content_Template_Embedded::CONTENT_TEMPLATE_PREVIEW_POST_META_KEY, true ) );
				$post = get_post( $ct_preview_post );
			}

			$meta_key = self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_CROSS_SELLS === $post_type_relationship_slug ?
				self::WC_CROSS_SELLS_POST_META :
				self::WC_UP_SELLS_POST_META;

			$up_cross_sells = get_post_meta( $post->ID, $meta_key, true );

			$view_settings[ \WPV_View_Base::VIEW_SETTINGS_ID_FILTER_ID_IN_OR_OUT ] = 'in';
			$view_settings[ \WPV_View_Base::VIEW_SETTINGS_ID_FILTER_ID_MODE ] = array( 'by_ids' );
			$view_settings[ \WPV_View_Base::VIEW_SETTINGS_ID_FILTER_POST_ID_IDS_LIST ] = is_array( $up_cross_sells ) && ! empty( $up_cross_sells ) ? implode( ',', $up_cross_sells ) : '0';

			$filters = toolset_getarr( $view_settings, \WPV_Filter_Manager::SETTING_KEY, array() );
			$filters[ 'post_id' ] = array(
				\WPV_Filter_Manager::EDITOR_MODE => \WPV_Filter_Manager::FILTER_MODE_FROM_CONTENT_SELECTION,
				\WPV_Filter_Manager::EDITOR_SUMMARY => ( self::WC_CROSS_SELLS_POST_META === $meta_key )
					? __( 'Filters only products marked as Cross-sells for the current product.', 'wpv-views' )
					: __( 'Filters only products marked as Upsells for the current product.', 'wpv-views' ),
					\WPV_Filter_Manager::EDITOR_TITLE => ( self::WC_CROSS_SELLS_POST_META === $meta_key )
					? __( 'Cross-sells filter', 'wpv-views' )
					: __( 'Upsells filter', 'wpv-views' ),
			);
			$view_settings[ \WPV_Filter_Manager::SETTING_KEY ] = $filters;
		}

		return $view_settings;
	}


	public function woocommerce_gui_overrides( $wpa_id ) {
		if ( null === $wpa_id ) {
			return;
		}

		if ( 0 === $wpa_id ) {
			return;
		}

		$settings = get_post_meta( $wpa_id, '_wpv_settings', true );

		if ( ! is_array( $settings ) ) {
			return;
		}

		add_filter( 'woocommerce_views_override_woo_sorting_settings', static function ( $override ) use ( $settings ) {
			if ( in_array( self::WC_SORTING_ENABLED, $settings, false ) ) {
				if ( ! isset( $settings[ self::WC_SORTING_ENABLED ] ) ) {
					return false;
				}
				return (bool) $settings[self::WC_SORTING_ENABLED];
			}

			return false;
		} );
		add_filter( 'woocommerce_views_override_woo_pagination_settings', static function ( $override ) use ( $settings ) {
			if ( in_array( self::WC_PAGINATION_ENABLED, $settings, false ) ) {
				if ( ! isset( $settings[ self::WC_PAGINATION_ENABLED ] ) ) {
					return false;
				}
				return (bool) $settings[self::WC_PAGINATION_ENABLED];
			}

			return false;
		} );
	}

	/**
	 * If WooCommerce settings are forced, pass it to the view data.
	 *
	 * @param array $data The view's data
	 * @param $view_id The view id
	 *
	 * @return mixed
	 */
	public function woocommerce_global_settings( $data, $view_id ) {
		if ( $this->is_woocommerce_settings_forced() ) {
			if ( ! isset ( $data[ 'woocommerceOptions' ] ) ) {
				$data[ 'woocommerceOptions' ] = array();
			}
			$data['woocommerceOptions']['force_woocommerce_settings'] = $this->is_woocommerce_settings_forced();
		} else {
			if ( isset ( $data['woocommerceOptions' ] ) ) {
				$data['woocommerceOptions']['force_woocommerce_settings'] = false;
			}
		}
		return $data;
	}

	/**
	 * Checks whether the default WooCommerce Frontend Sorting is globally enabled.
	 *
	 * @return bool
	 */
	public function is_woocommerce_settings_forced() {
		if ( 'yes' === get_option( 'woocommerce_views_frontend_sorting_setting' ) ) {
			return true;
		}
		return false;
	}

}
