<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Form\Link;

/**
 * Post edit link class.
 *
 * @since m2m
 */
class Post 
    extends Base 
    implements \CRED_Shortcode_Interface, \CRED_Shortcode_Interface_Conditional {
	
    const SHORTCODE_NAME = 'toolset-edit-post-link';

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
        'id' => '',
        'item' => ''
	);
	
    /**
	 * @var array
	 */
    private $supported_publish_post_statuses = array();

    /**
	 * @var array
	 */
    private $supported_extra_post_statuses = array();
	
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
		
		if ( ! $item_id = $this->item->get( $this->user_atts ) ) {
			// no valid item
			return;
		}
		
		$item_post = get_post( $item_id );
		
		$form_id = 0;
		$link_attributes = array();
		$translate_name = 'toolset-edit-post-link';
		
		if ( ! empty( $this->user_atts['layout_slug'] ) ) {
			$layout_id = apply_filters( 'ddl-get_layout_id_by_slug', 0, $this->user_atts['layout_slug'] );
			$translate_name .= '_' . $this->user_atts['layout_slug'];
			if ( $layout_id ) {
				$link_attributes['layout_id'] = $layout_id;
				$form_id = $this->get_form_in_layout( $layout_id, 'post' );
			} else {
				return;
			}
		} else if ( ! empty( $this->user_atts['content_template_slug'] ) ) {
			$ct_id = apply_filters( 'wpv_get_template_id_by_name', 0, $this->user_atts['content_template_slug'] );
			$translate_name .= '_' . $this->user_atts['content_template_slug'];
			if ( $ct_id ) {
				$link_attributes['content-template-id'] = $ct_id;
				$form_id = $this->get_form_in_content_template( $ct_id, 'post' );
			} else {
				return;
			}
		}

		if ( empty( $form_id ) ) {
            return;
        }

        $translate_name .= '_' . substr( md5( $content ), 0, 12 );
		
        $post_orig_id = $item_post->ID;
        // Adjust for WPML support
        // If WPML is enabled, $post_id should contain the right ID for the current post in the current language
        // However, if using the id attribute, we might need to adjust it to the translated post for the given ID
        $post_id = apply_filters( 'translate_object_id', $post_orig_id, $item_post->post_type, true, null );
        
        // Check if the current user can edit this post
        $post_author = ( $post_orig_id == $post_id ) ? $item_post->post_author : get_post_field( 'post_author', $post_id );
        if ( ! $this->can_current_user_edit_this_post( $form_id, $post_author ) ) {
            return;
        }
        
        // Check if the post to edit can be edited with this form
        $post_type = ( $post_orig_id == $post_id ) ? $item_post->post_type : get_post_type( $post_id );
        $form_settings = (array) get_post_meta( $form_id, '_cred_form_settings', true );
        if (
            ! is_array( $form_settings ) 
            || empty( $form_settings ) 
            || ! array_key_exists( 'form', $form_settings ) 
            || ! array_key_exists( 'type', $form_settings['form'] ) 
            || ! array_key_exists( 'post', $form_settings ) 
            || ! array_key_exists( 'post_type', $form_settings['post'] )
        ) {
            return;
        }
        if ( $post_type != $form_settings['post']['post_type'] ) {
            return;
        }
        
        // Get the edited post status, to be used latr when building the link
        $post_status = ( $post_orig_id == $post_id ) ? $item_post->post_status : get_post_status( $post_id );

        /**
         * Filter the list of publish-like allowed post statuses to be supported by the Toolst edit post links.
         *
         * By default, we display permalink-based edit links for published posts,
         * as well as for posts that are not published but
         * belong to any of those supported statuses
         *
         * @param array List of supported publish-like statuses
         * @param int $form_id ID of the form that this link is supposed to use
         *
         * @since 2.1.2
         */
        $this->supported_publish_post_statuses = apply_filters( 
            'toolset_filter_edit_post_link_publish_statuses_allowed',
            array( 'publish' ),
            $form_id
        );

        /**
         * Filter the list of extra allowed post statuses to be supported by the Toolst edit post links.
         *
         * By default, we display preview-link-based edit links for posts
         * that are not published but:
         * - belong to any of those supported statuses, and
         * - are editable by the current user.
         *
         * @param array List of supported non-published statuses
         * @param int $form_id ID of the form that this link is supposed to use
         *
         * @since 2.1
         */
        $this->supported_extra_post_statuses = apply_filters( 
            'toolset_filter_edit_post_link_extra_statuses_allowed',
            array( 'future', 'draft', 'pending', 'private' ),
            $form_id
        );
        
        $link = $this->get_link_href( $post_id, $post_type, $post_status, $link_attributes );

        if ( ! $link ) {
            return;
        }

        $this->classnames = empty( $this->user_atts['class'] ) 
			? array() 
			: explode( ' ', $this->user_atts['class'] );
		
		$this->classnames[] = 'cred-edit-post';
		
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
        
        $this->user_content = str_replace( '%%POST_TITLE%%', $item_post->post_title, $this->user_content );
        $this->user_content = str_replace( '%%POST_ID%%', $item_post->ID, $this->user_content );
        
        return $this->craft_link_output();
    }

    /**
     * Make sure that the current user can edit the referenced post.
     *
     * @param int $form_id
     * @param string $post_author
     * 
     * @return boolean
     * 
     * @since 2.1
     */
    private function can_current_user_edit_this_post( $form_id, $post_author ) {
        global $current_user;

        if ( 
            ! current_user_can( 'edit_own_posts_with_cred_' . $form_id ) 
            && $current_user->ID == $post_author 
        ) {
            return false;
        }
        if ( 
            ! current_user_can( 'edit_other_posts_with_cred_' . $form_id ) 
            && $current_user->ID != $post_author 
        ) {
            return false;
        }

        return true;
    }
	
	/**
	 * Get the link target basic URL.
     * 
     * @param int $post_id
     * @param string $post_type
     * @param string $post_status
     * @param array $link_attributes
	 *
	 * @return string|bool
	 *
	 * @since 2.1
	 */
	private function get_link_href( $post_id, $post_type, $post_status, $link_attributes ) {
        $link = false;
        
        $rfg_post_type_query_factory = new \Toolset_Post_Type_Query_Factory();
        $rfg_post_type_query = $rfg_post_type_query_factory->create(
            array(
                \Toolset_Post_Type_Query::IS_REPEATING_FIELD_GROUP => true,
                \Toolset_Post_Type_Query::RETURN_TYPE => 'slug'
            )
        );
        
        $rfg_post_types = $rfg_post_type_query->get_results();
        if ( in_array( $post_type, $rfg_post_types ) ) {
            if ( 
                ! apply_filters( 'toolset_is_m2m_enabled', false ) 
                || 'publish' != $post_status
            ) {
                return $out;
            }
            do_action( 'toolset_do_m2m_full_init' );
            
            $association_query = new \Toolset_Association_Query_V2();
            $associations = $association_query
                ->limit( 1 )
                ->add( $association_query->element_id_and_domain( $post_id, \Toolset_Element_Domain::POSTS, new \Toolset_Relationship_Role_Child() ) )
                ->return_element_ids( new \Toolset_Relationship_Role_Parent() )
                ->get_results();
                
            if ( 
                is_array( $associations ) 
                && count( $associations ) 
            ) {
                $post_belongs_id = reset( $associations );
                $post_belongs_status = get_post_status( $post_belongs_id );
                
                if ( 'publish' == $post_belongs_status ) {
                    $link = get_permalink( $post_belongs_id );
                } else if ( 
                    in_array( $post_belongs_status, $this->supported_extra_post_statuses ) 
                    && current_user_can( 'edit_post', $post_belongs_id ) 
                    && function_exists( 'get_preview_post_link' )
                    
                ) {
                    $link = get_preview_post_link( $post_belongs_id );
                }
                
                $link_attributes['cred_action'] = 'edit_rfg';
                $link_attributes['cred_rfg_id'] = $post_id;
            }
        } else if ( in_array( $post_status, $this->supported_publish_post_statuses ) ) {
            $link = get_permalink( $post_id );
        } else if ( 
            in_array( $post_status, $this->supported_extra_post_statuses ) 
            && current_user_can( 'edit_post', $post_id ) 
            && function_exists( 'get_preview_post_link' )
            
        ) {
            $link = get_preview_post_link( $post_id );
        }

        if ( $link ) {
            $link = add_query_arg( $link_attributes, $link );
        }

        return $link;
	}

	
}