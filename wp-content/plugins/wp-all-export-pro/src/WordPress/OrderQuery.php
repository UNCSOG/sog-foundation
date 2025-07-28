<?php

namespace Wpae\WordPress;


class OrderQuery
{
	private $post_id = false;

    public $query = ['post_type' => 'shop_order'];

    public function getOrders($offset = 0, $limit = 0, $post_id = false)
    {
        global $wpdb;

        $query = $this->getQuery($offset, $limit, $post_id);

        return $wpdb->get_results($query);
    }

    public function getQuery($offset = 0, $limit = 0, $post_id = false) {
		global $wpdb;

		if($post_id){
			$this->post_id = $post_id;
		}else if($this->post_id){
			$post_id = $this->post_id;
		}

		// default order_by
	    $order_by = " Order By {$wpdb->prefix}wc_orders.id ASC ";

		$post_id_where = '';

		// Handle RTE exports.
	    if(isset(\XmlExportEngine::$exportOptions['enable_real_time_exports'])
	       && \XmlExportEngine::$exportOptions['enable_real_time_exports']){
			$limit = 1;
			// We want to get the newest order as this is only used when generating an example file.
			$order_by = " Order By {$wpdb->prefix}wc_orders.id DESC ";
	    }

		// Handle RTE or other exports where a single post_id is provided.
	    if($post_id){
		    $post_id_where = " AND {$wpdb->prefix}wc_orders.id".' = "'.$post_id.'" ';
	    }

		// Order by - allow override
	    $order_by = apply_filters('wp_all_export_order_by', $order_by);

        global $wpdb;

        $defaultQuery = "SELECT * FROM {$wpdb->prefix}wc_orders ";

        if(!\PMXE_Plugin::$session) {
            $customWhere = \XmlExportEngine::$exportOptions['whereclause'];
            $customJoins = \XmlExportEngine::$exportOptions['joinclause'];
        } else {
            $customWhere = \PMXE_Plugin::$session->get('whereclause');
            $customJoins = \PMXE_Plugin::$session->get('joinclause');
        }
        if (is_countable($customJoins) && count($customJoins)) {
            foreach($customJoins as $join) {
                $defaultQuery = $defaultQuery . $join;
            }
        }

        $defaultQuery .= " WHERE status != 'auto-draft' AND type = 'shop_order' ";

        $defaultQuery = $defaultQuery . $customWhere . $post_id_where;

        $export_id = $this->get_export_id();
        $export = new \PMXE_Export_Record();
        $export->getById($export_id);

        if ($this->is_export_new_stuff()) {

            if ($export->iteration > 0) {
	            $postList      = new \PMXE_Post_List();
	            $postListTable = $postList->getTable();

	            $excludeSubQuery = "{$wpdb->prefix}wc_orders.id NOT IN (SELECT post_id FROM {$postListTable} WHERE export_id = %d AND iteration < %d)";
	            $defaultQuery   .= $this->get_exclude_query_where($wpdb->prepare( $excludeSubQuery, $export->id, $export->iteration ));
            }
        }


        if ($this->is_export_modfified_stuff() && !empty($export->registered_on)) {

            $export_id = $this->get_export_id();
            $export = new \PMXE_Export_Record();
            $export->getById($export_id);

            $defaultQuery .= $this->get_modified_query_where($export);
        }

		// Add order by
	    $defaultQuery = $defaultQuery . $order_by;

		// Don't set a limit when we are filtering by a single ID anyway.
        if (!$post_id && isset($offset) && isset($limit) && $limit) {
            $limit_query = " LIMIT $offset, $limit ";
            $defaultQuery = $defaultQuery . $limit_query;
        }

		return $defaultQuery;

    }

    public function get_exclude_query_where($postsToExclude)
    {

        return " AND (" . $postsToExclude . ")";

    }

    public function get_modified_query_where($export)
    {
        global $wpdb;

        return " AND {$wpdb->prefix}wc_orders.date_updated_gmt > '" . $export->registered_on . "' ";
    }

    /**
     * @return bool
     */
    protected function is_export_new_stuff()
    {

        $export_id = $this->get_export_id();

        return (!empty(\XmlExportEngine::$exportOptions['export_only_new_stuff']) &&
            $export_id);
    }

    /**
     * @return bool
     */
    protected function is_export_modfified_stuff()
    {

        $export_id = $this->get_export_id();

        return (!empty(\XmlExportEngine::$exportOptions['export_only_modified_stuff']) &&
            $export_id);
    }

    private function get_export_id()
    {
        $input = new \PMXE_Input();
        $export_id = $input->get('id', 0);

        if(!$export_id) {
            $export_id = $input->get('export_id', 0);
        }

        return $export_id;
    }

}