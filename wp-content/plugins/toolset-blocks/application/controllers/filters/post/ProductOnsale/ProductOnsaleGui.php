<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;

use OTGS\Toolset\Views\Controller\Filters\AbstractGui;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductOnsale;

/**
 * User interface for the filter.
 */
class ProductOnsaleGui extends AbstractGui {

	const SCRIPT_BACKEND = 'wpv-filter-post-product-onsale';
	const SCRIPT_BACKEND_FILENAME = 'post_product_onsale';
	const NONCE = 'wpv_view_filter_post_product_onsale_nonce';

	/**
	 * Load hooks to define and list the filter in the query filters section.
	 */
	public function load_hooks() {
		// Register filter in filter dialogs.
		add_filter( 'wpv_filters_add_filter', array( $this, 'add_view_filter' ), 1, 2 );
		add_filter( 'wpv_filters_add_archive_filter', array( $this, 'add_archive_filter' ), 1, 1 );
		// Register filter in filter lists.
		add_action( 'wpv_add_filter_list_item', array( $this, 'add_filter_list_item' ), 1, 1 );
		// Include the filter in the Views shortcodes GUI.
		add_filter( 'wpv_filter_register_shortcode_attributes_for_posts', array( $this, 'shortcode_attributes' ), 10, 2 );
		add_filter( 'wpv_filter_register_url_parameters_for_posts', array( $this, 'url_parameters' ), 10, 2 );
	}

	/**
	 * Offer the filter in Views.
	 *
	 * @param mixed[] $filters
	 * @param mixed $post_type
	 * @return mixed[]
	 */
	public function add_view_filter( $filters, $post_type ) {
		return $this->add_filter( $filters, 'normal' );
	}

	/**
	 * Offer the filter in WPAs.
	 *
	 * @param mixed[] $filters
	 * @return mixed[]
	 */
	public function add_archive_filter( $filters ) {
		return $this->add_filter( $filters, 'archive' );
	}

	/**
	 * Add the filter to the pool of available filters.
	 *
	 * @param mixed[] $filters
	 * @param string $query_mode
	 * @return mixed[]
	 */
	private function add_filter( $filters, $query_mode ) {
		$filters[ ProductOnsale::SELECTOR_SLUG ] = array(
			/* translators: Name for a query filter by WooCommerce product sale status */
			'name' => __( 'Product filter: on sale status', 'wpv-views' ),
			'present' => array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG ),
			'callback' => array( $this, 'add_filter_list_item' ),
			'args' => array(
				'view-query-mode' => $query_mode,
				\WPV_Filter_Manager::SETTING_KEY => array(
					ProductOnsale::SLUG => array(
						'mode' => 'query_filter',
					),
				),
			),
			/* translators: Name for the group of query filters for WooCommerce products */
			'group' => __( 'Product filters', 'wpv-views' )
		);
		return $filters;
	}

	/**
	 * Print the existing filter in the filters list.
	 *
	 * @param mixed[] $view_settings
	 */
	public function add_filter_list_item( $view_settings ) {
		if ( false === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG ), false ) ) {
			return;
		}

		\WPV_Filter_Item::simple_filter_list_item(
			ProductOnsale::SELECTOR_SLUG,
			'posts',
			ProductOnsale::SELECTOR_SLUG,
			/* translators: Title for the settings section fo the filter by WooCommere product sale status */
			__( 'Filter products by sale status', 'wpv-views' ),
			$this->get_filter_ui( $view_settings )
		);
	}

	/**
	 * Gather the filter options to offer to the user.
	 *
	 * @param mixed[] $view_settings
	 * @return string
	 */
	private function get_filter_ui( $view_settings ) {
		$view_settings = $this->set_filter_defaults( $view_settings );
		$views_ajax = \WPV_Ajax::get_instance();
		ob_start()
		?>
		<p class="wpv-filter-<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-edit-summary js-wpv-filter-summary js-wpv-filter-<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-summary">
			<?php echo $this->get_filter_summary( $view_settings ); ?>
		</p>
		<?php
		if ( \WPV_Filter_Manager::FILTER_MODE_FULL === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL ) ) {
			\WPV_Filter_Item::simple_filter_list_item_buttons(
				ProductOnsale::SELECTOR_SLUG,
				$views_ajax->get_action_js_name( \WPV_Ajax::CALLBACK_FILTER_POST_PRODUCT_ONSALE_UPDATE ),
				wp_create_nonce( self::NONCE ),
				$views_ajax->get_action_js_name( \WPV_Ajax::CALLBACK_FILTER_POST_PRODUCT_ONSALE_DELETE ),
				wp_create_nonce( self::NONCE )
			);
		}
		?>
		<div id="wpv-filter-<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-edit"
			class="wpv-filter-edit js-wpv-filter-edit"
			style="padding-bottom:28px;">
			<div id="wpv-filter-<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>"
				class="js-wpv-filter-options js-wpv-filter-<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-options">
				<p>
					<input type="radio" id="<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-mode-query_filter" class="js-post-product-onsale-mode" name="post_product/onsale[mode]" value="query_filter" <?php checked( 'query_filter', $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductOnsale::SLUG ][ 'mode' ] ); ?> autocomplete="off" />
					<?php /* translators: Label for the filter option to return only WooCommerce products on sale */ ?>
					<label for="<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-mode-query_filter"><?php _e( 'Return only products on sale', 'wpv-views' ); ?></label>
				</p>
				<?php if ( 'normal' === $view_settings['view-query-mode'] ) { ?>
					<p>
						<input type="radio" id="<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-mode-shortcode_attribute" class="js-post-product-onsale-mode" name="post_product/onsale[mode]" value="shortcode_attribute" <?php checked( 'shortcode_attribute', $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductOnsale::SLUG ][ 'mode' ] ); ?> autocomplete="off" />
						<?php /* translators: Label for the filter option to return only WooCommerce products on sale if a shortcode attribute is passed */ ?>
						<label for="<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-mode-shortcode_attribute"><?php _e( 'Return only products on sale when passing a value of <em>1</em> in the shortcode attribute ', 'wpv-views' ); ?></label>
						<input class="js-post-product-onsale-shortcode-attribute js-wpv-filter-validate" name="post_product/onsale[shortcode_attribute]" data-type="shortcode" type="text" value="<?php echo esc_attr( $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductOnsale::SLUG ][ 'shortcode_attribute' ] ); ?>" autocomplete="off" />
					</p>
				<?php } ?>
				<p>
					<input type="radio" id="<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-mode-url_parameter" class="js-post-product-onsale-mode" name="post_product/onsale[mode]" value="url_parameter" <?php checked( 'url_parameter', $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductOnsale::SLUG ][ 'mode' ] ); ?> autocomplete="off" />
					<?php /* translators: Label for the filter option to return only WooCommerce products on sale if an URL parameter is passed */ ?>
					<label for="<?php echo esc_attr( ProductOnsale::SELECTOR_SLUG ); ?>-mode-url_parameter"><?php _e( 'Return only products on sale when passing a value of <em>1</em> in the URL parameter ', 'wpv-views' ); ?></label>
					<input class="js-post-product-onsale-url-parameter js-wpv-filter-validate" name="post_product/onsale[url_parameter]" data-type="url" type="text" value="<?php echo esc_attr( $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductOnsale::SLUG ][ 'url_parameter' ] ); ?>" autocomplete="off" />
				</p>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
		</div>
		<?php
		$res = ob_get_clean();
		return $res;
	}

	/**
	 * Set filter default values.
	 *
	 * @param mixed[] $view_settings
	 * @return mixed[]
	 */
	private function set_filter_defaults( $view_settings ) {
		if ( false !== toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG ), false ) ) {
			$view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductOnsale::SLUG ] = array(
				'mode' => toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'mode' ), 'query_filter' ),
				'shortcode_attribute' => toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'shortcode_attribute' ), 'onsale' ),
				'url_parameter' => toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'url_parameter' ), 'wpv-on-sale' ),
				\WPV_Filter_Manager::EDITOR_MODE => toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL ),
			);
		}
		return $view_settings;
	}

	/**
	 * Register the shortcode attribute expected by this filter in the View shortcode GUI.
	 *
	 * @param mixed[] $attributes
	 * @param mixed[] $view_settings
	 * @return mixed[]
	 */
	public function shortcode_attributes( $attributes, $view_settings ) {
		if ( 'shortcode_attribute' === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'mode' ), 'query_filter' ) ) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_product/onsale',
				/* translators: Label for the option to provide a value for the shortcode attribute mastering this filter when inserting a View */
				'filter_label'	=> __( 'Post Product Onsale', 'wpv-views' ),
				'value'			=> toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'shortcode_attribute' ) ),
				'attribute'		=> toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'shortcode_attribute' ) ),
				'expected'		=> 'string',
				'placeholder'	=> '1',
				/* translators: Description for the option to provide a value for the shortcode attribute mastering this filter when inserting a View */
				'description'	=> __( 'Please type "1" to list only products on sale', 'wpv-views' ),
			);
		}
		return $attributes;
	}

	/**
	 * Register the URL parameter expected by this filter in the View shortcode GUI.
	 *
	 * @param mixed[] $attributes
	 * @param mixed[] $view_settings
	 * @return mixed[]
	 */
	public function url_parameters( $attributes, $view_settings ) {
		if ( 'url_parameter' === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'mode' ), 'query_filter' ) ) {
			$attributes[] = array(
				'query_type'	=> 'posts',
				'filter_type'	=> 'post_product/onsale',
				/* translators: Label for the option to provide a value for the URL parameter mastering this filter when inserting a View */
				'filter_label'	=> __( 'Post Product Onsale', 'wpv-views' ),
				'value'			=> toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'url_parameter' ) ),
				'attribute'		=> toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'url_parameter' ) ),
				'expected'		=> 'string',
				'placeholder'	=> '1',
				/* translators: Description for the option to provide a value for the URL parameter mastering this filter when inserting a View */
				'description'	=> __( 'Please type "1" to list only products on sale', 'wpv-views' ),
			);
		}
		return $attributes;
	}

	/**
	 * Gather the filter summary based on its settings.
	 *
	 * @param mixed[] $view_settings
	 * @return void|string
	 */
	public function get_filter_summary( $view_settings ) {
		if ( false  === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG ), false ) ) {
			return;
		}

		$editor_mode = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL );
		if ( \WPV_Filter_Manager::FILTER_MODE_FROM_SEARCH_FILTER === $editor_mode ) {
			/* translators: Summary for this filter when created with a frontend filter control */
			return __( 'Return products based on the frontend search filter selection.', 'wpv-views' );
		}

		$mode = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'mode' ), false );
		switch ( $mode ) {
			case 'shortcode_attribute':
				return sprintf(
					/* translators: Summary for this filter when set to listen to a shortcode attribute */
					__( 'Return only products on sale when passing a value of <em>1</em> in the shortcode attribute %s.', 'wpv-views' ),
					'<em>' . toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'shortcode_attribute' ) ) . '</em>'
				);
			case 'url_parameter':
				return sprintf(
					/* translators: Summary for this filter when set to listen to an URL parameter */
					__( 'Return only products on sale when passing a value of <em>1</em> in the URL parameter %s.', 'wpv-views' ),
					'<em>' . toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductOnsale::SLUG, 'url_parameter' ) ) . '</em>'
				);
			case 'query_filter':
				/* translators: Summary for this filter when set to return only WoCommerce products on sale */
				return __( 'Return only products on sale.', 'wpv-views' );
		}

		return;
	}

}
