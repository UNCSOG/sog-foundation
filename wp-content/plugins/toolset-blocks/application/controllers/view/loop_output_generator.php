<?php

namespace OTGS\Toolset\Views\View;

/**
 * Class LoopOutputGenerator
 *
 * Handles the generation of the Loop Output produced by the Loop Wizard for all the different options the Wizard offers.
 *
 * @package OTGS\Toolset\Views\View
 *
 * @since 2.6.4
 */
class LoopOutputGenerator {

	/** @var string[] Classes for the row element in the Bootstrap grid, per BS version  */
	const BOOTSTRAP_ROW_CLASS = [
		2 => 'row-fluid',
		3 => '',
		4 => '',
	];

	/** @var string[] Classes for the column element in the Bootstrap grid, per BS version  */
	const BOOTSTRAP_COL_CLASS = [
		2 => 'span',
		3 => 'col-sm-',
		4 => 'col-md-',
	];


	/** @noinspection PhpDocSignatureInspection */

	/**
	 * Generate default loop settings (former layout settings) for a View, based on chosen loop style
	 *
	 * @param string $style Loop style name, which must be one of the following values:
	 *     - table
	 *     - bootstrap-grid
	 *     - table_of_fields
	 *     - ordered_list
	 *     - un_ordered_list
	 *     - unformatted
	 *     - empty (since 1.10): Ignores fields and renders just an empty <wpv-loop></wpv-loop>
	 *
	 * @param array $fields (
	 *         Array of definitions of fields that will be present in the loop. If an element is not present, empty
	 *         string is used instead.
	 *
	 *         @type string $prefix Prefix, text before shortcode.
	 *         @type string $shortcode The shortcode ('[shortcode]').
	 *         @type string $suffix Text after shortcode.
	 *         @type string $field_name Field name.
	 *         @type string $header_name Header name.
	 *         @type string $row_title Row title <TH>.
	 *     )
	 *
	 * @param array $args (
	 *         Additional arguments.
	 *
	 *         @type bool $include_field_names If the loop style is table_of_fields, determines whether the rendered
	 *             loop will contain table header with field names. Optional. Default is true.
	 *
	 *         @type int $tab_column_count Number of columns for the bootstrap-grid style. Optional. Default is 1.
	 *         @type int $bootstrap_column_count Number of columns for the table style. Optional. Default is 1.
	 *         @type int $bootstrap_version Version of Bootstrap. Mandatory for bootstrap-grid style, irrelephant
	 *             otherwise. Must be 2 or 3.
	 *         @type bool $add_container Argument for bootstrap-grid style. If true, enclose rendered html in a
	 *             container div. Optional. Default is false.
	 *         @type bool $add_row_class Argument for bootstrap-grid style. If true, a "row" class will be added to
	 *             elements representing rows. For Bootstrap 3 it is added anyway. Optional. Default is false.
	 *         @type bool $render_individual_columns Argument for bootstrap-grid style. If true, a wpv-item shortcode
	 *             will be rendered for each singular column. Optional. Default is false.
	 *
	 *         @type bool $render_only_wpv_loop If true, only the code that should be within "<!-- wpv-loop start -->" and
	 *             "<!-- wpv-loop end -->" tags is rendered. Optional. Default is false.
	 *
	 *         @type bool $use_loop_template Determines whether a Content Template will be used for field shortcodes.
	 *             If true, the content of the CT will be returned in the 'ct_content' element and the loop will
	 *             contain shortcodes referencing it. In such case the argument loop_template_title is mandatory. Optional.
	 *             Default is false.
	 *
	 *         @type string $loop_template_title Title of the Content Template that should contain field shortcodes. Only
	 *             relevant if use_loop_template is true, and in such case it is mandatory.
	 *     )
	 *
	 * @return  null|array Null on error. Otherwise an array containing following elements:
	 *     array(
	 *         @type array loop_output_settings Loop settings for a View, as they should be stored in the database:
	 *             array(
	 *                 @type string $style
	 *                 @type string $layout_meta_html
	 *                 @type int $table_cols
	 *                 @type int $bootstrap_grid_cols
	 *                 @type string $bootstrap_grid_container '1' or ''
	 *                 @type string $bootstrap_grid_row_class '1' or ''
	 *                 @type string $bootstrap_grid_individual '1' or ''
	 *                 @type string $include_field_names '1' or ''
	 *                 @type array $fields
	 *                 @type array $real_fields
	 *             )
	 *         @type string ct_content Content of the Content Template (see use_loop_template argument for more info) or
	 *             an empty string.
	 *     )
	 *
	 * @since 1.10
	 * @since 2.6.4 The initial method that existed under the "WPV_Base_Class" class was deprecated and it was
	 *              replaced by this method inside a non static class. The "WPV_Base_Class::generate_loop_output" became
	 *              a wrapper that creates an instance of \OTGS\Toolset\Views\ViewLoopOutputGenerator and calls this
	 *              method.
	 * @since 2.7.3 Boolean options should be managed as string where '1' means TRUE and '' means FALSE,
	 *     because we are using methods to sanitize string to deal with them.
	 */
	public function generate( $style = 'empty', $fields = array(), $args = array() ) {
		// Default values for arguments.
		$args = array_merge(
			array(
				'include_field_names' => '1',
				'tab_column_count' => 1,
				'bootstrap_column_count' => 1,
				'bootstrap_version' => 'undefined',
				'add_container' => '',
				'add_row_class' => '',
				'render_individual_columns' => '',
				'use_loop_template' => '',
				'loop_template_title' => '',
				'render_only_wpv_loop' => '',
				'list_separator' => ',',
			),
			$args
		);

		// AValidate, and turn booleans into proper typed values.
		$include_field_names = ( '1' === $args['include_field_names'] ) ? true : false;
		$tab_column_count = (int) $args['tab_column_count'];
		$bootstrap_column_count = (int) $args['bootstrap_column_count'];
		$add_container = ( '1' === $args['add_container'] ) ? true : false;
		$add_row_class = ( '1' === $args['add_row_class'] ) ? true : false;
		$render_individual_columns = ( '1' === $args['render_individual_columns'] ) ? true : false;
		$use_loop_template = ( '1' === $args['use_loop_template'] ) ? true : false;
		$loop_template_title = $args['loop_template_title']; // can be anything.
		$render_only_wpv_loop = ( '1' === $args['render_only_wpv_loop'] ) ? true : false;

		// Disallow empty title if we're creating new CT.
		if (
			( true === $use_loop_template ) &&
			empty( $loop_template_title )
		) {
			return null;
		}

		// Results.
		$loop_output_settings = array(
			'style' => $style,  // this will be valid value, or we'll return null later.
			'additional_js'	=> '',
		);

		// Ensure all field keys are present for all fields.
		$fields_normalized = array();
		$field_defaults = array(
			'prefix' => '',
			'shortcode' => '',
			'suffix' => '',
			'field_name' => '',
			'header_name' => '',
			'row_title' => '',
		);
		foreach ( $fields as $field ) {
			$fields_normalized[] = wp_parse_args( $field, $field_defaults );
		}
		$fields = $fields_normalized;

		// Render layout HTML.
		switch ( $style ) {
			case 'table':
				$loop_output = $this->generate_table_layout( $fields, $args );
				break;
			case 'bootstrap-grid':
				$loop_output = $this->generate_bootstrap_grid_layout( $fields, $args );
				break;
			case 'table_of_fields':
				$loop_output = $this->generate_table_of_fields_layout( $fields, $args );
				break;
			case 'ordered_list':
				$loop_output = $this->generate_list_layout( $fields, $args, 'ol' );
				break;
			case 'un_ordered_list':
				$loop_output = $this->generate_list_layout( $fields, $args, 'ul' );
				break;
			case 'separators_list':
				$loop_output = $this->generate_separators_list( $fields, $args );
				break;
			case 'unformatted':
				$loop_output = $this->generate_unformatted_layout( $fields, $args );
				break;
			case 'empty':
				$loop_output = array(
					'loop_template' => "\t\t<wpv-loop>\n\t\t</wpv-loop>\n",
					'ct_content' => '',
				);
				break;
			default:
				// Invalid loop style.
				return null;
		}
		// If rendering has failed, we fail too.
		if ( null === $loop_output ) {
			return null;
		}

		$layout_meta_html = $loop_output['loop_template'];

		if ( ! $render_only_wpv_loop ) {
			// Render the whole layout_meta_html.
			$layout_meta_html = sprintf(
				"[wpv-layout-start]\n"
				. "\t[wpv-items-found]\n"
				. "\t<!-- wpv-loop-start -->\n"
				. "%s"
				. "\t<!-- wpv-loop-end -->\n"
				. "\t[/wpv-items-found]\n"
				. "\t[wpv-no-items-found]\n"
				. "\t\t<strong>[wpml-string context=\"wpv-views\"]No items found[/wpml-string]</strong>\n"
				. "\t[/wpv-no-items-found]\n"
				. "[wpv-layout-end]\n",
				$layout_meta_html
			);
		}

		$loop_output_settings['layout_meta_html'] = $layout_meta_html;

		// Pass other layout settings in the same way as it was in wpv_update_layout_extra_callback().
		// Only one value makes sense, but both are always stored...
		$loop_output_settings['table_cols'] = $tab_column_count;
		$loop_output_settings['bootstrap_grid_cols']  = $bootstrap_column_count;

		// These are '1' for true or '' for false (not sure if e.g. 0 can be passed instead, better leave it as it was).
		$loop_output_settings['bootstrap_grid_container'] = $add_container ? '1' : '';
		$loop_output_settings['bootstrap_grid_row_class'] = $add_row_class ? '1' : '';
		$loop_output_settings['bootstrap_grid_individual'] = $render_individual_columns ? '1' : '';
		$loop_output_settings['include_field_names'] = $include_field_names ? '1' : '';

		$loop_output_settings['list_separator'] = $args['list_separator'];

		/**
		 * The 'fields' element is originally constructed in wpv_layout_wizard_convert_settings() with a comment
		 * saying just "Compatibility".
		 *
		 * TODO it would be nice to explain why is this needed (compatibility with what?).
		 */
		$fields_compatible = array();
		$field_index = 0;
		foreach ( $fields as $field ) {
			$fields_compatible[ 'prefix_' . $field_index ] = '';

			$shortcode = stripslashes( $field['shortcode'] );

			if ( preg_match( '/\[types.*?field=\"(.*?)\"/', $shortcode, $matched ) ) {
				$fields_compatible[ 'name_' . $field_index ] = 'types-field';
				$fields_compatible[ 'types_field_name_' . $field_index ] = $matched[1];
				$fields_compatible[ 'types_field_data_' . $field_index ] = $shortcode;
			} else {
				$fields_compatible[ 'name_' . $field_index ] = trim( $shortcode, '[]' );
				$fields_compatible[ 'types_field_name_' . $field_index ] = '';
				$fields_compatible[ 'types_field_data_' . $field_index ] = '';
			}

			$fields_compatible[ 'row_title_' . $field_index ] = $field['field_name'];
			$fields_compatible[ 'suffix_' . $field_index ] = '';

			++$field_index;
		}
		$loop_output_settings['fields'] = $fields_compatible;

		// 'real_fields' will be an array of field shortcodes
		$field_shortcodes = array();
		foreach ( $fields as $field ) {
			$field_shortcodes[] = stripslashes( $field['shortcode'] );
		}
		$loop_output_settings['real_fields'] = $field_shortcodes;

		// we'll be returning layout settings and content of a CT (optionally).
		$result = array(
			'loop_output_settings' => $loop_output_settings,
			'ct_content' => $loop_output['ct_content'],
		);

		return $result;
	}

	/**
	 * Generate Table-based grid View layout.
	 *
	 * @see generate_view_loop_output()
	 *
	 * @param array $fields Array of fields to be used inside this layout.
	 * @param array $args Additional arguments.
	 *
	 * @return array(
	 *     @type string $loop_template Loop code.
	 *     @type string $ct_content Content of the Content Template or an empty string if it's not being used.
	 * )
	 *
	 * @since 1.10
	 * @since 2.6.4 The initial method that existed under the "WPV_Base_Class" class was deprecated and it was
	 *              replaced by this method inside a non static class. The "WPV_Base_Class::generate_table_layout" became
	 *              a wrapper that creates an instance of \OTGS\Toolset\Views\ViewLoopOutputGenerator and calls this
	 *              method.
	 */
	public function generate_table_layout( $fields, $args ) {

		$indent = $args['use_loop_template'] ? "" : "\t\t\t\t";
		$field_codes = $this->generate_field_codes( $fields, $indent );

		if ( $args['use_loop_template'] ) {
			$ct_content = $field_codes;
			$loop_template_body = "\t\t\t\t[wpv-post-body view_template=\"{$args['loop_template_name']}\"]";
		} else {
			$ct_content = '';
			$loop_template_body = $field_codes;
		}

		$cols = $args['tab_column_count'];

		$loop_template =
			"\t<table width=\"100%\" class=\"wpv-loop js-wpv-loop\">\n\t<wpv-loop wrap=\"$cols\" pad=\"true\">\n"
			. "\t\t[wpv-item index=1]\n"
			. "\t\t<tr>\n\t\t\t<td>\n$loop_template_body\n\t\t\t</td>\n"
			. "\t\t[wpv-item index=other]\n"
			. "\t\t\t<td>\n$loop_template_body\n\t\t\t</td>\n"
			. "\t\t[wpv-item index=$cols]\n"
			. "\t\t\t<td>\n$loop_template_body\n\t\t\t</td>\n\t\t</tr>\n"
			. "\t\t[wpv-item index=pad]\n"
			. "\t\t\t<td></td>\n"
			. "\t\t[wpv-item index=pad-last]\n"
			. "\t\t\t<td></td>\n\t\t</tr>\n"
			. "\t</wpv-loop>\n\t</table>\n\t";

		return array(
			'loop_template' => $loop_template,
			'ct_content' => $ct_content,
		);
	}


	/**
	 * Generate Bootstrap grid View layout.
	 *
	 * @see generate_view_loop_output()
	 *
	 * @param array $fields Array of fields to be used inside this layout.
	 * @param array $args Additional arguments (expected: bootstrap_column_count, bootstrap_version, add_container,
	 *     add_row_class, render_individual_columns).
	 *
	 * @return null|array Null on error (missing bootstrap version), otherwise the array:
	 *     array (
	 *         @type string $loop_template Loop code.
	 *         @type string $ct_content Content of the Content Template or an empty string if it's not being used.
	 *     )
	 *
	 * @since 1.10
	 * @since 2.6.4 The initial method that existed under the "WPV_Base_Class" class was deprecated and it was
	 *              replaced by this method inside a non static class. The "WPV_Base_Class::generate_bootstrap_grid_layout" became
	 *              a wrapper that creates an instance of \OTGS\Toolset\Views\ViewLoopOutputGenerator and calls this
	 *              method.
	 */
	public function generate_bootstrap_grid_layout( $fields, $args ) {

		$column_count = $args['bootstrap_column_count'];

		// Fail if we don't have valid bootstrap version.
		$bootstrap_version = (int) toolset_getarr( $args, 'bootstrap_version', 'undefined', array( 2, 3, 4 ) );
		if ( 'undefined' === $bootstrap_version ) {
			return null;
		}

		$indent = $args['use_loop_template'] ? "" : "\t\t\t\t";
		$field_codes = $this->generate_field_codes( $fields, $indent );

		// Prevent division by zero.
		if ( $column_count < 1 ) {
			return null;
		}

		$column_offset = 12 / $column_count;

		$output = '';

		// Row style and cols classes depending on bootstrap version.
		$row_style = self::BOOTSTRAP_ROW_CLASS[ $bootstrap_version ];
		$col_class = self::BOOTSTRAP_COL_CLASS[ $bootstrap_version ] . $column_offset;

		// Add row class (optional for bootstrap 2, mandatory otherwise).
		$row_class = ( '1' === $args['add_row_class'] || 2 !== $bootstrap_version ? 'row' : '' );

		if ( $args['use_loop_template'] ) {
			$ct_content = $field_codes;
			$loop_item = "<div class=\"$col_class\">[wpv-post-body view_template=\"{$args['loop_template_name']}\"]</div>";
		} else {
			$ct_content = '';
			$loop_item = "<div class=\"$col_class\">\n$field_codes\n\t\t\t</div>";
		}

		if ( $args['add_container'] ) {
			$output .= "\t<div class=\"container wpv-loop js-wpv-loop\">\n";
		}

		$output .= "\t<wpv-loop wrap=\"{$column_count}\" pad=\"true\">\n";

		// If the first column is also a last column, close the div tag.
		$ifone = ( 1 === intval( $column_count ) ) ? "\n\t\t</div>" : '';

		if ( $args['render_individual_columns'] ) {
			// Render items for each column.
			$output .=
				"\t\t[wpv-item index=1]\n"
				. "\t\t<div class=\"{$row_class} {$row_style}\">\n"
				. "\t\t\t$loop_item$ifone\n";
			for ( $i = 2; $i < $column_count; ++$i ) {
				$output .=
					"\t\t[wpv-item index=$i]\n" .
					"\t\t\t$loop_item\n";
			}
		} else {
			// Render compact HTML.
			$output .=
				"\t\t[wpv-item index=1]\n"
				. "\t\t<div class=\"{$row_class} {$row_style}\">\n"
				. "\t\t\t$loop_item$ifone\n"
				. "\t\t[wpv-item index=other]\n"
				. "\t\t\t$loop_item\n";
		}

		// Render item for last column.
		if ( $column_count > 1 ) {
			$output .=
				"\t\t[wpv-item index=$column_count]\n"
				. "\t\t\t$loop_item\n"
				. "\t\t</div>\n";
		}

		// Padding items.
		$output .=
			"\t\t[wpv-item index=pad]\n"
			. "\t\t\t<div class=\"{$col_class}\"></div>\n"
			. "\t\t[wpv-item index=pad-last]\n"
			. "\t\t\t<div class=\"{$col_class}\"></div>\n"
			. "\t\t</div>\n"
			. "\t</wpv-loop>\n\t";

		if ( $args['add_container'] ) {
			$output .= "</div>\n\t";
		}

		return array(
			'loop_template' => $output,
			'ct_content' => $ct_content,
		);
	}

	/**
	 * Generate Table View layout.
	 *
	 * @see generate_view_loop_output()
	 *
	 * @param array $fields Array of fields to be used inside this layout.
	 * @param array $args Additional arguments.
	 *
	 * @return array(
	 *     @type string $loop_template Loop code.
	 *     @type string $ct_content Content of the Content Template or an empty string if it's not being used.
	 * )
	 *
	 * @since 1.10
	 * @since 2.6.4 The initial method that existed under the "WPV_Base_Class" class was deprecated and it was
	 *              replaced by this method inside a non static class. The "WPV_Base_Class::generate_table_of_fields_layout" became
	 *              a wrapper that creates an instance of \OTGS\Toolset\Views\ViewLoopOutputGenerator and calls this
	 *              method.
	 */
	public function generate_table_of_fields_layout( $fields, $args = array() ) {

		// Optionally render table header with field names.
		$thead = '';
		if ( $args['include_field_names'] ) {
			$thead = "\t\t<thead>\n\t\t\t<tr>\n";
			foreach ( $fields as $field ) {
				$thead .= "\t\t\t\t<th>[wpv-heading name=\"{$field['header_name']}\"]{$field['row_title']}[/wpv-heading]</th>\n";
			}
			$thead .= "\t\t\t</tr>\n\t\t</thead>\n";
		}

		// Table body.
		$indent = $args['use_loop_template'] ? "" : "\t\t\t\t";
		$field_codes = $this->generate_field_codes( $fields, $indent . '<td>', '</td>' );

		if ( $args['use_loop_template'] ) {
			$ct_content = $field_codes;
			$loop_template_body = "\t\t\t\t[wpv-post-body view_template=\"{$args['loop_template_name']}\"]";
		} else {
			$ct_content = '';
			$loop_template_body = $field_codes;
		}

		// Put it all together.
		$loop_template =
			"\t<table width=\"100%\">\n"
			. $thead
			. "\t\t<tbody class=\"wpv-loop js-wpv-loop\">\n"
			. "\t\t<wpv-loop>\n"
			. "\t\t\t<tr>\n"
			. $loop_template_body . "\n"
			. "\t\t\t</tr>\n"
			. "\t\t</wpv-loop>\n\t\t</tbody>\n\t</table>\n\t";

		return array(
			'loop_template' => $loop_template,
			'ct_content' => $ct_content,
		);
	}

	/**
	 * Generate List View layout.
	 *
	 * @see generate_view_loop_output()
	 *
	 * @param array  $fields Array of fields to be used inside this layout.
	 * @param array  $args Additional arguments.
	 * @param string $list_type Type of the list. Can be 'ul' for unordered list or 'ol' for ordered list. Defaults to 'ul'.
	 *
	 * @return array(
	 *     @type string $loop_template Loop code.
	 *     @type string $ct_content Content of the Content Template or an empty string if it's not being used.
	 * )
	 *
	 * @since 1.10
	 * @since 2.6.4 The initial method that existed under the "WPV_Base_Class" class was deprecated and it was
	 *              replaced by this method inside a non static class. The "WPV_Base_Class::generate_list_layout" became
	 *              a wrapper that creates an instance of \OTGS\Toolset\Views\ViewLoopOutputGenerator and calls this
	 *              method.
	 */
	public function generate_list_layout( $fields, $args, $list_type = 'ul' ) {

		$indent = $args['use_loop_template'] ? "" : "\t\t\t\t";
		$field_codes = $this->generate_field_codes( $fields, $indent );
		$list_type = ( 'ol' === $list_type ) ? 'ol' : 'ul';

		if ( $args['use_loop_template'] ) {
			$ct_content = $field_codes;
			$loop_template_body = "\t\t\t<li>[wpv-post-body view_template=\"{$args['loop_template_name']}\"]</li>";
		} else {
			$ct_content = '';
			$loop_template_body = "\t\t\t<li>\n$field_codes\n\t\t\t</li>";
		}

		$loop_template =
			"\t<$list_type class=\"wpv-loop js-wpv-loop\">\n"
			. "\t\t<wpv-loop>\n"
			. $loop_template_body . "\n"
			. "\t\t</wpv-loop>\n"
			. "\t</$list_type>\n\t";

		return array(
			'loop_template' => $loop_template,
			'ct_content' => $ct_content,
		);
	}

	/**
	 * Generate List with separators.
	 *
	 * @see generate_view_loop_output()
	 *
	 * @param array $fields Array of fields to be used inside this layout.
	 * @param array $args Additional arguments.
	 *
	 * @return array(
	 *     @type string $loop_template Loop code.
	 *     @type string $ct_content Content of the Content Template or an empty string if it's not being used.
	 * )
	 *
	 * @since 2.6.4
	 */
	public function generate_separators_list( $fields, $args ) {

		$indent = "\t";

		if ( count( $fields ) > 1 ) {
			$fields = array( $fields[0] );
		}

		$field_codes = $this->generate_field_codes( $fields, $indent );

		$separator = $args['list_separator'];

		$loop_template_body = "$field_codes";

		$ct_content = '';

		$loop_template = "\t\t<wpv-loop>\n"
						 . "\t\t\t[wpv-item index=other]\n"
						 . "\t\t\t" . $loop_template_body . $separator . "\n"
						 . "\t\t\t[wpv-item index=last]\n"
						 . "\t\t\t" . $loop_template_body . "\n"
						 . "\t\t</wpv-loop>\n\t";

		return array(
			'loop_template' => $loop_template,
			'ct_content' => $ct_content,
		);
	}

	/**
	 * Generate unformatted View layout.
	 *
	 * @see generate_view_loop_output()
	 *
	 * @param array $fields Array of fields to be used inside this layout.
	 * @param array $args Additional arguments.
	 *
	 * @return array(
	 *     @type string $loop_template Loop code.
	 *     @type string $ct_content Content of the Content Template or an empty string if it's not being used.
	 * )
	 *
	 * @since 1.10
	 * @since 2.6.4 The initial method that existed under the "WPV_Base_Class" class was deprecated and it was
	 *              replaced by this method inside a non static class. The "WPV_Base_Class::generate_unformatted_layout" became
	 *              a wrapper that creates an instance of \OTGS\Toolset\Views\ViewLoopOutputGenerator and calls this
	 *              method.
	 */
	public function generate_unformatted_layout( $fields, $args ) {

		$indent = $args['use_loop_template'] ? "" : "\t\t";

		$field_codes = $this->generate_field_codes( $fields, $indent );

		if ( $args['use_loop_template'] ) {
			$ct_content = $field_codes;
			$loop_template_body = "\t\t[wpv-post-body view_template=\"{$args['loop_template_name']}\"]";
		} else {
			$ct_content = '';
			$loop_template_body = $field_codes;
		}

		$loop_template = "\t<wpv-loop>\n" . $loop_template_body . "\n\t</wpv-loop>\n\t";

		return array(
			'loop_template' => $loop_template,
			'ct_content' => $ct_content,
		);
	}

	/**
	 * Helper rendering function. Renders shortcodes for fields with all required prefixes and suffixes.
	 *
	 * Each field is rendered on a new line.
	 *
	 * @param array  $fields The array of definitions of fields. See generate_view_loop_output() for details.
	 * @param string $row_prefix Additional prefix for the field shortcode.
	 * @param string $row_suffix Additional suffix for the field shortcode.
	 *
	 * @return string The shortcodes for all given fields.
	 *
	 * @since 1.10
	 * @since 2.6.4 The initial method that existed under the "WPV_Base_Class" class was deprecated and it was
	 *              replaced by this method inside a non static class. The "WPV_Base_Class::generate_field_codes" became
	 *              a wrapper that creates an instance of \OTGS\Toolset\Views\ViewLoopOutputGenerator and calls this
	 *              method.
	 */
	public function generate_field_codes( $fields, $row_prefix = '', $row_suffix = '' ) {
		$field_codes = array();
		foreach ( $fields as $field ) {
			$field_codes[] = $row_prefix . $field['prefix'] . $field['shortcode'] . $field['suffix'] . $row_suffix;
		}
		return implode( "\n", $field_codes );
	}
}
