<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Gui;

use OTGS\Toolset\CRED\Model\Shortcode\Form\Link\Post as Shortcode;

/**
 * Class for form post edit links GUI.
 *
 * @since 2.1
 */
class Post extends Base {

    /**
	 * Register the shortcode in the GUI API.
	 *
	 * @param array $cred_shortcodes
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	public function register_shortcode_dynamic_data( $cred_shortcodes ) {
        $cred_shortcodes[ Shortcode::SHORTCODE_NAME ] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $cred_shortcodes;
    }

    /**
	 * Get the shortcode attributes data.
     *
     * @param array $parameters
     * @param array $overrides
     * @param string $pagenow
     * @param string $page
	 *
	 * @return array
	 *
	 * @since 2.1
	 */
	public function get_shortcode_data( $parameters = array(), $overrides = array(), $pagenow = '', $page = '' ) {

		$this->parameters = $parameters;
		$this->overrides  = $overrides;
		$this->pagenow    = $pagenow;
		$this->page       = $page;

        $this->toolset_ajax = \Toolset_Ajax::get_instance();

		$data = array(
			'attributes' => array(
                'display-options' => array(
                    'label' => __( 'Display options', 'wp-cred' ),
                    'header' => __( 'Display options', 'wp-cred' ),
                    'fields' => $this->get_edit_link_shortcodes_gui_basic_fields()
                ),
                'post-selection' => array(
                    'label' => __( 'Post selection', 'wp-cred' ),
                    'header' => __( 'Post selection', 'wp-cred' ),
                    'fields' => array(
                        'item' => array(
                            'label' => __( 'Display data for:', 'wp-cred' ),
                            'type' => 'postSelector'
                        )
                    )
                )
			)
        );

        $data['attributes'] = $this->adjust_edit_link_shortcodes_gui_fields( $data['attributes'] );

		return $data;
    }

    /**
	 * Get options for the text of the link shortcode.
	 *
	 * @return array
	 */
    protected function get_default_link_text_options() {
        return array(
            'label' => __( 'Link text', 'wp-cred' ),
            'type' => 'content',
            'defaultValue' => __( 'Edit', 'wp-cred' ),
            'defaultForceValue' => __( 'Edit', 'wp-cred' ),
            'description' => __( 'You can use %%POST_TITLE%% and %%POST_ID%% as placeholders.', 'wp-cred' )
        );
    }

}
