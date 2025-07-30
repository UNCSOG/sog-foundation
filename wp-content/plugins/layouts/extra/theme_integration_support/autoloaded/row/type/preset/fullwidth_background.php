<?php


abstract class WPDDL_Row_Type_Preset_Fullwidth_Background
	extends WPDDL_Row_Type_Abstract {

	protected function setup() {
		$this->image = WPDDL_GUI_RELPATH . 'dialogs/img/tn-full-fixed.png';

		parent::setup();
	}

	public function htmlOpen( $markup, $args, $row = null, $renderer = null ) {

		if( $args['mode'] === $this->id ) {

			$el_css = 'full-bg';

			$css_classes = $this->getCssClasses();

			$el_css .= ! empty( $css_classes )
				? ' ' . implode( ' ', $css_classes )
				: '';

			$el_css .= isset( $args['additionalCssClasses'] )
				? ' '.$args['additionalCssClasses']
				: '';

			$el_id = isset( $args['cssId'] ) && ! empty( $args['cssId'] )
				? ' id="' . $args['cssId'] . '"'
				: '';

			ob_start();
			echo '<' . $args['tag'] . $el_id . ' class="' . $el_css . '" '.$this->renderDataAttributes($row, $renderer).'>';
			echo '<div class="' . $args['container_class'] . '">';
			echo '<div class="' . $args['row_class'] . $args['type'] . '">';

			$markup = ob_get_clean();
		}

		return $markup;
	}

	public function htmlClose( $output, $mode, $tag ) {

		if( $mode === $this->id ) {
			$output = '</div></div></' . $tag . '>';
		}

		return $output;
	}
}
