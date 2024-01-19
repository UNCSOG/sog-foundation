<?php
class CRED_Association_Form_Model implements CRED_Association_Form_Model_Interface{

	CONST COMBINED_POST_META_KEY = 'form_settings';

	protected $name;
	protected $id = 0;
	protected $form_name = '';
	protected $slug = '';
	protected $relationship = null;
	protected $redirect_to = '';
	protected $disable_comments = false;
	protected $has_media_button = true;
	protected $has_toolset_buttons = true;
	protected $has_media_manager = true;
	protected $form_content = '';
	protected $messages = null;
	protected $form_type = CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE;
	protected $post_status = 'publish';
	protected $isActive = false;
	protected $ajax_submission = false;
	protected $form_script = '';
	protected $form_style = '';
	protected $redirect_custom_post = '';
	protected $scaffold_data = '';
	protected $editor_origin = 'scaffold';

	private $action = 'add_new';

	/**
	 * @var array default keys and values for $post related properties
	 */
	protected $post_defaults = array(
		"id" => 0,
		"form_name" => '',
		"slug" => '',
		"form_content" => '',
		"form_type" => CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE,
		"post_status" => false
	);

	/**
	 * @var array default keys and values for $meta related properties
	 */
	protected $meta_defaults = array(
		"relationship" => null,
		"redirect_to" => '',
		"redirect_custom_post" => '',
		"disable_comments" => false,
		"messages" => null,
		"ajax_submission" => false,
		"form_type" => CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE,
		"form_script" => '',
		"form_style"  => '',
		"scaffold_data"  => '',
		"editor_origin"  => 'scaffold',
	);

	/**
	 * @var array default keys and values for combined meta properties
	 */
	protected $meta_combined_defaults = array(
		'has_media_button' => true,
		'has_toolset_buttons' => true,
		'has_media_manager' => true,
	);

	/**
	 * @return bool|int|WP_Error
	 * Public method to update $post and its $metas at once
	 */
	public function process_data() {
		$as_post = $this->save_as_post();

		if( $as_post ){
			$this->save_properties_as_post_metas();
			$this->save_properties_as_combined_post_meta();

			// allow 3rd-party to do its own stuff on CRED form save
			$post_form = get_post( $as_post );
			do_action('cred_admin_save_form', $as_post, $post_form);

			return $as_post;
		}

		return false;
	}

	/**
	 * Delete association form and all post meta from db
	 * @return false|null|WP_Post
	 */
	public function delete_form(){
		$delete = wp_delete_post( $this->id, true );
		return $delete;
	}


	/**
	 * CRED_Association_Form_Model constructor.
	 *
	 * @param null $args
	 */
	public function __construct( $args = null ) {

		if( isset( $args['id'] ) ){
			$this->id = $args['id'];
		}

		if( isset( $args['name'] ) ){
			$this->name = $args['name'];
			$this->form_name = $this->name;
		} elseif ( isset( $args['form_name'] ) ){
			$this->name = $args['form_name'];
			$this->form_name = $this->name;
		}

		if( isset( $args['action'] ) ){
			$this->action = $args['action'];
		}

		if( $this->action !== 'create' && (int) $this->id !== 0 ){
			$this->update_from_post()->update_from_metas()->update_from_combined_meta();
		}
	}

	/**
	 * @param array $data
	 *
	 * @return $this
	 */
	public function populate( array $data ){
		// Messages are hidden in the create form wizard, so they are needed to add.
		if ( empty( $data['messages'] ) ) {
			$data['messages'] = array_map(
				function( $message ) {
					return $message['message'];
				},
				$this->get_default_messages()
			);
		}

		$defaults = array_merge( $this->meta_defaults, $this->meta_combined_defaults, $this->post_defaults );

		foreach( $data as $property => $value ){
			if( array_key_exists( $property, $defaults ) ){
				$this->{$property} = $value;
			}
		}

		return $this;
	}

	/**
	 * @param $name
	 *
	 * @return null
	 */
	public function __get( $name ) {

		if( $this->__isset( $name ) ){
			return $this->{$name};
		} else {
			throw new Exception( sprintf('Undefined property %s via __get(): %s', $name, E_USER_NOTICE ) );
			return null;
		}
	}

	/**
	 * @param $property
	 *
	 * @return bool
	 */
	public function __isset( $property ){
		return property_exists( $this, $property );
	}

	/**
	 * @param $method
	 * @param $arguments
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($method, $arguments) {
		if ( method_exists( $this, $method ) ) {
			return call_user_func( array($this, $method), $arguments);
		} else {
			throw new RuntimeException( sprintf( "Fatal error: Call to undefined method %s::%s()", get_class( $this ), $method ) );
		}
	}

	/**
	 * @param $name
	 * @param $value
	 */
	public function __set( $name, $value ) {
		if( $this->__isset( $name ) ){
			$this->{$name} = $value;
		} else {
			throw new RuntimeException( sprintf( "The property %s cannot be set since it's not in the list of declared properties for %s class", $name, __CLASS__ ) );
		}
	}

	/**
	 * @return $this
	 */
	protected function save_properties_as_post_metas(){
		foreach( $this->meta_defaults as $property => $value ){
			if( $this->__isset( $property ) ){
				$previous = $this->get_meta_value( $property );
				update_post_meta( $this->get_id(), $property, $this->__get( $property ), $previous );
			}
		}

		return $this;
	}

	/**
	 * Save form settings that are stored in a single postmeta entry with key self::COMBINED_POST_META_KEY.
	 *
	 * @return $this
	 * @since 2.4
	 */
	protected function save_properties_as_combined_post_meta() {
		$previous = $this->get_meta_value( self::COMBINED_POST_META_KEY );
		$current = is_array( $previous ) ? $previous : array();
		foreach( $this->meta_combined_defaults as $property => $value ){
			if( $this->__isset( $property ) ){
				$current[ $property ] = $this->__get( $property );
			}
		}
		update_post_meta( $this->get_id(), self::COMBINED_POST_META_KEY, $current, $previous );

		return $this;
	}

	/**
	 * @param $key
	 *
	 * @return mixed
	 */
	protected function get_meta_value( $key ){
		return get_post_meta( $this->get_id(), $key, true );
	}

	/**
	 * Keep backwards compatibility by filling properties
	 * that require a different default value on already existing forms
	 * than default values for new forms.
	 *
	 * @return $this
	 * @since 2.3.5
	 */
	private function update_from_metas_legacy() {
		$legacy_property_values = array(
			'editor_origin' => \OTGS\Toolset\CRED\Controller\EditorOrigin::HTML,
		);

		foreach( $legacy_property_values as $property => $legacy_value ) {
			if( $this->__isset( $property ) ) {
				$current_value = $this->__get( $property );
				if ( empty( $current_value ) ) {
					$this->__set( $property, $legacy_value );
				}
			}
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	protected function update_from_metas(){
		foreach( $this->meta_defaults as $property => $value ){
			if( $this->__isset( $property ) ){
				$this->__set( $property, $this->get_meta_value( $property ) );
			}
		}

		return $this->update_from_metas_legacy();
	}

	/**
	 * Update the model from values stored in a single combined postmeta entry.
	 *
	 * @return $this
	 * @since 2.4
	 */
	protected function update_from_combined_meta() {
		$current = $this->get_meta_value( self::COMBINED_POST_META_KEY );
		$current = is_array( $current ) ? $current : array();
		foreach( $this->meta_combined_defaults as $property => $value ){
			if( $this->__isset( $property ) ){
				$value = toolset_getarr( $current, $property, toolset_getarr( $this->meta_combined_defaults, $property, false ) );
				$this->__set( $property, $value );
			}
		}

		return $this;
	}

	/**
	 * @return bool|int|WP_Error
	 */
	protected function save_as_post(){

		$this->handle_slug_as_sample_permalink();


		$args = array(
			'ID' => (int) $this->id,
			'post_content' => $this->form_content,
			'post_title' => $this->form_name,
			'post_name' => $this->slug,
			'post_type' => CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE,
			'post_status' => $this->post_status
		);

		$ret = wp_insert_post( $args, true );

		if( is_wp_error( $ret ) ) return false;

		$this->id = $ret;

		return $this->id;
	}

	/**
	 * @return $this
	 */
	private function handle_slug_as_sample_permalink(){

		$post = $this->get_as_post();
		$parent = 0;

		if( $post === null || !$post->post_name || $post->post_name !== $this->slug ){
			$slug = get_sample_permalink( $this->id, $this->form_name, $this->slug );
			$this->slug = isset( $slug[1] ) ? $slug[1] : $this->slug;
			$parent = isset( $post->post_parent ) ? $post->post_parent  : null;
		}

		// If slug is still empty generate it from title
		if( ! $this->slug ){
			$this->slug = wp_unique_post_slug( sanitize_title($this->form_name), $this->id, $this->post_status, CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE, $parent );
		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_id(){
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function get_name(){
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_form_name(){
		return $this->form_name;
	}

	/**
	 * @return string
	 */
	public function get_slug(){
		return $this->slug;
	}

	/**
	 * @return string
	 */
	public function get_status(){
		return $this->post_status;
	}

	/**
	 * @return array
	 */
	public function get_messages(){
		return apply_filters( 'cred_association_form_get_messages', $this->messages ? $this->messages : $this->get_default_messages(), $this );
	}

	/**
	 * @return array
	 */
	public function to_array(){
		$ret = array();
		$defaults = array_merge( $this->meta_defaults, $this->meta_combined_defaults, $this->post_defaults );

		foreach( $defaults as $property => $value ){
			$ret[$property] = $this->__get( $property );
		}

		return $ret;
	}

	/**
	 * @return array|null|WP_Post
	 */
	protected function get_as_post(){
		return get_post( $this->id );
	}

	/**
	 * @return $this
	 */
	protected function update_from_post(){
		$post = $this->get_as_post();

		$this->name = $post->post_title;
		$this->form_name = $this->name;
		$this->slug = $post->post_name;
		$this->post_status = $post->post_status;
		$this->form_content = $post->post_content;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_default_messages(){

		$messages = array(
			'cred_message_post_saved'              => array(
				'message'     => __( 'Relationship Saved', 'wp-cred' ),
				'description' => __( 'Relationship saved Message', 'wp-cred' )
			),
			'cred_message_post_not_saved_singular' => array(
				'message'     => __( 'The relationship was not saved because of the following problem:', 'wp-cred' ),
				'description' => __( 'Relationship not saved message (one problem)', 'wp-cred' )
			),
			'cred_message_post_not_saved_plural'   => array(
				'message'     => __( 'The relationship was not saved because of the following %NN problems:', 'wp-cred' ),
				'description' => __( 'Relationship not saved message (several problems)', 'wp-cred' )
			),
			'cred_message_invalid_form_submission' => array(
				'message'     => __( 'Invalid Form Submission (nonce failure)', 'wp-cred' ),
				'description' => __( 'Invalid submission message', 'wp-cred' )
			),
			'cred_message_field_required'          => array(
				'message'     => __( 'This field is required', 'wp-cred' ),
				'description' => __( 'Required field message', 'wp-cred' )
			),
			'cred_message_values_do_not_match'     => array(
				'message'     => __( 'Field values do not match', 'wp-cred' ),
				'description' => __( 'Invalid hidden field value message', 'wp-cred' )
			),
			'cred_message_enter_valid_email'       => array(
				'message'     => __( 'Please enter a valid email address', 'wp-cred' ),
				'description' => __( 'Invalid email message', 'wp-cred' )
			),
			'cred_message_enter_valid_number'      => array(
				'message'     => __( 'Please enter numeric data', 'wp-cred' ),
				'description' => __( 'Invalid numeric field message', 'wp-cred' )
			),
			'cred_message_enter_valid_url'         => array(
				'message'     => __( 'Please enter a valid URL address', 'wp-cred' ),
				'description' => __( 'Invalid URL message', 'wp-cred' )
			)
		);

		return apply_filters( 'cred_association_form_get_default_messages', $messages, $this );
	}
}
