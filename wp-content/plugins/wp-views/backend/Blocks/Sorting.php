<?php

namespace OTGS\Toolset\Views\Blocks;

class Sorting {

	public function initialize() {
		/*
		 * Register render callback which will be used for view rendering
		 * using template from the Gutenberg "modern" mode
		 */
		register_block_type( 'toolset-views/sorting', array(
			'render_callback' => array( $this, 'render_callback' ),
		) );
	}


	/**
	 * Callback to render the view editor block as view shortcode ignoring Gutenberg output
	 *
	 * @param $attributes
	 * @param $content
	 *
	 * @return string
	 */
	public function render_callback( $attributes, $content ) {
		$attributes['showAs'] = isset( $attributes['showAs'] ) ? $attributes['showAs'] : 'select';
		$attributes['forceApply'] = isset( $attributes['forceApply'] ) ? $attributes['forceApply'] : 'reload';
		$attributes['includeLabel'] = isset( $attributes['includeLabel'] ) ? $attributes['includeLabel'] : true;
		$attributes['labelAlign'] = isset( $attributes['labelAlign'] ) ? $attributes['labelAlign'] : 'before';
		$attributes['contentAlign'] = isset( $attributes['contentAlign'] ) ? $attributes['contentAlign'] : 'left';
		$attributes['modifyLabels'] = isset( $attributes['modifyLabels'] ) ? $attributes['modifyLabels'] : false;
		$attributes['label'] = isset( $attributes['label'] ) ? $attributes['label'] : 'Sort by';
		$attributes['builtinListStyle'] = isset( $attributes['builtinListStyle'] ) ? $attributes['builtinListStyle']
			: 'default';

		$shortcode1 = $this->render_shortcode_sort_order_by( $attributes );
		$shortcode2 = $this->render_shortcode_sort_direction( $attributes );

		// Get user id and user css classes.
		$style = isset( $attributes['style'] ) ? $attributes['style'] : [];
		$user_id = isset( $style['id'] ) ? ' id="' . $style['id'] . '"' : '';
		$user_classes = isset( $style['cssClasses'] ) ? ' ' . implode( ' ', $style['cssClasses'] ) : '';

		$html = '<div'
			. $user_id
			. ' class="wpv-sorting-block wpv-sorting-block-label-'
			. $attributes['labelAlign']
			. ' wpv-sorting-block-align-'
			. $attributes['contentAlign']
			. $user_classes
			. '" data-toolset-views-sorting="'
			. ( ! empty( $attributes['blockId'] ) ? $attributes['blockId'] : '1' )
			. '">';
		if ( $attributes['includeLabel'] ) {
			$html .= '<div class="wpv-sorting-block-item wpv-sorting-block-label">';
			$html .= $attributes['label'];
			$html .= '</div>';
		}
		$html .= '<div class="wpv-sorting-block-item wpv-sorting-block-orderby">';
		$html .= $shortcode1;
		$html .= '</div>';
		if ( $attributes['modifyLabels'] ) {
			$html .= '<div class="wpv-sorting-block-item wpv-sorting-block-order">' . $shortcode2 . '</div>';
		}
		$html .= '</div>';

		return $html;
	}


	/**
	 * Render sort direction shortcode for sorting block.
	 *
	 * @param $attributes Block attributes.
	 *
	 * @return string
	 */
	protected function render_shortcode_sort_direction( $attributes ) {
		$parts = array(
			'[wpv-sort-order type="' . $attributes['showAs'] . '" options="asc,desc"',
		);

		$parts[] = 'label_for_asc="' . __( 'Ascending', 'wpv-views' ) . '"';
		$parts[] = 'label_for_desc="' . __( 'Descending', 'wpv-views' ) . '"';
		if ( ! empty( $attributes['fields'] ) ) {
			foreach ( $attributes['fields'] as $key => $field ) {
				$parts[] = 'label_asc_for_' . $field['value'] . '="' . $field['asc_label'] . '"';
				$parts[] = 'label_desc_for_' . $field['value'] . '="' . $field['desc_label'] . '"';
			}
		}

		if ( 'list' === $attributes['showAs'] ) {
			$parts[] = 'list_style="' . $attributes['builtinListStyle'] . '"';
		}
		$parts[] = 'select_style="' . ( empty( $attributes['orderDirectionSelectCSS'] ) ? ''
				: $attributes['orderDirectionSelectCSS'] ) . '"';
		$parts[] = 'force_apply="' . $attributes['forceApply'] . '"';
		$parts[] = ']';

		return implode( ' ', $parts );
	}


	/**
	 * Render sort order by shortcode for sorting block.
	 *
	 * @param $attributes Block attributes.
	 *
	 * @return string
	 */
	protected function render_shortcode_sort_order_by( $attributes ) {
		$parts = array(
			'[wpv-sort-orderby',
		);
		$parts[] = 'type="' . $attributes['showAs'] . '"';

		$options = array();
		$asc = array();
		$desc = array();
		$as_numeric = array();
		if ( ! empty( $attributes['fields'] ) ) {
			foreach ( $attributes['fields'] as $key => $field ) {
				$options[] = $field['value'];
				$parts[] = 'label_for_' . $field['value'] . '="' . toolset_getarr( $field, 'flabel', '' ) . '"';
				$field['default_direction'] = empty( $field['default_direction'] ) ? 'ASC'
					: $field['default_direction'];
				if ( 'DESC' === $field['default_direction'] ) {
					$desc[] = $field['value'];
				} else {
					$asc[] = $field['value'];
				}

				if ( isset( $field['sort_as'] ) && 'NUMERIC' === $field['sort_as'] ) {
					$as_numeric[] = $field['value'];
				}
			}
		}
		$parts[] = 'options="' . implode( ',', $options ) . '"';

		if ( count( $desc ) > 0 ) {
			$parts[] = 'orderby_descending_for="' . implode( ',', $desc ) . '"';
		}
		if ( count( $asc ) > 0 ) {
			$parts[] = 'orderby_ascending_for="' . implode( ',', $asc ) . '"';
		}
		if ( count( $as_numeric ) > 0 ) {
			$parts[] = 'orderby_as_numeric_for="' . implode( ',', $as_numeric ) . '"';
		}
		if ( 'list' === $attributes['showAs'] ) {
			$parts[] = 'list_style="' . $attributes['builtinListStyle'] . '"';
		}
		$parts[] = 'select_style="' . ( empty( $attributes['orderbySelectCSS'] ) ? ''
				: $attributes['orderbySelectCSS'] ) . '"';
		$parts[] = 'force_apply="' . $attributes['forceApply'] . '"';
		$parts[] = ']';

		return implode( ' ', $parts );
	}
}
