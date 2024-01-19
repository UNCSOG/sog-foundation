<?php

/**
 * Class CRED_Association_Form_Association_Model
 * since 2.0
 * Represent a single Association as submitted from a Toolset Relationship Form
 */
class CRED_Association_Form_Association_Model implements CRED_Association_Form_Model_Interface {

	const CRED_PREFIX = 'cred_';
	const TYPES_KEY = 'wpcf';
	const FIELDS_KEY = 'fields';
	const RELATIONSHIP_KEY = 'cred_relationship_slug';
	const ASSOCIATION_PREFIX = 'cred_association_';

	/**
	 * Default properties
	 */
	protected $form_id;
	protected $association = null;
	protected $relationship_slug = null;
	protected $fields = array();
	protected $parent = null;
	protected $child = null;

	/**
	 * @var CRED_Association_Form_Relationship_API_Helper|null
	 */
	private $api = null;

	/**
	 * @var array
	 * Default properties as associative array
	 */
	protected $defaults = array(
		"form_id"           => 0,
		"association"       => '',
		"relationship_slug" => '',
		"fields"            => array(),
		'parent'            => 0,
		'child'             => 0
	);

	/**
	 * CRED_Association_Form_Association_Model constructor.
	 *
	 * @param CRED_Association_Form_Relationship_API_Helper|null $api
	 */
	public function __construct( CRED_Association_Form_Relationship_API_Helper $api = null ) {
		$this->api = $api;
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function populate( array $data ) {

		foreach ( $data as $property => $value ) {
			$property = $this->process_properties_strings( $property, $data );
			if ( array_key_exists( $property, $this->defaults ) ) {
				$this->{$property} = $value;
			}
		}

		return $this;
	}

	/**
	 * @param $property
	 * @param array $data
	 *
	 * @return mixed|string
	 */
	private function process_properties_strings( $property, array $data ) {

		if ( isset( $data[ self::RELATIONSHIP_KEY ] ) && strpos( $property, self::ASSOCIATION_PREFIX . $data[ self::RELATIONSHIP_KEY ] . '_' ) !== false ) {
			$property = str_replace( self::ASSOCIATION_PREFIX . $data[ self::RELATIONSHIP_KEY ] . '_', '', $property );
		} elseif ( strpos( $property, self::CRED_PREFIX ) !== false ) {
			$property = str_replace( self::CRED_PREFIX, '', $property );
		} elseif ( $property === self::TYPES_KEY ) {
			$property = self::FIELDS_KEY;
		}

		return $property;
	}

	/**
	 * @param $name
	 *
	 * @return null
	 */
	public function __get( $name ) {

		if ( $this->__isset( $name ) ) {
			return $this->{$name};
		} else {
			throw new Exception( sprintf( 'Undefined property %s via __get(): %s', $name, E_USER_NOTICE ) );

			return null;
		}
	}

	/**
	 * @param $property
	 *
	 * @return bool
	 */
	public function __isset( $property ) {
		return property_exists( $this, $property );
	}

	/**
	 * @param $method
	 * @param $arguments
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __call( $method, $arguments ) {
		if ( method_exists( $this, $method ) ) {
			return call_user_func( array( $this, $method ), $arguments );
		} else {
			throw new RuntimeException( sprintf( "Fatal error: Call to undefined method %s::%s()", get_class( $this ), $method ) );
		}
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set( $name, $value ) {
		if ( $this->__isset( $name ) ) {
			$this->{$name} = $value;
		} else {
			throw new RuntimeException( sprintf( "The property %s cannot be set since it's not in the list of declared properties for %s class", $name, __CLASS__ ) );
		}
	}


	/**
	 * @return int
	 */
	public function get_form_id() {
		return (int) $this->form_id;
	}

	/**
	 * @return int
	 */
	public function get_parent() {
		return (int) $this->parent;
	}

	/**
	 * @return int
	 */
	public function get_child() {
		return (int) $this->child;
	}

	/**
	 * @return mixed
	 */
	public function get_association() {
		return $this->association;
	}

	/**
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * @return string
	 */
	public function get_relationship_slug() {
		return $this->relationship_slug;
	}

	/**
	 * @return mixed
	 */
	public function process_data() {

		$results = array();

		$this->set_up_api();

		if ( null !== $this->get_association() ) {
			$result = $this->api_edit_association_if();
		} else {
			$result = $this->api_save_association();
			$this->association = is_numeric( $result ) ? $result : null;
		}

		$results['association'] = $result;

		if ( null !== $this->get_association() ) {
			$results['fields'] = $this->api_handle_fields_save();
		}

		return $results;
	}

	/**
	 * @return mixed
	 */
	private function api_edit_association_if() {
		$this->api_set_association();

		return $this->api_handle_association_edit_if();
	}

	/**
	 * @return void
	 */
	private function api_set_association() {
		$this->api->set_association( $this->get_association() );
	}

	/**
	 * @return mixed
	 */
	private function api_handle_association_edit_if() {
		return $this->api->handle_association_edit_if( $this->get_parent(), $this->get_child() );
	}

	/**
	 * @return mixed
	 */
	private function api_save_association() {
		return $this->api->save_association( $this->get_parent(), $this->get_child() );
	}

	/**
	 * @param $slug
	 */
	private function api_set_relationship_slug( $slug ) {
		$this->api->set_relationship_slug( $slug );
	}

	/**
	 * @param $slug
	 *
	 * @return void
	 */
	private function api_set_relationship_definition( $slug ) {
		$this->api->set_relationship_definition( $slug );
	}

	/**
	 * @return array|null
	 */
	public function api_handle_fields_save() {
		if ( count( $this->fields ) === 0 ) {
			return null;
		}

		$results = array();

		$this->api_set_fields_definition();

		foreach ( $this->fields as $field_slug => $field_value ) {
			$results[ $field_slug ] = $this->api->handle_field_save( $field_slug, $field_value );
		}

		return $results;
	}

	/**
	 * @return void
	 */
	private function set_up_api() {
		$this->api_set_relationship_slug( $this->get_relationship_slug() );
		$this->api_set_relationship_definition( $this->get_relationship_slug() );
	}

	/**
	 * @return void
	 */
	private function api_set_fields_definition() {
		$this->api->set_fields_definition();
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function to_array() {
		$properties = array();
		$defaults   = $this->defaults;

		foreach ( $defaults as $property => $value ) {
			$properties[ $property ] = $this->__get( $property );
		}

		return $properties;
	}

	/**
	 * @return null|Toolset_Result
	 */
	public function is_parent_available_for_new_associations( ){
		return $this->api->is_parent_available_for_new_associations( $this->get_parent() );
	}

	/**
	 * @return null|Toolset_Result
	 */
	public function is_child_available_for_new_associations( ){
		return $this->api->is_child_available_for_new_associations( $this->get_child() );
	}
}