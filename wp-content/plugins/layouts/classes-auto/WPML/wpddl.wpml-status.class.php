<?php

class WPDDL_WPML_Status {

	public function is_string_translation_active() {
		return has_filter( 'wpml_register_string' );
	}

}