<?php
/*
 * Routes used by Views.
 *
 * This should be the only file which loads the dependency injections container.
 */
namespace OTGS\Toolset\Views;

use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\BlockContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\SearchPosition\Factory as FactorySearchPosition;
use OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener\ThePost;
use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener\WpvFilterForceWordpressArchive;
use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener\WpvPostContent;
use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener\WpvViewSettings;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener\WPMLFoundStringsInBlock;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener\WPMLUpdateStringsInBlock;
use OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\Repository\WordPressRepository;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\EventListener\WPMLPBRegisterAllStringsForTranslation;
use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\EventListener\WPMLTranslationJobSaved;

// Autoload Models
require_once __DIR__ . '/psr4-application-models.php';

add_action( 'init', function() {
	$dic = apply_filters( 'toolset_common_es_dic', false );

	// Common ES Blocks Styles - Add Block Factory for blocks of Views.
	add_filter( 'toolset_common_es_block_factories', function( $block_factories ) use ( $dic ) {
		if( $block_factory = $dic->make( 'OTGS\Toolset\Views\Models\Block\Style\Block\Factory' ) ) {
			$block_factories[] = $block_factory;
		}
		return $block_factories;
	}, 10, 1 );

	/**
	 * WPML register strings to translation package.
	 */
	/* @var WPMLFoundStringsInBlock $event_listener_wpml_found_strings_in_block */
	$event_listener_wpml_found_strings_in_block = $dic->make(
		'OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener\WPMLFoundStringsInBlock'
	);

	$event_listener_wpml_found_strings_in_block->start_listen();

	/* @var WPMLUpdateStringsInBlock $event_listener_wpml_update_strings_in_block */
	$event_listener_wpml_update_strings_in_block = $dic->make(
		'OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Infrastructure\EventListener\WPMLUpdateStringsInBlock'
	);
	$event_listener_wpml_update_strings_in_block->start_listen();

	/* @var WPMLPBRegisterAllStringsForTranslation $event_listener_wpml_tm_translation_job_data */
	$event_listener_wpml_pb_register_all_strings_for_translation = $dic->make(
		'OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\EventListener\WPMLPBRegisterAllStringsForTranslation'
	);
	$event_listener_wpml_pb_register_all_strings_for_translation->start_listen();

	/* @var WPMLTranslationJobSaved $event_listener_wpml_tm_translation_job_data */
	$event_listener_wpml_translation_job_saved = $dic->make(
		'OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Shortcode\Infrastructure\EventListener\WPMLTranslationJobSaved'
	);
	$event_listener_wpml_translation_job_saved->start_listen();

	// WPML Frontend Translation apply for views / content templates
	$is_frontend_call = ! is_admin();
	$is_ajax_call = wp_doing_ajax();

	if ( $is_frontend_call || $is_ajax_call ) {
		/** @var ThePost $view_translation_apply */
		$view_translation_apply = $dic->make(
			'OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener\ThePost'
		);
		$view_translation_apply->start_listen( $is_frontend_call, $is_ajax_call );
	}

	// WPML Frontend Translation apply for WPAs
	if( $is_frontend_call ) {
		// Share the WPA repository as we need to collect data over different hooks.
		$repository_wpa = new WordPressRepository( new BlockContent( new FactorySearchPosition() ) );
		$dic->share( $repository_wpa );

		// Register WPA.
		/** @var WpvFilterForceWordpressArchive $wpa_register_for_translations */
		$wpa_register_for_translations = $dic->make(
			'OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener\WpvFilterForceWordpressArchive'
		);
		$wpa_register_for_translations->start_listen();

		// Translate WPA settings.
		/** @var WpvViewSettings $wpa_translate_settings */
		$wpa_translate_settings = $dic->make(
			'OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener\WpvViewSettings'
		);

		$wpa_translate_settings->start_listen();

		// Translate WPA content before and after the loop.
		/** @var WpvPostContent $wpa_translate_content_before_and_after_the_loop */
		$wpa_translate_content_before_and_after_the_loop = $dic->make(
			'OTGS\Toolset\Views\Models\Translation\Frontend\WPA\Infrastructure\EventListener\WpvPostContent'
		);

		$wpa_translate_content_before_and_after_the_loop->start_listen();
	}
}, 1 );
