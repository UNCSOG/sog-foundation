<?php

/**
 * Class WPV_Shortcode_Post_Previous_Link
 *
 * @since 2.5.0
 */
class WPV_Shortcode_Post_Navigation_Link extends WPV_Shortcode_Base {
	/**
	 * @var string|null
	 */
	protected $user_content;

	/**
	 * @var array
	 */
	protected $user_atts;


	/**
	 * @var Toolset_Shortcode_Attr_Interface
	 */
	protected $item;

	/**
	 * WPV_Shortcode_Post_Previous_Link constructor.
	 *
	 * @param Toolset_Shortcode_Attr_Interface $item
	 */
	public function __construct(
		Toolset_Shortcode_Attr_Interface $item
	) {
		$this->item  = $item;
	}

	/**
	 * Fetches the post navigation link shortcode attribute translation deciding between either the legacy or the new context format.
	 *
	 * @param $name
	 * @param $value
	 * @param $context
	 *
	 * @return string
	 */
	protected function get_attribute_translation( $name, $value, $context ) {
		$name = $name . '_' . md5( $value );

		$legacy_attribute = $this->get_translation( $name, $value, null, true );

		$attribute = $legacy_attribute !== $value ?
			$legacy_attribute :
			$this->get_translation( $name, $value, $context );

		return $attribute;
	}

	public function get_value( $atts, $content ) {}
}