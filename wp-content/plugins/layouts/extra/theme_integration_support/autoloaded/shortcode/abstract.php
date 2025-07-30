<?php


abstract class WPDDL_Shortcode_Abstract {

	private $id;
	private $options;
	private $template_file;
	private $media_button = array();

	protected function setup() {
		if( $this->id !== null ){

            add_shortcode( $this->id, array( $this, 'output' ) );


        }

		if( isset( $this->media_button['title'] ) &&
		    isset( $this->media_button['output'] ) ) {
			add_filter( 'editor_addon_menus_wpv-views', array( $this, 'outputMediaButton' ) );
			add_action( 'wp_loaded', array( $this, 'outputShortcodesGroup' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'outputMediaButtonScript' ) );
		}
	}

	protected function setId( $id ) {
		if( is_string( $id ) )
			$this->id = $id;
	}

	public function getId() {
		return $this->id;
	}

	protected function setTemplate( $template ) {
		if( is_file( $template ) && is_readable( $template ) )
			$this->template_file = $template;
	}

	protected function setMediaButton( $title, $output = null ) {
		if( is_string( $title ) )
			$this->media_button['title'] = $title;

		$this->media_button['output'] = ( $output !== null )
			? $output
			: '[' . $this->id . ']';
	}

	public function setOption( $objectOption ) {
		if( is_a( $objectOption, 'WPDDL_Shortcode_Option_Abstract') ) {
			$objectOption->setShortcodeId( $this->id );
			$this->options = $objectOption;
			$this->options->apply();
		}
	}
	
	/**
	 * Since Toolset Views 2.3.0, the filter editor_addon_menus_wpv-views is no longer called to populate the 
	 * Fields and Views dialog, so keep this just for backwards compatibility.
	 *
	 * @deprecated
	 */
	public function outputMediaButton( $menus ) {
		$nonce = wp_create_nonce('wpv_editor_callback');

		if( ! empty( $this->options ) ) {
			$menus[LAYOUTS_INTEGRATION_THEME_NAME][$this->media_button['title']] = array(
					$this->media_button['title'],
					$this->id,
					$this->id,
					"WPViews.shortcodes_gui.wpv_insert_popup( '".$this->id."', '".$this->media_button['title']."', {}, '$nonce', this )",
			);
		} else {
			$menus[LAYOUTS_INTEGRATION_THEME_NAME][$this->media_button['title']] = array(
					$this->media_button['title'],
					$this->id,
					$this->id,
					''
			);

		}

		return $menus;
	}
	
	/**
	 * Since Toolset Views 2.3.0, this si the canonical API to register groups and fields inside the 
	 * Fields and Views dialog.
	 *
	 * @note WPViews.shortcodes_gui.wpv_insert_popup is also getting deprecated soon, keep an eye on its replacement.
	 */
	public function outputShortcodesGroup() {
		
		$nonce = wp_create_nonce('wpv_editor_callback');
		
		$group_id	= 'layouts-integration-genesis';
		$group_data	= array(
			'name'		=> LAYOUTS_INTEGRATION_THEME_NAME,
			'fields'	=> array()
		);
		
		if( ! empty( $this->options ) ) {
			$group_data['fields'][ $this->id ] = array(
				'name'		=> $this->media_button['title'],
				'shortcode'	=> $this->id,
				'callback'	=> "WPViews.shortcodes_gui.wpv_insert_popup( '".$this->id."', '".$this->media_button['title']."', {}, '$nonce', this )"
			);
		} else {
			$group_data['fields'][ $this->id ] = array(
				'name'		=> $this->media_button['title'],
				'shortcode'	=> $this->id,
				'callback'	=> ""
			);
		}
		
		do_action( 'wpv_action_wpv_register_dialog_group', $group_id, $group_data );
		
	}

	public function outputMediaButtonScript() {
		if( empty( $this->options ) ) {
			echo '<script type="text/javascript">
					;(function($){
						var btn = $(".button:contains(\'' . $this->media_button['title'] . '\')");
						btn.removeAttr( "onclick" );
						btn.on( "click", function(){
							icl_editor.insert( "' . $this->media_button['output'] . '" );
						});
					})(jQuery);
				</script>';
		}
	}

	public function output() {

		$output = '';

		if( $this->template_file !== null ) {
			ob_start();
				require( $this->template_file );
			$output = ob_get_clean();

		}

		return $output;
	}

    protected function disable_content_editor_overlay() {
        add_filter( 'ddl-do-not-apply-overlay-for-post-editor',
            array( &$this, 'filter_disable_content_editor_overlay' ) );
    }

    public function filter_disable_content_editor_overlay( $codes ){
        if( $this->getId() !== null )
            $codes[] = $this->getId();

        return $codes;
    }
}