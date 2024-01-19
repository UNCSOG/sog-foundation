<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Order extends AAttribute {
	private $order;

	public function __construct( $value ) {
		$this->order = $value;
	}

	public function get_name() {
		return 'order';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		if ( empty( $this->order ) ) {
			return '';
		}

		return 'order: ' . $this->order;
	}
}
