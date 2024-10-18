<?php
class WPDD_EDITOR{
    function __construct($inline = false){
		$this->inline = $inline;
		
        $this->init();      
    }
    function __destruct(){
    }
    
    function init(){

        
        
        $this->render_editor();
    }
    
    function render_editor(){

        do_action('wpddl_layout_not_found');

		do_action('layouts_edit_screen');
		
		do_action('wpddl_pre_render_editor', $this->inline);
		
		do_action('wpddl_render_editor', $this->inline);
		
		do_action('wpddl_after_render_editor');
    }
}