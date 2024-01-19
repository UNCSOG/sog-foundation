<?PHP

/**
 * Class CRED_Association_Form_Relationship_API_Helper
 */
class CRED_Association_Form_Relationship_API_Helper {

	const ASSOCIATION_ROLE_PARENT = 'parent';
	const ASSOCIATION_ROLE_CHILD = 'child';

	private $relationships = array();
	private $relationship_slug;

	/** @var IToolset_Association */
	private $association;

	/** @var IToolset_Relationship_Definition */
	private $relationship_definition;

	private $fields_definition;
	private $temp_slug = '';

	private $m2m_api_inited = false;
	private $m2m_relationships_populated = false;

	private $relationships_factory;

	/**
	 * CRED_Association_Form_Relationship_API_Helper constructor.
	 */
	public function __construct() {
		// Initialize the Toolset Common M2M API as late as possible on init,
		// since Types registers its custom post types on init at a variable priority
		add_action( 'init', array( $this, 'init_m2m_API' ), PHP_INT_MAX );
	}

	/**
	 * Initialize the M2M API
	 */
	public function init_m2m_API() {
		do_action( 'toolset_do_m2m_full_init' );
		$this->m2m_api_inited = true;
	}

	/**
	 * Populate the list of existing relationships, JIT and just once.
	 */
	public function maybe_populate_active_relationships() {
		// Populate the list of existing relationships as late as possible and JIT.
		if ( ! $this->m2m_api_inited ) {
			return;
		}

		if ( $this->m2m_relationships_populated ) {
			return;
		}

		$this->set_relationships( $this->fetch_all_active_relatioships() );
		$this->m2m_relationships_populated = true;
	}

	/**
	 * @return null|\OTGS\Toolset\Common\Relationships\API\RelationshipQuery
	 */
	public function get_relationship_query() {
		return $this->get_relationships_factory()->relationship_query();
	}


	/**
	 * @return \OTGS\Toolset\Common\Relationships\API\AssociationQuery
	 */
	public function get_association_query() {
		return $this->get_relationships_factory()->association_query();
	}

	/**
	 * @return IToolset_Relationship_Definition[]
	 */
	private function fetch_all_active_relatioships() {
		return $this->get_relationship_query()->get_results();
	}

	/**
	 * @return array
	 */
	public function get_relationships() {
		$this->maybe_populate_active_relationships();
		return $this->relationships;
	}

	/**
	 * @return int
	 */
	public function relationships_count() {
		$this->maybe_populate_active_relationships();
		return count( $this->relationships );
	}

	/**
	 * @return bool
	 */
	public function has_relationships() {
		$this->maybe_populate_active_relationships();
		return $this->relationships_count() > 0;
	}

	/**
	 * @param $relationships
	 */
	public function set_relationships( $relationships ) {
		$this->relationships = $relationships;
	}

	/**
	 * @param $association_id
	 *
	 * @return null
	 */
	public function get_association_object_by_id( $association_id ) {

		if ( ! $association_id ) {
			return null;
		}

		$query = $this->get_association_query();

		$results = $query->add( $query->element_id_and_domain( $association_id, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Intermediary() ) )->limit( 1 )->get_results();

		return isset( $results[0] ) ? $results[0] : null;
	}

	/**
	 * @param $association_id
	 *
	 * @return null
	 */
	public function set_association( $association_id ) {
		$this->association = $this->get_association_object_by_id( $association_id );

		return $this->association;
	}

	/**
	 * @return mixed
	 */
	private function delete_association() {
		return $this->relationship_definition->get_driver()->delete_association( $this->association );
	}

	/**
	 * @return Toolset_Relationship_Definition_Repository
	 */
	private function get_definition_repository() {
		$definition_repository = Toolset_Relationship_Definition_Repository::get_instance();

		return $definition_repository;
	}

	/**
	 * @param $relationship_slug
	 *
	 * @return IToolset_Relationship_Definition|null
	 */
	public function set_relationship_definition( $relationship_slug ) {
		$definition_repository         = $this->get_definition_repository();
		$this->relationship_definition = $definition_repository->get_definition( $relationship_slug );

		return $this->relationship_definition;
	}

	/**
	 * @param $slug
	 */
	public function set_relationship_slug( $slug ) {
		$this->relationship_slug = $slug;
	}

	/**
	 * @return mixed
	 */
	public function get_relationship_slug() {
		return $this->relationship_slug;
	}

	/**
	 * @param $parent
	 * @param $child
	 *
	 * @return bool
	 */
	private function association_needs_edit( $parent, $child ) {
		$old_parent = $this->association->get_element_id( $this->get_relationship_role_object( self::ASSOCIATION_ROLE_PARENT ) );
		$old_child  = $this->association->get_element_id( $this->get_relationship_role_object( self::ASSOCIATION_ROLE_CHILD ) );

		return $old_parent !== $parent && $old_child !== $child;
	}

	/**
	 * @param $parent
	 * @param $child
	 *
	 * @return mixed
	 */
	public function handle_association_edit_if( $parent, $child ) {
		if ( $this->association_needs_edit( $parent, $child ) ) {
			$this->delete_association();
		}

		return $this->save_association( $parent, $child );
	}

	/**
	 * @param $parent
	 * @param $child
	 *
	 * @return mixed
	 */
	public function save_association( $parent, $child ) {

		try{
			$result = $this->relationship_definition->create_association( $parent, $child );
		} catch( Exception $exception ){
			// if there is no matching element to create return the error message to display
			return $exception->getMessage();
		}


		if ( $result instanceof Toolset_Association ) {
			$this->association = $result;

			return $this->association->get_uid();
		} else if ( $result instanceof Toolset_Result ) {
			return $result->get_message();
		}

		return null;
	}

	/**
	 * void
	 */
	public function set_fields_definition() {
		$this->fields_definition = $this->relationship_definition->get_association_field_definitions();
	}

	public function get_fields_definition() {
		return $this->fields_definition;
	}

	/**
	 * @param $field_slug
	 *
	 * @return Toolset_Field_Definition|null
	 */
	private function get_field_definition( $field_slug ) {
		$definition      = $this->get_fields_definition();
		$this->temp_slug = $field_slug;
		$field           = array_values( array_filter( $definition, array( $this, 'filter_definition_by_slug' ) ) );

		return isset( $field[0] ) && $field[0] instanceof Toolset_Field_Definition ? $field[0] : null;
	}

	/**
	 * @param Toolset_Field_Definition $definition
	 *
	 * @return bool
	 */
	public function filter_definition_by_slug( $definition ) {
		return $definition->get_slug() === $this->temp_slug;
	}

	/**
	 * @param $field_slug
	 * @param $field_value
	 *
	 * @return bool|int|int[]
	 */
	public function handle_field_save( $field_slug, $field_value ) {
		$post = $this->get_intermediary_post();

		if ( $post instanceof IToolset_Element === false ) {
			return false;
		}

		$meta_key = $this->get_association_field_meta_key( $field_slug );

		if ( ! $meta_key ) {
			return false;
		}

		if ( is_array( $field_value ) ) {
			return $this->handle_repeating_field_save( $post, $meta_key, $field_value );
		}

		return $this->handle_simple_field_save( $post, $meta_key, $field_value );
	}

	/**
	 * Save simple fields.
	 *
	 * @param IToolset_Element $post
	 * @param string $meta_key
	 * @param string $field_value
	 * @return bool|int
	 * @since 2.4
	 */
	private function handle_simple_field_save( $post, $meta_key, $field_value ) {
		$previous_value = get_post_meta( $post->get_id(), $meta_key, true );

		if ( $previous_value === $field_value ) {
			return false;
		}

		$value = $this->sanitize_value( $field_value );

		if ( empty( $value ) ) {
			$return = delete_post_meta( $post->get_id(), $meta_key, $value );
		} else {
			$return = update_post_meta( $post->get_id(), $meta_key, $value, $previous_value );
		}

		if ( $return ) {
			return $value;
		}
		return false;
	}

	/**
	 * Save repeating fields.
	 *
	 * @param IToolset_Element $post
	 * @param string $meta_key
	 * @param array $field_value
	 * @return bool|int[]
	 * @since 2.4
	 */
	private function handle_repeating_field_save( $post, $meta_key, $field_value ) {
		delete_post_meta( $post->get_id(), $meta_key );
		$updated = array();

		foreach ( $field_value as $field_instance ) {
			$value = $this->sanitize_value( $field_instance );
			if ( ! empty( $value ) ) {
				$updated[] = add_post_meta( $post->get_id(), $meta_key, $value, false );
			}
		}

		$updated = array_filter( $updated );

		if ( count( $updated ) ) {
			return $updated;
		}

		return false;
	}

	/**
	 * @param $meta_value
	 *
	 * @return mixed|string
	 */
	private function sanitize_value( $meta_value ) {


		$allowed_tags      = wp_kses_allowed_html( 'post' );
		$allowed_protocols = array( 'http', 'https', 'mailto' );

		// in case if mate value is array check all elements
		if( is_array( $meta_value ) ){

			foreach( $meta_value as $key => $value ){
				$new_value              = str_replace( '\\\\"', '"', $value );
				$new_value              = wp_kses( $new_value, $allowed_tags, $allowed_protocols );
				$new_value              = str_replace( "&amp;", "&", $new_value );
				$meta_value[ $key ] = $new_value;
			}

			return $meta_value;

		}


		$meta_value        = str_replace( '\\\\"', '"', $meta_value );
		$meta_value        = wp_kses( $meta_value, $allowed_tags, $allowed_protocols );
		$meta_value        = str_replace( "&amp;", "&", $meta_value );

		return $meta_value;
	}

	/**
	 * Turn posted $_FILE entries into $_POSTed values
	 * for media fields when using native HTL file inputs.
	 *
	 * @since 2.4
	 */
	public function upload_posted_media_fields() {
		if ( empty( $_FILES ) ) {
			return;
		}

		if ( false === toolset_getarr( $_FILES, 'wpcf', false ) ) {
			return;
		}

		$files = toolset_getarr( $_FILES, 'wpcf', array() );

		if ( ! is_array( $files ) ) {
			return;
		}

		$name = toolset_getarr( $files, 'name', array() );
		$type = toolset_getarr( $files, 'type', array() );
		$tmp_name = toolset_getarr( $files, 'tmp_name', array() );
		$error = toolset_getarr( $files, 'error', array() );
		$size = toolset_getarr( $files, 'size', array() );

		if (
			! is_array( $name )
			|| ! is_array( $type )
			|| ! is_array( $tmp_name )
			|| ! is_array( $error )
			|| ! is_array( $size )
		) {
			return;
		}

		$_POST['wpcf'] = isset( $_POST['wpcf'] ) ? $_POST['wpcf'] : array();

		// Dependencies: load the file-related functions.
		require_once( ABSPATH . '/wp-admin/includes/file.php' );

		foreach ( $name as $field_name => $field_value ) {
			if ( is_array( $field_value ) ) {
				foreach ( $field_value as $field_value_key => $field_value_instance ) {
					$field_data = array(
						'name' => toolset_getnest( $name, array( $field_name, $field_value_key ) ),
						'type' => toolset_getnest( $type, array( $field_name, $field_value_key ) ),
						'tmp_name' => toolset_getnest( $tmp_name, array( $field_name, $field_value_key ) ),
						'error' => toolset_getnest( $error, array( $field_name, $field_value_key ) ),
						'size' => toolset_getnest( $size, array( $field_name, $field_value_key ) ),
					);
					$upload = wp_handle_upload( $field_data, array(
						'test_form' => false,
						'test_upload' => false,
						'mimes' => CRED_StaticClass::$_allowed_mime_types,
					) );
					if (
						! isset( $upload[ 'error' ] )
						&& isset( $upload[ 'url' ] )
					) {
						$_POST['wpcf'][ $field_name ] = toolset_getarr( $_POST['wpcf'], $field_name, array() );
						// Placing in the right array index, so the order is kept
						$_POST['wpcf'][ $field_name ][ $field_value_key ] = $upload['url'];
					}
				}
			} else {
				$field_data = array(
					'name' => toolset_getarr( $name, $field_name ),
					'type' => toolset_getarr( $type, $field_name ),
					'tmp_name' => toolset_getarr( $tmp_name, $field_name ),
					'error' => toolset_getarr( $error, $field_name ),
					'size' => toolset_getarr( $size, $field_name ),
				);
				$upload = wp_handle_upload( $field_data, array(
					'test_form' => false,
					'test_upload' => false,
					'mimes' => CRED_StaticClass::$_allowed_mime_types,
				) );
				if (
					! isset( $upload[ 'error' ] )
					&& isset( $upload[ 'url' ] )
				) {
					$_POST['wpcf'][ $field_name ] = $upload['url'];
				}
			}
		}

	}

	/**
	 * @return null|IToolset_Element
	 */
	public function get_intermediary_post() {
		if ( ! $this->association ) {
			return null;
		}

		return $this->association->get_element( $this->get_relationships_factory()->role_intermediary() );
	}

	/**
	 * @param $field_slug
	 *
	 * @return null/string
	 */
	private function get_association_field_meta_key( $field_slug ) {
		$field = $this->get_field_definition( $field_slug );

		return $field ? $field->get_meta_key() : null;
	}

	/**
	 * @param $role
	 *
	 * @return mixed|null
	 */
	private function get_relationship_role_object( $role ) {
		$roles = array(
			self::ASSOCIATION_ROLE_CHILD => new Toolset_Relationship_Role_Child(),
			self::ASSOCIATION_ROLE_PARENT => new Toolset_Relationship_Role_Parent(),
		);

		return array_key_exists( $role, $roles ) ? $roles[ $role ] : null;
	}


	/**
	 * @param $role_string
	 * @param $post_id_or_post_object
	 * @param array $args
	 *
	 * @return null|\OTGS\Toolset\Common\Relationships\API\PotentialAssociationQuery
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_potential_associations_posts( $role_string, $post_id_or_post_object, $args = array() ){
		$role = $this->get_relationship_role_object( $role_string );

		if( null === $role ) return null;

		$element = $this->get_toolset_element_from_post( $post_id_or_post_object );

		if( null === $element ) return null;

		try{
			$potential_associations = $this->get_toolset_potential_association_query_posts( $this->relationship_definition, $role,  $element, $args );
		} catch( InvalidArgumentException $exception ){
			/** @noinspection ForgottenDebugOutputInspection */
			error_log( sprintf( 'Invalid Argument Exception: %s', $exception->getMessage() ) );
			return null;
		}


		return $potential_associations;
	}


	/**
	 * @param null $post_id_or_post_object
	 *
	 * @return IToolset_Element|null
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_toolset_element_from_post( $post_id_or_post_object = null ){
		$element = Toolset_Element::get_instance( 'posts', $post_id_or_post_object );

		if( ! $element ) return null;

		return $element;
	}


	/**
	 * @param $role
	 * @param $other
	 *
	 * @return null|array
	 *
	 * if result.is_error property is true, no associations are possible anymore for the element passed as $other
	 * else further associations are still possible
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function is_element_available_for_new_associations( $role, $other ){
		$potential_associations = $this->get_potential_associations_posts( $role, $other );

		if( ! $potential_associations ){
			return null;
		}

		$result = $potential_associations->can_connect_another_element();

		return $result->to_array();
	}


	/**
	 * @param $parent_id
	 *
	 * @return null|array
	 * if result.is_error property is true, no associations are possible anymore for parent element
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function is_parent_available_for_new_associations( $parent_id ){
		return $this->is_element_available_for_new_associations( self::ASSOCIATION_ROLE_CHILD, $parent_id );
	}


	/**
	 * @param $child_id
	 *
	 * @return null|array
	 * if result.is_error property is true, no associations are possible anymore for child element
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function is_child_available_for_new_associations( $child_id ){
		return $this->is_element_available_for_new_associations( self::ASSOCIATION_ROLE_PARENT, $child_id );
	}

	/**
	 * @param IToolset_Relationship_Definition $relationship
	 * @param \OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild $target_role
	 * @param IToolset_Element $for_element
	 * @param array $args
	 *
	 * @return \OTGS\Toolset\Common\Relationships\API\PotentialAssociationQuery
	 */
	public function get_toolset_potential_association_query_posts(
		IToolset_Relationship_Definition $relationship,
		\OTGS\Toolset\Common\Relationships\API\RelationshipRoleParentChild $target_role,
		IToolset_Element $for_element,
		$args = array()
	) {
		return $this->get_relationships_factory()->potential_association_query(
			$relationship, $target_role, $for_element, $args
		);
	}


	private function get_relationships_factory() {
		if( null === $this->relationships_factory ) {
			$this->relationships_factory = new \OTGS\Toolset\Common\Relationships\API\Factory();
		}

		return $this->relationships_factory;
	}
}
