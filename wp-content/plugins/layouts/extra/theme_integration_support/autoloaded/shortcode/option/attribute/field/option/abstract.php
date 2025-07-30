<?php

abstract class WPDDL_Shortcode_Option_Attribute_Field_Option_Abstract {
	private $shortcode_id;
	private $attribute_id;

	private $id;
	private $description;
	private $default = false;

	private $custom_output = false;

	public function __construct( $shortcode_id, $attribute_id, $id, $description, $default = false, $custom_output = false ) {
		$this->shortcode_id = $shortcode_id;
		$this->attribute_id = $attribute_id;
		$this->id = $id;
		$this->description = $description;
		$this->default = $default;
		$this->custom_output = $custom_output;

		$this->setCustomOutput();
	}

	public function setCustomOutput() {
		if( $this->custom_output ) {
			if( is_file( $this->custom_output ) && is_readable( $this->custom_output ) ) {
				ob_start();
					include( $this->custom_output );
				$this->custom_output = ob_get_clean();
			}
			$this->custom_output = $this->convertToSingleLine( $this->custom_output );

			add_action( 'admin_print_footer_scripts', array( $this, 'manipulateInsertShortcodeToAddCustomOutput' ) );
		}
	}

	public function getId() {
		return $this->id;
	}

	public function getDescription() {
		return $this->description;
	}

	public function isDefault() {
		return $this->default;
	}

	private function convertToSingleLine( $string ) {
		$string = addslashes( $string );
		$string = preg_replace( '/\t/', '\\t', $string );
		$string = preg_replace( '/\r\n|\n\r|\n|\r/', '\\r\\n', $string );
		return $string;
	}

	public function manipulateInsertShortcodeToAddCustomOutput() {
		echo '<script type="text/javascript">
				;(function($){
					var btnDefault, btnCustomTextToEditor = false;
					$( "body" ).on( "mouseenter", ".js-wpv-shortcode-gui-insert, .js-wpv-shortcode-gui-insert-clone", function(e){
						if( $( "input[name=' . $this->shortcode_id . '-' . $this->attribute_id . ']:checked" ).val() == "' . $this->id . '" ) {
							if( btnCustomTextToEditor == false ) {
								btnDefault = $( ".js-wpv-shortcode-gui-insert" ).first();
								btnCustomTextToEditor = btnDefault.clone().removeClass( "js-wpv-shortcode-gui-insert" ).addClass( "js-wpv-shortcode-gui-insert-clone" ).insertAfter( btnDefault );
								btnCustomTextToEditor.on( "click", function() {
									icl_editor.insert( "' . $this->custom_output . '" );
									$( "#js-wpv-shortcode-gui-dialog-container" ).dialog( "close" );
									try{
										btnCustomTextToEditor.hide();

									} catch( e ){
									    console.log( e.message );
									}
									try{

										btnDefault.show();
									} catch( e ){
									    console.log( e.message );
									}
								} );
							}
							try{
                                btnDefault.hide();
							}catch( e ){
							    console.log( e.message );
							}
							try{
							    btnCustomTextToEditor.show();
							}catch( e ){
							    console.log( e.message );
							}

						} else {
						    try{
							    btnCustomTextToEditor.hide();

							}catch( e ){
							    console.log( e.message );
							}
							try{

							    btnDefault.show();
							}catch( e ){
							    console.log( e.message );
							}
						}
					});
				})(jQuery);</script>';
	}
}