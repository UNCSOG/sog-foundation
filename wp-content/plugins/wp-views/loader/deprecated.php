<?php
/**
 * Functions cemetery.
 *
 * @since 1.6.2
 */

/**
 * @param bool $include_hidden
 * @return array
 * @since unknown
 * @deprecated kept for backwards compatibility as it is called from common - let's remove it from there before deleting this.
 */
function get_user_meta_keys( $include_hidden = false ) {
	global $wpdb;
	$values_to_prepare = array();
	//static $cf_keys = null;
	$umf_mulsitise_string = " 1 = 1 ";
	if ( is_multisite() ) {
		global $blog_id;
		$umf_mulsitise_string = " ( meta_key NOT REGEXP '^{$wpdb->base_prefix}[0-9]_' OR meta_key REGEXP '^{$wpdb->base_prefix}%d_' ) ";
		$values_to_prepare[] = $blog_id;
	}
	$umf_hidden = " 1 = 1 ";
	if ( ! $include_hidden ) {
		$hidden_usermeta = array('first_name','last_name','name','nickname','description','yim','jabber','aim',
		'rich_editing','comment_shortcuts','admin_color','use_ssl','show_admin_bar_front',
		'capabilities','user_level','user-settings',
		'dismissed_wp_pointers','show_welcome_panel',
		'dashboard_quick_press_last_post_id','managenav-menuscolumnshidden',
		'primary_blog','source_domain',
		'closedpostboxes','metaboxhidden','meta-box-order_dashboard','meta-box-order','nav_menu_recently_edited',
		'new_date','show_highlight','language_pairs',
		'module-manager',
		'screen_layout');
	//	$umf_hidden = " ( meta_key NOT REGEXP '" . implode("|", $hidden_usermeta) . "' AND meta_key NOT REGEXP '^_' ) "; // NOTE this one make sites with large usermeta tables to fall
		$umf_hidden = " ( meta_key NOT IN ('" . implode("','", $hidden_usermeta) . "') AND meta_key NOT REGEXP '^_' ) ";
	}
	$where = " WHERE {$umf_mulsitise_string} AND {$umf_hidden} ";
	$values_to_prepare[] = 100;
	$usermeta_keys = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT meta_key FROM {$wpdb->usermeta}
			{$where}
			LIMIT 0, %d",
			$values_to_prepare
		)
	);
	if ( ! empty( $usermeta_keys ) ) {
		natcasesort( $usermeta_keys );
	}
	return $usermeta_keys;
}

/**
 * Ajax function to set the current content template to posts of a type set in $_POST['type'].
 *
 * @since unknown
 * @deprecated 2.8
 * @delete 3.0
 */
add_action( 'wp_ajax_set_view_template', 'wpv_deprecated_set_view_template_callback' );
function wpv_deprecated_set_view_template_callback() {
	_deprecated_hook( 'wp_ajax_set_view_template', 'Toolset Views 2.8' );
	wp_send_json_error();
}

/**
 * Original callback for the wpv-control shortcode
 * when rendering search controls for taxonomies.
 *
 * @param array $atts
 * @return string
 * @since unknown
 * @deprecated 2.9 Use WPV_Taxonomy_Frontend_Filter::wpv_shortcode_wpv_control_post_taxonomy instead
 */
function wpv_render_taxonomy_control( $atts ) {
	$adjusted_atts = shortcode_atts( array(
		'taxonomy' => '',
		'type' => '',
		'url_param' => '',
		'default_label' => '',
		'taxonomy_orderby' => '',
		'taxonomy_order' => '',
		'format' => '',
		'hide_empty' => '',
		'style' => '', // input inline styles
		'class' => '', // input classes
		'label_style' => '', // inline styles for input label
		'label_class' => '' // classes for input label
	), $atts );

	// Translate the sorting attributes to the ones expected by the callback
	$adjusted_atts['orderby'] = $adjusted_atts['taxonomy_orderby'];
	$adjusted_atts['order'] = $adjusted_atts['taxonomy_order'];
	return WPV_Taxonomy_Frontend_Filter::wpv_shortcode_wpv_control_post_taxonomy( $adjusted_atts );
}

/**
 * Taxonomy independent version of wp_category_checklist
 *
 * @param array $args
 * @since unknown
 * @uses WPV_Walker_Category_Checklist
 * @deprecated 2.9 Not used anywhere, maybe keep for backwards compatibility
 */
if ( ! function_exists( 'wpv_terms_checklist' ) ) {
	function wpv_terms_checklist( $post_id = 0, $args = array() ) {
		$defaults = array(
			'descendants_and_self' => 0,
			'selected_cats' => false,
			'popular_cats' => false,
			'walker' => null,
			'url_format' => false,
			'format' => false,
			'taxonomy' => 'category',
			'taxonomy_orderby' => 'name',
			'taxonomy_order' => 'ASC',
			'checked_ontop' => false,
			'get_value' => 'all',
			'classname' => '',
            'style' => '',
            'class' => '',
            'label_class' => '',
            'label_style' => ''
		);
		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if ( empty( $walker ) || !is_a( $walker, 'Walker' ) )
			$walker = new WPV_Walker_Category_Checklist( $url_format, $format, $taxonomy, $selected_cats, $style, $class, $label_style, $label_class );

		if ( !in_array( $taxonomy_orderby, array( 'id', 'count', 'name', 'slug', 'term_group', 'none' ) ) ) $taxonomy_orderby = 'name';
		if ( !in_array( $taxonomy_order, array( 'ASC', 'DESC' ) ) ) $taxonomy_order = 'ASC';

		$descendants_and_self = (int) $descendants_and_self;

		$args = array( 'taxonomy' => $taxonomy );

		$tax = get_taxonomy( $taxonomy );
		$args['disabled'] = false;

		if ( is_array( $selected_cats ) )
			$args['selected_cats'] = $selected_cats;
		elseif ( $post_id )
			$args['selected_cats'] = wp_get_object_terms( $post_id, $taxonomy, array_merge( $args, array( 'fields' => 'ids' ) ) );
		else
			$args['selected_cats'] = array();

		if ( is_array( $popular_cats ) )
			$args['popular_cats'] = $popular_cats;
		else
			$args['popular_cats'] = get_terms( $taxonomy, array( 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );

		if ( $descendants_and_self ) {
			$categories = (array) get_terms( $taxonomy, array( 'child_of' => $descendants_and_self, 'hierarchical' => 0, 'hide_empty' => 1 ) );
			$self = get_term( $descendants_and_self, $taxonomy );
			array_unshift( $categories, $self );
		} else {
			$categories = (array) get_terms( $taxonomy, array('get' => $get_value, 'orderby' => $taxonomy_orderby, 'order' => $taxonomy_order ) );
		}

		if ( $checked_ontop ) {
			// Post process $categories rather than adding an exclude to the get_terms() query to keep the query the same across all posts (for any query cache)
			$checked_categories = array();
			$keys = array_keys( $categories );

			foreach( $keys as $k ) {
				if ( in_array( $categories[$k]->term_id, $args['selected_cats'] ) ) {
					$checked_categories[] = $categories[$k];
					unset( $categories[$k] );
				}
			}

			// Put checked cats on top
			echo call_user_func_array( array( &$walker, 'walk' ), array( $checked_categories, 0, $args ) );
		}
		// Then the rest of them
		echo call_user_func_array( array( &$walker, 'walk' ), array( $categories, 0, $args ) );
	}
}

/**
 * Processes wpv-for-each shortcodes ahead of time,
 * adding index attributes to wpv-post-field and types inner shortcodes.
 *
 * @param string $content
 * @return string
 * @since 1.9.1
 * @deprecated 3.3.0
 */
function wpv_preprocess_foreach_shortcodes( $content ) {
	$dic = apply_filters( 'toolset_dic', false );

	if ( false === $dic ) {
		return $content;
	}
	$resolver = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\Iterators' );

	return $resolver->apply_resolver( $content );
}

/**
 * Process generic loop iterator shortcodes in a given content.
 *
 * Note that the content of the iterator shortcode is base64-encoded,
 * so WordPress does not process it. The loop iterator shortcode should
 * decode its own content and adjust it properly.
 *
 * @param string $shortcode
 * @param string $content
 * @return string
 * @since 3.0.1
 * @deprecated 3.3.0
 */
function wpv_preprocess_post_x_iterator_shortcode( $shortcode, $content ) {
	if ( false === strpos( $content, '[' . $shortcode ) ) {
		return $content;
	}

	global $shortcode_tags;
	// Back up current registered shortcodes and clear them all out
	$orig_shortcode_tags = $shortcode_tags;
	remove_all_shortcodes();

	// Register only $shortcode
	$relationship_service = new Toolset_Relationship_Service();
	$attr_item_chain = new Toolset_Shortcode_Attr_Item_M2M(
		new Toolset_Shortcode_Attr_Item_Legacy(
			new Toolset_Shortcode_Attr_Item_Id(),
			$relationship_service
		),
		$relationship_service
	);
	$factory = new WPV_Shortcode_Factory( $attr_item_chain );

	$shortcode_object = $factory->get_shortcode( $shortcode );
	if ( $shortcode_object ) {
		add_shortcode( $shortcode, array( $shortcode_object, 'render' ) );
	}

	$expression = "/\\[" . $shortcode . ".*?\\](.*?)\\[\\/" . $shortcode ."\\]/is";
	$counts = preg_match_all( $expression, $content, $matches );
	while ( $counts ) {
		foreach( $matches[0] as $index => $match ) {
			// Encode the data to stop WP from trying to fix or parse it.
			// The iterator shortcode will manage this on render.
			$match_encoded = str_replace( $matches[ 1 ][ $index ], 'wpv-b64-' . base64_encode( $matches[ 1 ][ $index ] ), $match );
			$shortcode = do_shortcode( $match_encoded );
			$content = str_replace( $match, $shortcode, $content );
		}
		$counts = preg_match_all( $expression, $content, $matches );
	}
	$shortcode_tags = $orig_shortcode_tags;

	return $content;
}

/**
 * Preprocess wpv-conditional shortcodes.
 *
 * @param string $content
 * @return string
 * @deprecated 3.3.0
 */
function wpv_preprocess_wpv_conditional_shortcodes( $content ) {
	$dic = apply_filters( 'toolset_dic', false );

	if ( false === $dic ) {
		return $content;
	}
	$resolver = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\Conditionals' );

	return $resolver->apply_resolver( $content );
}

/**
 * Processes Views shortcodes inside HTML attributes, fixing a compatibility issue with WordPress 4.2.3 and beyond.
 * Heavily inspired in do_shortcodes_in_html_tags.
 *
 * @since 1.9.1
 * @deprecated 3.3.0
 */
function wpv_preprocess_shortcodes_in_html_elements( $content ) {
	$dic = apply_filters( 'toolset_dic', false );

	if ( false === $dic ) {
		return $content;
	}
	$resolver = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\HtmlAttributes' );

	return $resolver->apply_resolver( $content );
}

/**
 * Resolve shortcodes in shortcode attributes.
 *
 * Alias for wpv_parse_content_shortcodes.
 *
 * @param string $content
 * @return string
 * @deprecated 3.3.0
 */
function wpv_resolve_internal_shortcodes( $content ) {
	$dic = apply_filters( 'toolset_dic', false );

	if ( false === $dic ) {
		return $content;
	}
	$resolver = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\Internals' );

	return $resolver->apply_resolver( $content );
}

/**
 * Parse shortcodes inside other shortcodes.
 *
 * @param string Content to be evaluated for internal shortcodes
 * @return string
 * @since unknown
 * @since 1.4.0 Add support for custom inner shortcodes stored in the global settings.
 * @deprecated 3.3.0
 */
function wpv_parse_content_shortcodes( $content ) {
	$dic = apply_filters( 'toolset_dic', false );

	if ( false === $dic ) {
		return $content;
	}
	$resolver = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\Internals' );

	return $resolver->apply_resolver( $content );
}

/**
 * Separate HTML elements and comments from the text.
 *
 * Heavily inspired in wp_html_split
 *
 * @param string $input The text which has to be formatted.
 * @return array The formatted text.
 * @since 1.10
 * @deprecated 3.3.0
 */
function wpv_html_split( $input ) {
	static $regex;

	if ( ! isset( $regex ) ) {
		$comments =
			  '!'           // Start of comment, after the <.
			. '(?:'         // Unroll the loop: Consume everything until --> is found.
			.     '-(?!->)' // Dash not followed by end of comment.
			.     '[^\-]*+' // Consume non-dashes.
			. ')*+'         // Loop possessively.
			. '(?:-->)?';   // End of comment. If not found, match all input.

		$cdata =
			  '!\[CDATA\['  // Start of comment, after the <.
			. '[^\]]*+'     // Consume non-].
			. '(?:'         // Unroll the loop: Consume everything until ]]> is found.
			.     '](?!]>)' // One ] not followed by end of comment.
			.     '[^\]]*+' // Consume non-].
			. ')*+'         // Loop possessively.
			. '(?:]]>)?';   // End of comment. If not found, match all input.

		$regex =
			  '/('              // Capture the entire match.
			.     '<'           // Find start of element.
			.     '(?(?=!--)'   // Is this a comment?
			.         $comments // Find end of comment.
			.     '|'
			.         '(?(?=!\[CDATA\[)' // Is this a comment?
			.             $cdata // Find end of comment.
			.         '|'
			.             '[^>]*>?' // Find end of element. If not found, match all input.
			.         ')'
			.     ')'
			. ')/s';
	}

	return preg_split( $regex, $input, -1, PREG_SPLIT_DELIM_CAPTURE );
}

/**
  * Get the regular expression for shortcode_inside_shortcode allowed shortcodes and wpv-conditional shortcodes list
 *
 * @deprecated 3.3.0
 */
function wpv_inner_shortcodes_list_regex() {
	$dic = apply_filters( 'toolset_dic', false );

	if ( false === $dic ) {
		return '';
	}
	$resolver = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\NestedBase' );

	return $resolver->get_inner_shortcodes_regex();
}
