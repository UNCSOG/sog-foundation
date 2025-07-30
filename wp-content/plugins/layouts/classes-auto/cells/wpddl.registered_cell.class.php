<?php

class WPDD_registed_cell_types{

	private $cell_types;
	private $current_cell_type;

	function __construct(){
		$this->cell_types = array();
		$this->current_cell_type = '';
	}

	function register_dd_layout_cell_type($cell_type, $data) {
		if (array_key_exists($cell_type, $this->cell_types)) {
			return false;
		} else {
			$this->current_cell_type = $cell_type;

			if (!isset($data['cell-image-url'])) { $data['cell-image-url'] = ''; }
			if (!isset($data['name'])) { $data['name'] = ''; }
			if (!isset($data['description'])) { $data['description'] = ''; }
			if (!isset($data['preview-image-url'])) { $data['preview-image-url'] = ''; }
			if (!isset($data['button-text'])) { $data['button-text'] = ''; }
			if (!isset($data['dialog-template'])) { $data['dialog-template'] = ''; }
			if (!isset($data['allow-multiple'])) { $data['allow-multiple'] = true; }
			if (!isset($data['cell-class'])) { $data['cell-class'] = ''; }
			if (!isset($data['register-styles'])) { $data['register-styles'] = array(); }
			if (!isset($data['register-scripts'])) { $data['register-scripts'] = array(); }

			$this->cell_types[$cell_type] = $data; // Initialize here so it can be accessed during dialog template callback.

			if (
				isset( $data['dialog-template-callback'] )
				&& $this->is_layout_editor()
			) {
				$data['dialog-template'] = call_user_func( $data['dialog-template-callback'] );
			}

			$this->cell_types[$cell_type] = $data;

			$this->current_cell_type = '';

			return true;
		}
	}

	/**
	 * Check whether we are on a Layout editor page, either backend or frontend.
	 *
	 * @return bool
	 * @since 2.6.3
	 */
	private function is_layout_editor() {
		if ( is_admin() ) {
			// Are we on a backend Layout editor page?
			return ( 'dd_layouts_edit' === toolset_getget( 'page' ) );
		}

		// Are we on a frontend Layout editor?
		return array_key_exists( 'toolset_editor', $_GET );
	}

	function get_input_name($name) {
		return 'ddl-layout-' . $name;
	}

	function get_cell_types () {
		return array_keys($this->cell_types);
	}

	function get_cell_templates () {
		$templates = '';

		foreach ($this->cell_types as $cell_type => $data) {
			$templates .= '<script type="text/html" id="' . $cell_type . '-template">';
			$templates .= $this->get_cell_template($data);
			$templates .= '</script>';
		}

		return $templates;
	}

	public static function clean_js_variables( $js_string )
	{
		// strip js not allowed characters
		$clean = preg_replace('/[\|&;\$%@"<>\(\)\+,]/', '', $js_string);

		// strip spaces
		$clean = str_replace(' ', '', $clean );

		return $clean;
	}

	function get_cell_template($cell_data) {
		$data_to_display = is_callable( $cell_data['cell-template-callback'] ) ? call_user_func( $cell_data['cell-template-callback'] ) : false;
		if ( $data_to_display && strpos($data_to_display, '<div class="cell-content">') !== false ) {
			return $data_to_display;
		} else {
			ob_start();
			?>
				<div class="cell-content">
					<p class="cell-name">{{ name }}</p>
					<?php if ($data_to_display): $data_to_display = WPDD_registed_cell_types::clean_js_variables( $data_to_display ); ?>
						<div class="cell-content">
							<#
							/*
							 * fails silently with a console message if
							 * content is undefined or null
							 * anyway it prints cells on the screen
							 * if content.<?php echo $data_to_display; ?>
							 * is undefined _.template handles
							 * the issue internally an print empty string silently
							 */
							try {
									var element = DDL_Helper.sanitizeHelper.stringToDom( content.<?php echo $data_to_display; ?> );
									print( element.innerHTML );
								}
								catch(e) {
									 console.log( e.message );
								}
							#>
						</div>
					<?php endif; ?>
				</div>
			<?php
			return ob_get_clean();
		}
	}

	function get_cell_info($cell_type) {
		return $this->cell_types[$cell_type];
	}

	function get_current_cell_info() {
		if ($this->current_cell_type) {
			return $this->cell_types[$this->current_cell_type];
		} else {
			return array();
		}

	}

	function create_cell($cell_type, $name, $width, $css_class_name, $content, $css_id, $tag, $unique_id) {
		if (isset($this->cell_types[$cell_type])) {
            $cell = new WPDD_registered_cell(null, $cell_type, $name, $width, $css_class_name, $content, $this->cell_types[$cell_type], $css_id, $tag, $unique_id);
            global $wpddlayout;
            $wpddlayout->set_registered_cells( $cell );
			return $cell;
		} else {
			return null;
		}
	}

	function enqueue_cell_styles() {
		foreach ($this->cell_types as $cell_type => $data) {
			foreach ($data['register-styles'] as $style_data) {
				call_user_func_array('wp_register_style', $style_data);
				wp_enqueue_style($style_data[0]);
			}
		}
	}

	function enqueue_cell_scripts() {
		foreach ($this->cell_types as $cell_type => $data) {
			if( isset( $data['register-scripts'] ) && is_array( $data['register-scripts'] ) ){
				foreach ($data['register-scripts'] as $script_data) {
					call_user_func_array('wp_register_script', $script_data);
					wp_enqueue_script($script_data[0]);
				}
			}
		}
	}
}

class WPDD_registered_cell extends WPDD_layout_cell {

	private $cell_data;
	function __construct($id, $cell_type, $name, $width, $css_class_name, $content, $cell_data, $css_id, $tag, $unique_id) {
		parent::__construct($id, $name, $width, $css_class_name, '', $content, $css_id, $tag, $unique_id);
		$this->set_cell_type($cell_type);
		$this->cell_data = $cell_data;
	}

	function frontend_render($target) {
		$css = $this->get_css_class_name();
		if ($this->cell_data['cell-class']) {
			$css .= ' ' . $this->cell_data['cell-class'];
		}
		do_action( 'ddl_before_cell_start_callback', $this, $target );
		$out = $target->cell_start_callback( $css, $this->get_width(), $this->get_css_id(), $this->get_tag(), $this );
        do_action( 'ddl_before_frontend_render_cell', $this, $target );

		$out .= $this->frontend_render_cell_content($target);

        do_action( 'ddl_after_frontend_render_cell', $this, $target );
		$out .= $target->cell_end_callback($this->get_tag());
		do_action( 'ddl_after_cell_end_callback', $this, $target );

		return $out;
	}

    function get_cell_data(){
        return $this->cell_data;
    }

	function frontend_render_cell_content($target) {
		if (isset($this->cell_data['cell-content-callback'])) {
			global $ddl_fields_api;

			$content = $this->get_translated_content($target->get_context());
            $content['is_private_layout'] = $target->is_private_layout;

            if( isset($this->cell_data['translatable_fields']) ){
	            $ddl_fields_api->set_current_cell_content( $content, $this->cell_data['translatable_fields'], $target->get_context() );
            } else {
	            $ddl_fields_api->set_current_cell_content( $content );
            }

			$content = call_user_func($this->cell_data['cell-content-callback'], $content);
		} else {
			$content = '';
		}

		return $target->cell_content_callback($content, $this);
	}

    function register_strings_for_translation ( $context ) {

        if (isset($this->cell_data['translatable_fields'])) {
			$unique_id = $this->get_unique_id();
			if ($unique_id) {
				$content = $this->get_content();
				foreach($this->cell_data['translatable_fields'] as $field_name => $field) {

					if ( isset($field['title']) ) {
						$field_title = $this->get_name() . ' - ' . $field['title'];
					} else {
						$field_title = $this->get_name() . ' - ' . $field_name;
					}

					if ( isset($content[$field_name]) && !empty($content[$field_name]) ) {
						if( is_array($content[$field_name]) && isset( $field['child_field'] ) && isset( $content[ $field_name][ $field['child_field'] ] ) ){
							do_action('wpml_register_string',
								$content[ $field_name][ $field['child_field'] ],
								$unique_id . '_' . $field_name . '_' . $field['child_field'],
								$context,
								$field_title,
								isset($field['type']) ? $field['type'] : 'LINE');
						} else {
							do_action('wpml_register_string',
								$content[$field_name],
								$unique_id . '_' . $field_name,
								$context,
								$field_title,
								isset($field['type']) ? $field['type'] : 'LINE');
						}

					} else {
						// It might be a repeating field.
						$regex = '/(.*?)\[(.*?)\]/siU';
						if(preg_match_all($regex, $field_name, $matches, PREG_SET_ORDER)) {
							foreach ($matches as $val) {
								$group = $val[1];
								$name = $val[2];
								if (isset($content[$group])) {
									foreach ($content[$group] as $item) {
										if (isset($item[$name]) && isset($item['ddl-repeat-id']) && !empty($item[$name]) ) {
											do_action('wpml_register_string',
														  $item[$name],
														  $unique_id . '_' . $field_name . '_' . $item['ddl-repeat-id'],
														  $context,
														  $field_title,
														  isset($field['type']) ? $field['type'] : 'LINE');

										}
									}
								}
							}
						}

					}
				}
			}
        }
    }

	function get_translated_content ($context, $translate_method = null) {
		$content = $this->get_content();

		if (isset($this->cell_data['translatable_fields'])) {

			if ( ! $translate_method ) {
				$translate_method = new WPDDL_Translate_String_Via_Filter();
			}

			$unique_id = $this->get_unique_id();

			foreach($this->cell_data['translatable_fields'] as $field_name => $field) {
				if (isset($content[$field_name])) {
					if ( isset( $field['field'] ) ) {
						if( is_array($content[$field_name]) && isset( $field['field'] ) && isset( $content[ $field_name][ $field['child_field'] ] ) ){
							$content[ $field_name][ $field['child_field'] ] = $translate_method->translate(
								isset( $content[ $field_name][ $field['child_field'] ] ) ? $content[ $field_name][ $field['child_field'] ] : '',
								$unique_id . '_' . $field_name . '_' . $content[ $field_name][ $field['child_field'] ],
								$context);
						} else {
							$content[$field_name][$field['field']] = $translate_method->translate(
								isset( $content[$field_name][$field['field']] ) ? $content[$field_name][$field['field']] : '',
								$unique_id . '_' . $field_name . '_' . $field['field'],
								$context);
						}

					} else {
						if( is_array( $content[$field_name] ) && isset( $content[$field_name]['title'] ) ){
							$content[$field_name]['title'] = $translate_method->translate(
									$content[$field_name]['title'],
									$unique_id . '_' . $field_name,
									$context);
						} else {

							$content[$field_name] = $translate_method->translate(
									isset( $content[$field_name] ) ? $content[$field_name] : '',
									$unique_id . '_' . $field_name,
									$context);

						}
					}
				} else {
					// It might be a repeating field.
					$regex = '/(.*?)\[(.*?)\]/siU';
					if(preg_match_all($regex, $field_name, $matches, PREG_SET_ORDER)) {
						foreach ($matches as $val) {
							$group = $val[1];
							$name = $val[2];
							if (isset($content[$group])) {
								foreach ($content[$group] as $index => $item) {
									if (isset($item[$name]) && isset($item['ddl-repeat-id'])) {
										$content[$group][$index][$name] = $translate_method->translate(
													  $item[$name],
													  $unique_id . '_' . $field_name . '_' . $item['ddl-repeat-id'],
													  $context);

									}
								}
							}
						}
					}
				}
			}
		}

		return $content;
	}

	public function process( $processor ) {
		$processor->process_cell( $this );
	}
}
