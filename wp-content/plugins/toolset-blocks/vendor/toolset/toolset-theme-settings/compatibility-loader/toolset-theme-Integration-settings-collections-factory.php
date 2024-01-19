<?php

/**
 * Class Toolset_Theme_Integration_Settings_Collections_Factory
 * @author: Riccardo
 * @since: 2.5
 * Singleton factory class to programmatically build Collections of theme settings models based on their type
 * if a collection type exists already get_collection method simply returns it, otherwise it builds it then returns it
 */
class Toolset_Theme_Integration_Settings_Collections_Factory{

	private static $instance;
	private $collections = array();
	private $label_search = '';

	private $allowed_types = array(
		"global" => "global",
		"customizer" => "customizer",
		"local" => "local",
		"control_filters" => "control_filters",
		"toolset_custom" => "toolset_custom",
		"toolset_info" => "toolset_info",
	);

	/**
	 * @param $type
	 * @param $label optional in case you don't want to create but get it only
	 *
	 * @return Toolset_Theme_Integration_Settings_Model_Collection
	 * if a collection type is not in the array it creates it, otherwise it simply returns it
	 */
	public function get_collection( $type,  $label = '' ){
		if( !in_array( $type, array_values( $this->allowed_types ) ) ){
			throw new Exception( sprintf("Invalid type %s in %s.", $type, __METHOD__ ) );
		}

		if( !isset( $this->collections[$type] ) ){
			$this->collections[$type] = new Toolset_Theme_Integration_Settings_Model_Collection( $type,  $label );
			return $this->collections[$type];
		} else {
			return $this->collections[$type];
		}
	}

	/**
	 * @return Toolset_Theme_Integration_Settings_Collections_Factory
	 */
	public static function getInstance(  )
	{
		if (!self::$instance)
		{
			self::$instance = new Toolset_Theme_Integration_Settings_Collections_Factory(  );
		}

		return self::$instance;
	}

	/**
	 * to be used in TestClass::tearDown() to reset singleton
	 */
	public static function tearDown(){
		self::$instance = null;
	}

	/**
	 * @return array Toolset_Theme_Integration_Settings_Model_Collection[]
	 * returns all the collections available at a given time
	 */
	public function get_collections(){
		return $this->collections;
	}

	/**
	 * @param $target
	 *
	 * @return array Toolset_Theme_Integration_Settings_Model_Interface[]
	 */
	public function get_models_by_target( $target ){
		$models = array();

		foreach( $this->get_collections() as $collection ){
			$models = array_merge( $collection->get_models_by_target( $target ), $models );
		}

		return array_values( $models );
	}

	/**
	 * @param $label
	 *
	 * @return Toolset_Theme_Integration_Settings_Model_Collection|null
	 */
	public function get_collection_by_label( $label ){
		$this->label_search = $label;

		$collections = array_filter( $this->get_collections(), array( $this, 'filter_by_label') );

		if (
			is_array( $collections )
			&& count( $collections ) === 1
		) {
			$collections = array_values( $collections );
			return $collections[0];
		}

		return null;
	}

	/**
	 * @param Toolset_Theme_Integration_Settings_Model_Collection $collection
	 *
	 * @return array Toolset_Theme_Integration_Settings_Model_Interface[]
	 * callback to array filter returns array
	 */
	public function filter_by_label( $collection ){
		return $collection->get_label() === $this->label_search;
	}

	/**
	 * @param $property
	 * @param $value
	 *
	 * @return array Toolset_Theme_Integration_Settings_Model_Interface[]
	 */
	public function where( $property, $value ){
		$models = array();

		foreach( $this->get_collections() as $collection ){
			$models = array_merge( $collection->where( $property, $value ), $models );
		}

		return array_values( $models );
	}

	/**
	 * @param $type the $type declared in the JSON file
	 *
	 * @return Toolset_Theme_Integration_Settings_Model_Collection
	 * @throws Exception
	 * @usage same as get_collection() but doesn't try to create anything, just returns the existing ones
	 * try{
	 *      $collection = $this->get_collection_by_type( 'global' );
	 *      $singles = $collection->get_models_by_target( 'single' );
	 * } catch ( Exception $e ){
	 *      error_log( $e->message );
	 * }
	 */
	public function get_collection_by_type( $type ) {

		if( !in_array( $type, array_values( $this->allowed_types ) ) ){
			throw new Exception("Invalid type $type.");
		}

		if ( isset( $this->collections[$type] ) ) {
			return $this->collections[$type];
		}
		else {
			return null;
		}
	}
}
