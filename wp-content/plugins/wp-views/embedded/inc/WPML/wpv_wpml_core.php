<?php

/* ************************************************************************* *\
        WPML Translation Management integration
\* ************************************************************************* */


/**
 * Auxiliar function to override the current language
 *
 * @param $lang string the current language
 * @return bool $sitepress->get_default_language()
 *
 * @since unknown
 */
function wpv_wpml_icl_current_language( $lang ) { // TODO check why is this needed: it just returns the default language when looking for the current language...
	global $sitepress;

	return $sitepress->get_default_language();
}

/**
 * Converts links in a string to the corresponding ones in the current language
 *
 * @param $body string to check against
 * @return bool|mixed|string $body
 *
 * @since unknown
 */
function wpml_content_fix_links_to_translated_content($body){
	global $WPV_settings, $wpdb, $sitepress, $sitepress_settings, $wp_taxonomies;

	if (isset($sitepress)) {

		static $content_cache = array();

		$target_lang_code = apply_filters( 'wpml_current_language', '' );

		$cache_code = md5($body . $target_lang_code);
		if (isset($content_cache[$cache_code])) {
			$body = $content_cache[$cache_code];
		} else {

			// On the latest fix, those two hooks were  moved to after the _process_generic_text call
			// This needs wild testing on sites with a non-english first language
			add_filter('icl_current_language', 'wpv_wpml_icl_current_language');
			remove_filter('option_rewrite_rules', array($sitepress, 'rewrite_rules_filter'));

			require_once ICL_PLUGIN_PATH . '/inc/absolute-links/absolute-links.class.php';
			$icl_abs_links = new AbsoluteLinks;

			$old_body = $body;
			$alp_broken_links = array();
			$body = $icl_abs_links->_process_generic_text($body, $alp_broken_links);

			// Restore the language as the above call can change the current language.
			do_action( 'wpml_switch_language', $target_lang_code );

			if ($body == '') {
				// Handle a problem with abs links occasionally return empty.
				$body = $old_body;
			}

			$new_body = $body;

			$base_url_parts = parse_url(get_option('home'));

			$links = wpml_content_get_link_paths($body);

			$all_links_fixed = 1;

			$pass_on_qvars = array();
			$pass_on_fragments = array();

			foreach($links as $link_idx => $link) {
				$path = $link[2];
				$url_parts = parse_url($path);

				if(isset($url_parts['fragment'])){
					$pass_on_fragments[$link_idx] = $url_parts['fragment'];
				}

				if((!isset($url_parts['host']) or $base_url_parts['host'] == $url_parts['host']) and
				   (!isset($url_parts['scheme']) or $base_url_parts['scheme'] == $url_parts['scheme']) and
				   isset($url_parts['query'])) {
					$query_parts = explode('&', $url_parts['query']);

					foreach($query_parts as $query){
						// find p=id or cat=id or tag=id queries
						$query_elements = explode('=', $query);
						if ( count( $query_elements ) < 2 ) {
							continue;
						}
						$key = $query_elements[0];
						$value = $query_elements[1];
						$translations = NULL;
						$is_tax = false;
						if($key == 'p'){
							$kind = 'post_' . $wpdb->get_var(
									$wpdb->prepare(
										"SELECT post_type FROM {$wpdb->posts} 
									WHERE ID = %d 
									LIMIT 1",
										$value
									)
								);
						} else if($key == "page_id"){
							$kind = 'post_page';
						} else if($key == 'cat' || $key == 'cat_ID'){
							$is_tax = true;
							$kind = 'tax_category';
							$taxonomy = 'category';
						} else if($key == 'tag'){
							$is_tax = true;
							$taxonomy = 'post_tag';
							$kind = 'tax_' . $taxonomy;
							$value = $wpdb->get_var(
								$wpdb->prepare(
									"SELECT term_taxonomy_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x 
									ON t.term_id = x.term_id 
									WHERE x.taxonomy = %s 
									AND t.slug = %s 
									LIMIT 1",
									$taxonomy,
									$value
								)
							);
						} else {
							$found = false;
							foreach($wp_taxonomies as $ktax => $tax){
								if($tax->query_var && $key == $tax->query_var){
									$found = true;
									$is_tax = true;
									$kind = 'tax_' . $ktax;
									$value = $wpdb->get_var(
										$wpdb->prepare(
											"SELECT term_taxonomy_id FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x 
											ON t.term_id = x.term_id 
											WHERE x.taxonomy = %s 
											AND t.slug = %s 
											LIMIT 1",
											$ktax,
											$value
										)
									);
									$taxonomy = $ktax;
								}
							}
							if(!$found){
								$pass_on_qvars[$link_idx][] = $query;
								continue;
							}
						}

						$link_id = (int)$value;

						if (!$link_id) {
							continue;
						}

						$trid = $sitepress->get_element_trid($link_id, $kind);
						if(!$trid){
							continue;
						}
						if($trid !== NULL){
							$translations = $sitepress->get_element_translations($trid, $kind);
						}
						if(isset($translations[$target_lang_code]) && $translations[$target_lang_code]->element_id != null){

							// use the new translated id in the link path.

							$translated_id = $translations[$target_lang_code]->element_id;

							if($is_tax){ //if it's a tax, get the translated link based on the term slug (to avoid the need to convert from term_taxonomy_id to term_id)
								$translated_id = $wpdb->get_var(
									$wpdb->prepare(
										"SELECT slug FROM {$wpdb->terms} t JOIN {$wpdb->term_taxonomy} x 
										ON t.term_id = x.term_id 
										WHERE x.term_taxonomy_id = %d 
										LIMIT 1",
										$translated_id
									)
								);
							}

							// if absolute links is not on turn into WP permalinks
							if(empty($GLOBALS['WPML_Sticky_Links'])){
								////////
								if(preg_match('#^post_#', $kind)){
									$replace = get_permalink($translated_id);
								}elseif(preg_match('#^tax_#', $kind)){
									remove_filter('icl_current_language', 'wpv_wpml_icl_current_language');
									if(is_numeric($translated_id)) $translated_id = intval($translated_id);
									$replace = get_term_link($translated_id, $taxonomy);
									add_filter('icl_current_language', 'wpv_wpml_icl_current_language');
								}
								$new_link = str_replace($link[2], $replace, $link[0]);

								$replace_link_arr[$link_idx] = array('from'=> $link[2], 'to'=>$replace);
							}else{
								$replace = $key . '=' . $translated_id;
								$new_link = str_replace($query, $replace, $link[0]);

								$replace_link_arr[$link_idx] = array('from'=> $query, 'to'=>$replace);
							}

							// replace the link in the body.
							// $new_body = str_replace($link[0], $new_link, $new_body);
							$all_links_arr[$link_idx] = array('from'=> $link[0], 'to'=>$new_link);
							// done in the next loop

						} else {
							// translation not found for this.
							$all_links_fixed = 0;
						}
					}
				}

			}

			if(!empty($replace_link_arr))
				foreach($replace_link_arr as $link_idx => $rep){
					$rep_to = $rep['to'];
					$fragment = '';

					// if sticky links is not ON, fix query parameters and fragments
					if(empty($GLOBALS['WPML_Sticky_Links'])){
						if(!empty($pass_on_fragments[$link_idx])){
							$fragment = '#' . $pass_on_fragments[$link_idx];
						}
						if(!empty($pass_on_qvars[$link_idx])){
							$url_glue = (strpos($rep['to'], '?') === false) ? '?' : '&';
							$rep_to = $rep['to'] . $url_glue . join('&', $pass_on_qvars[$link_idx]);
						}
					}

					$all_links_arr[$link_idx]['to'] = str_replace($rep['to'], $rep_to . $fragment, $all_links_arr[$link_idx]['to']);

				}

			if(!empty($all_links_arr))
				foreach($all_links_arr as $link){
					$new_body = str_replace($link['from'], $link['to'], $new_body);
				}

			$body = $new_body;
			$content_cache[$cache_code] = $body;

			remove_filter('icl_current_language', 'wpv_wpml_icl_current_language');
			add_filter('option_rewrite_rules', array($sitepress, 'rewrite_rules_filter'));

		}
	}

	return $body;
}


/**
 * Parse links from a given string
 *
 * @param $body string to be parsed
 * @return array $links array of parsed links
 *
 * @since unknown
 */
function wpml_content_get_link_paths($body) {

	$regexp_links = array(
		/*"/<a.*?href\s*=\s*([\"\']??)([^\"]*)[\"\']>(.*?)<\/a>/i",*/
		"/<a[^>]*href\s*=\s*([\"\']??)([^\"^>]+)[\"\']??([^>]*)>/i",
	);

	$links = array();

	foreach($regexp_links as $regexp) {
		if (preg_match_all($regexp, $body, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$links[] = $match;
			}
		}
	}
	return $links;
}

if( !function_exists('disable_wpml_admin_lang_switcher') ) {
	add_filter( 'wpml_show_admin_language_switcher', 'disable_wpml_admin_lang_switcher' );

	/**
	 * Disable the WPML admin bar language switcher on Views, CT and WPA related pages
	 *
	 * @param bool $state The state of the admin lang switcher.
	 *
	 * @return bool $state
	 *
	 * @since 1.9
	 * @since 2.5.0 Moved to a new Class, WPV_WPML_Integration_Embedded.
	 */
	function disable_wpml_admin_lang_switcher( $state ) {
		$disable_in_views_pages = array(
			'views',
			'views-editor',
			'embedded-views',
			'views-embedded',
			'view-templates',
			'ct-editor',
			'embedded-views-templates',
			'view-templates-embedded',
			'view-archives',
			'view-archives-editor',
			'embedded-views-archives',
			'view-archives-embedded',
			'views-settings', // DEPRECATED
			'views-import-export', // DEPRECATED
			'views-debug-information', // DEPRECATED
			'views-update-help', // DEPRECATED
		);
		if (
			is_admin()
			&& isset( $_GET['page'] )
			&& in_array( $_GET['page'], $disable_in_views_pages )
		) {
			$state = false;
		}

		return $state;
	}
}


/* ************************************************************************* *\
        WPML String Translation integration
\* ************************************************************************* */


/****************************************************************/
/*				Deprecated functions					*/
/****************************************************************/
/**
 * Utility function to translate strings used in wpv-control shortcodes.
 *
 * @param string $content The content of the Filter HTML textarea to parse
 * @param int $view_id The current View ID to build the content from
 *
 * @since 1.3.0
 * @deprecated 2.4.0 Use the wpv_register_shortcode_attributes_to_translate callback for the wpv_action_wpv_register_wpml_strings action instead
 */
function wpv_add_controls_labels_to_translation( $content, $view_id ) {
	return;
}

/**
 * wpv_parse_wpml_shortcode
 *
 * Parses wpml-string shortcodes in a given string, handling slashes coming from escaped quotes
 *
 * @param $content the string to parse shortcodes from
 * @return array $output array( N => array( 'context'=> $context, 'content'=> $content, 'name'=> $name ) )
 *
 * @since 1.5.0
 * @deprecated 2.3.0 Keep for backwards compatibility
 */
function wpv_parse_wpml_shortcode( $content ) {

	_doing_it_wrong(
		'wpv_parse_wpml_shortcode',
		__( 'This function was deprecated in Views 2.3.0.', 'wpv-views' ),
		'2.2.2'
	);

	$output = array();
	$content = stripslashes( $content );
	preg_match_all( "/\[wpml-string context=\"([^\"]+)\"]([^\[]+)\[\/wpml-string\]/iUs", $content, $out );
	if ( count( $out[0] ) > 0 ) {
		$matches = count( $out[0] );
		for( $i=0; $i < $matches; $i++ ){
			$output[] = array( 'context' => $out[1][$i], 'content' => $out[2][$i], 'name' => 'wpml-shortcode-' . md5( $out[2][$i] ) );
		}
	}
	return $output;
}

/**
 * wpv_register_wpml_strings
 *
 * Registers strings wrapped into wpml-string shortcodes for translation using WPML, handling slashes coming from escaped quotes
 *
 * @param string $content The string to parse shortcodes from.
 *
 * @since 1.5.0
 * @since 2.2.2 Return early when there is no wpml-string shortode to register.
 * @since 2.2.2 Register strings using a fake wpml-string shortcode callback.
 * @deprecated 2.3.0 Keep for backwards compatibility.
 */
function wpv_register_wpml_strings( $content ) {

	_doing_it_wrong(
		'wpv_register_wpml_strings',
		__( 'This function was deprecated in Views 2.3.0. Use the "wpv_action_wpv_register_wpml_strings" action instead.', 'wpv-views' ),
		'2.3.0'
	);

	if ( strpos( $content, '[wpml-string' ) === false ) {
		return;
	}

	if ( function_exists( 'icl_register_string' ) ) {

		$content = stripslashes( $content );

		global $shortcode_tags;
		// Back up current registered shortcodes and clear them all out
		$orig_shortcode_tags = $shortcode_tags;
		remove_all_shortcodes();

		add_shortcode( 'wpml-string', 'wpv_fake_wpml_string_shortcode_to_icl_register_string' );
		do_shortcode( $content );

		$shortcode_tags = $orig_shortcode_tags;
	}
}

/**
 * wpv_fake_wpml_string_shortcode_to_icl_register_string
 *
 * Fake callback for the wpml-string shortcode,
 * so its attributes can be parsed and defaulted, and the string can be registered.
 *
 * @param atts array
 * @param content string
 *
 * @since 2.2.2
 * @deprecated 2.3.0 Keep for backwards compatibility.
 */
function wpv_fake_wpml_string_shortcode_to_icl_register_string( $atts, $content ) {

	_doing_it_wrong(
		'wpv_fake_wpml_string_shortcode_to_icl_register_string',
		__( 'This function was deprecated in Views 2.3.0. Use the "wpv_action_wpv_register_wpml_strings" action instead.', 'wpv-views' ),
		'2.3.0'
	);

	if ( function_exists( 'icl_register_string' ) ) {
		$atts = shortcode_atts(
			array(
				'context'	=> 'wpml-shortcode',
				'name'		=> ''
			),
			$atts
		);
		$atts['name'] = empty( $atts['name'] ) ? 'wpml-shortcode-' . md5( $content ) : $atts['name'];
		icl_register_string( $atts['context'], $atts['name'], $content );
	}
	return;
}

/****************************************************************/
/*			End of Deprecated shortcode callbacks				*/
/****************************************************************/