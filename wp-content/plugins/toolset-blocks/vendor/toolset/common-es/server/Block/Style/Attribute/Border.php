<?php
namespace ToolsetCommonEs\Block\Style\Attribute;

class Border extends AAttribute {
	/** @var BorderSide  */
	private $top;
	/** @var BorderSide  */
	private $right;
	/** @var BorderSide  */
	private $bottom;
	/** @var BorderSide  */
	private $left;

	/**
	 * Border constructor.
	 *
	 * @param BorderSide $top
	 * @param BorderSide $right
	 * @param BorderSide $bottom
	 * @param BorderSide $left
	 */
	public function __construct( BorderSide $top, BorderSide $right, BorderSide $bottom, BorderSide $left ) {
		$this->top = $top;
		$this->right = $right;
		$this->bottom = $bottom;
		$this->left = $left;
	}

	public function get_name() {
		return 'border';
	}

	/**
	 * @return string
	 */
	public function get_css() {
		$top_value = $this->top->get_css( true );
		$right_value = $this->right->get_css( true );
		$bottom_value = $this->bottom->get_css( true );
		$left_value = $this->left->get_css( true );

		if( empty( $top_value ) &&
		    empty( $right_value ) &&
		    empty( $bottom_value ) &&
		    empty( $left_value )
		) {
			// no border
			return '';
		}

		if( $top_value === $right_value &&
			$right_value === $bottom_value &&
			$bottom_value === $left_value
		) {
			// all borders have the same value
			return 'border: ' . $top_value . ';';
		}

		// each side is different
		return $this->top->get_css() . $this->right->get_css() . $this->bottom->get_css() . $this->left->get_css();
	}
}
