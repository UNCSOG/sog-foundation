<?php

namespace OTGS\Toolset\Views\Model\Shortcode\Control;

use OTGS\Toolset\Views\Controller\Filters\Post\ProductPrice;
use OTGS\Toolset\Views\Model\WooCommerce\WcQuery;
use OTGS\Toolset\Views\Model\Wordpress\Wpdb;

class WpvControlPostProductPrice implements \WPV_Shortcode_Interface {

	const SHORTCODE_NAME = 'wpv-control-post-product-price';

	/** @var array */
	private $shortcode_atts = array(
		'type' => 'range',
		'step' => '1',
		'start' => 'minimum',
	);

	/** @var array */
	private $required_atts = array();

	/** @var \WPV_Filter_Manager */
	private $filter_manager;

	/** @var \wpdb */
	private $wpdb;

	/** @var string|null */
	private $user_content;

	/** @var array */
	private $user_atts;

	/** @var WPV_Filter_Base */
	private $filter;

	/**
	 * Constructor.
	 *
	 * @param \WPV_Filter_Manager $filter_manager
	 * @param Wpdb $wpdb
	 */
	public function __construct(
		\WPV_Filter_Manager $filter_manager,
		Wpdb $wpdb
	) {
		$this->filter_manager = $filter_manager;
		$this->wpdb = $wpdb->get_wpdb();
	}

	private function get_min_max_values( $is_wpa ) {
		$product_visibility_not_in_query = '';
		$product_visibility_terms  = wc_get_product_visibility_term_ids();
		// Do not include out of stock products on WPAs.
		if (
			true === $is_wpa
			&& 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' )
		) {
			$product_visibility_terms  = wc_get_product_visibility_term_ids();
			$product_visibility_not_in = $product_visibility_terms['outofstock'];
			if ( ! empty( $product_visibility_not_in ) ) {
				$product_visibility_not_in_query = "AND {$this->wpdb->posts}.ID NOT IN (
					SELECT object_id FROM {$this->wpdb->term_relationships}
					WHERE {$this->wpdb->term_relationships}.term_taxonomy_id IN ( $product_visibility_not_in )
				)";
			}
		}

		$sql = "
			SELECT min( min_price ) as min_price, MAX( max_price ) as max_price
			FROM {$this->wpdb->wc_product_meta_lookup}
			WHERE product_id IN (
				SELECT ID FROM {$this->wpdb->posts}
				WHERE {$this->wpdb->posts}.post_type IN ( %s )
				AND {$this->wpdb->posts}.post_status = %s
				{$product_visibility_not_in_query}
			)";

		$min_max = $this->wpdb->get_row(
			$this->wpdb->prepare(
				$sql,
				array(
					'product',
					'publish',
				)
			)
		);

		return $min_max;
	}

	/**
	 * Get the value of the shortcode.
	 *
	 * @param string[] $atts
	 * @param string|null $content
	 * @return string
	 */
	public function get_value( $atts, $content = null ) {
		$this->user_atts = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		foreach ( $this->required_atts as $required_att ) {
			if ( empty( $this->user_atts[ $required_att ] ) ) {
				return '';
			}
		}

		$this->filter = $this->filter_manager->get_filter( \Toolset_Element_Domain::POSTS, ProductPrice::SLUG );

		if ( false === $this->filter->are_conditions_met() ) {
			return '';
		}

		if ( null == $this->user_content ) {
			$this->user_content = __( 'Price: %%MIN%% &mdash; %%MAX%%', 'wpv-views' );
		}

		$current_view = apply_filters( 'wpv_filter_wpv_get_current_view', 0 );
		$is_wpa = \WPV_View_Base::is_archive_view( $current_view );

		// Gather the minimum and maximum prices for existing products.
		$min_max = $this->get_min_max_values( $is_wpa );

		// Decide which name attribute (hence URL parameter) is relevant here.
		$min_price_name = $is_wpa
			? WcQuery::URL_PARAM_MIN_PRICE
			: WcQuery::URL_PARAM_VIEW_MIN_PRICE;
		$max_price_name = $is_wpa
			? WcQuery::URL_PARAM_MAX_PRICE
			: WcQuery::URL_PARAM_VIEW_MAX_PRICE;

		// Calculate minimum and maximum boundaries for the slider.
		// Minimum depends on the user attribute or the minimum product price.
		// Maximum depends on step as the slider defaults to the later complete "step" group that fits into the maximum boundary.
		$min_boundary = ( 'minimum' === $this->user_atts['start'] ) ? $min_max->min_price : 0;
		$max_boundary = $min_max->max_price;
		if ( '1' !== $this->user_atts['step'] ) {
			$boundary_remainder = ( (int) $max_boundary - (int) $min_boundary ) % (int) $this->user_atts['step'];
			if ( 0 !== $boundary_remainder ) {
				$max_boundary = (string) ( (int) $max_boundary + ( (int) $this->user_atts['step'] - $boundary_remainder ) );
			}
		}

		// Do we have any posted value?
		$min_price = toolset_getget( $min_price_name, $min_boundary );
		$max_price = toolset_getget( $max_price_name, $max_boundary );

		// Currency data so we can adjust the price range preview.
		$data_currency = array(
			'format' => str_replace( array( '%1$s', '%2$s' ), array( get_woocommerce_currency_symbol(), '%v' ), get_woocommerce_price_format() ),
			'decimal_separator' => wc_get_price_decimal_separator(),
			'thousand_separator' => wc_get_price_thousand_separator(),
		);

		$this->user_content = esc_html( $this->user_content );
		$min_span = '<span class="wpv-range-slider-amount-label-from js-wpv-range-slider-amount-label-from">' . str_replace( '%v', (int) $min_price, $data_currency['format'] ) . '</span>';
		$max_span = '<span class="wpv-range-slider-amount-label-to js-wpv-range-slider-amount-label-to">' . str_replace( '%v', (int) $max_price, $data_currency['format'] ) . '</span>';
		$this->user_content = str_replace(
			[ '%%MIN%%', '%%MAX%%' ],
			[ $min_span, $max_span ],
			$this->user_content
		);

		ob_start();
		?>
		<div class="wpv-range-slider-wrapper js-wpv-range-slider-wrapper" data-currency="<?php echo esc_attr( json_encode( $data_currency ) ); ?>">
			<div class="wpv-range-slider js-wpv-range-slider"></div>
			<div class="wpv-range-slider-amount js-wpv-range-slider-amount" data-step="<?php echo esc_attr( $this->user_atts['step'] ); ?>">
				<div>
					<input type="text" class="wpv-range-slider-amount-min js-wpv-range-slider-amount-min js-wpv-filter-trigger" id="wpv-product-min_price-<?php echo esc_attr( $current_view ); ?>" name="<?php echo esc_attr( $min_price_name ); ?>" value="<?php echo esc_attr( $min_price ); ?>" data-min="<?php echo esc_attr( $min_boundary ); ?>" placeholder="<?php echo esc_attr__( 'Min price', 'woocommerce' ); ?>" style="display: none;"><input type="text" class="wpv-range-slider-amount-max js-wpv-range-slider-amount-max js-wpv-filter-trigger" id="wpv-product-max_price-<?php echo esc_attr( $current_view ); ?>" name="<?php echo esc_attr( $max_price_name ); ?>" value="<?php echo esc_attr( $max_price ); ?>" data-max="<?php echo esc_attr( $max_boundary ); ?>" placeholder="<?php echo esc_attr__( 'Max price', 'woocommerce' ); ?>" style="display: none;">
				</div>
				<div class="wpv-range-slider-amount-label js-wpv-range-slider-amount-label">
				<?php echo $this->user_content; ?>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
		$outcome = ob_get_clean();

		return $outcome;
	}

}
