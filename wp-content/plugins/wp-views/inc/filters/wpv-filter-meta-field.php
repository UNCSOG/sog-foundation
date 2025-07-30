<?php

use OTGS\Toolset\Views\Controller\Filters\Post\ProductStock;

/**
* Meta Field filter
*
* Base filter for postmeta, usermeta and termmeta
*
* @package Views
*
* @since 1.12
* @since 2.1	Added to WordPress Archives
* @since 2.1	Include this file only when editing a View or WordPress Archive, or when doing AJAX
*/

use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

// Register common methods
WPV_Meta_Field_Filter::on_load();
// Register specific methods
WPV_Custom_Field_Filter::on_load();
WPV_Termmeta_Field_Filter::on_load();
WPV_Usermeta_Field_Filter::on_load();

class WPV_Meta_Field_Filter {

    static function on_load() {
        add_action( 'init',			array( 'WPV_Meta_Field_Filter', 'init' ) );
		add_action( 'admin_init',	array( 'WPV_Meta_Field_Filter', 'admin_init' ) );
    }

    static function init() {
		wp_register_script(
			'views-filter-meta-field-js',
			WPV_URL . "/res/js/filters/views_filter_meta_field.js",
			array( 'views-filters-js'),
			WPV_VERSION,
			false
		);
		$filter_texts = array(
			'custom'			=> array(
									'dialog_title'		=> __( 'Delete custom field filters', 'wpv-views' ),
									'cancel'			=> __( 'Cancel', 'wpv-views' ),
									'edit_filters'		=> __( 'Edit the custom field filters', 'wpv-views' ),
									'delete_filters'	=> __( 'Delete all custom field filters', 'wpv-views' )
								),
			'usermeta'			=> array(
									'dialog_title'		=> __( 'Delete usermeta field filters', 'wpv-views' ),
									'cancel'			=> __( 'Cancel', 'wpv-views' ),
									'edit_filters'		=> __( 'Edit the usermeta field filters', 'wpv-views' ),
									'delete_filters'	=> __( 'Delete all usermeta field filters', 'wpv-views' )
								),
			'termmeta'			=> array(
									'dialog_title'		=> __( 'Delete termmeta field filters', 'wpv-views' ),
									'cancel'			=> __( 'Cancel', 'wpv-views' ),
									'edit_filters'		=> __( 'Edit the termmeta field filters', 'wpv-views' ),
									'delete_filters'	=> __( 'Delete all termmeta field filters', 'wpv-views' ),
								),
		);
		wp_localize_script( 'views-filter-meta-field-js', 'wpv_meta_field_filter_texts', $filter_texts );
    }

	static function admin_init() {
		add_action( 'admin_enqueue_scripts', array( 'WPV_Meta_Field_Filter','admin_enqueue_scripts' ), 20 );
	}

	/**
	* admin_enqueue_scripts
	*
	* Register the needed script for this filter
	*
	* @since 1.12
	*/

	static function admin_enqueue_scripts( $hook ) {
		wp_enqueue_script( 'views-filter-meta-field-js' );
	}

	/**
	 * Name out fields that can not be used as filters.
	 *
	 * @param string $meta_key
	 * @param string $domain
	 * @return boolean
	 * @since 2.6.4
	 * @since 3.5 File-based fields can not be used as filters.
	 */
	static function can_filter_by( $meta_key, $domain = 'cf' ) {
		// Get the field type as it is a master element to compare against.
		$field_type = wpv_types_get_field_type( $meta_key, $domain );

		// Do not support filtering by field types that make no sense.
		$unfilterable_field_types = array(
			\Toolset_Field_Type_Definition_Factory::AUDIO,
			\Toolset_Field_Type_Definition_Factory::EMBED,
			\Toolset_Field_Type_Definition_Factory::FILE,
			\Toolset_Field_Type_Definition_Factory::IMAGE,
			\Toolset_Field_Type_Definition_Factory::VIDEO,
		);
		if ( in_array( $field_type, $unfilterable_field_types, true ) ) {
			return false;
		}

		// Do not support filtering by Types checkboxes fields that do not save a value in the database when unchecked.
		if ( in_array( $field_type, array( \Toolset_Field_Type_Definition_Factory::CHECKBOXES ), true ) ) {
			$field_data = wpv_types_get_field_data( $meta_key, $domain );
			if ( 'yes' == toolset_getnest( $field_data, array( 'data', 'save_empty' ), 'no' ) ) {
				return false;
			}
		}

		// Do not support filtering by meta keys containing a space,
		// a dot and an underscore, or combinations of two of those items
		$meta_key_has_space = ( strpos( $meta_key, ' ' ) !== false );
		$meta_key_has_dot = ( strpos( $meta_key, '.' ) !== false );
		$meta_key_has_underscore = ( strpos( $meta_key, '_' ) !== false );

		if (
			( $meta_key_has_space && $meta_key_has_dot )
			|| ( $meta_key_has_space && $meta_key_has_underscore )
			|| ( $meta_key_has_dot && $meta_key_has_underscore )
		) {
			return false;
		}

		// Do not support filtering by meta keys containing a space unless specific support has been declared for postmeta keys.
		if ( $meta_key_has_space || $meta_key_has_dot ) {
			if ( 'cf' != $domain ) {
				// Do not support problematic characters in termmeta or usermeta filters by default.
				return false;
			}
			$global_views_settings = \WPV_Settings::get_instance();
			if ( ! $global_views_settings->support_spaces_in_meta_filters ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param $args			array(
	 * 							@param name		string	The field name
	 * 							@param nicename	string	The field nicename
	 * 						)
	 * @param $meta_type		string	postmeta|usermeta|termmeta
	 * @param $view_settings	array
	 *
	 * @since 1.12
	*/
	private static function wpv_render_meta_field_options( $args, $meta_type, $view_settings = array() ) {
		global $WP_Views_fapi;
		$compare = array(
			'='				=> __( 'equal to', 'wpv-views' ),
			'!='			=> __( 'different from', 'wpv-views' ),
			'>'				=> __( 'greater than', 'wpv-views' ),
			'>='			=> __( 'greater than or equal', 'wpv-views' ),
			'<'				=> __( 'lower than', 'wpv-views' ),
			'<='			=> __( 'lower than or equal', 'wpv-views' ),
			'LIKE'			=> __( 'like', 'wpv-views' ),
			'NOT LIKE'		=> __( 'not like', 'wpv-views' ),
			'IN'			=> __( 'in', 'wpv-views' ),
			'NOT IN'		=> __( 'not in', 'wpv-views' ),
			'BETWEEN'		=> __( 'between', 'wpv-views' ),
			'NOT BETWEEN'	=> __( 'not between', 'wpv-views' )
		);
		$types = array(
			'CHAR'			=> __( 'string', 'wpv-views' ),
			'NUMERIC'		=> __( 'number', 'wpv-views' ),
			'BINARY'		=> __( 'boolean', 'wpv-views' ),
			'DECIMAL'		=> 'DECIMAL',
			'DATE'			=> 'DATE',
			'DATETIME'		=> 'DATETIME',
			'TIME'			=> 'TIME',
			'SIGNED'		=> 'SIGNED',
			'UNSIGNED'		=> 'UNSIGNED'
		);
		$options = array(
			__( 'Constant', 'wpv-views' )				=> 'constant',
			__( 'URL parameter', 'wpv-views' )			=> 'url',
			__( 'Shortcode attribute', 'wpv-views' )	=> 'attribute',
			'NOW'										=> 'now',
			'TODAY'										=> 'today',
			'FUTURE_DAY'								=> 'future_day',
			'PAST_DAY'									=> 'past_day',
			'THIS_MONTH'								=> 'this_month',
			'FUTURE_MONTH'								=> 'future_month',
			'PAST_MONTH'								=> 'past_month',
			'THIS_YEAR'									=> 'this_year',
			'FUTURE_YEAR'								=> 'future_year',
			'PAST_YEAR'									=> 'past_year',
			'SECONDS_FROM_NOW'							=> 'seconds_from_now',
			'MONTHS_FROM_NOW'							=> 'months_from_now',
			'YEARS_FROM_NOW'							=> 'years_from_now',
			'DATE'										=> 'date'
		);
		$options_with_framework = array(
			__( 'Constant', 'wpv-views' )				=> 'constant',
			__( 'URL parameter', 'wpv-views' )			=> 'url',
			__( 'Shortcode attribute', 'wpv-views' )	=> 'attribute',
			__( 'Framework value', 'wpv-views' )		=> 'framework',
			'NOW'										=> 'now',
			'TODAY'										=> 'today',
			'FUTURE_DAY'								=> 'future_day',
			'PAST_DAY'									=> 'past_day',
			'THIS_MONTH'								=> 'this_month',
			'FUTURE_MONTH'								=> 'future_month',
			'PAST_MONTH'								=> 'past_month',
			'THIS_YEAR'									=> 'this_year',
			'FUTURE_YEAR'								=> 'future_year',
			'PAST_YEAR'									=> 'past_year',
			'SECONDS_FROM_NOW'							=> 'seconds_from_now',
			'MONTHS_FROM_NOW'							=> 'months_from_now',
			'YEARS_FROM_NOW'							=> 'years_from_now',
			'DATE'										=> 'date'
		);
		$options_with_framework_broken = array(
			__( 'Select one option...', 'wpv-views' )	=> '',
			__( 'Constant', 'wpv-views' )				=> 'constant',
			__( 'URL parameter', 'wpv-views' )			=> 'url',
			__( 'Shortcode attribute', 'wpv-views' )	=> 'attribute',
			'NOW'										=> 'now',
			'TODAY'										=> 'today',
			'FUTURE_DAY'								=> 'future_day',
			'PAST_DAY'									=> 'past_day',
			'THIS_MONTH'								=> 'this_month',
			'FUTURE_MONTH'								=> 'future_month',
			'PAST_MONTH'								=> 'past_month',
			'THIS_YEAR'									=> 'this_year',
			'FUTURE_YEAR'								=> 'future_year',
			'PAST_YEAR'									=> 'past_year',
			'SECONDS_FROM_NOW'							=> 'seconds_from_now',
			'MONTHS_FROM_NOW'							=> 'months_from_now',
			'YEARS_FROM_NOW'							=> 'years_from_now',
			'DATE'										=> 'date'
		);
		if ( ! isset( $view_settings['view-query-mode'] ) ) {
			$view_settings['view-query-mode'] = 'normal';
		}
		$fw_key_options = array();
		$fw_key_options = apply_filters( 'wpv_filter_extend_framework_options_for_' . $meta_type . '_field', $fw_key_options );
		$name_sanitized = $args['name'];
		if (
			$view_settings['view-query-mode'] == 'normal'
			&& $meta_type == 'postmeta'
		) {
			// LEGACY
			// For some reason, postmeta store a meta field data in a trimmed key
			// Usermeta used to transform but did nto transform back on frontend, so whitelisting it
			// Termmeta and postmeta on WPAs do not do it and at some point we will revert this for all
			$name_sanitized = str_replace( ' ', '_', $name_sanitized );
			$name_sanitized = str_replace( '.', '_', $name_sanitized );
		} else if ( $view_settings['view-query-mode'] != 'normal' ) {
			// Remove shortcode attribute options on WPAs.
			unset( $options[ __( 'Shortcode attribute', 'wpv-views' ) ] );
			unset( $options_with_framework[ __( 'Shortcode attribute', 'wpv-views' ) ] );
			unset( $options_with_framework_broken[ __( 'Shortcode attribute', 'wpv-views' ) ] );
		}
		// Defaults
		$value = '';
		$compare_selected = '=';
		$type_selected = 'CHAR';
		// Actual data
		if ( isset( $view_settings[ $meta_type . '-field-' . $name_sanitized . '_value' ] ) ) {
			$value = $view_settings[ $meta_type . '-field-' . $name_sanitized . '_value' ];
		}
		$parts = array( $value );
		$value = WPV_Filter_Item::encode_date( $value );
		if ( isset( $view_settings[ $meta_type . '-field-' . $name_sanitized . '_compare' ] ) ) {
			$compare_selected = $view_settings[ $meta_type . '-field-' . $name_sanitized . '_compare' ];
		}
		if ( isset( $view_settings[ $meta_type . '-field-' . $name_sanitized . '_type' ] ) ) {
			$type_selected = $view_settings[ $meta_type . '-field-' . $name_sanitized . '_type' ];
		}
		$name = $meta_type . '-field-' . $name_sanitized . '%s';
		switch ( $compare_selected ) {
			case 'BETWEEN':
			case 'NOT BETWEEN':
				$parts = explode( ',', $value );
				// Make sure we have only 2 items
				while ( count( $parts ) < 2 ) {
					$parts[] = '';
				}
				while ( count( $parts ) > 2 ) {
					array_pop( $parts );
				}
				break;
			case 'IN':
			case 'NOT IN':
				$parts = explode( ',', $value );
				if ( count( $parts ) < 1 ) {
					$parts = array( $value );
				}
				break;
		}
		$value = WPV_Filter_Item::unencode_date( $value );

		$select_type = '<select'
			. ' name="' . esc_attr( sprintf( $name, '_type' ) ) . '"'
			. ' class="' . esc_attr( 'js-wpv-' . $meta_type . '-field-type-select' ) . '"'
			. ' autocomplete="off"'
			. '>';
		foreach ( $types as $type_key => $type_val ) {
			$select_type .= '<option'
				. ' value="' . esc_attr( $type_key ) . '"'
				. ' ' . selected( $type_selected, $type_key, false )
				. '>'
				. esc_html( $type_val )
				. '</option>';
		}
		$select_type .= '</select>';

		/**
		 * @since 2.3
		 */
		$decimals_selected = 2;
		if ( isset( $view_settings[ $meta_type . '-field-' . $name_sanitized . '_decimals' ] ) ) {
			$decimals_selected = $view_settings[ $meta_type . '-field-' . $name_sanitized . '_decimals' ];
		}

		$show_hide_decimals = ( $type_selected == 'DECIMAL' ) ? '' : 'display: none;';
		$select_decimals = '<span class="' . esc_attr( 'js-wpv-' . $meta_type . '-field-decimals-span' ) . '" style="' . $show_hide_decimals . '">';
		$select_decimals .= 'with <select'
			. ' name="' . esc_attr( sprintf( $name, '_decimals' ) ) . '"'
			. ' class="' . esc_attr( 'js-wpv-' . $meta_type . '-field-decimals-select' ) . '"'
			. ' autocomplete="off"'
			. '>';
		for ( $i = 1; $i <= 10; $i++ ) {
			$select_decimals .= '<option'
				. ' value="' . $i . '"'
				. ' ' . selected( $decimals_selected, $i, false )
				. '>'
				. $i
				. '</option>';
		}
		$select_decimals .= '</select> decimal places';
		$select_decimals .= '</span>';

		$select_compare = '<select'
			. ' name="' . esc_attr( sprintf( $name, '_compare' ) ) . '"'
			. ' class="' . esc_attr( 'wpv_' . $meta_type . '_field_compare_select js-wpv-' . $meta_type . '-field-compare-select' ) . '"'
			. ' autocomplete="off"'
			. '>';
		foreach ( $compare as $com_key => $com_val ) {
			$select_compare .= '<option'
			. ' value="' . esc_attr( $com_key ) . '"'
			. ' ' . selected( $compare_selected, $com_key, false )
			. '>'
			. esc_html( $com_val )
			. '</option>';
		}
		$select_compare .= '</select>';
		?>
		<?php
		/* translators: for example, "The field *field-slug* is a *string|number|date* that is *equal to|different from|greater than* the following: */
		echo sprintf(
			__( 'The field %1$s is a %2$s %4$s that is %3$s the following:', 'wpv-views' ),
			esc_attr( $args['nicename'] ),
			$select_type,
			$select_compare,
			$select_decimals
		);
		?>
			<div class="<?php echo esc_attr( 'wpv-filter-multiple-element-options-mode js-wpv-' . $meta_type . '-field-values' ); ?>">
				<input type="hidden" class="<?php echo esc_attr( 'js-wpv-' . $meta_type . '-field-values-real' ); ?>" name="<?php echo esc_attr( sprintf( $name, '_value' ) ); ?>" value="<?php echo esc_attr( $value ); ?>" autocomplete="off" />
				<?php
				foreach ( $parts as $i => $value_part ) {
					?>
					<div class="<?php echo esc_attr( 'wpv_' . $meta_type . '_field_value_div js-wpv-' . $meta_type . '-field-value-div' ); ?>">
						<?php
						$function_value = WPV_Filter_Item::get_custom_filter_function_and_value( $value_part );
						$selected_function = $function_value['function'];
						$options_to_pass = $options;
						if ( $WP_Views_fapi->framework_valid ) {
							$options_to_pass = $options_with_framework;
						} else if ( $selected_function == 'framework' ) {
							$options_to_pass = $options_with_framework_broken;
						}
						echo wpv_form_control(
							array(
								'field' => array(
									'#name'				=> 'wpv_' . $meta_type . '_field_compare_mode-' . $name_sanitized . $i ,
									'#type'				=> 'select',
									'#attributes'		=> array(
										'style'			=> '',
										'class'			=> 'wpv_' . $meta_type . '_field_compare_mode js-wpv-' . $meta_type . '-field-compare-mode js-wpv-element-not-serialize js-wpv-filter-validate',
										'data-type'		=> 'select',
										'autocomplete'	=> 'off'
									),
									'#inline'			=> true,
									'#options'			=> $options_to_pass,
									'#default_value'	=> $selected_function,
								)
							)
						);
						$validate_class = '';
						$validate_type = 'none';
						$hidden_input = '';
						$hidden_date = '';
						$hidden_framework_select = '';
						switch ( $selected_function ) {
							case 'constant':
							case 'future_day':
							case 'past_day':
							case 'future_month':
							case 'past_month':
							case 'future_year':
							case 'past_year':
							case 'seconds_from_now':
							case 'months_from_now':
							case 'years_from_now':
								$hidden_date = ' style="display:none"';
								$hidden_framework_select = ' style="display:none"';
								break;
							case 'url':
								$validate_class = 'js-wpv-filter-validate';
								$validate_type = 'url';
								$hidden_date = ' style="display:none"';
								$hidden_framework_select = ' style="display:none"';
								break;
							case 'attribute':
								$validate_class = 'js-wpv-filter-validate';
								$validate_type = 'shortcode';
								$hidden_date = ' style="display:none"';
								$hidden_framework_select = ' style="display:none"';
								break;
							case 'date':
								$hidden_input = ' style="display:none"';
								$hidden_framework_select = ' style="display:none"';
								break;
							case 'framework':
								$hidden_input = ' style="display:none"';
								$hidden_date = ' style="display:none"';
								break;
							default:
								$hidden_input = ' style="display:none"';
								$hidden_date = ' style="display:none"';
								$hidden_framework_select = ' style="display:none"';
								break;
						}
						?>
						<span class="<?php echo esc_attr( 'js-wpv-' . $meta_type . '-field-value-combo-input' ); ?>" <?php echo $hidden_input; ?>>
						<input type="text" class="<?php echo esc_attr( 'js-wpv-' . $meta_type . '-field-value-text js-wpv-element-not-serialize' ); ?> <?php echo $validate_class; ?>" value="<?php echo esc_attr( $function_value['value'] ); ?>" data-class="<?php echo esc_attr( 'js-wpv-' . $meta_type . '-field-' . $args['name'] . '-value-text' ); ?>" data-type="none" name="<?php echo esc_attr( 'wpv-' . $meta_type . '-field-' . $args['name'] . '-value-text' ); ?>" autocomplete="off" />
						</span>
						<span class="<?php echo esc_attr( 'js-wpv-' . $meta_type . '-field-value-combo-framework' ); ?>" <?php echo $hidden_framework_select; ?>>
						<?php
						if ( $WP_Views_fapi->framework_valid ) {
							?>
							<select class="<?php echo esc_attr( 'js-wpv-' . $meta_type . '-field-framework-value js-wpv-' . $meta_type . '-field-framework-value-text js-wpv-element-not-serialize' ); ?>" name="<?php echo esc_attr( 'wpv-' . $meta_type . '-field-' . $args['name'] . '-framework-value-text' ); ?>" autocomplete="off">
								<option value=""><?php echo esc_html( __( 'Select a key', 'wpv-views' ) ); ?></option>
								<?php
								foreach ( $fw_key_options as $index => $value ) {
								?>
								<option value="<?php echo esc_attr( $index ); ?>" <?php selected( $function_value['value'], $index ); ?>><?php echo esc_html( $value ); ?></option>
								<?php
								}
								?>
							</select>
							<?php
						} else {
							?>
							<span class="wpv-combo">
							<input type="hidden" class="<?php echo esc_attr( 'js-wpv-' . $meta_type . '-field-framework-value js-wpv-' . $meta_type . '-field-framework-value-text js-wpv-element-not-serialize' ); ?>" value="" autocomplete="off" />
							<?php
							$WP_Views_fapi->framework_missing_message_for_filters( false, false );
							?>
							</span>
							<?php
						}
						?>
						</span>
						<span class="<?php echo esc_attr( 'js-wpv-' . $meta_type . '-field-value-combo-date' ); ?>" <?php echo $hidden_date; ?>>
						<?php
						WPV_Filter_Item::date_field_controls( $function_value['function'], $function_value['value'] );
						?>
						</span>
						<button class="<?php echo esc_attr( 'button-secondary js-wpv-' . $meta_type . '-field-remove-value' ); ?>"><i class="icon-remove fa fa-times"></i> <?php echo esc_html( __( 'Remove', 'wpv-views' ) ); ?></button>
					</div>
					<?php
				}
				?>
				<button class="<?php echo esc_attr( 'button-secondary js-wpv-' . $meta_type . '-field-add-value' ); ?>" style="margin-top:10px;"><i class="icon-plus fa fa-plus"></i> <?php echo esc_html( __( 'Add another value', 'wpv-views' ) ); ?></button>
			</div>
	<?php
	}

	/**
	 * wpv_get_list_item_ui_meta_field
	 *
	 * @param $type			string	{$meta_type}-field-{$field_name}
	 * @param $meta_type		string	postmeta|usermeta|termmeta
 	 * @param $view_settings	array
	 *
	 * @since 1.12
	 */
	private static function wpv_get_list_item_ui_meta_field( $type, $meta_type, $view_settings = array() ) {
		$field_name = substr( $type, strlen( $meta_type . '-field-' ) );
		$args = array( 'name' => $field_name );
		if ( ! isset( $view_settings['view-query-mode'] ) ) {
			$view_settings['view-query-mode'] = 'normal';
		}
		if ( ! isset( $view_settings[ $type . '_compare' ] ) ) {
			$view_settings[ $type . '_compare' ] = '=';
		}
		if ( ! isset( $view_settings[ $type . '_type' ] ) ) {
			$view_settings[ $type . '_type' ] = 'CHAR';
		}
		if ( ! isset( $view_settings[ $type . '_value' ] ) ) {
			$view_settings[ $type . '_value' ] = '';
		}
		$field_nicename = wpv_types_get_field_name( $field_name );
		$args['nicename'] = $field_nicename;
		ob_start();
		?>
		<div class="<?php echo esc_attr( 'wpv-filter-multiple-element js-wpv-filter-multiple-element js-wpv-filter-' . $meta_type . '-field-multiple-element js-filter-row-' . $meta_type . '-field-' . $field_name ); ?>" data-field="<?php echo esc_attr( $field_name ); ?>">
			<h4><?php echo __('Field', 'wpv-views') . ' - ' . $field_nicename; ?></h4>
			<span class="wpv-filter-multiple-element-delete">
				<button class="button button-secondary button-small js-filter-remove" data-field="<?php echo esc_attr( $field_name ); ?>" data-nonce="<?php echo wp_create_nonce( 'wpv_view_filter_' . $meta_type . '_field_delete_nonce' );?>">
					<i class="icon-trash fa fa-trash"></i>&nbsp;<?php _e( 'Delete', 'wpv-views' ); ?>
				</button>
			</span>
			<div class="wpv-filter-multiple-element-options">
			<?php self::wpv_render_meta_field_options( $args, $meta_type, $view_settings ); ?>
			</div>
			<div class="js-wpv-filter-toolset-messages"></div>
		</div>
		<?php
		$buffer = ob_get_clean();
		return $buffer;
	}

	/**
	* wpv_add_filter_meta_field_list_item
	*
	* @param $view_settings	array
	* @param $meta_type		string	postmeta|usermeta|termmeta
	* @param $target		string	posts|taxonomies|users
	* @param $label			string
	*
	* @since 1.12
	*/

	static function wpv_add_filter_meta_field_list_item( $view_settings, $meta_type, $target, $label ) {
		if ( ! isset( $view_settings['view-query-mode'] ) ) {
			$view_settings['view-query-mode'] = 'normal';
		}
		if ( ! isset( $view_settings[ $meta_type . '_fields_relationship' ] ) ) {
			$view_settings[ $meta_type . '_fields_relationship' ] = 'AND';
		}
		$summary = '';
		$td = '';
		$count = 0;
		foreach ( array_keys( $view_settings ) as $key ) {
			if (
				strpos( $key, $meta_type . '-field-' ) === 0
				&& strpos( $key, '_compare' ) === strlen( $key ) - strlen( '_compare' )
			) {
				$name = substr( $key, 0, strlen( $key ) - strlen( '_compare' ) );
				$field_name = substr( $name, strlen( $meta_type . '-field-' ) );
				$field_domain = ( 'custom' === $meta_type ) ? 'postmeta' : $meta_type;
				if ( \WPV_Filter_Manager::FILTER_MODE_FULL === toolset_getnest( $view_settings, array( \WPV_Filter_Manager::SETTING_KEY, $field_domain, $field_name, \WPV_Filter_Manager::EDITOR_MODE ), \WPV_Filter_Manager::FILTER_MODE_FULL ) ) {
					$td .= self::wpv_get_list_item_ui_meta_field( $name, $meta_type, $view_settings );
				} else {
					$td .= '<div class="wpv-filter-multiple-element wpv-filter-multiple-readonly-element js-wpv-filter-multiple-element js-wpv-filter-multiple-readonly-element js-wpv-filter-' . esc_attr( $meta_type ) . '-field-multiple-element">';
					$td .= wpv_get_meta_field_summary( $name, $meta_type, $view_settings );
					$td .= '</div>';
				}
				$count++;
				if ( $summary != '' ) {
					if ( $view_settings[ $meta_type . '_fields_relationship' ] == 'OR' ) {
						$summary .= __( ' OR', 'wpv-views' );
					} else {
						$summary .= __( ' AND', 'wpv-views' );
					}
				}
				$summary .= wpv_get_meta_field_summary( $name, $meta_type, $view_settings );
			}
		}
		if ( $count > 0 ) {
			ob_start();
			WPV_Filter_Item::filter_list_item_buttons( $meta_type . '-field', 'wpv_filter_' . $meta_type . '_field_update', wp_create_nonce( 'wpv_view_filter_' . $meta_type . '_field_nonce' ), 'wpv_filter_' . $meta_type . '_field_delete', wp_create_nonce( 'wpv_view_filter_' . $meta_type . '_field_delete_nonce' ) );
			?>
				<?php if ($summary != '') { ?>
					<p class='<?php echo esc_attr( 'wpv-filter-' . $meta_type . '-field-edit-summary js-wpv-filter-summary js-wpv-filter-' . $meta_type . '-field-summary' ); ?>'>
					<?php _e('Select items with field: ', 'wpv-views');
					echo $summary; ?>
					</p>
				<?php } ?>
				<div id="<?php echo esc_attr( 'wpv-filter-' . $meta_type . '-field-edit' ); ?>" class="<?php echo esc_attr( 'wpv-filter-edit js-filter-' . $meta_type . '-field-edit js-wpv-filter-' . $meta_type . '-field-edit js-wpv-filter-edit js-wpv-filter-options' ); ?>" style="padding-bottom:28px;">
				<?php echo $td; ?>
					<div class="<?php echo esc_attr( 'wpv-filter-' . $meta_type . '-field-relationship wpv-filter-multiple-element js-wpv-filter-' . $meta_type . '-field-relationship-container' ); ?>">
						<h4><?php _e( 'Fields relationship:', 'wpv-views' ) ?></h4>
						<div class="wpv-filter-multiple-element-options">
							<?php _e( 'Relationship to use when querying with multiple fields:', 'wpv-views' ); ?>
							<select name="<?php echo esc_attr( $meta_type . '_fields_relationship' ); ?>" class="<?php echo esc_attr( 'js-wpv-filter-' . $meta_type . '-fields-relationship' ); ?>" autocomplete="off">
								<option value="AND" <?php selected( $view_settings[ $meta_type . '_fields_relationship' ], 'AND' ); ?>><?php _e( 'AND', 'wpv-views' ); ?></option>
								<option value="OR" <?php selected( $view_settings[ $meta_type . '_fields_relationship' ], 'OR' ); ?>><?php _e( 'OR', 'wpv-views' ); ?></option>
							</select>
						</div>
					</div>
					<div class="js-wpv-filter-multiple-toolset-messages"></div>
					<?php
					// todo: Uncomment and fix the line below, addressing views-1198 when the related doc tickets (ToolsetDocs-577 & ToolsetDocs-578) will be resolved.
					$doc_link = ''; //apply_filters( 'wpv_filter_wpv_meta_field_filter_documentaton_link', '', $meta_type );
					if ( ! empty( $doc_link ) ) {
						?>
						<span class="filter-doc-help">
							<?php echo $doc_link; ?>
						</span>
						<?php
					}
					?>
				</div>
		<?php
			$li_content = ob_get_clean();
			// WARNINg!!! We have a label and a target here that needs adjusting!!!!
			WPV_Filter_Item::multiple_filter_list_item( $meta_type . '-field', $target, $label, $li_content );
		}
	}

	/**
	* wpv_filter_meta_field_update_callback
	*
	* @param $meta_type string postmeta|usermeta|termmeta
	*
	* @note From an AJAX callback
	*
	* @since 1.12
	*/

	static function wpv_filter_meta_field_update_callback( $meta_type ) {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_' . $meta_type . '_field_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["id"] )
			|| ! is_numeric( $_POST["id"] )
			|| intval( $_POST['id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if ( empty( $_POST['fields'] ) ) {
			$data = array(
				'type' => 'data_missing',
				'message' => __( 'Wrong or missing data.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$change = false;
		$view_id = $_POST['id'];
		parse_str( $_POST['fields'], $fields );
		$view_array = get_post_meta( $view_id, '_wpv_settings', true );
		$summary = __( 'Select items with field: ', 'wpv-views' );
		$result = '';
		foreach ( $fields as $filter_key => $filter_data ) {
			if (
				! isset( $view_array[$filter_key] )
				|| $filter_data != $view_array[$filter_key]
			) {
				if ( is_array( $filter_data ) ) {
					$filter_data = array_map( 'sanitize_text_field', $filter_data );
					$filter_data = array_map( array( 'WPV_Meta_Field_Filter', 'fix_lower_saving' ), $filter_data );
				} else {
					$filter_data = sanitize_text_field( $filter_data );
					$filter_data = WPV_Meta_Field_Filter::fix_lower_saving( $filter_data );
				}
				$change = true;
				$view_array[$filter_key] = $filter_data;
			}
		}
		if ( ! isset( $view_array[ $meta_type . '_fields_relationship' ] ) ) {
			$view_array[ $meta_type . '_fields_relationship' ] = 'AND';
			$change = true;
		}
		if ( $change ) {
			update_post_meta( $view_id, '_wpv_settings', $view_array );
			do_action( 'wpv_action_wpv_save_item', $view_id );
		}
		foreach ( array_keys( $view_array ) as $key ) {
			if (
				strpos( $key, $meta_type . '-field-' ) === 0
				&& strpos( $key, '_compare' ) === strlen( $key ) - strlen( '_compare' )
			) {
				$name = substr( $key, 0, strlen( $key ) - strlen( '_compare' ) );
				if ( $result != '' ) {
					if ( $view_array[ $meta_type . '_fields_relationship' ] == 'OR' ) {
						$result .= __( ' OR', 'wpv-views' );
					} else {
						$result .= __( ' AND', 'wpv-views' );
					}
				}
				$result .= wpv_get_meta_field_summary( $name, $meta_type, $view_array );
			}
		}
		$summary .= $result;

		$parametric_search_hints = array(
			'existence'		=> '',
			'intersection'	=> '',
			'missing'		=> ''
		);
		if ( $meta_type == 'custom' ) {
			$parametric_search_hints = wpv_get_parametric_search_hints_data( $view_id );
		}

		$data = array(
			'id'			=> $view_id,
			'message'		=> __( 'Field filter saved', 'wpv-views' ),
			'summary'		=> $summary,
			'parametric'	=> $parametric_search_hints
		);
		wp_send_json_success( $data );
	}

	/**
	* wpv_filter_meta_field_delete_callback
	*
	* @param $meta_type string postmeta|usermeta|termmeta
	*
	* @note From an AJAX callback
	*
	* @since 1.12
	*/

	static function wpv_filter_meta_field_delete_callback( $meta_type ) {
		if ( ! current_user_can( EDIT_VIEWS ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["wpnonce"] )
			|| ! wp_verify_nonce( $_POST["wpnonce"], 'wpv_view_filter_' . $meta_type . '_field_delete_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST["id"] )
			|| ! is_numeric( $_POST["id"] )
			|| intval( $_POST['id'] ) < 1
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		$view_array = get_post_meta( $_POST["id"], '_wpv_settings', true );
		$fields = is_array( $_POST['field'] ) ? $_POST['field'] : array( $_POST['field'] );
		foreach ( $fields as $field ) {
			$to_delete = array(
				$meta_type . '-field-' . $field . '_compare',
				$meta_type . '-field-' . $field . '_type',
				$meta_type . '-field-' . $field . '_value',
				$meta_type . '-field-' . $field . '_decimals',
			);
			if ( ProductStock::FIELD_SLUG === $field ) {
				$to_delete[] = $meta_type . '-field-' . ProductStock::LEGACY_FIELD_SLUG . '_compare';
				$to_delete[] = $meta_type . '-field-' . ProductStock::LEGACY_FIELD_SLUG . '_type';
				$to_delete[] = $meta_type . '-field-' . ProductStock::LEGACY_FIELD_SLUG . '_value';
				$to_delete[] = $meta_type . '-field-' . ProductStock::LEGACY_FIELD_SLUG . '_decimals';
			}
			foreach ( $to_delete as $index ) {
				if ( isset( $view_array[ $index ] ) ) {
					unset( $view_array[ $index ] );
				}
			}
		}
		update_post_meta( $_POST["id"], '_wpv_settings', $view_array );
		do_action( 'wpv_action_wpv_save_item', $_POST["id"] );

		$parametric_search_hints = array(
			'existence'		=> '',
			'intersection'	=> '',
			'missing'		=> ''
		);
		if ( $meta_type == 'custom' ) {
			$parametric_search_hints = wpv_get_parametric_search_hints_data( $_POST["id"] );
		}

		$data = array(
			'id'			=> $_POST["id"],
			'parametric'	=> $parametric_search_hints,
			'message'		=> __( 'Field filter deleted', 'wpv-views' )
		);
		wp_send_json_success( $data );
	}

	/**
	* fix_lower_saving
	*
	* Fix saving of "lower than" and "lower or equal to" comparisons, which get HTML-encoded when passed through sanitize_text_field
	*
	* @param $data string
	*
	* @return string
	*
	* @since 1.12
	*/

	static function fix_lower_saving( $data ) {
		if (
			'&lt;' == $data
			|| '&lt;=' == $data
		) {
			$data = str_replace( '&lt;', '<', $data );
		}
		return $data;
	}

}

/**
* WPV_Custom_Field_Filter
*
* Views Custom Field Filter Class
*
* @since 1.7
* @since 2.1	Added to WordPress Archives
* @since 2.1	Include this file only when editing a View or WordPress Archive, or when doing AJAX
*/

class WPV_Custom_Field_Filter {

    static function on_load() {
        add_action( 'init',			array( 'WPV_Custom_Field_Filter', 'init' ) );
		add_action( 'admin_init',	array( 'WPV_Custom_Field_Filter', 'admin_init' ) );
		add_action( 'rest_api_init',array( 'WPV_Custom_Field_Filter', 'admin_init' ) );
		// Register custom search filter in dialog
		add_filter( 'wpv_filter_wpv_register_form_filters_shortcodes', array( 'WPV_Custom_Field_Filter', 'wpv_custom_search_filter_shortcodes_postmeta' ) );
    }

    static function init() {

    }

	static function admin_init() {
		// Register filters in dialogs
		add_filter( 'wpv_filters_add_filter',								array( 'WPV_Custom_Field_Filter', 'wpv_filters_add_filter_custom_field' ), 20, 2 );
		add_filter( 'wpv_filters_add_archive_filter',						array( 'WPV_Custom_Field_Filter', 'wpv_filters_add_archive_filter_post_field' ), 1, 1 );
		// Register filters in lists
		add_action( 'wpv_add_filter_list_item',								array( 'WPV_Custom_Field_Filter', 'wpv_add_filter_custom_field_list_item' ), 1, 1 );
		// Update and delete
		add_action( 'wp_ajax_wpv_filter_custom_field_update',				array( 'WPV_Custom_Field_Filter', 'wpv_filter_custom_field_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_custom_field_delete',				array( 'WPV_Custom_Field_Filter', 'wpv_filter_custom_field_delete_callback' ) );
		// Doc link
		add_filter( 'wpv_filter_wpv_meta_field_filter_documentaton_link',	array( 'WPV_Custom_Field_Filter', 'wpv_custom_field_documentation_link' ), 10, 2 );
	}

	/**
	 * LEGACY
	 * @todo check what happens with all the _compare, _type and _value when the meta key has a space AND an underscore?
	 */

	static function wpv_filters_add_filter_custom_field( $filters ) {
		$meta_keys = apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
		foreach ( $meta_keys as $key ) {
			if ( ! WPV_Meta_Field_Filter::can_filter_by( $key ) ) {
				continue;
			}
			$key_nicename = wpv_types_get_field_name( $key );
			// If the key has spaces, and passed WPV_Meta_Field_Filter::can_filter_by, adjust to underscores
			$key = str_replace( ' ', '_', $key );
			// If the key has dots, and passed WPV_Meta_Field_Filter::can_filter_by, adjust to underscores
			$key = str_replace( '.', '_', $key );
			$filters[ 'custom-field-' . $key ] = array(
				'name'		=> sprintf( __( 'Custom field - %s', 'wpv-views' ), $key_nicename ),
				'present'	=> 'custom-field-' . $key . '_compare',
				'callback'	=> array( 'WPV_Custom_Field_Filter', 'wpv_add_new_filter_custom_field_list_item' ),
				'args'		=> array(
								'name' =>'custom-field-' . $key
							)
			);
		}
		return $filters;
	}

	/**
	* wpv_filters_add_archive_filter_post_field
	*
	* @since 2.1
	*/

	static function wpv_filters_add_archive_filter_post_field( $filters ) {
		$meta_keys = apply_filters( 'wpv_filter_wpv_get_postmeta_keys', array() );
		foreach ( $meta_keys as $key ) {
			if ( ! WPV_Meta_Field_Filter::can_filter_by( $key ) ) {
				continue;
			}
			$key_nicename = wpv_types_get_field_name( $key );
			// If the key has spaces, and passed WPV_Meta_Field_Filter::can_filter_by, adjust to underscores
			$key = str_replace( ' ', '_', $key );
			// If the key has dots, and passed WPV_Meta_Field_Filter::can_filter_by, adjust to underscores
			$key = str_replace( '.', '_', $key );
			$filters[ 'custom-field-' . $key ] = array(
				'name'		=> sprintf( __( 'Custom field - %s', 'wpv-views' ), $key_nicename ),
				'present'	=> 'custom-field-' . $key . '_compare',
				'callback'	=> array( 'WPV_Custom_Field_Filter', 'wpv_add_new_archive_filter_custom_field_list_item' ),
				'args'		=> array(
								'name' =>'custom-field-' . $key
							)
			);
		}
		return $filters;
	}

	static function wpv_add_new_filter_custom_field_list_item( $args ) {
		$new_filter_settings = array(
			'view-query-mode'			=> 'normal',
			$args['name'] . '_compare'	=> '=',
			$args['name'] . '_type'		=> 'CHAR',
			$args['name'] . '_value'	=> '',
		);
		WPV_Custom_Field_Filter::wpv_add_filter_custom_field_list_item( $new_filter_settings );
	}

	static function wpv_add_new_archive_filter_custom_field_list_item( $args ) {
		$new_filter_settings = array(
			'view-query-mode'			=> 'archive',
			$args['name'] . '_compare'	=> '=',
			$args['name'] . '_type'		=> 'CHAR',
			$args['name'] . '_value'	=> '',
		);
		WPV_Custom_Field_Filter::wpv_add_filter_custom_field_list_item( $new_filter_settings );
	}

	static function wpv_add_filter_custom_field_list_item( $view_settings ) {
		WPV_Meta_Field_Filter::wpv_add_filter_meta_field_list_item( $view_settings, 'custom', 'posts', __( 'Custom field filter', 'wpv-views' ) );
	}

	static function wpv_filter_custom_field_update_callback() {
		WPV_Meta_Field_Filter::wpv_filter_meta_field_update_callback( 'custom' );
	}

	static function wpv_filter_custom_field_delete_callback() {
		WPV_Meta_Field_Filter::wpv_filter_meta_field_delete_callback( 'custom' );
	}

	static function wpv_custom_field_documentation_link( $link, $meta_type ) {
		if ( $meta_type == 'custom' ) {
			$link = sprintf(
				__( '%sLearn about filtering by custom fields%s', 'wpv-views' ),
				'<a class="wpv-help-link" href="' . WPV_FILTER_BY_CUSTOM_FIELD_LINK . '" target="_blank">',
				' &raquo;</a>'
			);
		}
		return $link;
	}

	/**
	 * Register the wpv-control-postmeta shortcode filter.
	 *
	 * @param array $shortcodes
	 * @return array
	 * @since 2.4.0
	 * @since 3.0 Cache the postmeta field groups for better performance.
	 */
	static function wpv_custom_search_filter_shortcodes_postmeta( $shortcodes ) {
		$subgroups = array();

		$subgroups = apply_filters( \OTGS\Toolset\Views\Controller\Cache\MetaFilters\Post::GET_HOOK_HANDLE, array() );

		$shortcodes['wpv-control-postmeta'] = array(
			'query_type_target'					=> 'posts',
			'query_filter_define_callback'		=> array( 'WPV_Custom_Field_Filter', 'query_filter_define_callback' ),
			'custom_search_filter_subgroups'	=> $subgroups
		);

		return $shortcodes;
	}

	/**
	 * Callback to create or modify the query filter after creating or editing the custom search shortcode.
	 *
	 * @param $view_id		int		The View ID
	 * @param $shortcode	string	The affected shortcode, wpv-control-postmeta
	 * @param $attributes	array	The associative array of attributes for this shortcode
	 * @param $attributes_raw array	The associative array of attributes for this shortcode, as collected from its dialog, before being filtered
	 *
	 * @uses wpv_action_wpv_save_item
	 *
	 * @since 2.4.0
	 */
	static function query_filter_define_callback( $view_id, $shortcode, $attributes, $attributes_raw ) {
		if ( ! isset( $attributes['url_param'] ) ) {
			return;
		}
		$view_array = get_post_meta( $view_id, '_wpv_settings', true );

		$url_param = sanitize_text_field( $attributes_raw['url_param'] );
		$url_param_min = sanitize_text_field( $attributes_raw['url_param_min'] );
		$url_param_max = sanitize_text_field( $attributes_raw['url_param_max'] );

		$value_value = 'URL_PARAM(' . $attributes['url_param'] . ')';
		$value_compare = isset( $attributes_raw['value_compare'] ) ? sanitize_text_field( $attributes_raw['value_compare'] ) : '=';
		// Due to the text field sanitization using the "sanitize_text_field", single instances of the "<" character are converted to
        // entities. So for the cases of "lower than" & "lower than or equal" comparisons, we need to convert it back.
		$value_compare = str_replace( '&lt;', '<', $value_compare );
		$value_type = isset( $attributes_raw['value_type'] ) ? sanitize_text_field( $attributes_raw['value_type'] ) : 'CHAR';

		$value_value_old = isset( $view_array['custom-field-' . $attributes['field'] . '_value'] )
			? $view_array['custom-field-' . $attributes['field'] . '_value']
			: '';
		$value_value_old_multi = explode( ',', $value_value_old );
		switch ( $value_compare ) {
			case 'BETWEEN':
			case 'NOT BETWEEN':
				$value_value = 'URL_PARAM(' . $url_param_min . '),URL_PARAM(' . $url_param_max . ')';
				break;
			case 'BETWEEN LOW':
			case 'NOT BETWEEN LOW':
				$value_value_old_multi[ 0 ] = $value_value;
				$value_value = implode( ',', $value_value_old_multi );
				$value_compare = str_replace( ' LOW', '', $value_compare );
				break;
			case 'BETWEEN HIGH':
			case 'NOT BETWEEN HIGH':
				$value_value_old_multi[ 1 ] = $value_value;
				$value_value = implode( ',', $value_value_old_multi );
				$value_compare = str_replace( ' HIGH', '', $value_compare );
				break;
		}

		$view_array['custom-field-' . $attributes['field'] . '_value'] = $value_value;
		$view_array['custom-field-' . $attributes['field'] . '_compare'] = $value_compare;
		$view_array['custom-field-' . $attributes['field'] . '_type'] = $value_type;
		$result = update_post_meta( $view_id, '_wpv_settings', $view_array );
		do_action( 'wpv_action_wpv_save_item', $view_id );
	}

}

/**
* WPV_Termmeta_Field_Filter
*
* Views Termmeta Field Filter Class
*
* @since 1.12
* @since 2.1	Added to WordPress Archives
* @since 2.1	Include this file only when editing a View or WordPress Archive, or when doing AJAX
*/

class WPV_Termmeta_Field_Filter {

    static function on_load() {
        add_action( 'init',			array( 'WPV_Termmeta_Field_Filter', 'init' ) );
		add_action( 'admin_init',	array( 'WPV_Termmeta_Field_Filter', 'admin_init' ) );
    }

    static function init() {
		global $wp_version;
		if ( version_compare( $wp_version, '4.4' ) < 0 ) {
			return;
		}
    }

	static function admin_init() {
		global $wp_version;
		if ( version_compare( $wp_version, '4.4' ) < 0 ) {
			return;
		}
		// Register filters in dialogs
		add_filter( 'wpv_taxonomy_filters_add_filter',						array( 'WPV_Termmeta_Field_Filter', 'wpv_filters_add_filter_termmeta_field' ), 20, 2 );
		// Register filters in lists
		add_action( 'wpv_add_taxonomy_filter_list_item',					array( 'WPV_Termmeta_Field_Filter', 'wpv_add_filter_termmeta_field_list_item' ), 1, 1 );
		// Update and delete
		add_action( 'wp_ajax_wpv_filter_termmeta_field_update',				array( 'WPV_Termmeta_Field_Filter', 'wpv_filter_termmeta_field_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_termmeta_field_delete',				array( 'WPV_Termmeta_Field_Filter', 'wpv_filter_termmeta_field_delete_callback' ) );
		// Doc link
		add_filter( 'wpv_filter_wpv_meta_field_filter_documentaton_link',	array( 'WPV_Termmeta_Field_Filter', 'wpv_termmeta_field_documentation_link' ), 10, 2 );
	}

	/**
	* wpv_filters_add_filter_termmeta_field
	*
	* Register query filters for each termmeta
	*
	*  @since 1.12
	*/

	static function wpv_filters_add_filter_termmeta_field( $filters ) {
		$meta_keys = apply_filters( 'wpv_filter_wpv_get_termmeta_keys', array() );
		foreach ( $meta_keys as $key ) {
			if ( ! WPV_Meta_Field_Filter::can_filter_by( $key, 'tf' ) ) {
				continue;
			}
			$key_nicename = wpv_types_get_field_name( $key, 'tf' );
			$filters[ 'termmeta-field-' . $key ] = array(
				'name'		=> sprintf( __( 'Termmeta field - %s', 'wpv-views' ), $key_nicename ),
				'present'	=> 'termmeta-field-' . $key . '_compare',
				'callback'	=> array( 'WPV_Termmeta_Field_Filter', 'wpv_add_new_filter_termmeta_field_list_item' ),
				'args'		=> array(
								'name' => 'termmeta-field-' . $key
							)
			);
		}
		return $filters;
	}

	/**
	* wpv_add_new_filter_termmeta_field_list_item
	*
	* Callback when adding a new termmeta field
	*
	* @since 1.12
	*/

	static function wpv_add_new_filter_termmeta_field_list_item( $args ) {
		$new_filter_settings = array(
			'view-query-mode'			=> 'normal',
			$args['name'] . '_compare'	=> '=',
			$args['name'] . '_type'		=> 'CHAR',
			$args['name'] . '_value'	=> '',
		);
		WPV_Termmeta_Field_Filter::wpv_add_filter_termmeta_field_list_item( $new_filter_settings );
	}

	static function wpv_add_filter_termmeta_field_list_item( $view_settings ) {
		WPV_Meta_Field_Filter::wpv_add_filter_meta_field_list_item( $view_settings, 'termmeta', 'taxonomies', __( 'Termmeta field filter', 'wpv-views' ) );
	}

	static function wpv_filter_termmeta_field_update_callback() {
		WPV_Meta_Field_Filter::wpv_filter_meta_field_update_callback( 'termmeta' );
	}

	static function wpv_filter_termmeta_field_delete_callback() {
		WPV_Meta_Field_Filter::wpv_filter_meta_field_delete_callback( 'termmeta' );
	}

	static function wpv_termmeta_field_documentation_link( $link, $meta_type ) {
		if ( $meta_type == 'termmeta' ) {
			/*
			$link = sprintf(
				__( '%sLearn about filtering by taxonomy fields%s', 'wpv-views' ),
				'<a class="wpv-help-link" href="' . WPV_FILTER_BY_CUSTOM_FIELD_LINK . '" target="_blank">',
				' &raquo;</a>'
			);
			*/
		}
		return $link;
	}

}

/**
* WPV_Usermeta_Field_Filter
*
* Views Usermeta Field Filter Class
*
* @since 1.7
* @since 2.1	Added to WordPress Archives
* @since 2.1	Include this file only when editing a View or WordPress Archive, or when doing AJAX
*/

class WPV_Usermeta_Field_Filter {

    static function on_load() {
        add_action( 'init',			array( 'WPV_Usermeta_Field_Filter', 'init' ) );
		add_action( 'admin_init',	array( 'WPV_Usermeta_Field_Filter', 'admin_init' ) );
    }

    static function init() {

    }

	static function admin_init() {
		// Register filters in dialogs
		add_filter( 'wpv_users_filters_add_filter',							array( 'WPV_Usermeta_Field_Filter', 'wpv_filters_add_filter_usermeta_field' ), 20, 2 );
		// Register filters in lists
		add_action( 'wpv_add_users_filter_list_item',						array( 'WPV_Usermeta_Field_Filter', 'wpv_add_filter_usermeta_field_list_item' ), 1, 1 );
		// Update and delete
		add_action( 'wp_ajax_wpv_filter_usermeta_field_update',				array( 'WPV_Usermeta_Field_Filter', 'wpv_filter_usermeta_field_update_callback' ) );
		add_action( 'wp_ajax_wpv_filter_usermeta_field_delete',				array( 'WPV_Usermeta_Field_Filter', 'wpv_filter_usermeta_field_delete_callback' ) );
		// Doc link
		add_filter( 'wpv_filter_wpv_meta_field_filter_documentaton_link',	array( 'WPV_Usermeta_Field_Filter', 'wpv_usermeta_field_documentation_link' ), 10, 2 );
	}

	static function wpv_filters_add_filter_usermeta_field( $filters ) {
        $basic = array(
            array( __( 'First Name', 'wpv-views' ), 'first_name','Basic','' ),
            array( __( 'Last Name', 'wpv-views' ), 'last_name','Basic','' ),
            array( __( 'Nickname', 'wpv-views' ), 'nickname','Basic','' ),
            array( __( 'Description', 'wpv-views' ), 'description','Basic','' ),
            array( __( 'Yahoo IM', 'wpv-views' ), 'yim','Basic','' ),
            array( __( 'Jabber', 'wpv-views' ), 'jabber','Basic','' ),
            array( __( 'AIM', 'wpv-views' ), 'aim','Basic','' ),
        );
        foreach ( $basic as $b_filter ) {
			$filters[ 'usermeta-field-basic-' . $b_filter[1] ] = array(
				'name'		=> sprintf( __( 'User field - %s', 'wpv-views' ), $b_filter[0]),
                'present'	=> 'usermeta-field-' . $b_filter[1] . '_compare',
                'callback'	=> array( 'WPV_Usermeta_Field_Filter', 'wpv_add_new_filter_usermeta_field_list_item' ),
                'args'		=> array( 'name' =>'usermeta-field-' . $b_filter[1] ),
				'group'		=> __( 'User data', 'wpv-views' )
			);
		}
		// @todo review this for gods sake!!!!!!!!!!!!!!!!!!!!!!!!
		// @since m2m wpcf_admin_fields_get_fields_by_group returns strings for repeatng fields groups
        if ( function_exists( 'wpcf_admin_fields_get_groups' ) ) {
            $groups = wpcf_admin_fields_get_groups( 'wp-types-user-group' );
            $user_id = wpcf_usermeta_get_user();
            $add = array();
            if ( ! empty( $groups ) ) {
                foreach ( $groups as $group_id => $group ) {
                    if ( empty( $group['is_active'] ) ) {
                        continue;
                    }
                    $fields = wpcf_admin_fields_get_fields_by_group(
						$group['id'],
                        'slug',
						true,
						false,
						true,
						'wp-types-user-group',
                        'wpcf-usermeta'
					);
					$fields = array_filter( $fields, 'is_array' );
                    if ( ! empty( $fields ) ) {
                        foreach ( $fields as $field_id => $field ) {
							$add[] = $field['meta_key'];
							if ( ! WPV_Meta_Field_Filter::can_filter_by( $field['meta_key'], 'uf' ) ) {
								continue;
							}
                            $filters[ 'usermeta-field-' . $field['meta_key'] ] = array(
								'name'		=> sprintf( __( 'User field - %s', 'wpv-views' ), $field['name'] ),
                                'present'	=> 'usermeta-field-' . $field['meta_key'] . '_compare',
                                'callback'	=> array( 'WPV_Usermeta_Field_Filter', 'wpv_add_new_filter_usermeta_field_list_item' ),
                                'args'		=> array( 'name' =>'usermeta-field-' . $field['meta_key'] )
							);
                        }
                    }
                }
            }
            $cf_types = wpcf_admin_fields_get_fields( true, true, false, 'wpcf-usermeta' );
            foreach ( $cf_types as $cf_id => $cf ) {
                 if ( ! in_array( $cf['meta_key'], $add ) ) {
					if ( ! WPV_Meta_Field_Filter::can_filter_by( $cf['meta_key'], 'uf' ) ) {
						continue;
					}
                    $filters[ 'usermeta-field-' . $cf['meta_key'] ] = array(
						'name'		=> sprintf( __( 'User field - %s', 'wpv-views' ), $cf['name'] ),
                        'present'	=> 'usermeta-field-' . $cf['meta_key'] . '_compare',
                        'callback'	=> array( 'WPV_Usermeta_Field_Filter', 'wpv_add_new_filter_usermeta_field_list_item' ),
                        'args'		=> array( 'name' =>'usermeta-field-' . $cf['meta_key'] )
					);
                 }
            }
        }

        $meta_keys = apply_filters( 'wpv_filter_wpv_get_usermeta_keys', array() );
        foreach ( $meta_keys as $key ) {
			if ( ! WPV_Meta_Field_Filter::can_filter_by( $key, 'uf' ) ) {
				continue;
			}
            $key_nicename = '';
            if (
				stripos( $key, 'wpcf-' ) === 0
				&& function_exists( 'wpcf_admin_fields_get_groups' )
			) {
               	continue;
            } else {
                $key_nicename = $key;
            }
            $filters[ 'usermeta-field-' . $key ] = array(
				'name'		=> sprintf( __( 'User field - %s', 'wpv-views' ), $key_nicename ),
                'present'	=> 'usermeta-field-' . $key . '_compare',
                'callback'	=> array( 'WPV_Usermeta_Field_Filter', 'wpv_add_new_filter_usermeta_field_list_item' ),
                'args'		=> array( 'name' =>'usermeta-field-' . $key )
			);
        }
		return $filters;
	}

	static function wpv_add_new_filter_usermeta_field_list_item( $args ) {
		$new_filter_settings = array(
			'view-query-mode'			=> 'normal',
			$args['name'] . '_compare'	=> '=',
			$args['name'] . '_type'		=> 'CHAR',
			$args['name'] . '_value'	=> '',
		);
		WPV_Usermeta_Field_Filter::wpv_add_filter_usermeta_field_list_item( $new_filter_settings );
	}

	static function wpv_add_filter_usermeta_field_list_item( $view_settings ) {
		WPV_Meta_Field_Filter::wpv_add_filter_meta_field_list_item( $view_settings, 'usermeta', 'users', __( 'Usermeta field filter', 'wpv-views'  ) );
	}

	static function wpv_filter_usermeta_field_update_callback() {
		WPV_Meta_Field_Filter::wpv_filter_meta_field_update_callback( 'usermeta' );
	}

	static function wpv_filter_usermeta_field_delete_callback() {
		WPV_Meta_Field_Filter::wpv_filter_meta_field_delete_callback( 'usermeta' );
	}

	static function wpv_usermeta_field_documentation_link( $link, $meta_type ) {
		if ( $meta_type == 'usermeta' ) {
			$link = sprintf(
				__( '%sLearn about filtering by user fields%s', 'wpv-views' ),
				'<a class="wpv-help-link" href="' . WPV_FILTER_BY_USER_FIELDS_LINK . '" target="_blank">',
				' &raquo;</a>'
			);
		}
		return $link;
	}

}

function wpv_meta_fields_get_url_params( $view_settings, $meta_type ) {
	$pattern = '/URL_PARAM\(([^(]*?)\)/siU';
	$results = array();
	foreach ( array_keys( $view_settings ) as $key ) {
		if (
			strpos( $key, $meta_type . '-field-' ) === 0
			&& strpos( $key, '_compare' ) === strlen( $key ) - strlen( '_compare' )
		) {
			$name = substr( $key, 0, strlen( $key ) - strlen( '_compare' ) );
			$name = substr( $name, strlen( $meta_type . '-field-' ) );
			$value = $view_settings[ $meta_type . '-field-' . $name . '_value' ];
			if ( preg_match_all( $pattern, $value, $matches, PREG_SET_ORDER ) ) {
				foreach ( $matches as $match ) {
					$results[] = array(
						'name'	=> $name,
						'param'	=> $match[1],
						'mode'	=> 'cf'
					);
				}
			}
		}
	}
	return $results;
}

function wpv_custom_fields_get_url_params( $view_settings ) {
	$results = wpv_meta_fields_get_url_params( $view_settings, 'custom' );
	return $results;
}

function wpv_usermeta_fields_get_url_params( $view_settings ) {
	$results = wpv_meta_fields_get_url_params( $view_settings, 'usermeta' );
	return $results;
}

function wpv_termmeta_fields_get_url_params( $view_settings ) {
	$results = wpv_meta_fields_get_url_params( $view_settings, 'termmeta' );
	return $results;
}
