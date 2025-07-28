<?php

namespace Wpae\Pro\Filtering;


/**
 * Class FilteringOrders
 * @package Pmwe\Pro\Filtering
 */
class FilteringOrdersHPOS extends \Wpae\Pro\Filtering\FilteringCPT
{

    /**
     * @var string
     */
    private $productsWhere = "";
    /**
     * @var array
     */
    private $productsjoin = array();

    private $ordersTableName = '';

    public function __construct()
    {
        parent::__construct();
        $this->ordersTableName = $this->wpdb->prefix . 'wc_orders';
    }

    /**
     *
     */
    public function parse(){

        if ( $this->isFilteringAllowed()){

            $this->checkNewStuff();

            // No Filtering Rules defined
            if ( empty($this->filterRules)) return FALSE;

            $this->queryWhere = ($this->isExportNewStuff() || $this->isExportModifiedStuff()) ? $this->queryWhere . " AND (" : " AND (";

            // Apply Filtering Rules
            foreach ($this->filterRules as $rule) {
                if ( is_null($rule->parent_id) ) {
                    $this->parse_single_rule($rule);
                }
            }
            if ($this->meta_query || $this->tax_query) {
                $this->queryWhere .= " ) GROUP BY $this->ordersTableName.ID";
            }
            else {
                $this->queryWhere .= ")";
            }
        }
    }

    /**
     * @param $rule
     */
    public function parse_single_rule($rule) {

        // Filtering by Order meta data
        if (strpos($rule->element, "item_") === 0){
            apply_filters('wp_all_export_single_filter_rule', $rule);
            $rule->element = preg_replace('%^item_%', '', $rule->element);
            $table_prefix = $this->wpdb->prefix;

            switch ($rule->element){
                case '__product_sku':
                    $rule->element = 'cf__sku';
                    $this->filterByProducts($rule);
                    break;
                case '__product_title':
                    $rule->element = 'post_title';
                    $this->filterByProducts($rule);
                    break;
                case '__coupons_used':
                    $this->meta_query = true;
                    $item_alias = (count($this->queryJoin) > 0) ? 'order_item' . count($this->queryJoin) : 'order_item';
                    $this->queryJoin[] = " INNER JOIN {$table_prefix}woocommerce_order_items AS $item_alias ON ($this->ordersTableName.id = $item_alias.order_id) ";
                    $this->queryWhere .= "$item_alias.order_item_type = 'coupon' AND $item_alias.order_item_name " . $this->parse_condition($rule, false, $item_alias);
                    break;
                default:
                    $this->meta_query = true;
                    if ($rule->condition == 'is_empty') {
                        $item_alias = (count($this->queryJoin) > 0) ? 'order_item' . count($this->queryJoin) : 'order_item';
                        $item_meta_alias = (count($this->queryJoin) > 0) ? 'order_itemmeta' . count($this->queryJoin) : 'order_itemmeta';
                        $this->queryJoin[] = " LEFT JOIN {$table_prefix}woocommerce_order_items AS $item_alias ON ($this->ordersTableName.id = $item_alias.order_id) ";
                        $this->queryJoin[] = " LEFT JOIN {$table_prefix}woocommerce_order_itemmeta AS $item_meta_alias ON ($item_alias.order_item_id = $item_meta_alias.order_item_id AND $item_meta_alias.meta_key = '{$rule->element}') ";
                        $this->queryWhere .= "$item_meta_alias.meta_id " . $this->parse_condition($rule);
                    }
                    else {
                        $item_alias = (count($this->queryJoin) > 0) ? 'order_item' . count($this->queryJoin) : 'order_item';
                        $item_meta_alias = (count($this->queryJoin) > 0) ? 'order_itemmeta' . count($this->queryJoin) : 'order_itemmeta';
                        $this->queryJoin[] = " INNER JOIN {$table_prefix}woocommerce_order_items AS $item_alias ON ($this->ordersTableName.id = $item_alias.order_id) ";
                        $this->queryJoin[] = " INNER JOIN {$table_prefix}woocommerce_order_itemmeta AS $item_meta_alias ON ($item_alias.order_item_id = $item_meta_alias.order_item_id) ";
                        $this->queryWhere .= "$item_meta_alias.meta_key = '{$rule->element}' AND $item_meta_alias.meta_value " . $this->parse_condition($rule, false, $item_meta_alias);
                    }
                    break;
            }
            $this->recursion_parse_query($rule);
            return;
        }

        // Filtering by Order Items data
        if (strpos($rule->element, "product_") === 0){
            // Filter Orders by order item data
            apply_filters('wp_all_export_single_filter_rule', $rule);
            $this->filterByProducts($rule);
            $this->recursion_parse_query($rule);
            return;
        }

        if($rule->element === 'ID') {
            $rule->element = 'id';
        }
        switch ($rule->element) {
            case 'id':
            case 'post_parent':
            case 'post_author':

                $this->queryWhere .= "$this->ordersTableName.$rule->element " . $this->parse_condition($rule, true);
                break;
            case 'post_status':
                $this->queryWhere .= "$this->ordersTableName.status " . $this->parse_condition($rule);
                break;
            case 'cf__order_currency':
                $this->queryWhere .= "$this->ordersTableName.currency " . $this->parse_condition($rule);
                break;
            case 'cf__order_key':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_operational_data AS operational_data ON ($this->ordersTableName.id = operational_data.order_id) ";
                $this->queryWhere .= " operational_data.order_key " . $this->parse_condition($rule);
                break;
            case 'post_date':
                $this->parse_date_field($rule);
                $this->queryWhere .= "$this->ordersTableName.date_created_gmt " . $this->parse_condition($rule);
                break;
            case 'cf__completed_date':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_operational_data AS operational_data ON ($this->ordersTableName.id = operational_data.order_id) ";
                $this->parse_date_field($rule);
                $this->queryWhere .= "operational_data.date_completed_gmt " . $this->parse_condition($rule);
                break;
            case 'cf__payment_method_title':
                $this->queryWhere .= "$this->ordersTableName.payment_method_title " . $this->parse_condition($rule);
                break;
            case 'cf__payment_method':
                $this->queryWhere .= "$this->ordersTableName.payment_method " . $this->parse_condition($rule);
                break;
            case 'cf__order_total':
                $this->queryWhere .= "$this->ordersTableName.total_amount " . $this->parse_condition($rule);
                break;
            case 'cf__customer_user':
                $this->queryWhere .= "$this->ordersTableName.customer_id " . $this->parse_condition($rule);
                break;
            case 'post_excerpt':
                $this->queryWhere .= "$this->ordersTableName.customer_note " . $this->parse_condition($rule);
                break;
            case 'cf__billing_first_name':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.first_name " . $this->parse_condition($rule);
                break;
            case 'cf__billing_last_name':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.last_name " . $this->parse_condition($rule);
                break;
            case 'cf__billing_company':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.company " . $this->parse_condition($rule);
                break;
            case 'cf__billing_address_1':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.address_1 " . $this->parse_condition($rule);
                break;
            case 'cf__billing_address_2':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.address_2 " . $this->parse_condition($rule);
                break;
            case 'cf__billing_city':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.city " . $this->parse_condition($rule);
                break;
            case 'cf__billing_state':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.state " . $this->parse_condition($rule);
                break;
            case 'cf__billing_postcode':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.postcode " . $this->parse_condition($rule);
                break;
            case 'cf__billing_country':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.country " . $this->parse_condition($rule);
                break;
            case 'cf__billing_email':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.email " . $this->parse_condition($rule);
                break;
            case 'cf__billing_phone':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS billing_address ON ($this->ordersTableName.id = billing_address.order_id) ";
                $this->queryWhere .= "billing_address.phone " . $this->parse_condition($rule);
                break;
            case 'cf__shipping_first_name':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS shipping_address ON ($this->ordersTableName.id = shipping_address.order_id) ";
                $this->queryWhere .= "shipping_address.first_name " . $this->parse_condition($rule);
                break;
            case 'cf__shipping_last_name':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS shipping_address ON ($this->ordersTableName.id = shipping_address.order_id) ";
                $this->queryWhere .= "shipping_address.last_name " . $this->parse_condition($rule);
                break;
            case 'cf__shipping_company':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS shipping_address ON ($this->ordersTableName.id = shipping_address.order_id) ";
                $this->queryWhere .= "shipping_address.company " . $this->parse_condition($rule);
                break;
            case 'cf__shipping_address_1':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS shipping_address ON ($this->ordersTableName.id = shipping_address.order_id) ";
                $this->queryWhere .= "shipping_address.address_1 " . $this->parse_condition($rule);
                break;
            case 'cf__shipping_address_2':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS shipping_address ON ($this->ordersTableName.id = shipping_address.order_id) ";
                $this->queryWhere .= "shipping_address.address_2 " . $this->parse_condition($rule);
                break;
            case 'cf__shipping_city':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS shipping_address ON ($this->ordersTableName.id = shipping_address.order_id) ";
                $this->queryWhere .= "shipping_address.city " . $this->parse_condition($rule);
                break;
            case 'cf__shipping_state':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS shipping_address ON ($this->ordersTableName.id = shipping_address.order_id) ";
                $this->queryWhere .= "shipping_address.state " . $this->parse_condition($rule);
                break;
            case 'cf__shipping_postcode':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS shipping_address ON ($this->ordersTableName.id = shipping_address.order_id) ";
                $this->queryWhere .= "shipping_address.postcode " . $this->parse_condition($rule);
                break;
            case 'cf__shipping_country':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_addresses AS shipping_address ON ($this->ordersTableName.id = shipping_address.order_id) ";
                $this->queryWhere .= "shipping_address.country " . $this->parse_condition($rule);
                break;
            case 'cf__created_via':
                $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_order_operational_data AS operational_data ON ($this->ordersTableName.id = operational_data.order_id) ";
                $this->queryWhere .= "operational_data.created_via " . $this->parse_condition($rule);
                break;
            case 'cf__transaction_id':
                $this->queryWhere .= "$this->ordersTableName.transaction_id " . $this->parse_condition($rule);
                break;

            case 'post_title':
            case 'post_content':
            case 'guid':
            case 'post_name':
            case 'menu_order':
                $this->queryWhere .= "$this->ordersTableName.$rule->element " . $this->parse_condition($rule);
                break;

	        default:
		        if (strpos($rule->element, 'cf_') === 0) {
					$cf_name = substr($rule->element, 3);

			        $this->queryJoin[] = " INNER JOIN {$this->wpdb->prefix}wc_orders_meta AS order_meta ON ($this->ordersTableName.id = order_meta.order_id) ";
			        $this->queryWhere .= "order_meta.meta_key = '$cf_name' AND order_meta.meta_value " . $this->parse_condition($rule);
		        }
				break;
        }
        $this->recursion_parse_query($rule);
    }

    /**
     * @param $rule
     */
    private function filterByProducts($rule){

        $rule->element = preg_replace('%^product_%', '', $rule->element);

        $mapping = array(
            'content' => 'post_content',
            'excerpt' => 'post_excerpt',
            'date'    => 'post_date'
        );

        if (!empty($mapping[$rule->element])) $rule->element = $mapping[$rule->element];

        $filter_args = array(
            'filter_rules_hierarhy' => json_encode(array($rule)),
            'product_matching_mode' => 'strict',
            'taxonomy_to_export' => ''
        );

        $productsFilter = new \Wpae\Pro\Filtering\FilteringCPT();
        $productsFilter->init($filter_args);
        $productsFilter->parse();

        $this->productsWhere = $productsFilter->get('queryWhere');
        $this->productsjoin  = $productsFilter->get('queryJoin');

        remove_all_actions('parse_query');
        remove_all_filters('posts_clauses');
        wp_all_export_remove_before_post_except_toolset_actions();

        add_filter('posts_join', array(&$this, 'posts_join'), 10, 1);
        add_filter('posts_where', array(&$this, 'posts_where'), 10, 1);
        $productsQuery = new \WP_Query( array( 'post_type' => array('product', 'product_variation'), 'post_status' => 'any', 'orderby' => 'ID', 'order' => 'ASC', 'posts_per_page' => -1 ));

        $ids = array();
        while ( $productsQuery->have_posts() ) {
            $productsQuery->the_post();
            $ids[] = get_the_ID();
        }

        remove_filter('posts_where', array(&$this, 'posts_where'));
        remove_filter('posts_join', array(&$this, 'posts_join'));

        if (!empty($ids)){
            $this->meta_query = true;
            $table_prefix = $this->wpdb->prefix;
            $ids_str = implode(",", $ids);
            $item_alias = (count($this->queryJoin) > 0) ? 'order_item' . count($this->queryJoin) : 'order_item';
            $item_meta_alias = (count($this->queryJoin) > 0) ? 'order_itemmeta' . count($this->queryJoin) : 'order_itemmeta';
            $this->queryJoin[] = " INNER JOIN {$table_prefix}woocommerce_order_items AS $item_alias ON ($this->ordersTableName.id = $item_alias.order_id) ";
            $this->queryJoin[] = " INNER JOIN {$table_prefix}woocommerce_order_itemmeta AS $item_meta_alias ON ($item_alias.order_item_id = $item_meta_alias.order_item_id) ";
            $this->queryWhere .= "($item_meta_alias.meta_key = '_product_id' OR $item_meta_alias.meta_key = '_variation_id') AND $item_meta_alias.meta_value IN ($ids_str)";
        }
    }

    /**
     * @param $where
     * @return string
     */
    public function posts_where($where)
    {
        if ( ! empty($this->productsWhere) ) $where .= $this->productsWhere;
        return $where;
    }

    /**
     * @param $join
     * @return string
     */
    public function posts_join($join){
        if ( ! empty($this->productsjoin) ) {
            $join .= implode( ' ', array_unique( $this->productsjoin ) );
        }
        return $join;
    }

	/**
	 * Included only to override the FilteringCPT default as this is handled in the OrderQuery.php file instead.
	 * @param $postsToExclude
	 * @return void
	 */
	public function getExcludeQueryWhere($postsToExclude)
	{

		//return " AND ({$this->wpdb->posts}.ID NOT IN (" . implode(',', $postsToExclude) . "))";

	}

	/**
	 * Included only to override the FilteringCPT default as this is handled in the OrderQuery.php file instead.
	 * @param $export
	 * @return void
	 */
	public function getModifiedQueryWhere($export)
	{
		//$this->queryWhere .= " AND {$this->wpdb->posts}.post_modified_gmt > '" . $export->registered_on . "' ";
	}
}