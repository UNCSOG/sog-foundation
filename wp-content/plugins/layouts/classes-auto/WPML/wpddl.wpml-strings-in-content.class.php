<?php

class WPDDL_WPML_Strings_In_Content {

	public function find( $content ) {
		$wpml_string_shortcodes = array();

		$pattern             = get_shortcode_regex( array( 'wpml-string' ) );

		if ( preg_match_all( '/' . $pattern . '/s', $content, $matches ) ) {
			for ( $i = 0; $i < count( $matches[5] ); $i++ ) {
				$shortcode = array( 'content' => $matches[0][ $i ],'string' => $matches[5][ $i ], 'context' => '' );
				$attributes = (array) shortcode_parse_atts( $matches[3][ $i ] );
				foreach ( $attributes as $key => $attribute ) {
					if ( 'context' == $key ) {
						$shortcode['context'] = $attribute;
					} else if( 'name' == $key ){
						$shortcode['name'] = $attribute;
					}
				}

				$wpml_string_shortcodes[] = $shortcode;
			}
		}

		return $wpml_string_shortcodes;

	}
}
