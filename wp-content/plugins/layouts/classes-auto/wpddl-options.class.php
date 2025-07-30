<?php

/**
 * Class WPDDL_Options
 */
class WPDDL_Options{

    private $layouts_options_array;
    private $layouts_options = array();
    const PARENTS_OPTIONS = 'parents_options';
	const COLUMN_PREFIX = 'column_prefix';
	const JS_GLOBAL = 'js_global';
	const CSS_GLOBAL = 'css_global';

    /**
     * WPDDL_Options constructor.
     */
    public function __construct()
    {
        $this->set_up();
        $this->run();
        add_filter('ddl-get_layouts_options_array', array(&$this, 'get_layouts_options_array') );
        add_filter('ddl-get_layouts_options', array(&$this, 'get_layouts_options') );
    }

    /**
     * @return void
     */
    private function set_up(){
        $this->layouts_options_array = array(
            self::PARENTS_OPTIONS,
	        self::COLUMN_PREFIX,
	        self::JS_GLOBAL,
	        self::CSS_GLOBAL
        );
    }

    /**
     * @return void
     */
    private function run(){
		if (
			( defined( 'WP_CLI' ) && WP_CLI )
			|| is_admin()
		) {
            foreach( $this->layouts_options_array as $option ){
                $this->layouts_options[$option] = new WPDDL_Options_Manager( $option, apply_filters( 'ddl-get_'.$option.'_default_value', null ) );
                add_filter('ddl-get-default-'.$option, array(&$this, 'get_default_option' ), 10, 2 );
                add_filter('ddl-set-default-'.$option, array(&$this, 'set_default_option' ), 10, 2 );
                add_filter( 'ddl-delete-default-'.$option, array(&$this, 'delete_default_option'), 10, 1 );
            }
        }
    }

	/**
	 * @param $dummy
	 * @param $option
	 *
	 * @return mixed
	 */
	public function get_default_option( /*make php happy*/ $default_value, $option = null ){
		if( $option === null ) {
			$option = $default_value;
		}

		return $this->layouts_options[$option]->get_options( $option );
	}

	/**
	 * @param $option
	 * @param $value
	 *
	 * @return null/mixed
	 */
	public function set_default_option( $option, $value ) {
		return isset( $this->layouts_options[ $option ] ) ? $this->layouts_options[ $option ]->update_options( $option, $value ) : null;
	}

    /**
     * @param $option
     * @return bool true on success false on failure
     */
    function delete_default_option( $option ){
        return $this->layouts_options[$option]->delete_option( $option );
    }

    public function get_layouts_options_array(){
        return $this->layouts_options_array;
    }

    /**
     * @return array
     */
    public function get_layouts_options(){
        return $this->layouts_options;
    }
}

/**
 * Class WPDDL_OptionsImportExport
 */
class WPDDL_OptionsImportExport implements IteratorAggregate{

    private $options = array();
    const OPTIONS_FILE = 'ddl-settings.json';

    /**
     * WPDDL_OptionsImportExport constructor.
     */
    public function __construct()
    {
        add_filter('ddl-get-exported-data-for-download', array(&$this, 'export_for_download'), 10, 2 );
        add_filter('ddl-get-layouts-options-as-json', array(&$this, 'get_layouts_options'), 10, 1 );
        add_filter('ddl-import-layouts-options-from-dir', array(&$this, 'import_options_from_dir'), 10, 2 );
        add_filter('ddl-layouts-save_imported_options', array(&$this, 'save_imported_options'), 10, 2 );
        add_filter('ddl-layouts-exported_for_theme', array(&$this, 'exported_for_theme'), 10, 1 );
    }

    /**
     * @return bool|false|mixed|string
     */
    public function toJSON(){
        return wp_json_encode( $this->getIterator() );
    }

    /**
     * @return ArrayIterator
     */
	#[\ReturnTypeWillChange]
    function getIterator()
    {
        return new ArrayIterator( $this->options );
    }

    function get_options(){
        return $this->options;
    }

    /**
     * @return array
     */
    protected function set_options_array(){
        $options = apply_filters('ddl-get_layouts_options', null);

        foreach( $options as $option => $value ){
            $this->options[$option] = $value->get_options( $option );
        }

        return $this->options;
    }

    /**
     * @param $results
     * @param $object
     * @return array
     */
    function export_for_download($results, $object ){

        $this->set_options_array();

        $process = new WPDDL_ImportExportFixData( $this->options, 'export');

        $results[] = array(
            'file_data' => $process->toJSON(),
            'file_name' => self::OPTIONS_FILE,
            'title' => 'Layouts Options',
        );

        return $results;
    }

    function exported_for_theme( $null ){

        $this->set_options_array();

        $process = new WPDDL_ImportExportFixData( $this->options, 'export');

        return $process->toJSON();
    }

    /**
     * @param $as_json
     * @return ArrayIterator|bool|false|mixed|string|Traversable
     */
    public function get_layouts_options($as_json ){
        $this->set_options_array();

        if( $as_json ){
            return $this->toJSON();
        } else {
            return $this->getIterator();
        }
    }

    /**
     * @param $source_dir
     * @return bool
     */
    public function import_options_from_dir($source_dir, $overwrite = true )
    {

        $file = $source_dir. '/' . self::OPTIONS_FILE;

        if( !file_exists( $file  ) ) return false;

        $json = @file_get_contents( $file );

        if( !$json ) return false;

        return $this->save_imported_options( $json, $overwrite );
    }

    /**
     * @param $json
     * @param bool|true $overwrite
     * @return bool
     */
    public function save_imported_options($json, $overwrite = true  ){

        $options = json_decode( $json, true );
        $res = array();

        //if( $overwrite === false ) return false;
        if( !is_array($options) ) return false;

        $process = new WPDDL_ImportExportFixData( $options, 'import' );
        $options = $process->get_out();

        foreach( $options as $option => $value ){
            $this->options[$option] = new WPDDL_Options_Manager( $option );
            $old = $this->options[$option]->get_options( );

            if( is_null( $old ) || $overwrite  ){
                $save = $this->options[$option]->update_options( $option, $value );
                if( $save ) $res[] = $save;
            }

        }

        return count($res) > 0;
    }

    function __toString()
    {
        return $this->toJSON();
    }
}

class WPDDL_ImportExportFixData{
    private $to_post_slug_and_viceversa = array(
        WPDDL_Options::PARENTS_OPTIONS
    );

    private $input;
    private $output;
    private $process;

    public function __construct( $input, $process )
    {
        $this->input = $input;
        $this->process = $process;
        $this->process_data();
    }

    private function process_data(){
        if( $this->process === 'export'){
            $this->set_for_export();
        } elseif( $this->process === 'import' ){
            $this->set_for_import();
        }
    }

    private function set_for_export(){
        if( !is_array( $this->input ) && !$this->input instanceof Traversable ){
            return;
        }
        foreach( $this->input as $key => $val ){
            if( in_array( $key, $this->to_post_slug_and_viceversa ) ){
                $val = WPDD_Utils::get_post_property_from_ID( (int) $val );
            }
            $this->output[$key] = $val;
        }
    }

    private function set_for_import(){
        if( !is_array( $this->input ) && !$this->input instanceof Traversable ){
            return;
        }
        foreach( $this->input as $key => $val ){
            if( in_array( $key, $this->to_post_slug_and_viceversa ) ){
                $val = WPDD_Utils::get_layout_id_from_post_name( $val );
            }
            $this->output[$key] = $val;
        }
    }

    public function get_out(){
        return $this->output;
    }

    public function toJSON(){
        return wp_json_encode( $this->output );
    }
}
