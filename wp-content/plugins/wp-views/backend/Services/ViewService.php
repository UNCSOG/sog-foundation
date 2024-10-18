<?php

namespace OTGS\Toolset\Views\Services;

use OTGS\Toolset\Views\Services\ViewStructureConvertor;
use OTGS\Toolset\Views\Services\ViewQueryService;
use OTGS_Views\KubAT\PhpSimple\HtmlDomParser;

class ViewService {
	/**
	 * @var string
	 */
	private $view_class;

	public function __construct( $view_class = '\WPV_View' ) {
		$this->view_class = $view_class;
	}

	/**
	 * Create a view using data provided
	 */
	public function create( $view_data ) {
		// continue with view creation once slug is generated
		$convertor = new ViewStructureConvertor($view_data);
		$view_settings = $convertor->convert_view_for_backend();
		$loop_settings = $convertor->convert_loop_for_backend();
		$response = array(
			'success' => true
		);
		try {
			$view = call_user_func(
				array( $this->view_class, 'create' ),
				$view_data['general']['name'],
				array(
					'view_settings' => $view_settings,
					'loop_settings' => $loop_settings,
					'forbid_loop_template' => false,
					'create_draft' => toolset_getnest( $view_data, ['create_draft'], false ),
				)
			);

			update_post_meta( $view->id, '_wpv_is_gutenberg_view', 1 );
			update_post_meta( $view->id, '_wpv_view_data', $view_data );
			$response['id'] = $view->id;
			$response['slug'] = \WP_Post::get_instance($view->id)->post_name;
			$response['html'] = '';
		}
		catch( \WPV_RuntimeExceptionWithMessage $e ) {
			$response['success'] = false;
			$response['message'] = $e->getUserMessage();
		} catch( \Exception $e ) {
			$response['success'] = false;
			$response['message'] = __( 'The View could not be created.', 'wpv-views' );
		}
		return $response;
	}

	/**
	 * Generates view preview
	 *
	 * @param int   $id  View ID.
	 * @param mixed $view_data View Data array.
	 * @return mixed
	 */
	public function preview( $id, $view_data ) {
		$html = $this->render_preview_html( $id, $view_data );

		$convertor = new ViewStructureConvertor( $view_data );
		$view_settings = $convertor->convert_view_for_backend();

		return array(
			'success' => true,
			'id' => $id,
			'html' => $html,
			'first_item_id' => $this->get_first_item_id( $id, $view_settings, $view_data ),
		);
	}

	/**
	 * Get ID of the first item in view
	 *
	 * @param int   $id View ID.
	 * @param array $view_settings View Settings.
	 * @param array $view_data View data structure from react.
	 * @return int Post ID
	 */
	protected function get_first_item_id( $id, $view_settings, $view_data ) {
		// Receive the first item of loop and put its id into the output.
		$query_service = new ViewQueryService();
		return apply_filters(
			'wpv_filter_view_editor_first_item_id',
			$query_service->get_first_view_item_id( $view_data['content_selection']['query_type'], $id, $view_settings ),
			$id
		);
	}

	/**
	 * Get loop preview for View
	 *
	 * @param int   $id View ID.
	 * @param array $view_data View data.
	 * @return string
	 */
	protected function render_preview_html( $id, $view_data ) {
		// Save initial post with its content.
		$initial_post = \WP_Post::get_instance( $id );

		// Temporary set post content to view loop only.
		// This is needed for preview generation.
		wp_update_post(
			array(
				'ID' => $id,
				'post_content' => '[wpv-layout-meta-html]',
			)
		);

		$view_template = $view_data['general']['view_template'];

		/**
		 * Triggers an action before doing the blocks in the View block's template on the editor preview.
		 *
		 * @param string $view_template
		 */
		do_action( 'wpv_action_before_doing_blocks_in_view_block_template', $view_template );

		// do_blocks to trigger 'render_block' which is used to collect block styles. Needed for styling on the preview.
		do_blocks( $view_template );

		$responsive_device = array_key_exists( 'preview_for_responsive_device', $view_data ) ?
			$view_data['preview_for_responsive_device'] :
			null;

		// Using a filter as passing the device to 'wpv_do_shortcode' wouldn't work for nested shortcodes.
		// And the preview rendering is always being done by a nested shortcode.
		add_filter( 'wpv_view_block_preview_for_responsive_device',
			function() use ( $responsive_device ) { return $responsive_device; }, 10, 1 );

		/**
		 * Hook for the actions that precede the rendering the View shortcode in the editor preview.
		 *
		 * @param int $id View ID
		 */
		do_action( 'wpv_action_before_render_view_editor_shortcode', $id );
		$html = wpv_do_shortcode( '[wpv-view id="' . (int) $id . '"]' );

		/**
		 * Hook for the actions that follow the rendering the View shortcode in the editor preview.
		 *
		 * @param int $id View ID
		 */
		do_action( 'wpv_action_after_render_view_editor_shortcode', $id );

		// Restore original content.
		wp_update_post(
			array(
				'ID' => $id,
				'post_content' => $initial_post->post_content,
			)
		);

		$view = \WPV_View::get_instance( $id );

		$blocks = array_filter(
			array_map(
				function ( $value ) {
					return $value['blockName'];
				},
				parse_blocks( $view->loop_meta_html )
			)
		);

		$html = apply_filters( 'wpv_filter_view_editor_preview_generation', $html, $blocks );

		// Remove hyperlinks to avoid navigation.
		$dom = HtmlDomParser::str_get_html( $html );
		if ( $dom ) {
			foreach ( $dom->find( 'a' ) as $link ) {
				$link->href = '#';
			}
			$maybe_nodes = $dom->find( '.js-wpv-loop-wrapper' );
			$node = count( $maybe_nodes ) > 0 ? $maybe_nodes[0] : null;
			if ( $node ) {
				$html = $node->save();
			}
		}

		return $html;
	}

	/**
	 * Update a view with data provided and generate a preview
	 * @param int $id View ID
	 * @param mixed $view_data View Data array
	 * @return mixed
	 */
	public function save( $id, $view_data ) {
		//Extract custom search block content if any and replace it with shortcode
		$view_template = $view_data['general']['view_template'];
		$parser = new ViewParsingService();
		$data_from_search_container = $parser->find_block_in_text( $view_template, 'toolset-views/custom-search-container' );
		if ( null !== $data_from_search_container ) {
			$view_data['loop']['filter_template'] = substr($view_template, $data_from_search_container['start'], $data_from_search_container['end'] - $data_from_search_container['start']);
			//replace custom search with correct shortcode
			$view_template = substr($view_template, 0, $data_from_search_container['start']) .
			                 '[wpv-filter-meta-html]' .
			                 substr($view_template, $data_from_search_container['end']);
		} else {
			$view_template .= '[wpv-filter-meta-html]';
		}

		$convertor = new ViewStructureConvertor($view_data);
		$view_settings = $convertor->convert_view_for_backend();
		$loop_settings = $convertor->convert_loop_for_backend();

		// We need this for correct work of "Don't include the current page in the query result" option
		global $post;
		$post = \WP_Post::get_instance( $view_data['general']['parent_post_id'] );

		//Save default loop template as user meta to be reused in the future view wizards
		$user_id = get_current_user_id();
		$default_loop_template = get_user_meta( $user_id, '_wpv_default_template' );
		if ( empty( $default_loop_template ) ) {
			if ( in_array( $loop_settings['style'], array( 'wp_columns', 'bootstrap-grid', 'table', 'bootstrap-4-grid' ) ) ) {
				update_user_meta( $user_id, '_wpv_default_template', $loop_settings['style'] );
			}
		}
		if ( $view_data['loop']['save_loop_item_position'] && isset( $view_data['loop']['loop_item_on_top'] ) ) {
			update_user_meta( $user_id, '_wpv_default_loop_item_on_top', $view_data['loop']['loop_item_on_top'] );
		}

		$view = \WPV_View::get_instance( $id );
		$view->defer_after_update_actions();
		$view->begin_modifying_view_settings();
		$view->begin_modifying_loop_settings();

		// As it is part of a array, it needs to be escaped because when updating the post meta
		// it only does a escape, missing escapes in second levels
		$view_data['loop']['view_layout'] = addslashes( $view_data['loop']['view_layout'] );
		update_post_meta( $view->id, '_wpv_view_data', $view_data );

		$view_layout = $view_data['loop']['view_layout'];
		$parser = new ViewParsingService();
		// replace the loop item block with $view_data['loop']['loop_template'] contents here
		$data = $parser->find_block_in_text( $view_layout, 'toolset-views/view-template-block' );
		//build the correct view markup
		$view_layout = '[wpv-layout-start]' .
						substr( $view_layout, 0, toolset_getnest( $data, array( 'start' ), null ) ) .
						$view_data['loop']['loop_template'] .
						substr( $view_layout, toolset_getnest( $data, array( 'end' ), null ) ) .
						'[wpv-layout-end]';

		$view->loop_meta_html = addslashes( $view_layout );

		$view->loop_style = $loop_settings['style'];
		$view->loop_table_column_count = $loop_settings['table_cols'];
		$view->loop_bs_column_count = $loop_settings['bootstrap_grid_cols'];
		$view->loop_bs_grid_container = $loop_settings['bootstrap_grid_container'];
		$view->loop_row_class = $loop_settings['bootstrap_grid_row_class'];
		$view->loop_bs_individual = $loop_settings['bootstrap_grid_individual'];
		$view->loop_include_field_names = $loop_settings['include_field_names'];
		$view->list_separator = $loop_settings['list_separator'];
		if ( null === $data_from_search_container ) {
			$view->reset_filter_meta_html();
		}

		// Theme integration settings
		$toolset_theme_integration_settings = toolset_getnest( $view_data, array( 'themeIntegration', 'settings' ), array() );
		if (
			defined('TOOLSET_THEME_SETTINGS_DATA_KEY' ) &&
			! empty( $toolset_theme_integration_settings )
		) {
			$view_settings[ TOOLSET_THEME_SETTINGS_DATA_KEY ] = $toolset_theme_integration_settings;
		}

		$view->set_view_settings($view_settings);

		if ( defined( 'WPV_BLOCK_PREVIEW_RENDER' ) && isset( $view_data['ordering']['first'] )
			&& 'rand'
			=== $view_data['ordering']['first'] ) {
			$view_data['ordering']['first'] = 'title';
		}

		// update on post preview
		wp_update_post(array(
			'ID' => $id,
			'post_title' => $view_data['general']['name'],
		));

		$view->description = $view_data['general']['description'];

		$view->finish_modifying_view_settings();
		$view->finish_modifying_loop_settings();
		$view->resume_after_update_actions();

		$html = $this->render_preview_html( $view->id, $view_data );

		// replace visual loop template editor with [wpv-layout-meta-html] shortcode,
		// since this is not available on the backend - we have to support two different modes to make this work

		//process the view layout block
		$data = $parser->find_block_in_text( $view_template, 'toolset-views/view-layout-block' );
		if ( $data != null ) {
			$layout_block_content = '[wpv-layout-meta-html]';
			if ( false === strpos( $view_template, '[wpv-filter-meta-html]')  ) {
				/*
				 * [wpv-filter-meta-html] must be part of the View content and it must render the View form container.
				 * Without a form container there is no [wpv-filter-start][[wpv-filter-end] hence no form. Also without
				 * a form, the sorting controls, the pagination controls etc won't work either. So if [wpv-filter-meta-html]
				 * is not present because of a custom search container, we need to add it manually here.
				 */
				$layout_block_content .= '[wpv-filter-meta-html]';
			}
			//replace custom search with correct shortcode
			$view_template = substr($view_template, 0, $data['start']) .
							 $layout_block_content .
			                 substr($view_template, $data['end']);
		}

		$dom = HtmlDomParser::str_get_html($view_template);
		if ($dom) {
			$elems = $dom->find('.php-to-be-replaced-with-shortcode');
			if (count($elems) > 0) {
				$elems[0]->innertext = '[wpv-layout-meta-html]';
			}
			// remove view ID identity class
			$elems2 = $dom->find('.wp-block-toolset-views-view-editor');
			if (count($elems2) > 0) {
				$elems2[0]->class = 'wp-block-toolset-views-view-editor';
			}
			$elems3 = $dom->find('.wp-block-toolset-views-view-template-block');
			if (count($elems3)) {
				$elems3[0]->style = '';
			}
			$view_template = $dom->save();
		}
		// update view post content to correct value
		$post_for_update = array(
			'ID' => $view->id,
			'post_content' => $view_template
		);
		wp_update_post($post_for_update);
		$content_sanitized = sanitize_post_field( 'post_content', $view_data['general']['view_template'], $view->id, 'db' );
		do_action( 'wpv_action_wpv_register_wpml_strings', $content_sanitized, $view->id );
		do_action( 'wpv_action_wpv_save_item', $view->id );

		// format output
		return array(
			'success' => true,
			'id' => $view->id,
			'html' => $html,
			'first_item_id' => $this->get_first_item_id( $id, $view_settings, $view_data ),
 		);
	}
}
