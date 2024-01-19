<?php

/**
 * Class CRED_Shortcode_Form_Base
 *
 * @since m2m
 */
abstract class CRED_Shortcode_Form_Abstract {

	/**
	 * @var CRED_Frontend_Form_Flow
	 */
	private $frontend_form_flow;

	/**
	 * @param CRED_Frontend_Form_Flow $relationship_service
	 */
	public function __construct( CRED_Frontend_Form_Flow $frontend_form_flow ) {
		$this->frontend_form_flow = $frontend_form_flow;
	}

	/**
	 * @var array
	 */
	protected $shortcode_atts = array(
		'form' => ''
	);

	/**
	 * @var string|null
	 */
	protected $user_content;

	/**
	 * @var array
	 */
	protected $user_atts;

	/**
	 * Set the right form attributes needed by each form type
	 *
	 * @since m2m
	 */
	abstract protected function set_shortcode_atts();

	/**
	 * @return CRED_Frontend_Form_Flow
	 *
	 * @since m2m
	 */
	protected function get_frontend_form_flow() {
		return $this->frontend_form_flow;
	}

	/**
	 * @return null|WP_Post
	 *
	 * @since m2m
	 */
	abstract protected function get_object_form();

	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	*
	* @return string
	*
	* @since m2m
	*/
	public function get_value( $atts, $content = null ) {
		$this->set_shortcode_atts();
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;

		if ( empty( $this->user_atts['form'] ) ) {
			return;
		}

		$output = '';

		$form_object = $this->get_object_form();

		if (
			is_wp_error( $form_object )
			&& current_user_can( 'manage_options' )
		) {
			return $form_object->get_error_message();
		}

		if ( $form_object instanceof WP_Post ) {
			$this->get_frontend_form_flow()->form_start( $form_object, $this->user_atts );

			$output = apply_filters( \OTGS\Toolset\Common\BasicFormatting::FILTER_NAME, $form_object->post_content );

			$this->get_frontend_form_flow()->form_end();
		}

		do_action( 'toolset_forms_enqueue_frontend_form_assets' );

		return $output;
	}

}
