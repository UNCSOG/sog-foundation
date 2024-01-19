<?php

/**
 * Class CRED_Shortcode_Association_Form_Container
 *
 * @since m2m
 */
class CRED_Shortcode_Association_Form_Container extends CRED_Shortcode_Form_Container_Base implements CRED_Shortcode_Interface {

	const SHORTCODE_NAME = 'cred-relationship-form-container';

	/**
	 * @var Toolset_Relationship_Service
	 */
	protected $relationship_service;

	public function __construct( CRED_Shortcode_Helper_Interface $helper ) {
		parent::__construct( $helper );
		$this->relationship_service = $helper->get_relationship_service();
	}

	/**
	 * @return Toolset_Relationship_Service
	 *
	 * @since m2m
	 */
	protected function get_relationship_service() {
		return $this->relationship_service;
	}

	/**
	 * @var array
	 */
	protected $permanent_query_args = array(
		CRED_Shortcode_Association_Helper::PARENT_URL_PARAMETER,
		CRED_Shortcode_Association_Helper::CHILD_URL_PARAMETER,
		CRED_Shortcode_Association_Helper::ACTION_URL_PARAMETER,
		'content-template-id'
	);

	/**
	 * @return null|string
	 *
	 * @since m2m
	 */
	private function get_current_relationship() {
		$current_form_id = $this->get_current_form_id();

		if ( ! $current_form_id ) {
			return null;
		}

		$relationship = get_post_meta( $current_form_id, 'relationship', true );
		return empty( $relationship )
			? null
			: $relationship;
	}

	/**
	 * @return string
	 *
	 * @since m2m
	 */
	protected function get_hidden_fields() {
		$hidden_fields = array();

		$relationship_slug = $this->helper->get_current_relationship();
		$association = $this->helper->get_current_association();
		$form_id = $this->get_current_form_id();
		$form_count = $this->get_current_form_count();

		add_filter( 'cred_current_form_post_id', function( $original_post_id ) use ( $form_id ) {
			if ( $original_post_id ) {
				return $original_post_id;
			}
			return $form_id;
		} );

		$hidden_fields[ 'form_id' ] = toolset_form_control( array(
			'field' => array(
				'#type' => 'hidden',
				'#id' => 'cred_form_id',
				'#name' => 'cred_form_id',
				'#attributes' => array(),
				'#inline' => true,
				'#value' => $form_id,
			)
		) );

        $hidden_fields[ 'form_count' ] = toolset_form_control( array(
            'field' => array(
                '#type' => 'hidden',
                '#id' => 'cred_form_count',
                '#name' => 'cred_form_count',
                '#attributes' => array(),
                '#inline' => true,
                '#value' => $form_count,
            )
        ) );

		$redirect_to = $this->get_form_setting( $form_id, self::REDIRECT_KEY );

		$hidden_fields[ self::REDIRECT_KEY ] = toolset_form_control( array(
			'field' => array(
				'#type' => 'hidden',
				'#id' => 'cred_'.self::REDIRECT_KEY,
				'#name' => 'cred_'.self::REDIRECT_KEY,
				'#attributes' => array(),
				'#inline' => true,
				'#value' => $redirect_to,
			)
		) );

		if( $redirect_to === 'custom_post' ){
			$hidden_fields[ self::CUSTOM_POST_KEY ] = toolset_form_control( array(
				'field' => array(
					'#type' => 'hidden',
					'#id' => 'cred_'.self::CUSTOM_POST_KEY,
					'#name' => 'cred_'.self::CUSTOM_POST_KEY,
					'#attributes' => array(),
					'#inline' => true,
					'#value' => $this->get_form_setting( $form_id, self::CUSTOM_POST_KEY ),
				)
			) );
		}

		$hidden_fields[ self::AJAX_SUBMIT_KEY ] = toolset_form_control( array(
			'field' => array(
				'#type' => 'hidden',
				'#id' => 'cred_'.self::AJAX_SUBMIT_KEY,
				'#name' => 'cred_'.self::AJAX_SUBMIT_KEY,
				'#attributes' => array(),
				'#inline' => true,
				'#value' => $this->get_form_setting( $form_id, self::AJAX_SUBMIT_KEY ),
			)
		) );

		$hidden_fields[ 'redirect_url' ] = toolset_form_control( array(
			'field' => array(
				'#type' => 'hidden',
				'#id' => self::REDIRECT_URL_KEY,
				'#name' => self::REDIRECT_URL_KEY,
				'#attributes' => array(),
				'#inline' => true,
				'#value' => $this->redirect_url,
			)
		) );

		$hidden_fields[ 'relationship' ] = toolset_form_control( array(
			'field' => array(
				'#type' => 'hidden',
				'#id' => 'cred_relationship_slug',
				'#name' => 'cred_relationship_slug',
				'#attributes' => array(),
				'#inline' => true,
				'#value' => $relationship_slug,
			)
		) );

		if ( $association instanceof Toolset_Post ) {
			$hidden_fields[ 'association' ] = toolset_form_control( array(
				'field' => array(
					'#type' => 'hidden',
					'#id' => 'cred_association',
					'#name' => 'cred_association',
					'#attributes' => array(),
					'#inline' => true,
					'#value' => $association->get_id(),
				)
			) );
		}

		$hidden_fields[ 'wpnonce' ] = wp_nonce_field( CRED_Association_Form_Main::CRED_ASSOCIATION_FORM_AJAX_NONCE, CRED_Association_Form_Main::CRED_ASSOCIATION_FORM_AJAX_NONCE, true, false );

		return implode( apply_filters( 'cred_form_shortcode_get_hidden_fields', $hidden_fields, $form_id, $relationship_slug, $association, $this ) );
	}

}
