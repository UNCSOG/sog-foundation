<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\Factory;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Application\RegisterStringsToPackageService;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Common\Domain\TranslationService;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes\Attributes;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes\CommaSeparated;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Attributes\AttributeProperties;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Shortcode;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\Shortcodes;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\ShortcodeSlug;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\WpvControl;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Domain\WpvControlPostmeta;

/**
 * Class ShortcodesFactory
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\Factory
 * @codeCoverageIgnore No need to test this factory.
 *
 * @since TB 1.3
 */
class ShortcodesFactory {
	/** @var TranslationService */
	private $translation_service;

	/**
	 * ShortcodesFactory constructor.
	 *
	 * @param TranslationService $translation_service
	 */
	public function __construct( TranslationService $translation_service ) {
		$this->translation_service = $translation_service;
	}

	/**
	 * @return Shortcodes
	 */
	public function get_shortcodes() {
		$shortcodes = new Shortcodes( $this->translation_service );
		$this->add_wpv_control_and_wpv_control_postmeta( $shortcodes );

		// Return shortcodes.
		return $shortcodes;
	}

	private function add_wpv_control_and_wpv_control_postmeta( Shortcodes $shortcodes ) {
		$attr_display_values = new CommaSeparated(
			new AttributeProperties( 'display_values', __( 'Field Display Value', 'wpv-views' ) ),
			$this->translation_service
		);

		// Shortcode wpv-control.
		$sc_wpv_control_attributes = new Attributes();
		$sc_wpv_control_attributes->add( $attr_display_values );
		$sc_wpv_control = new Shortcode(
			new ShortcodeSlug( 'wpv-control' ),
			$sc_wpv_control_attributes
		);

		// Shortcode wpv-control-postmeta.
		$sc_wpv_control_postmeta_attributes = new Attributes();
		$sc_wpv_control_postmeta_attributes->add( $attr_display_values );
		$sc_wpv_control_postmeta = new Shortcode(
			new ShortcodeSlug( 'wpv-control-postmeta' ),
			$sc_wpv_control_postmeta_attributes
		);

		// Add shortcodes.
		$shortcodes->add( $sc_wpv_control );
		$shortcodes->add( $sc_wpv_control_postmeta );
	}
}
