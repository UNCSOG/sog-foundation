<?php

/**
 * Factory for Toolset Forms shortcodes.
 *
 * @since m2m
 */
class CRED_Shortcode_Factory {

	/**
	 * @var CRED_Frontend_Form_Flow
	 */
	private $frontend_form_flow;

	/**
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	private $attr_item_chain;

	/**
	 * @var array
	 */
	private $helpers;

	/**
	 * @var array
	 */
	private $dependency_categories = array();

	public function __construct(
		CRED_Frontend_Form_Flow $frontend_form_flow,
		Toolset_Shortcode_Attr_Interface $attr_item_chain,
		array $helpers
	) {
		$this->frontend_form_flow = $frontend_form_flow;
		$this->attr_item_chain = $attr_item_chain;
		$this->helpers = $helpers;

		$this->set_dependency_categories();
	}

	/**
	 * Classify each shortcode into one of the following categories,
	 * based on their constructor dependencies.
	 *
	 * There should be no need for multiple dependencies,
	 * since helpers can wrap both frontend flow and attribute item chain instances.
	 *
	 * Each shortcode declares its model class, and can declare:
	 * * whether it implemets the CRED_Shortcode_Interface_Conditional interface,
	 *   hence runs a condition_is_met method before registering
	 * * whether it declares a companion class for GUI mnagement
	 *
	 * @since 2.1
	 */
	private function set_dependency_categories() {
		$this->dependency_categories = array(
			'none' => array(
				OTGS\Toolset\CRED\Model\Shortcode\Form\Message::SHORTCODE_NAME => array(
					'class' => 'OTGS\Toolset\CRED\Model\Shortcode\Form\Message',
					'class_gui' => 'OTGS\Toolset\CRED\Model\Shortcode\Form\MessageGui',
				),
			),
			'frontend_form_flow' => array(
				CRED_Shortcode_Form_Submit::SHORTCODE_NAME => array(
					'class' => 'CRED_Shortcode_Form_Submit',
				),
				CRED_Shortcode_Form_Cancel::SHORTCODE_NAME => array(
					'class' => 'CRED_Shortcode_Form_Cancel',
				),
				CRED_Shortcode_Form_Feedback::SHORTCODE_NAME => array(
					'class' => 'CRED_Shortcode_Form_Feedback',
				),
				CRED_Shortcode_Association_Form::SHORTCODE_NAME => array(
					'class' => 'CRED_Shortcode_Association_Form',
					'is_conditional' => true,
				),
			),
			'attr_item_chain' => array(
				OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Post::SHORTCODE_NAME => array(
					'class' => 'OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Post',
					'class_gui' => 'OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Gui\Post',
					'is_conditional' => true,
				),
				OTGS\Toolset\CRED\Model\Shortcode\Form\Link\User::SHORTCODE_NAME => array(
					'class' => 'OTGS\Toolset\CRED\Model\Shortcode\Form\Link\User',
					'class_gui' => 'OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Gui\User',
					'is_conditional' => true,
				),
				OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Association::SHORTCODE_NAME => array(
					'class' => 'OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Association',
					'is_conditional' => true,
				),
				OTGS\Toolset\CRED\Model\Shortcode\Delete\Association::SHORTCODE_NAME => array(
					'class' => 'OTGS\Toolset\CRED\Model\Shortcode\Delete\Association',
					'class_gui' => 'OTGS\Toolset\CRED\Model\Shortcode\Delete\Gui\Association',
					'is_conditional' => true,
				),
				OTGS\Toolset\CRED\Model\Shortcode\Expiration\Post::SHORTCODE_NAME => array(
					'class' => 'OTGS\Toolset\CRED\Model\Shortcode\Expiration\Post',
				),
				OTGS\Toolset\CRED\Model\Shortcode\Delete\Post::SHORTCODE_NAME => array(
					'class' => 'OTGS\Toolset\CRED\Model\Shortcode\Delete\Post',
					'class_gui' => 'OTGS\Toolset\CRED\Model\Shortcode\Delete\Gui\Post',
				),
			),
			'helpers' => array(
				'association' => array(
					CRED_Shortcode_Association_Form_Container::SHORTCODE_NAME => array(
						'class' => 'CRED_Shortcode_Association_Form_Container',
					),
					CRED_Shortcode_Association_Title::SHORTCODE_NAME => array(
						'class' => 'CRED_Shortcode_Association_Title',
					),
					CRED_Shortcode_Association_Field::SHORTCODE_NAME => array(
						'class' => 'CRED_Shortcode_Association_Field',
					),
					CRED_Shortcode_Association_Role::SHORTCODE_NAME => array(
						'class' => 'CRED_Shortcode_Association_Role',
					),
				),
			),
		);
	}

	/**
	 * Get the shortcode class data.
	 *
	 * Given a shortcode name, get the associated data for registering it,
	 * and eventually its GUI into the right shortcodes GUI API.
	 *
	 * @param string $shortcode The shrtcode to get settings from.
	 * @param array $dependency_nest The branch in the $dependency_categories property
	 *     to follow to get the set of data for this shortcode. It describes the dependencies
	 *     that need to be injected into the shortcode controller.
	 *
	 * @return bool|array {
	 *     The associated data to register this shortcode, and eventually its GUI.
	 *
	 *     @type string $class The class that decines this shortcode and produces its output.
	 *     @type bool $is_conditional Optional.  Whether the controller class includes a
	 *         'condition_is_met' method that needs to return TRUE before initializing the shortcode.
	 *     @type string $class_gui Optional. The additional class to initialize if this
	 *         shortcode packs also a GUI consistent with the shortcodes GUI API.
	 * }
	 *
	 * @since 2.1
	 */
	private function get_shortcode_settings( $shortcode, $dependency_nest ) {
		$dependency = toolset_getnest( $this->dependency_categories, $dependency_nest, array() );
		if ( array_key_exists(
			$shortcode,
			$dependency
		) ) {
			return $dependency[ $shortcode ];
		}
		return false;
	}

	/**
	 * Evantually initialize the GUI class companio for a shortcode.
	 *
	 * @param array $shortcode_class_data
	 *
	 * @since 2.1
	 */
	private function maybe_get_shortcode_gui( $shortcode_class_data ) {
		$optional_shortcode_gui = toolset_getarr( $shortcode_class_data, 'class_gui', '' );
		if ( ! empty( $optional_shortcode_gui ) ) {
			$shortcode_gui = new $optional_shortcode_gui();
		}
	}

	/**
	 * Instantiate the shortcode class with the needed dependencies.
	 *
	 * @param string $shortcode_class
	 * @param mixed $dependency
	 *
	 * @return CRED_Shortcode_Interface
	 *
	 * @since 2.1
	 */
	private function get_shortcode_object( $shortcode_class, $dependency = null ) {
		if ( is_null( $dependency ) ) {
			return new $shortcode_class;
		}
		return new $shortcode_class( $dependency );
	}

	/**
	 * Get the CRED_Shortcode_Base_View instance for the shortcode.
	 *
	 * @param array $shortcode_class_data
	 * @param mixed $dependency
	 *
	 * @return CRED_Shortcode_Base_View
	 *
	 * @since 2.1
	 */
	private function get_shortcode_view( $shortcode_class_data, $dependency = null ) {
		$shortcode_class = toolset_getarr( $shortcode_class_data, 'class' );
		$shortcode_object = $this->get_shortcode_object( $shortcode_class, $dependency );
		if ( toolset_getarr( $shortcode_class_data, 'is_conditional', false ) ) {
			if ( $shortcode_object->condition_is_met() ) {
				$this->maybe_get_shortcode_gui( $shortcode_class_data );
				return new CRED_Shortcode_Base_View( $shortcode_object );
			}
			return new CRED_Shortcode_Base_View( new CRED_Shortcode_Empty() );
		}
		$this->maybe_get_shortcode_gui( $shortcode_class_data );
		return new CRED_Shortcode_Base_View( $shortcode_object );
	}

	/**
	 * @param $shortcode
	 *
	 * @return false|CRED_Shortcode_Base_View
	 */
	public function get_shortcode( $shortcode ) {
		if ( $shortcode_class_data = $this->get_shortcode_settings( $shortcode, array( 'none' ) ) ) {
			return $this->get_shortcode_view( $shortcode_class_data );
		}
		if ( $shortcode_class_data = $this->get_shortcode_settings( $shortcode, array( 'frontend_form_flow' ) ) ) {
			return $this->get_shortcode_view( $shortcode_class_data, $this->frontend_form_flow );
		}
		if ( $shortcode_class_data = $this->get_shortcode_settings( $shortcode, array( 'attr_item_chain' ) ) ) {
			return $this->get_shortcode_view( $shortcode_class_data, $this->attr_item_chain );
		}
		if ( $shortcode_class_data = $this->get_shortcode_settings( $shortcode, array( 'helpers', 'association' ) ) ) {
			return $this->get_shortcode_view( $shortcode_class_data, $this->helpers['association'] );
		}

		return false;
	}
}
