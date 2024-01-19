<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Form;

class MessageGui extends \CRED_Shortcode_Base_GUI {

    /**
	 * Register the shortcode in the GUI API.
	 *
	 * @param array $cred_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	public function register_shortcode_data( $cred_shortcodes ) {
		$cred_shortcodes[ Message::SHORTCODE_NAME ] = array(
			'attributes' => array(
				'information' => array(
                    'header' => __( 'Instructions', 'wp-cred' ),
                    'fields' => array(
                        'info' => array(
                            'type' => 'information',
                            'content' => '<p>' . __( 'Forms can redirect to the edited post, or to a specific page, after they are submitted.', 'wp-cred' ) . '</p>'
                                . '<p>' . __( 'Add this shortcode to the redirect target to display the success message from the form that just redirected there.', 'wp-cred' ) . '</p>'
                        )
                    )
                )
			)
		);
		return $cred_shortcodes;
    }

}