<?php

namespace OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice;

use OTGS\Toolset\Views\Controller\Filters\AbstractGui;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice;
use OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice\ProductPriceQuery;
use OTGS\Toolset\Views\Model\WooCommerce\WcQuery;

/**
 * User interface for the filter.
 * @todo The GUI should reflect if a legacy field was used, and inform of the change.
 */
class ProductPriceGui extends AbstractGui {

	const SCRIPT_BACKEND = 'wpv-filter-post-product-price';
	const SCRIPT_BACKEND_FILENAME = 'post_product_price';
	const NONCE = 'wpv_view_filter_post_product_price_nonce';

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
		$filters[ ProductPrice::SELECTOR_SLUG ] = array(
			/* translators: Name for a query filter by WooCommerce product price */
			'name' => __( 'Product filter: price', 'wpv-views' ),
			'present' => array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG ),
			'callback' => array( $this, 'add_filter_list_item' ),
			'args' => array(
				'view-query-mode' => $query_mode,
				\WPV_Filter_Manager::SETTING_KEY => array(
					ProductPrice::SLUG => array(
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
		if ( false === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG ), false ) ) {
			return;
		}

		\WPV_Filter_Item::simple_filter_list_item(
			ProductPrice::SELECTOR_SLUG,
			'posts',
			ProductPrice::SELECTOR_SLUG,
			/* translators: Title for the settings section fo the filter by WooCommere product price */
			__( 'Filter products by price', 'wpv-views' ),
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
		$filter_summary = $this->get_filter_summary( $view_settings );
		ob_start()
		?>
		<p class="wpv-filter-<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-edit-summary js-wpv-filter-summary js-wpv-filter-<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-summary">
			<?php echo $filter_summary; ?>
		</p>
		<?php
		if ( \WPV_Filter_Manager::FILTER_MODE_FULL === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL ) ) {
			\WPV_Filter_Item::simple_filter_list_item_buttons(
				ProductPrice::SELECTOR_SLUG,
				$views_ajax->get_action_js_name( \WPV_Ajax::CALLBACK_FILTER_POST_PRODUCT_PRICE_UPDATE ),
				wp_create_nonce( self::NONCE ),
				$views_ajax->get_action_js_name( \WPV_Ajax::CALLBACK_FILTER_POST_PRODUCT_PRICE_DELETE ),
				wp_create_nonce( self::NONCE )
			);
		}
		$legacy_settings = toolset_getnest( $view_settings, [ \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, 'legacy' ], false );
		if ( false !== $legacy_settings ) {
			?>
			<div id="wpv-filter-<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-edit"
			class="wpv-filter-edit js-wpv-filter-edit"
			style="padding-bottom:28px;">
				<div id="wpv-filter-<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>"
					class="js-wpv-filter-options js-wpv-filter-<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-options">
					<p>
					<?php
						_e( 'This filter by product price was ported from a previous version of Toolset WooCommerce Blocks and can not be edited.', 'wpv-views' );
					?>
					</p>
				</div>
				<div class="js-wpv-filter-toolset-messages"></div>
			</div>
			<?php
			$res = ob_get_clean();
			return $res;
		}
		?>
		<div id="wpv-filter-<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-edit"
			class="wpv-filter-edit js-wpv-filter-edit"
			style="padding-bottom:28px;">
			<div id="wpv-filter-<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>"
				class="js-wpv-filter-options js-wpv-filter-<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-options">
				<p>
					<input type="radio" id="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-mode-query_filter" class="js-post-product-price-mode" name="post_product/price[mode]" value="query_filter" <?php checked( 'query_filter', $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductPrice::SLUG ][ 'mode' ] ); ?> autocomplete="off" />
					<?php /* translators: Label for the filter option to return only WooCommerce products with price between fixed boundaries */ ?>
					<label for="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-mode-query_filter"><?php _e( 'Return only products with price between <strong>those values</strong>:', 'wpv-views' ); ?></label>
					<br />
					<label for="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-value-min"><?php _e( 'Minimum', 'wpv-views' ); ?></label>
					<input type="text" id="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-value-min" value="<?php echo esc_attr( $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductPrice::SLUG ]['values']['min'] ) ; ?>" name="post_product/price[values][min]">
					<br />
					<label for="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-value-max"><?php _e( 'Maximum', 'wpv-views' ); ?></label>
					<input type="text" id="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-value-max" value="<?php echo esc_attr( $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductPrice::SLUG ]['values']['max']  ) ; ?>" name="post_product/price[values][max]">
				</p>
				<?php if ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) ) { ?>
					<p>
						<input type="radio" id="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-mode-shortcode_attribute" class="js-post-product-price-mode" name="post_product/price[mode]" value="shortcode_attribute" <?php checked( 'shortcode_attribute', $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductPrice::SLUG ][ 'mode' ] ); ?> autocomplete="off" />
						<?php /* translators: Label for the filter option to return only WooCommerce products with price between values from shortcode attributes */ ?>
						<label for="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-mode-shortcode_attribute">
							<?php echo sprintf(
								__( 'Return only products between minimum and maximum prices passed in the <strong>shortcode attributes</strong> %1$s and %2$s', 'wpv-views' ),
								'<em>' . ProductPriceQuery::SHORTCODE_ATTRIBUTE_MIN_PRICE . '</em>',
								'<em>' . ProductPriceQuery::SHORTCODE_ATTRIBUTE_MAX_PRICE . '</em>'
							); ?>
						</label>
					</p>
				<?php } ?>
				<p>
					<input type="radio" id="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-mode-url_parameter" class="js-post-product-price-mode" name="post_product/price[mode]" value="url_parameter" <?php checked( 'url_parameter', $view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductPrice::SLUG ][ 'mode' ] ); ?> autocomplete="off" />
					<?php /* translators: Label for the filter option to return only WooCommerce products with price between values from URL parameters */ ?>
					<label for="<?php echo esc_attr( ProductPrice::SELECTOR_SLUG ); ?>-mode-url_parameter">
						<?php echo sprintf(
							__( 'Return only products between minimum and maximum prices passed in the <strong>URL parameters</strong> %1$s and %2$s', 'wpv-views' ),
							'<em>' . ( ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) ) ? WcQuery::URL_PARAM_VIEW_MIN_PRICE : WcQuery::URL_PARAM_MIN_PRICE ) . '</em>',
							'<em>' . ( ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) ) ? WcQuery::URL_PARAM_VIEW_MAX_PRICE : WcQuery::URL_PARAM_MAX_PRICE ) . '</em>'
						); ?>
					</label>
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
		if ( false !== toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG ), false ) ) {
			$view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ ProductPrice::SLUG ] = array(
				'mode' => toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, 'mode' ), 'query_filter' ),
				'values' => array(
					'min' => '',
					'max' => '',
				),
				'legacy' => toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, 'legacy' ), false ),
				\WPV_Filter_Manager::EDITOR_MODE => toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL ),
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
		if ( 'shortcode_attribute' === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, 'mode' ), 'query_filter' ) ) {
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_product/price',
				/* translators: Label for the option to provide a value for the shortcode attribute mastering this filter when inserting a View */
				'filter_label'	=> __( 'Post Product Price Min', 'wpv-views' ),
				'value'			=> ProductPriceQuery::SHORTCODE_ATTRIBUTE_MIN_PRICE,
				'attribute'		=> ProductPriceQuery::SHORTCODE_ATTRIBUTE_MIN_PRICE,
				'expected'		=> 'string',
				'placeholder'	=> '10.00',
				/* translators: Description for the option to provide a value for the shortcode attribute mastering this filter when inserting a View */
				'description'	=> __( 'Please type the minimum price to filter by; use . to separate decimals', 'wpv-views' ),
			);
			$attributes[] = array(
				'query_type'	=> $view_settings['query_type'][0],
				'filter_type'	=> 'post_product/price',
				/* translators: Label for the option to provide a value for the shortcode attribute mastering this filter when inserting a View */
				'filter_label'	=> __( 'Post Product Price Max', 'wpv-views' ),
				'value'			=> ProductPriceQuery::SHORTCODE_ATTRIBUTE_MAX_PRICE,
				'attribute'		=> ProductPriceQuery::SHORTCODE_ATTRIBUTE_MAX_PRICE,
				'expected'		=> 'string',
				'placeholder'	=> '90.00',
				/* translators: Description for the option to provide a value for the shortcode attribute mastering this filter when inserting a View */
				'description'	=> __( 'Please type the maximum price to filter by; use . to separate decimals', 'wpv-views' ),
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
		if ( 'url_parameter' === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, 'mode' ), 'query_filter' ) ) {
			$attributes[] = array(
				'query_type'	=> 'posts',
				'filter_type'	=> 'post_product/price',
				/* translators: Label for the option to provide a value for the URL parameter mastering this filter when inserting a View */
				'filter_label'	=> __( 'Post Product Price Min', 'wpv-views' ),
				'value'			=> ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) ) ? WcQuery::URL_PARAM_VIEW_MIN_PRICE : WcQuery::URL_PARAM_MIN_PRICE,
				'attribute'		=> ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) ) ? WcQuery::URL_PARAM_VIEW_MIN_PRICE : WcQuery::URL_PARAM_MIN_PRICE,
				'expected'		=> 'string',
				'placeholder'	=> '10.00',
				/* translators: Description for the option to provide a value for the URL parameter mastering this filter when inserting a View */
				'description'	=> __( 'Please type the minimum price to filter by; use . to separate decimals', 'wpv-views' ),
			);
			$attributes[] = array(
				'query_type'	=> 'posts',
				'filter_type'	=> 'post_product/price',
				/* translators: Label for the option to provide a value for the URL parameter mastering this filter when inserting a View */
				'filter_label'	=> __( 'Post Product Price Max', 'wpv-views' ),
				'value'			=> ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) ) ? WcQuery::URL_PARAM_VIEW_MAX_PRICE : WcQuery::URL_PARAM_MAX_PRICE,
				'attribute'		=> ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) ) ? WcQuery::URL_PARAM_VIEW_MAX_PRICE : WcQuery::URL_PARAM_MAX_PRICE,
				'expected'		=> 'string',
				'placeholder'	=> '90.00',
				/* translators: Description for the option to provide a value for the URL parameter mastering this filter when inserting a View */
				'description'	=> __( 'Please type the maximum price to filter by; use . to separate decimals', 'wpv-views' ),
			);
		}
		return $attributes;
	}

	private function get_legacy_summary( $legacy_settings ) {
		$field_filter_value = toolset_getarr( $legacy_settings, 'value', false );
		$field_filter_compare = toolset_getarr( $legacy_settings, 'compare', false );

		if (
			false === $field_filter_value
			|| false === $field_filter_compare
		) {
			return __( 'This filter has a problem and will not be applied.', 'wpv-views' );
		}

		$field_filter_value_array = explode( ',', $field_filter_value );
		$field_filter_value_array = array_map( 'trim', $field_filter_value_array );

		switch ( $field_filter_compare ) {
			case '=':
			case 'eq':
				// Filter products with an exact price.
				$value_to_compare = reset( $field_filter_value_array );
				return sprintf(
					__( 'Return only products with a price of %1$s.', 'wpv-views' ),
					esc_html( $value_to_compare )
				);
			case '!=':
			case 'neq':
				// Not supported by this API.
				return __( 'This filter has a problem and will not be applied.', 'wpv-views' );
			case '>':
			case 'gt':
			case '>=':
			case 'get':
				$value_to_compare = reset( $field_filter_value_array );
				return sprintf(
					__( 'Return only products with a price greater than %1$s.', 'wpv-views' ),
					esc_html( $value_to_compare )
				);
			case '<':
			case 'lt':
			case '<=':
			case 'let':
				$value_to_compare = reset( $field_filter_value_array );
				return sprintf(
					__( 'Return only products with a price lower than %1$s.', 'wpv-views' ),
					esc_html( $value_to_compare )
				);
			case 'LIKE':
			case 'NOT LIKE':
				// Not supported by this API.
				return __( 'This filter has a problem and will not be applied.', 'wpv-views' );
			case 'IN':
			case 'NOT IN':
				// Not supported by this API.
				return __( 'This filter has a problem and will not be applied.', 'wpv-views' );
			case 'BETWEEN':
				if ( count( $field_filter_value_array ) < 2 ) {
					return __( 'This filter has a problem and will not be applied.', 'wpv-views' );
				}
				return sprintf(
					__( 'Return only products with a price between %1$s and %2$s.', 'wpv-views' ),
					esc_html( $field_filter_value_array[ 0 ] ),
					esc_html( $field_filter_value_array[ 1 ] )
				);
			case 'NOT BETWEEN':
				// Not supported by this API.
				return __( 'This filter has a problem and will not be applied.', 'wpv-views' );
		}

		return __( 'This filter has a problem and will not be applied.', 'wpv-views' );
	}

	/**
	 * Gather the filter summary based on its settings.
	 *
	 * @param mixed[] $view_settings
	 * @return void|string
	 */
	public function get_filter_summary( $view_settings ) {
		if ( false  === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG ), false ) ) {
			return;
		}

		$editor_mode = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL );
		if ( \WPV_Filter_Manager::FILTER_MODE_FROM_SEARCH_FILTER === $editor_mode ) {
			/* translators: Summary for this filter when created with a frontend filter control */
			return __( 'Return products based on the frontend search filter selection.', 'wpv-views' );
		}

		$legacy_settings = toolset_getnest( $view_settings, [ \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, 'legacy' ], false );
		if ( false !== $legacy_settings ) {
			return $this->get_legacy_summary( $legacy_settings );
		}

		$mode = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, 'mode' ), false );
		switch ( $mode ) {
			case 'shortcode_attribute':
				return sprintf(
					/* translators: Summary for this filter when set to listen to a shortcode attribute */
					__( 'Return only products with prices between the values passed in the shortcode attributes %1$s and %2$s.', 'wpv-views' ),
					'<em>' . ProductPriceQuery::SHORTCODE_ATTRIBUTE_MIN_PRICE . '</em>',
					'<em>' . ProductPriceQuery::SHORTCODE_ATTRIBUTE_MAX_PRICE . '</em>'
				);
			case 'url_parameter':
				return sprintf(
					/* translators: Summary for this filter when set to listen to an URL parameter */
					__( 'Return only products with prices between the values passed in the URL parameters %1$s and %2$s.', 'wpv-views' ),
					'<em>' . ( ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) ) ? WcQuery::URL_PARAM_VIEW_MIN_PRICE : WcQuery::URL_PARAM_MIN_PRICE ) . '</em>',
					'<em>' . ( ( 'normal' === toolset_getarr( $view_settings, 'view-query-mode', 'normal' ) ) ? WcQuery::URL_PARAM_VIEW_MAX_PRICE : WcQuery::URL_PARAM_MAX_PRICE ) . '</em>'
				);
			case 'query_filter':
				$min_value = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, 'values', 'min' ), 0 );
				$max_value = toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, ProductPrice::SLUG, 'values', 'max' ), PHP_INT_MAX );

				$min_value = ( empty( $min_value ) ) ? 0 : $min_value;
				$max_value = ( empty( $max_value ) ) ? PHP_INT_MAX : $max_value;
				if ( 0 === $min_value ) {
					if ( PHP_INT_MAX === $max_value ) {
						return __( 'Return all products, apply no filter by price', 'wpv-views' );
					}
					return sprintf(
						__( 'Return products with price below %1$s', 'wpv-views' ),
						esc_html( $max_value )
					);
				}
				if ( PHP_INT_MAX === $max_value ) {
					return sprintf(
						__( 'Return products with price above %1$s', 'wpv-views' ),
						esc_html( $min_value )
					);
				}
				return sprintf(
					__( 'Return products with price between %1$s and %2$s', 'wpv-views' ),
					esc_html( $min_value ),
					esc_html( $max_value )
				);
		}

		return;
	}

}
