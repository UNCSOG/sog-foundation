<?php

namespace OTGS\Toolset\Views\Services;

class ViewQueryService {
    public function get_view_items($query_type, $view_id, $view_settings) {
        switch ($query_type) {
            case 'posts':
                return wpv_filter_get_posts( $view_id )->posts;
            case 'taxonomy':
                return get_taxonomy_query( $view_settings );
            case 'users':
                return get_users_query( $view_settings );
        }
        return [];
    }

    public function get_first_view_item_id($query_type, $view_id, $view_settings) {
		$id = null;
        $items = $this->get_view_items($query_type, $view_id, $view_settings);
        if (count($items) > 0) {
            $id = $items[0]->ID;
        }
        return $id;
    }
}
