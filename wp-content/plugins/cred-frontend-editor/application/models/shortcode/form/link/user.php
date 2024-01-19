<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Form\Link;

/**
 * User edit link class.
 *
 * @since m2m
 */
class User 
	extends Base 
	implements \CRED_Shortcode_Interface, \CRED_Shortcode_Interface_Conditional {
	
	const SHORTCODE_NAME = 'toolset-edit-user-link';

	/**
     * @var \Toolset_Condition_Plugin_Views_Active
     */
    private $views_condition;

    /**
     * @var \Toolset_Condition_Plugin_Layouts_Active
     */
    private $layouts_condition;
    
    /**
	 * @param \Toolset_Shortcode_Attr_Interface $item
     * @param \Toolset_Condition_Plugin_Views_Active $di_views_condition
     * @param \Toolset_Condition_Plugin_Layouts_Active $di_layouts_condition
	 */
	public function __construct(
        \Toolset_Shortcode_Attr_Interface $item,
        \Toolset_Condition_Plugin_Views_Active $di_views_condition = null,
        \Toolset_Condition_Plugin_Layouts_Active $di_layouts_condition = null
    ) {
        parent::__construct( $item );
        $this->views_condition = ( $di_views_condition instanceof \Toolset_Condition_Plugin_Views_Active )
            ? $di_views_condition
            : new \Toolset_Condition_Plugin_Views_Active();
        $this->layouts_condition = ( $di_layouts_condition instanceof \Toolset_Condition_Plugin_Layouts_Active )
            ? $di_layouts_condition
            : new \Toolset_Condition_Plugin_Layouts_Active();
	}
	
	/**
	 * @var array
	 */
	private $shortcode_atts = array(
		'content_template_slug' => '',
        'layout_slug' => '',
        'target' => 'self',
        'style' => '',
        'class' => '',
        'id' => ''
	);
	
	/**
	 * @return bool
	 *
	 * @since 2.1
	 */
	public function condition_is_met() {
		return (
            $this->views_condition->is_met()
            || $this->layouts_condition->is_met()
		);
	}
	
	/**
	* Get the shortcode output value.
	*
	* @param $atts
	* @param $content
	*
	* @return string
	*
	* @since 2.1
	*/
	public function get_value( $atts, $content = null ) {
		$this->user_atts    = shortcode_atts( $this->shortcode_atts, $atts );
		$this->user_content = $content;
		
		if (
			empty( $this->user_atts['content_template_slug'] ) 
			&& empty( $this->user_atts['layout_slug'] ) 
		) {
			return;
        }

        $form_id = 0;
		$link_attributes = array();
		$translate_name = 'toolset-edit-user-link';
		
		if ( ! empty( $this->user_atts['layout_slug'] ) ) {
			$layout_id = apply_filters( 'ddl-get_layout_id_by_slug', 0, $this->user_atts['layout_slug'] );
			$translate_name .= '_' . $this->user_atts['layout_slug'];
			if ( $layout_id ) {
				$link_attributes['layout_id'] = $layout_id;
				$form_id = $this->get_form_in_layout( $layout_id, 'user' );
			} else {
				return;
			}
		} else if ( ! empty( $this->user_atts['content_template_slug'] ) ) {
			$ct_id = apply_filters( 'wpv_get_template_id_by_name', 0, $this->user_atts['content_template_slug'] );
			$translate_name .= '_' . $this->user_atts['content_template_slug'];
			if ( $ct_id ) {
				$link_attributes['content-template-id'] = $ct_id;
				$form_id = $this->get_form_in_content_template( $ct_id, 'user' );
			} else {
				return;
			}
		}

		if ( empty( $form_id ) ) {
            return;
        }

        $translate_name .= '_' . substr( md5( $content ), 0, 12 );

        if ( 
			isset( $this->user_atts['id'] ) 
			&& ! empty( $this->user_atts['id'] )
		) {
			if ( is_numeric( $this->user_atts['id'] ) ) {
				$data = get_user_by( 'id', $this->user_atts['id'] );
				if ( $data ) {
					$user_id = $this->user_atts['id'];
					if ( isset( $data->data ) ) {
						$data = $data->data;
					} else {
						return;
					}
				} else {
					return;
				}
			} else {
				return;
			}
		} else {
			global $WP_Views;
			if (
                isset( $WP_Views )
				&& isset( $WP_Views->users_data['term']->ID )
				&& ! empty( $WP_Views->users_data['term']->ID )
			) {
				$user_id = $WP_Views->users_data['term']->ID;
				$data = $WP_Views->users_data['term']->data;
			} else {
				global $current_user;
				if ( $current_user->ID > 0 ) {
					$user_id = $current_user->ID;
					$data = new \WP_User( $user_id );
					if ( isset( $data->data ) ) {
						$data = $data->data;
					} else {
						return;
					}
				} else {
					return;
				}
			}
		}
		
        $link_attributes['user_id'] = $user_id;
        
        global $post;
		
		if ( empty( $post ) ) {
            return;
        }

        $post_status = $post->post_status;
        
        if ( 'publish' != $post_status ) {
            return;
        }
			
        $form_settings = (array) get_post_meta( $form_id, '_cred_form_settings', true );
        if (
            ! is_array( $form_settings ) 
            || empty( $form_settings ) 
            || ! array_key_exists( 'form', $form_settings ) 
            || ! array_key_exists( 'type', $form_settings['form'] )
        ) {
            return;
        }
        
        $post_id = $post->ID;

        $link = get_permalink( $post_id );
        $link = add_query_arg( $link_attributes, $link );

        $this->classnames = empty( $this->user_atts['class'] ) 
			? array() 
			: explode( ' ', $this->user_atts['class'] );
		
		$this->classnames[] = 'cred-edit-user';
		
		$this->attributes = array(
			'class' => $this->classnames,
			'style' => $this->user_atts['style'],
			'href'  => $link,
			'target' => in_array( $this->user_atts['target'], array( 'top', 'blank' ) ) ? ( '_' . $this->user_atts['target'] ) : ''
        );
        
        if ( empty( $this->attributes['href'] ) ) {
			return '';
        }
        
        $this->user_content = $this->translate_link( $translate_name, $this->user_content, true, 'Toolset Shortcodes' );
        
        $this->user_content = str_replace( '%%USER_LOGIN%%', $data->user_login, $this->user_content );
        $this->user_content = str_replace( '%%USER_NICENAME%%', $data->user_nicename, $this->user_content );
        $this->user_content = str_replace( '%%USER_ID%%', $user_id, $this->user_content );

        return $this->craft_link_output();
	}
	
}