<?php

namespace OTGS\Toolset\Views\Services;

use \OTGS\Toolset\Views\Models\Translation\Frontend\Common\Domain\BlockContent as CommonBlockContent;

class WPMLService {

	/** @var CommonBlockContent */
	private $common;

	/**
	 * WPMLService constructor.
	 *
	 * @param CommonBlockContent $common Part of the new translation mechanism.
	 */
	public function __construct( CommonBlockContent $common ) {
		$this->common = $common;
	}

	public function init() {
		add_filter( 'wpv_filter_override_view_layout_settings', array( $this, 'adapt_settings_for_translation' ), 10, 2 );
		add_filter( 'wpv_filter_localize_view_block_strings', array( $this, 'add_if_is_translated_content' ) );
	}

	/**
	 * Modifies View settings if WPML is installed, but only for Views created as blocks, in the frontend.
	 *
	 * @param array $settings View settings.
	 * @param int   $id View/WPA ID.
	 * @return array
	 */
	public function adapt_settings_for_translation( $settings, $id ) {
		// If WPML is active.
		$wpml_active_and_configured = apply_filters( 'wpml_setting', false, 'setup_complete' );

		// is_admin() is always true for ajax calls. Also Check if it's a pagination ajax call.
		$is_ajax_pagination = wp_doing_ajax() &&
							  array_key_exists( 'action', $_REQUEST ) &&
							in_array(
								$_REQUEST['action'],
								[ 'wpv_get_view_query_results', 'wpv_get_archive_query_results' ],
								true
							);
		$is_frontend_call_or_ajax_call = ! is_admin() || $is_ajax_pagination;

		if ( $is_frontend_call_or_ajax_call && $wpml_active_and_configured && ! \WPV_View_Base::is_archive_view( $id ) && isset( $settings['layout_meta_html'] ) ) {
			$view_data = get_post_meta( $id, '_wpv_view_data', true );
			if ( empty( $view_data ) ) {
				// This View does not hold proper block data:
				// it was probably created with the legacy editor.
				return $settings;
			}

			$helper_id = toolset_getnest( $view_data, array( 'general', 'initial_parent_post_id' ), 0 );
			if ( 0 === $helper_id ) {
				// This View does not hold proper block data:
				// it was probably created as a duplicate for one created with the legacy editor.
				return $settings;
			}

			$post = \WP_Post::get_instance( $helper_id );
			if ( ! $post ) {
				// Maybe the initial post where the View was created has been removed.
				return $settings;
			}

			$translated_helper_id = apply_filters( 'wpml_object_id', $helper_id, $post->post_type, true );
			if ( $helper_id !== $translated_helper_id ) {
				// This holds the actual View ID.
				// TODO why is this needed if we get it from the method args?
				$id = toolset_getnest( $view_data, array( 'general', 'id' ), 0 );
				// if post is translated we need to extract the view markup
				// and replace content using it
				// we're extracting view markup because we can have more
				// than one view on a page
				$translated_helper = get_post( $translated_helper_id );
				$service = new ViewParsingService();
				$html = $service->get_view_markup( $translated_helper->ID, $id );
				$settings = $this->update_settings_from_html( $html, $settings );
			}
		}
		return $settings;
	}

	/**
	 * Parses through a View block and replaces those part of content that are translatable.
	 *
	 * @param string $html
	 * @param array  $settings
	 *
	 * @return array
	 */
	public function update_settings_from_html( $html, $settings ) {
		// Main content.
		$translated_loop = $this->common->get_content_between_search_positions(
			$html,
			$this->common->factory_search_position()
				->opening_block_tag( 'toolset-views/view-template-block' )
				->point_to_start(),
			$this->common->factory_search_position()
				->closing_block_tag( 'toolset-views/view-template-block' )
				->point_to_end()
		);

		if ( $translated_loop ) {
			$settings['layout_meta_html'] =
				$this->replace_loop( $settings['layout_meta_html'], do_blocks( $translated_loop ) );
		}

		// Main content. Tables.
		if( strpos( $translated_loop, 'views/table') !== false ) {
			$settings['layout_meta_html'] = preg_replace_callback(
				'#<!-- wpv-loop-start -->.*?<!-- wpv-loop-end -->#ism',
				function( $matches ) use ( $translated_loop ) {
					return $this->translate_table( $matches[0], $translated_loop );
				},
				$settings['layout_meta_html']
			);
		}

		// Top content.
		$translated_top_content = $this->common->get_content_between_search_positions(
			$html,
			$this->common->factory_search_position()
				->closing_block_tag( 'toolset-views/view-template-block' )
				->point_to_end(),
			$this->common->factory_search_position()
				->closing_block_tag( 'toolset-views/view-layout-block' )
				->point_to_start()
		);

		if ( $translated_top_content ) {
			$translated_top_content = $this->remove_orphan_closing_block( $translated_top_content );
			$settings['layout_meta_html'] = $this->replace_between(
				$settings['layout_meta_html'],
				do_blocks( $translated_top_content ),
				'[/wpv-no-items-found]',
				'[wpv-layout-end]'
			);
		}

		// Bottom content.
		$translated_bottom_content = $this->common->get_content_between_search_positions(
			$html,
			$this->common->factory_search_position()
				->opening_block_tag( 'toolset-views/view-layout-block' )
				->point_to_end(),
			$this->common->factory_search_position()
				->opening_block_tag( 'toolset-views/view-template-block' )
				->point_to_start()
		);

		if ( $translated_bottom_content ) {
			$translated_bottom_content = $this->remove_orphan_closing_block( $translated_bottom_content );
			$settings['layout_meta_html'] = $this->replace_between(
				$settings['layout_meta_html'],
				do_blocks( $translated_bottom_content ),
				'[wpv-layout-start]',
				'[wpv-items-found]'
			);
		}

		return $settings;
	}

	/**
	 * Stores in `toolset_view_block_strings` if it is translated content
	 *
	 * @param array $data Actual toolset_view_block_strings data.
	 * @return array
	 */
	public function add_if_is_translated_content( $data ) {
		global $post;
		if ( $post === null ) {
			return $data;
		}

		$default_language = apply_filters( 'wpml_default_language', null );
		$translated_id = apply_filters( 'wpml_object_id', $post->ID, $post->post_type, true, $default_language );
		$source_lang = toolset_getget( 'source_lang' );
		$lang = toolset_getget( 'lang' );
		$data['isTranslatedContent'] = $translated_id !== $post->ID || ( $source_lang && $lang && $source_lang !== $lang ) ? 1 : 0;
		return $data;
	}


	/**
	 * As the translated parts are picked by parts it can happen that a container starts in one part
	 * and closes in another. For do_blocks() the start is not a problem to handle, but if a closing
	 * block without the start of it is in a string the complete string is not parsed.
	 *
	 * Note: 'container' means any block which can contain other blocks, not only the TB Container block.
	 *
	 * @param string $content The content to filter.
	 *
	 * @return string
	 */
	private function remove_orphan_closing_block( $content ) {
		$open = '<!-- wp:';
		$close = '<!-- /wp:';

		$pos_open = strpos( $content, $open );
		$pos_close = strpos( $content, $close );

		// The loop is surrounded by a container. The container is parsed probably but in the
		// "after-loop" ($content) part the closing tag prevents the pagination block from being parsed
		// (or any other block put there).
		// In this simple case there is no container in the "after-loop" part.
		while( $pos_close < $pos_open ) {
			// Get the length of the closing tag. This varies by the block name.
			// First remove anything until the start of the $pos_close to make sure
			// this does not fail to any other comment containing '-->'.
			$content_without = substr( $content, $pos_close );
			$closing_block_signs = ' -->';
			$close_length = strpos( $content_without, $closing_block_signs ) + strlen( $closing_block_signs );

			// Remove the orphan.
			$content = substr_replace( $content, '', $pos_close, $close_length );

			// Check for more orphans.
			$pos_open = strpos( $content, $open );
			$pos_close = strpos( $content, $close );
		}

		return $content;
	}


	/**
	 * Translation of View with table layout.
	 *
	 * @param string $original_loop
	 * @param string $translated_loop
	 *
	 * @return mixed|string|string[]|null
	 */
	private function translate_table( $original_loop, $translated_loop ) {
		// Table Header.
		if ( preg_match(
			'#<!-- (?:wp:)?toolset-views/table-header-row.*?-->(.*?)<!-- /(?:wp:)?toolset-views\/table-header-row -->#ism',
			$translated_loop,
			$translated_table_header
		) ) {
			$original_loop = preg_replace(
				'#(<table.*?class=".*?view-table.*?>.*?<thead><tr>)(.*?)(<\/tr><\/thead>)#ism',
				'\1' . do_blocks( $translated_table_header[1] ) . '\3',
				$original_loop
			);
		}

		// Table Body.
		if ( preg_match(
			'#(<!-- (?:wp:)?toolset-views/table-row.*?-->(.*?)<!-- /(?:wp:)?toolset-views\/table-row -->)#ism',
			$translated_loop,
			$translated_table_body
		) ) {
			$original_loop = preg_replace(
				'#(<wpv-loop><tr.*?>)(.*?)(</tr></wpv-loop>)#ism',
				'\1' . do_blocks( $translated_table_body[1] ) . '\3',
				$original_loop
			);
		}

		// Table Footer.
		// -- There is no Table Footer option for the View table layout.

		return $original_loop;
	}


	/**
	 * Replace content with replacement between start and end needle.
	 *
	 * @param string $content
	 * @param string $replace_with
	 * @param string $start_needle
	 * @param string $end_needle
	 *
	 * @return string
	 */
	private function replace_between( $content, $replace_with, $start_needle, $end_needle ) {
		$start_pos = strpos( $content, $start_needle );
		$end_pos = strpos( $content, $end_needle );

		if ( false !== $start_pos && false !== $end_pos ) {
			$start_pos = $start_pos + strlen( $start_needle );
			$content_before_replace = substr( $content, 0, $start_pos );
			$content_after_replace = substr( $content, $end_pos );

			return $content_before_replace . $replace_with . $content_after_replace;
		}

		return $content;
	}


	/**
	 * Replace loop in content.
	 *
	 * @param string $content
	 * @param string $replace_with
	 *
	 * @return string
	 */
	private function replace_loop( $content, $replace_with ) {
		if ( ! $start_loop = strpos( $content, '<div class="wp-block-toolset-views-view-template-block' ) ) {
			return $content;
		}

		if ( ! $closing_loop_tag = strpos( $content, '</wpv-loop>' ) ) {
			return $content;
		}

		// Get only loop content.
		$loop_content = substr( $content, $start_loop, $closing_loop_tag - $start_loop );

		// Find last closing block followed by closing div.
		// With current setup options this can only occur and the while loop will end after one iteration.
		$find = ' --></div>';
		$find_length = strlen( $find );
		$end_loop = 0;
		while ( $pos = strpos( $loop_content, $find ) ) {
			$end_loop = $end_loop + $pos + $find_length;

			// Cut found part and try to find another. Looking for the last one before </wpv-loop>
			$loop_content = substr( $loop_content, $pos + $find_length );
		}

		if ( 0 === $end_loop ) {
			return $content;
		}

		$end_loop = $end_loop + $start_loop;

		// Build translated layout meta html.
		$content_before_translated_part = substr( $content, 0, $start_loop );
		$content_after_translated_part = substr( $content, $end_loop );
		return $content_before_translated_part . $replace_with . $content_after_translated_part;
	}
}
