<?php

/**
 * Public Toolset Forms hook API.
 *
 * This should be the only point where other plugins (incl. Toolset) interact with Toolset Forms directly.
 * Always use as a singleton in production code.
 *
 * Note: CRED_Api is initialized on after_setup_theme with priority 10.
 *
 * When implementing filter hooks, please follow these rules:
 *
 * 1.  All filter names were automatically prefixed with 'cred_'. New filters should be prefixed
 *     with 'toolset_forms_'. Only lowercase characters and underscores can be used.
 * 2.  Filter names (without a prefix) should be defined in their right method.
 * 3.  For each filter, there should be a dedicated class implementing the CRED_Api_Handler_Interface. Name of the class
 *     must be CRED_Api_Handler_{$capitalized_filter_name}. So for example, for a hook to
 *     'cred_get_available_relationship_forms' you need to create a class 'CRED_Api_Handler_Get_Available_Relationship_Forms'.
 * 4.  Actions support comming soon in a different class.
 *
 * @since 2.0
 */
final class CRED_Api {

	const HOOK_PREFIX_LEGACY = 'cred_';

	const HOOK_PREFIX = 'toolset_forms_';

	/** Prefix for the callback method name */
	const CALLBACK_PREFIX = 'callback_';

	/** Prefix for the handler class name */
	const HANDLER_CLASS_PREFIX = 'CRED_Api_Handler_';

	const DELIMITER = '_';

	private static $instance;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function initialize() {
		$instance = self::get_instance();

		$instance->register_callbacks();
	}

	private $callbacks_registered = false;

	/**
	 * @return array Filter names (without prefix) as keys, filter parameters as values:
	 *     - int $args: Number of arguments of the filter
	 *     - callable $callback: A callable to override the default mechanism.
	 * @since 2.0
	 */
	private function get_legacy_callbacks_to_register() {
		return array(

			/**
			 * cred_get_available_forms
			 *
			 * Return a list of published forms given a domain, as stored in the right cached transient.
			 *
			 * Generates the transient in case it is not set.
			 *
			 * @return array
			 * @since 2.0
			 */
			'get_available_forms' => array( 'args' => 2 ),
			/**
			 * cred_delete_form
			 *
			 * Delete form based on passed ID or slug and form type
			 *
			 * @return array
			 * @since 2.0
			 * @todo Turn this into an action
			 */
			'delete_form' => array( 'args' => 2 ),
			/**
			 * cred_create_new_form
			 *
			 * Creates a new $form and returns an object with ID, post_title, post_name of the $form created
			 *
			 * @argument $form object/null
			 * @argument $name string
			 * @argument @domain post/user/relationship
			 * @argument @args array / null
			 *
			 * @return object
			 * @since 2.0
			 */
			'create_new_form' => array( 'args' => 4 )
		);
	}

	private function get_callbacks_to_register() {
		return array(
			'current_user_can_use_post_form' => array( 'args' => 3 ),
			'current_user_can_use_user_form' => array( 'args' => 3 ),
			'current_user_can_use_any_attachment' => array( 'args' => 2 ),
		);
	}

	private function register_callbacks() {
		if ( $this->callbacks_registered ) {
			return;
		}

		$this->register_prefixed_callbacks( self::HOOK_PREFIX_LEGACY, $this->get_legacy_callbacks_to_register() );
		$this->register_prefixed_callbacks( self::HOOK_PREFIX, $this->get_callbacks_to_register() );

		$this->callbacks_registered = true;
	}

	private function register_prefixed_callbacks( $prefix, $callbacks ) {
		foreach( $callbacks as $callback_name => $args ) {
			$argument_count = toolset_getarr( $args, 'args', 1 );
			$priority = toolset_getarr( $args, 'priority', 10 );

			$callback = toolset_getarr( $args, 'callback', null );
			if ( ! is_callable( $callback ) ) {
				$callback = array( $this, self::CALLBACK_PREFIX . $callback_name );
			}

			add_filter( $prefix . $callback_name, $callback, $priority, $argument_count );
		}
	}

	/**
	 * Handle a call to undefined method on this class, hopefully an action/filter call.
	 *
	 * @param string $name Method name.
	 * @param array $parameters Method parameters.
	 * @since 2.0
	 * @return mixed
	 */
	public function __call( $name, $parameters ) {

		$default_return_value = toolset_getarr( $parameters, 0, null );

		// Check for the callback prefix in the method name
		$name_parts = explode( self::DELIMITER, $name );
		if( 0 !== strcmp( $name_parts[0] . self::DELIMITER, self::CALLBACK_PREFIX ) ) {
			// Not a callback, resign.
			return $default_return_value;
		}

		// Deduct the handler class name from the callback name
		unset( $name_parts[0] );
		$class_name = implode( self::DELIMITER, $name_parts );
		$class_name = strtolower( $class_name );
		$class_name = Toolset_Utils::resolve_callback_class_name( $class_name );
		$class_name = self::HANDLER_CLASS_PREFIX . $class_name;

		// Obtain an instance of the handler class.
		try {
			/** @var \OTGS\Toolset\Common\Auryn\Injector $dic */
			$dic = apply_filters( 'toolset_dic', false );
			$handler = $dic->make( $class_name );
		} catch ( \OTGS\Toolset\Common\Auryn\InjectionException $injection_exception ) {
			// For some reason, we're unable to instantiate the class with DIC. Use the old way, assuming
			// the handler constructor is handling everything on its own.
			//
			// This happens mostly when the constructor contains other parameters which the DIC is unable to solve.

			/** @var \CRED_Api_Handler_Interface $handler */
			$handler = new $class_name();
		} catch( Exception $e ) {
			// The handler class could not have been instantiated, resign.
			return $default_return_value;
		}

		// Success
		return $handler->process_call( $parameters );
	}

}
