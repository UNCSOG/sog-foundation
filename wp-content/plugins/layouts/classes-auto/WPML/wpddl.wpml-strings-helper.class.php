<?php

/**
 * Class WPDD_WPML_Strings_Helper
 * Provides base methods to interact with [wpml-string] created String Packages API when used within a given Layout in JSON format
**/
abstract class WPDD_WPML_Strings_Helper implements WPDD_WPML_Strings_Helper_Interface{

	const VISUAL_EDITOR = 'cell-text';
	const STRING_OPTION = 'wpml_strings_option';
	const STRING_OPTION_VALUE = 'selected';

	protected $parser;
	protected $layout;
	protected $strings = array();
	protected $translations = array();

	/**
	 * WPDD_WPML_Strings_Helper constructor.
	 *
	 * @param $layout_json
	 * @param WPDD_json2layout|null $parser
	 */
	public function __construct( $layout_json, WPDD_json2layout $parser = null ) {
		$this->set_parser( $parser );

		try{
			$this->layout = $this->build_layout_object_and_return( $layout_json );
		} catch( Exception $exception ){
			error_log( $exception->getMessage() );
		}

	}

	/**
	 * @param $layout_json
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function build_layout_object_and_return( $layout_json ){

		$return = $this->parser->json_decode( $layout_json, false );

		if( ! $return instanceof WPDD_layout ){
			throw new Exception( sprintf( 'Returned value should be instance of WPDD_layout class, while it is %s instead' ), get_class( $return) );
		}

		return $return;
	}

	/**
	 * @return WPDD_json2layout
	 */
	protected function build_parser_and_return(){
		return new WPDD_json2layout();
	}

	/**
	 * @param $parser
	 *
	 * @return WPDD_json2layout
	 */
	protected function set_parser( $parser ){
		if( ! $parser ){
			$this->parser = $this->build_parser_and_return();
		} else{
			$this->parser = $parser;
		}
		return $this->parser;
	}

	/**
	 * @return WPDDL_WPML_Strings_In_Content
	 */
	protected function get_string_parser(){
		return new WPDDL_WPML_Strings_In_Content();
	}

	/**
	 * @return array
	 */
	protected function get_text_base_cells( ){

		if( ! $this->layout ) return array();

		return $this->layout->get_all_cells_of_type( self::VISUAL_EDITOR );
	}

	/**
	 * @return array
	 */
	protected function get_cells_with_wpml_strings( ){
		$cells = $this->get_text_base_cells( );

		if( count( $cells ) === 0 ) return array();

		return array_filter( $cells, array( $this, 'filter_string_option') );
	}

	/**
	 * @param $cell
	 *
	 * @return array/null
	 */
	public function filter_string_option( $cell ){
		if( !$cell instanceof WPDD_layout_cell ) return null;

		return $cell->get_where(  self::STRING_OPTION, self::STRING_OPTION_VALUE );
	}

	/**
	 * @return array|null
	 */
	protected function populate_strings_array(){
		$cells = $this->get_cells_with_wpml_strings( );

		if( !$cells || count($cells) === 0 ) return null;

		foreach( $cells as $cell ){

			$contexts_array = $this->process_cell_strings( $cell, $cell->get( 'content' ) );

			if( $contexts_array && count( $contexts_array ) ){
				$this->strings = array_merge( $this->strings, $contexts_array );
			}

		}

		return $this->strings;
	}

	/**
	 * @param $cell
	 * @param $content
	 *
	 * @return mixed
	 */
	protected abstract function process_cell_strings( $cell, $content );

	/**
	 * @param $unique_id
	 * @param $string
	 *
	 * @return string
	 */
	protected function get_string_name_for_wpml_string( $unique_id, $string ) {
		return $unique_id . '_content_wpml-string_' . md5( $string );
	}

	/**
	 * @return array
	 */
	public function get_strings(){
		return $this->strings;
	}

	/**
	 * @return mixed
	 */
	abstract public function wpml_get_translated_strings();

	/**
	 * @param null $translations
	 *
	 * @return mixed
	 */
	abstract public function wpml_set_translated_strings( $translations = null );
}