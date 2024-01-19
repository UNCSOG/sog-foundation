<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Form;

class Message implements \CRED_Shortcode_Interface {

    const SHORTCODE_NAME = 'cred-form-message';
	
	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'form_id' => '' // The ID of the form to use in case there is no URL parameter, not in use at this point
    );

    /**
	 * Get the shortcode output value.
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return string
	 *
     * @since 2.1
     *
     * @note This will return nothing if the URL parameter cred_referrer_form_id is missing or empty
     */
    public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
        $this->user_content = $content;

        if (
			$this->user_atts['form_id'] == '' 
			&& '' == toolset_getget( 'cred_referrer_form_id' )
		) {
			return;
		}
		
		$cred_referrer_form_id = (int) toolset_getget( 'cred_referrer_form_id' );
		$cred_messages = apply_filters( 'toolset_cred_form_messages', array(), $cred_referrer_form_id );
		if ( empty( $cred_messages ) ) {
			return;
		}

		$default_message      = ( array_key_exists( 'cred_message_post_saved', $cred_messages ) ? $cred_messages['cred_message_post_saved'] : '' );
		$cred_selected_message_with_markup = '<div class="alert alert-success"><p>' . $default_message . '</p></div>';

		/**
		 * Applies custom markup to cred form success message
		 *
		 * @since 2.4.0
		 *
		 * @param string  $cred_selected_message_with_markup The message to be displayed with the default markup.
		 * @param string  $cred_selected_message             The message to be displayed with no markup at all.
		 * @param int     $cred_referrer_form_id             The form ID to display the message for.
		 */
		$cred_selected_message_with_markup = apply_filters( 'toolset_filter_cred_form_message_shortcode_output', $cred_selected_message_with_markup, $default_message, $cred_referrer_form_id );
		
		/**
		 * Applies custom markup to cred form success message
		 *
		 * @since 2.4.0
		 *
		 * @param string  $cred_selected_message_with_markup The message to be displayed with the default markup.
		 * @param string  $cred_selected_message the message to be displayed with no markup at all.
		 */
		$cred_selected_message_with_markup = apply_filters( 'toolset_filter_cred_form_message_shortcode_output_' . $cred_referrer_form_id, $cred_selected_message_with_markup, $default_message );

		return $cred_selected_message_with_markup;
    }
    
} 