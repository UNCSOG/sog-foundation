<?php

interface WPDLL_Hierarchical{
    public function get_children();
    public function get_children_count();
    public function is_parent();
}

class WPDDL_ParentLayoutHelper implements WPDLL_Hierarchical{

    private $layout_post;
    private $layout;
    private $layout_id;
    private $children = array();

    public function __construct( $id )
    {
        $this->layout_id = $id;
        $this->layout = $this->fetch_layout();
        $this->children = $this->fetch_layouts_children();
        add_filter( 'ddl-parent_helper_get_create_children', array(&$this, 'get_children_assignments') );
    }

    function get_layout(){
        return $this->layout;
    }

    function get_layout_id(){
        return $this->layout_id;
    }

    function get_layout_as_post(){
        return $this->layout_post;
    }

    private function fetch_layouts_children(){

        if( $this->is_parent() === false ) return array();

        $all = WPDD_Utils::get_all_published_settings_as_array();
        return WPDD_Utils::where( $all, 'parent', $this->get_layout_slug() );
    }

    private function fetch_layout(){
        return apply_filters( 'ddl-get_layout_settings', $this->layout_id, true, false );
    }

    private function fetch_layout_as_post(){
        $this->layout_post = get_post( $this->layout_id );
        return $this->layout_post;
    }

    public function is_parent(){
        return isset($this->layout->has_child) && ( $this->layout->has_child === 'true' || $this->layout->has_child === true );
    }

    public function get_layout_slug(){
        return $this->layout->slug;
    }

    public function get_children(){
        return $this->children;
    }

    public function get_children_assignments(){
        $groups = DDL_GroupedLayouts::getInstance();
        $groups->set_layouts( $this->get_children() );
        $layouts_belongs_to_groups = $groups->build_groups_from_settings();
        return $layouts_belongs_to_groups;
    }

    /**
     * @param null $null
     * @return bool|false|mixed|null|string prevents php error
     */
    public function get_children_data_json( $null = null ){
        $data = $this->get_children_assignments();
        if( count($data) > 0 ){
            return wp_json_encode( $data );
        } else{
            return null;
        }
    }

    public function get_children_count(){
        return count( $this->get_children() );
    }
}