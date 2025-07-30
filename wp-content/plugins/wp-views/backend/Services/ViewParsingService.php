<?php

namespace OTGS\Toolset\Views\Services;

use Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured;

//TODO: add support to views created in a "Standard way"
class ViewParsingService {
	public function init() {
		// Run these 2 at runlevel 11, because Ocean Extra is hooked at 10 for saving its settings, and if it gets
		// executed later, it then saves to view postmeta, instead of actual post postmeta. The result is the same as if
		// it didn't save the settings at all.
		add_action( 'save_post', array( $this, 'scan_for_view_block_usage' ), 11, 3 );
		add_action( 'save_post', array( $this, 'save_views_from_previews' ), 11, 4 );
		add_action( 'post_updated', array( $this, 'synchronize_view_block_content' ), 20, 3 );
		add_action( 'before_delete_post', array( $this, 'scan_for_view_before_deletion' ) );
		add_action( 'wp_trash_post', array( $this, 'scan_for_view_before_deletion' ) );
	}

	/**
	 * Check if post was translated with WPML
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	protected function post_is_translated_with_wpml( $post_id ) {
		$condition = new Toolset_Condition_Plugin_Wpml_Is_Active_And_Configured();
		if ( $condition->is_met() ) {
			$type = apply_filters( 'wpml_element_type', get_post_type( $post_id ) );
			$opid = intval( apply_filters( 'wpml_original_element_id', 0, $post_id, $type ) );
			if ( $opid !== $post_id && 0 !== $opid ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Process block and if it or any of its children are view blocks -
	 * saves the primary view data from the preview post
	 *
	 * @param Array $block Block to process.
	 */
	protected function save_view_from_previews_for_nested_block( $block ) {
		if (
			in_array( $block['blockName'], array( 'toolset-views/view-editor', 'toolset-views/wpa-editor' ) ) &&
			! empty( $block['attrs']['previewId'] ) &&
			! empty( $block['attrs']['viewId'] )
		) {
			$view_id = $block['attrs']['viewId'];
			$preview_id = $block['attrs']['previewId'];
			$preview = \WP_Post::get_instance( $preview_id );

			// The preview post content is only temporary set to '[wpv-layout-meta-html]'. If that's the case
			// we do not want to move the preview to the published view post.
			// See \OTGS\Toolset\Views\Services\ViewService::render_preview_html
			if ( $preview && $preview->post_content != '[wpv-layout-meta-html]' ) {
				wp_update_post( array(
					'ID'           => $view_id,
					'post_title' => $preview->post_title,
					'post_content' => $preview->post_content,
				) );
				$data = get_post_meta( $preview_id, '_wpv_view_data' );
				if ( ! empty( $data ) && count($data) > 0 ) {
					update_post_meta( $view_id, '_wpv_view_data', $data[0] );
				}
				$settings = get_post_meta( $preview_id, '_wpv_settings' );
				if ( ! empty( $settings ) && count($settings) > 0 ) {
					update_post_meta( $view_id, '_wpv_settings', $settings[0] );
				}
				$layout_settings = get_post_meta( $preview_id, '_wpv_layout_settings' );
				if ( ! empty( $layout_settings ) && count( $layout_settings ) > 0 ) {
					update_post_meta( $view_id, '_wpv_layout_settings', $layout_settings[0] );
				}

				/**
				 * Hook for the actions that follow the transferring of the View preview post settings into the actual View post.
				 *
				 * @param int|string $view_id
				 * @param array      $view_data
				 */
				do_action( 'wpv_action_after_save_views_from_previews', $view_id, $data[0] );

				do_action( 'wpv_action_wpv_save_item', $view_id );
			}
		}
		foreach ( $block['innerBlocks'] as $inner_block ) {
			$this->save_view_from_previews_for_nested_block( $inner_block );
		}
	}

	/**
	 * Parse post on save, find view editor blocks and copy meta attributes from the preview post
	 *
	 * @param integer $post_id ID of post.
	 * @param WP_Post $post post object.
	 * @return void
	 */
	public function save_views_from_previews( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( $this->post_is_translated_with_wpml( $post_id ) ) {
			return;
		}
		$blocks = parse_blocks( $post->post_content );
		foreach ( $blocks as $block ) {
			$this->save_view_from_previews_for_nested_block( $block );
		}
	}

	/**
	 * Parse post meta array of IDs
	 *
	 * @param object $value Value to convert.
	 * @return array
	 */
	public function meta_to_array( $value ) {
		if (empty($value)) {
			return array();
		}
		if (is_array($value)) {
			if ( empty( $value[0] ) ) {
				return [];
			}
			return json_decode($value[0], true);
		}
		return json_decode($value, true);
	}

	/**
	 * Save array of IDs as post meta
	 *
	 * @param object $value Value to convert.
	 * @return string
	 */
	public function array_to_meta( $value ) {
		if (count($value) == 0) {
			return '';
		}
		return json_encode($value);
	}

	/**
     * Save as post meta:
     * _wpv_gutenberg_views - IDs of views used in current post (added to post)
     * _wpv_used_in_posts - IDs of posts where view is used (added to view)
	 *
	 * @param integer $post_id ID of post.
	 * @param WP_Post $post Post object.
     */
	public function scan_for_view_block_usage( $post_id, $post ) {
		if ( $this->post_is_translated_with_wpml( $post_id ) ) {
			return;
		}
		// don't save revisions
		if ($post->post_status == 'inherit') {
			return;
		}
		if ($post->post_type == 'view-template') {
			return;
		}
		$view_ids = $this->meta_to_array( get_post_meta( $post_id, '_wpv_contains_gutenberg_views' ) );
		// Scan post for views contained
		$ids = $this->get_view_ids_in_post( $post->post_content );
		foreach ( $ids as $id ) {
			$used_in_posts = $this->meta_to_array( get_post_meta( $id, '_wpv_used_in_posts' ) );
			if ( ! in_array( $post_id, $used_in_posts ) ) {
				$used_in_posts[] = $post_id;
				$data = $this->array_to_meta(
					array_filter( $used_in_posts )
				);
				update_post_meta( $id, '_wpv_used_in_posts', $data );
			}
		}
		// Check if some views were removed from the post
		$removedViews = array_diff( $view_ids, $ids );
		foreach ( $removedViews as $view_id ) {
			$used_in_posts = $this->meta_to_array( get_post_meta( $view_id, '_wpv_used_in_posts' ) );
			$used_in_posts = $this->array_to_meta(
				array_filter( array_diff( $used_in_posts, [ $post_id ] ) )
			);
			update_post_meta( $view_id, '_wpv_used_in_posts', $used_in_posts );
		}
		update_post_meta( $post_id, '_wpv_contains_gutenberg_views', $this->array_to_meta( $ids ) );
	}

	/**
	 * Extract all view IDs used inside the Post
	 *
	 * @param string $post_content Post content to search in.
	 * @return array
	 */
	public function get_view_ids_in_post( $post_content ) {
		$result = array();
		$blocks = parse_blocks( $post_content );
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] == 'toolset-views/view-editor' ) {
				if ( ! empty( $block['attrs']['viewId'] ) ) {
					$result[] = $block['attrs']['viewId'];
				}
			}
		}
		return array_unique( $result );
	}

	/**
	 * Scan for views inside the post and update view usage data
	 *
	 * @param integer $post_id ID of post.
	 */
	public function scan_for_view_before_deletion( $post_id ) {
		$data = get_post_meta( $post_id, '_wpv_view_data' );
		// Don't run deletion on the preview post because we'll have infinite recursion in such case
		if ( count( $data ) > 0 && $data[0]['general']['preview_id'] != $post_id ) {
			wp_delete_post( $data[0]['general']['preview_id'], true );
		}
		$viewIds = $this->meta_to_array( get_post_meta( $post_id, '_wpv_contains_gutenberg_views') );
		foreach ($viewIds as $viewId) {
			$usedInPosts = $this->meta_to_array( get_post_meta( $viewId, '_wpv_used_in_posts' ) );
			$usedInPosts = array_diff( $usedInPosts, [ $post_id ] );
			update_post_meta( $viewId, '_wpv_used_in_posts', $this->array_to_meta( $usedInPosts ) );
		}
	}

	/**
	 * Compares two arrays of blocks from block parser
	 * and returns the difference if there is something
	 * Used to compare blocks output on each run of
	 * WP_Block_Parser->proceed to find positions inside the string
	 * where specific block is located (index from and index to)
	 *
	 * @param $arr1 First array of parsed blocks.
	 * @param $arr2 Second array of parsed blocks.
	 *
	 * @return array
	 */
	protected function diff_view_block( $arr1, $arr2 ) {
		return $this->diff_block( $arr1, $arr2, 'toolset-views/view-editor' );
	}

	/**
	 * Compares two arrays of blocks from block parser
	 * and returns the difference if there is something
	 * Used to compare blocks output on each run of
	 * WP_Block_Parser->proceed to find positions inside the string
	 * where specific block is located (index from and index to)
	 *
	 * @param array $arr1 First array of parsed blocks.
	 * @param array $arr2 Second array of parsed blocks.
	 * @param string $block_type Block type.
	 *
	 * @return array
	 */
	protected function diff_block( $arr1, $arr2, $block_type ) {
		$result = array();
		foreach ( $arr1 as $item ) {
			$found = false;
			$str = json_encode($item);
			foreach ( $arr2 as $item2 ) {
				if ( $str == json_encode($item2) ) {
					$found = true;
				}
			}
			if ( ! $found ) {
				if ( $item['blockName'] == $block_type ) {
					$result[] = $item;
				}
			}
		}
		return $result;
	}

	/**
	 * Replaces Gutenberg View block markup with new post content
	 *
	 * @param integer $post_id ID of post containing View block.
	 * @param integer $view_id ID of view to replace.
	 * @param string $new_markup New View HTML markup.
	 *
	 * @return mixed|void
	 */
	public function replace_view_markup( $post_id, $view_id, $new_markup ) {
		$location = $this->find_view_inside_post( $post_id, $view_id );
		if ( $location == null ) {
			return $new_markup;
		}
		$post = \WP_Post::get_instance( $post_id );
		return substr($post->post_content, 0, $location['start']) .
		       $new_markup .
		       substr($post->post_content, $location['end']);
	}

	/**
	 * Get Gutenberg View markup start and end index inside the Post content
	 *
	 * @param integer $post_id ID of post containing View block.
	 * @param integer $view_id ID of view to replace.
	 *
	 * @return mixed|void
	 */
	public function find_view_inside_post( $post_id, $view_id ) {
		if ( ! $post_id ) {
			return null;
		}

		$post = \WP_Post::get_instance( $post_id );
		return $this->find_block_in_text(
			$post->post_content,
			'toolset-views/view-editor',
			function ( $item ) use ( $view_id ) {
				return isset( $item->block->attrs['viewId'] ) &&
					// These variables could come from the different places and I discovered sometimes one them could be string.
					intval( $view_id ) === intval( $item->block->attrs['viewId'] );
			}
		);
	}

	/**
	 * Get Gutenberg View markup start and end index inside the given text
	 *
	 * @param string   $text Text to search for block.
	 * @param string   $block_type Block type to search for.
	 * @param callable $function Optional additional function to check block, return true if block meets required conditions.
	 *
	 * @return mixed|void
	 */
	public function find_block_in_text( $text, $block_type, $function = null ) {
		$parser = new \WP_Block_Parser();
		$parser->document = $text;
		$parser->offset = 0;
		$parser->output = array();
		$parser->stack = array();

		$view_start = null;
		$prev_view = null;
		$view_end = null;
		do {
			// if view block found, we need to save its start and end index inside the post content
			$view = null;
			$blocks = array_filter( $parser->stack, function ( $item ) use ( $block_type, $function ) { //extract all view blocks from the stack
				if ( null === $function ) {
					return $block_type === $item->block->blockName;
				}
				return ( $block_type === $item->block->blockName ) && $function( $item );
			} );
			if ( count( $blocks ) > 0 ) {
				$view = array_pop( $blocks );
			}
			if ( null !== $view && null === $view_start ) {
				$view_start = $view->token_start;
			}
			if ( null !== $prev_view && null === $view ) {
				$view_end = $parser->offset;
				// The end of the block was found so the repetition should stop to prevent finding another instance of
				// the same block type later, which will ruin the end position calculation.
				break;
			}
			$prev_view = $view;
		} while ( $parser->proceed() );
		if ( null === $view_start ) {
			return null;
		}
		if ( null === $view_end ) {
			$view_end = strlen( $text );
		}
		return array(
			'start' => $view_start,
			'end' => $view_end
		);
	}

	/**
	 * Extracts Gutenberg View markup from the given post content
	 *
	 * @param integer $post_id ID of post containing View block.
	 * @param integer $view_id ID of view to replace.
	 *
	 * @return mixed|void
	 */
	public function get_view_markup( $post_id, $view_id ) {
		$location = $this->find_view_inside_post( $post_id, $view_id );
		if ( $location === null ) {
			return '';
		}
		$post = \WP_Post::get_instance( $post_id );
		return substr( $post->post_content, $location['start'], $location['end'] - $location['start']);
	}

	/**
	 * Parse post on save, find view editor blocks and sync their markup if View was edited inside
	 * other post than post it was created inside
	 *
	 * @param integer $post_id ID of post.
	 * @param WP_Post $post post object.
	 * @return void
	 */
	public function synchronize_view_block_content( $post_id, $post ) {
		if ( $this->post_is_translated_with_wpml( $post_id ) ) {
			return;
		}
		$view_ids = $this->get_view_ids_in_post( $post->post_content );
		foreach ($view_ids as $view_id) {
			$meta = get_post_meta( $view_id, '_wpv_is_gutenberg_view' );
			$is_gutenberg_view = false;
			if ( !empty($meta) ) {
				$is_gutenberg_view = $meta[0];
			}
			if ( ! $is_gutenberg_view ) {
				continue;
			}
			$used_in_posts = $this->meta_to_array( get_post_meta( $view_id, '_wpv_used_in_posts' ) );
			$block_markup = $this->get_view_markup( $post_id, $view_id );
			// if we don't have a view layout block inside the markup
			// this means it's an original block and not the inserted one
			// in this case we should process, otherwise we have to skip this block
			// not to overwrite the original block
			$data = $this->find_block_in_text( $block_markup, 'toolset-views/view-layout-block' );
			if ( null === $data ) {
				continue;
			}
			remove_action( 'post_updated', array( $this, 'synchronize_view_block_content' ), 20 );
			foreach ( $used_in_posts as $pid ) {
				if ( $pid != $post_id ) {
					// If there is not a View layout block inside the markup of the post the View is used in, it means that
					// it is using an existing View, thus its markup shouldn't be updated.
					$secondary_post_view_block_markup = $this->get_view_markup( $pid, $view_id );
					$secondary_post_view_layout_position_data = $this->find_block_in_text( $secondary_post_view_block_markup, 'toolset-views/view-layout-block' );
					if ( null === $secondary_post_view_layout_position_data ) {
						continue;
					}

					$this->tmp_storage_of_pid_for_filter = $pid;
					// Add filter to let WPML TM know which language the View has.
					add_filter( 'wpml_tm_save_post_lang_value', array( $this, 'filter_wpml_tm_save_post_lang_value' ) );
					wp_update_post( array(
						'ID' => $pid,
						'post_content' => wp_slash( $this->replace_view_markup( $pid, $view_id, $block_markup ) ),
					) );
					// Remove previous added filter after the post was updated.
					remove_filter( 'wpml_tm_save_post_lang_value', array( $this, 'filter_wpml_tm_save_post_lang_value' ) );
				}
			}
			add_action( 'post_updated', array( $this, 'synchronize_view_block_content' ), 20, 3 );
		}
	}

	/**
	 * Filter for wpml_tm_save_post_lang_value
	 * Views loops over all language posts to update the post content of the View. But when the language is not
	 * specified, the current language will be used and that will lead to a InvalidArgumenetException on WPML TM as
	 * DIFFERENT_THAN_CURRENT Post_ID with CURRENT language can't be found in the database.
	 */
	private $tmp_storage_of_pid_for_filter;

	public function filter_wpml_tm_save_post_lang_value( $lang ) {
		$post_info = apply_filters( 'wpml_post_language_details', NULL, $this->tmp_storage_of_pid_for_filter );

		if( is_array( $post_info ) && array_key_exists( 'language_code', $post_info ) ) {
			return $post_info[ 'language_code' ];
		}

		return $lang;
	}

	/**
	 * Try to restore view block markup using block metadata
	 *
	 * @param mixed $view_id ID of the view.
	 * @return string Restored HTML or blank string if restore was unsuccessful.
	 */
	public function try_to_restore_markup( $view_id ) {
		$view_data = get_post_meta( $view_id, '_wpv_view_data', true );
		if ( ! $view_data ) {
			return '';
		}
		$preview_id = null;
		if ( isset( $view_data['general'] ) && isset( $view_data['general']['preview_id'] ) ) {
			$preview_id = $view_data['general']['preview_id'];
		}
		$slug = null;
		if ( isset( $view_data['general'] ) && isset( $view_data['general']['slug'] ) ) {
			$slug = $view_data['general']['slug'];
		}
		$markup = null;
		if ( isset( $view_data['general'] ) && isset( $view_data['general']['view_template'] ) ) {
			$markup = $view_data['general']['view_template'];
		}
		if ( null === $slug || null === $preview_id || null === $markup ) {
			return '';
		}
		return '<!-- wp:toolset-views/view-editor {"reduxStoreId":"views-editor","viewId":' . $view_id . ',"viewSlug":"' . $slug . '","previewId":' . $preview_id . ',"focused":false,"insertExisting":"0","wizardDone":true,"wizardStep":3} -->' .
			$markup .
			'<!-- /wp:toolset-views/view-editor -->';
	}
}
