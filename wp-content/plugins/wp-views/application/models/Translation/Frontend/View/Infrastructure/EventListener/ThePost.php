<?php

namespace OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener;

use OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener\Helper\PostActions;
use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class ThePost
 *
 * Hook to the post filter to trigger any translation apply for View Blocks inside the post.
 *
 * @package OTGS\Toolset\Views\Models\Translation\Frontend\View\Infrastructure\EventListener
 *
 * @since TB 1.13
 */
class ThePost {
	/** @var Actions */
	private $wp_actions;

	/** @var PostActions */
	private $post_actions;

	/** @var Factory */
	private $factory_event_listener;

	/**
	 * ThePost constructor.
	 *
	 * @param Factory $factory_event_listener
	 * @param Actions $wp_actions
	 * @param PostActions $post_actions
	 */
	public function __construct(
		Factory $factory_event_listener,
		Actions $wp_actions,
		PostActions $post_actions
	) {
		$this->factory_event_listener = $factory_event_listener;
		$this->wp_actions = $wp_actions;
		$this->post_actions = $post_actions;
	}

	/**
	 * Listen to 'the_post' only when WPML is active and the current language differs from the default language.
	 *
	 * @param bool $is_frontend_call
	 * @param bool $is_doing_ajax
	 */
	public function start_listen( $is_frontend_call = false, $is_doing_ajax = false ) {
		$current_language = $this->wp_actions->apply_filters( 'wpml_current_language', false );
		if( $current_language === false ) {
			return;
		}

		$default_language = $this->wp_actions->apply_filters( 'wpml_default_language', false );

		if( $current_language !== $default_language ) {
			if( $is_doing_ajax ) {
				// For view ajax refreshs (view search inputs) we need to use this for fetching the post:
				$this->wp_actions->add_filter( 'wpv_action_wpv_set_top_current_post', array( $this, 'on_event' ), 10, 1 );
			}

			if( $is_frontend_call ) {
				$this->wp_actions->add_filter( 'the_post', array( $this, 'on_event' ), 10, 1 );
			}
		}
	}

	public function on_event( \WP_Post $post ) {
		try {
			// Check for Content Template
			$content_template_id = $this->post_actions->has_wpv_content_template( $post->ID );
			if( $content_template_id > 0 ) {
				$ct_translated = $this->get_translated_content_template( $content_template_id );

				if( ! $ct_translated ) {
					return $post;
				}

				$post = $ct_translated;
			}

			// Check for content templates which are inserted via the Content Template block.
			if ( strpos( $post->post_content, 'toolset/ct' ) ) {
				// The following is just for translations and we need to keep the $post clean.
				$translated_post = unserialize( serialize( $post ) );

				// Get CT blocks to fetch translated version of it.
				$blocks = $this->post_actions->parse_blocks( $post->post_content );
				foreach ( $blocks as $block ) {
					if (
						$block['blockName'] !== 'toolset/ct'
						|| ! array_key_exists( 'attrs', $block )
						|| ! is_array( $block['attrs'] )
						|| ! array_key_exists( 'ct', $block['attrs'] )
						|| empty( $block['attrs']['ct'] )
					) {
						continue;
					}

					$ct_translated = $this->get_translated_content_template( $block['attrs']['ct'] );

					if ( $ct_translated ) {
						// Just append the translated CT content to the post content. This will just be used as source
						// of translations, so the order does not matter.
						$translated_post->post_content = $translated_post->post_content . $ct_translated->post_content;
					}
				}
			}

			// Check for a specific translated post object.
			if( ! isset( $translated_post ) ) {
				// Not set, the original $post input already contains the translations.
				$translated_post = $post;
			}

			// This shouldn't be here from the code concept perspective, but as the "the_post" filter is very general
			// we want to check as early as possible if the post contains a view.
			if( $post && strpos( $translated_post->post_content, "toolset-views/view-editor" ) === false ) {
				// No view post.
				return $post;
			}

			$settings = $this->factory_event_listener->wpv_view_settings();
			$settings->set_post_translated( $translated_post );
			$settings->start_listen();

			$content = $this->factory_event_listener->wpv_post_content();
			$content->set_post_translated( $translated_post );
			$content->start_listen();

			return $post;
		} catch ( \InvalidArgumentException $exception ) {
			// Not a Views block.
			return $post;
		} catch ( \Exception $exception ) {
			// Unexpected.
			if( defined( 'WPV_TRANSLATION_DEBUG' ) && WPV_TRANSLATION_DEBUG ) {
				// @codeCoverageIgnoreStart
				trigger_error(  'Problem with Views translation: ' . $exception->getMessage(), E_USER_WARNING );
				// @codeCoverageIgnoreEnd
			}
			return $post;
		}
	}


	/**
	 * Returns the translated content template as \WP_Post object.
	 * Null is returned if no translated content template is available for the given id.
	 *
	 * @param $content_template_id
	 *
	 * @return ?\WP_Post
	 */
	private function get_translated_content_template( $content_template_id ) {
		if( ! is_numeric( $content_template_id ) ) {
			$content_template_id = $this->get_content_template_id_by_slug( $content_template_id );
		}

		$ct = $this->post_actions->get_post( (int) $content_template_id );

		if( ! $ct ) {
			return null;
		}

		$ct_translated_id = $this->wp_actions->apply_filters( 'wpml_object_id', $ct->ID, $ct->post_type );

		if( ! $ct_translated_id ) {
			return null;
		}

		$ct_translated = $this->post_actions->get_post( $ct_translated_id );

		if( ! $ct_translated ) {
			return null;
		}

		return $ct_translated;
	}

	private function get_content_template_id_by_slug( $content_template_slug ) {
		$content_template = get_page_by_path( $content_template_slug, OBJECT, 'view-template');

		return is_object( $content_template ) ? $content_template->ID : $content_template_slug;
	}
}
