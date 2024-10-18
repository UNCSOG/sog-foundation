<?php

class WPDDL_Translate_String_Via_Filter {

	public function translate( $string_value, $string_name, $package ) {
		return apply_filters( 'wpml_translate_string', $string_value, $string_name, $package );
	}
}