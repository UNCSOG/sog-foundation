<?php
// ddl-fields-api.php

function the_ddl_field($field_name) {
    echo get_ddl_field($field_name);
}

function get_ddl_field($field_name) {
    global $ddl_fields_api;
    return $ddl_fields_api->get_field($field_name);
}

function get_attachment_field( $field_name, $size = "thumbnail" ){
    global $ddl_fields_api;
    return $ddl_fields_api->get_attachment_field( $field_name, $size );
}

function get_attachment_field_url( $field_name, $size = "thumbnail" ) {
    global $ddl_fields_api;
    return $ddl_fields_api->get_attachment_field_url( $field_name, $size );
}

function the_attachment_field( $field_name, $size = "thumbnail" ){
    global $ddl_fields_api;
    echo $ddl_fields_api->get_attachment_field( $field_name, $size );
}

function has_ddl_repeater($group_name) {
    global $ddl_fields_api;
    return $ddl_fields_api->has_repeater($group_name);
}

function the_ddl_repeater($group_name) {
    global $ddl_fields_api;
    return $ddl_fields_api->the_repeater($group_name);
}

function the_ddl_repeater_index($group_name = null) {
    echo get_ddl_repeater_index($group_name);
}

function get_ddl_repeater_index($group_name = null) {
    global $ddl_fields_api;
    return $ddl_fields_api->the_repeater_index($group_name);
}

function the_ddl_sub_field($field_name) {
    echo get_ddl_sub_field($field_name);
}

function get_attachment_sub_field( $field_name, $size = "thumbnail" ){
    global $ddl_fields_api;
    return $ddl_fields_api->get_attachment_sub_field( $field_name, $size );
}

function the_attachment_sub_field( $field_name, $size = "thumbnail" ){
    $temp = get_attachment_sub_field( $field_name, $size );
    echo is_null( $temp ) ? '' : $temp;
}

function get_ddl_sub_field($field_name) {
    global $ddl_fields_api;
    return $ddl_fields_api->get_sub_field($field_name);
}

function ddl_rewind_repeater($group_name) {
    global $ddl_fields_api;
    $ddl_fields_api->rewind($group_name);
}

global $ddl_repeats;
$ddl_repeats = array();

function ddl_repeat_start($group_name, $button_text, $max_items = -1) {
	global $ddl_repeats;

	array_push($ddl_repeats, array('group' => $group_name,
								   'button' => $button_text));

	?>
		<div class="js-repeat-field-container ddl-repeat-field-container" data-max-items="<?php echo $max_items; ?>">
			<div class="ddl-repeat-field js-ddl-repeat-field" name="<?php echo md5($button_text);?>">
				<div class="ddl-repeat-field-toolbar">
					<i class="fa fa-arrows-v icon-resize-vertical js-ddl-repeat-field-move"></i>
					<i class="fa fa-remove icon-remove js-ddl-repeat-field-remove"></i>
				</div>
				<input type="hidden" name="<?php the_ddl_name_attr('ddl-repeat-id'); ?>" />				
				
	<?php
}

function ddl_repeat_end( $args = array() ) {
	global $ddl_repeats;
	$group_info = array_pop($ddl_repeats);
	$button_text = $group_info['button'];
    $additional_wrap_class = isset( $args['additional_wrap_class'] ) ? $args['additional_wrap_class'] : '';
	?>
			</div> <!-- .js-ddl-repeat-field -->
		</div> <!-- .js-repeat-field-container -->
		<div class="ddl-form-button-wrap<?php echo $additional_wrap_class;?>">
			<button class="button button-secondary js-ddl-repeat-field-button" >
				<i class="icon-plus fa fa-plus"></i> <?php echo $button_text; ?>
			</button>
		</div>
	<?php

}

function get_ddl_name_attr($name) {
	global $ddl_repeats;

	if (sizeof($ddl_repeats)) {
		$group_info = end($ddl_repeats);
		$name = '[' . $group_info['group'] . ']' . $name . '[]'; // Add array indicator
	}

	return 'ddl-layout-' . $name;
}

function the_ddl_name_attr($name) {
	echo get_ddl_name_attr($name);
}

function get_ddl_cell_info($name) {
	global $wpddlayout;

	$info = $wpddlayout->get_current_cell_info();

	if (isset($info[$name])) {
		return $info[$name];
	} else {
		'';
	}
}

function the_ddl_cell_info($name) {
	echo get_ddl_cell_info($name);
}


class DDLFieldsAPI {

    private $attachment_prefix = 'attachment_';
    private $attachment_suffix = '_attachment';
    private $translatable_fields = null;
    private $is_private = false;
    private $context = null;
    private $do_not_translate = array( 'slide_url' );

	function set_current_cell_content( $content, $translatable_fields = null, $context = null ) {
        $this->content = $content;
        $this->is_private = isset( $content['is_private_layout'] ) ? $content['is_private_layout'] : $this->is_private;
        $this->repeaters = array();
        $this->current_group = '';
        $this->translatable_fields = $translatable_fields;
        $this->context = is_array( $context ) ? isset( $context['name'] ) ? $context['name'] : '' : $context;
		$this->do_not_translate = apply_filters( 'ddl_fields-api-do-not-translate-fields', $this->do_not_translate, $this->translatable_fields, $this->context );
    }

    private function needs_translation( $field_name ){
        if( ! $this->translatable_fields || in_array( $field_name, $this->do_not_translate ) ) return false;

        $keys = array_keys( $this->translatable_fields );

        $has_key = array_filter( $keys, array( new DDL_Filter_By_String( $field_name ), 'filter_by_position' ) );

        return count( $has_key ) !== 0;

    }

    function get_field($field_name) {
		if ( isset($this->content[$field_name]) ){
			return $this->content[$field_name];
		}
    }

    function get_attachment_field( $field_name, $size = "thumbnail" ) {
        if ( isset( $this->content[$this->attachment_prefix.$field_name] ) ){
            return wp_get_attachment_image( $this->content[$this->attachment_prefix.$field_name], $size );
        }
    }

    function get_attachment_field_url( $field_name, $size = "thumbnail" ) {
        if ( isset( $this->content[$this->attachment_prefix.$field_name] ) ){
            return wp_get_attachment_image_src( $this->content[$this->attachment_prefix.$field_name], $size );
        }
    }

    function has_repeater($group_name) {
        if (isset($this->repeaters[$group_name]['index'])) {
            if ($this->repeaters[$group_name]['count'] == 0) {
                return false;
            } else {
                return $this->repeaters[$group_name]['index'] + 1 < $this->repeaters[$group_name]['count'];
            }
        } else {

            if (isset($this->content[$group_name])) {
                $this->repeaters[$group_name] = array('index' => -1, 'count' => sizeof($this->content[$group_name]));
                return $this->repeaters[$group_name]['count'] > 0;
            } else {
                return false;
            }
        }
    }
    function the_repeater($group_name) {
        $this->current_group = $group_name;
        if (isset($this->repeaters[$group_name]['index'])) {
            $this->repeaters[$group_name]['index']++;
        }
    }

    function the_repeater_index($group_name = null) {
        if (!$group_name) {
            if ($this->current_group) {
                $group_name = $this->current_group;
            } else {
                return 0;
            }
        }
        if (isset($this->repeaters[$group_name]['index'])) {
            return $this->repeaters[$group_name]['index'];
        } else {
            return 0;
        }

    }

    function get_sub_field($field_name) {
        $temp = $this->content[$this->current_group][$this->repeaters[$this->current_group]['index']][$field_name];

        if( ! $this->is_private ){
	        return $temp;
        } elseif( $this->is_private && $this->needs_translation( $field_name ) ){
            $temp = $this->build_wpml_string( $temp, $field_name, $this->current_group );
            return $temp;
        } else {
	        return $temp;
        }
    }

    private function build_wpml_string( $value, $field_name, $current_group ){
        $context = $this->get_context( $field_name, $current_group );
        return sprintf( '[wpml-string context="%s"]%s[/wpml-string]', $context, $value );
    }

    private function get_context( $field_name, $current_group ){
        if( $this->context ){
            return $this->context . '_' . $current_group . '_' . $field_name;
        } else {
            return $current_group . '_' . $field_name;
        }
    }

    function get_attachment_sub_field( $field_name, $size = "thumbnail" ) {
        if( isset( $this->content[$this->current_group][$this->repeaters[$this->current_group]['index']][$field_name.$this->attachment_suffix] ) ){
            $temp = $this->content[$this->current_group][$this->repeaters[$this->current_group]['index']][$field_name.$this->attachment_suffix];
            return wp_get_attachment_image( $temp, $size );
        }
        return null;
    }

    function rewind($group_name) {
        unset($this->repeaters[$group_name]);
    }

}

class DDL_Filter_By_String{
    private $string;

    public function __construct( $string ) {
        $this->string = $string;
    }

    public function filter_by_position( $item ){
        return strpos( $item, $this->string ) !== false;
    }

}

global $ddl_fields_api;

$ddl_fields_api = new DDLFieldsAPI();

