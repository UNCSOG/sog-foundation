<?php

class WPDD_Gutenberg_Editor_Condition_Layouts extends Toolset_Gutenberg_Editor_Condition_Default{

	private $post_id;

	public function __construct(  $pagenow, $post_id = 0 ) {
		parent::__construct( $pagenow );
		$this->post_id = $post_id;
	}

	public function is_met(){

		if( $this->post_id === 0 ) return false;

		if( ! parent::is_met() ) return false;

		return apply_filters( 'ddl-page_has_private_layout', $this->post_id ) === true &&
		       apply_filters( 'ddl-is_private_layout_in_use', $this->post_id ) === 'yes';
	}
}