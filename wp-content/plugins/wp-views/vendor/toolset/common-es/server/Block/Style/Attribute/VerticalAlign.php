<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

/**
 * Class VerticalAlign
 * @package ToolsetCommonEs\Block\Style\Attribute
 */
class VerticalAlign extends AAttribute {
	private $valign;

	public function __construct( $valign ) {
		$this->valign = $valign;
	}

	public function get_name() {
		return 'vertical-align';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		switch( $this->valign ) {
			case 'middle':
				return $this->get_css_flex_column() . '-ms-flex-pack:center;justify-content:center;';
			case 'bottom':
				return $this->get_css_flex_column() . '-ms-flex-pack:end;justify-content:flex-end;';
			default:
				// 'top' or invalid value.
				return '';
		}
	}

	private function get_css_flex_column() {
		// Added !important to force display flex for Container used as link (tb-1755).
		return 'display:ms-flexbox !important;display:flex !important;-ms-flex-direction:column;flex-direction:column;';
	}
}
