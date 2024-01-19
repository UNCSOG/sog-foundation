<?php

namespace Toolset\DynamicSources;

use Toolset\DynamicSources\PostProviders\IdentityPostFactory;
use Toolset\DynamicSources\SourceContext\SourceContext;
use Toolset\DynamicSources\SourceContext\PostTypeSourceContextFactory;
use Toolset\DynamicSources\Sources\MediaFeaturedImageData;

class Registration {
	/** @var IdentityPostFactory */
	private $identity_post_factory;

	/**
	 * @var PostTypeSourceContextFactory
	 */
	private $post_type_source_context_factory;

	/** @var MediaFeaturedImageData */
	private $media_featured_image_source;

	/**
	 * Registration constructor.
	 *
	 * @param IdentityPostFactory          $identity_post_factory
	 * @param PostTypeSourceContextFactory $post_type_source_context_factory
	 * @param MediaFeaturedImageData $media_featured_image_source
	 */
	public function __construct(
		IdentityPostFactory $identity_post_factory,
		PostTypeSourceContextFactory $post_type_source_context_factory,
		MediaFeaturedImageData $media_featured_image_source
	) {
		$this->identity_post_factory = $identity_post_factory;
		$this->post_type_source_context_factory = $post_type_source_context_factory;
		$this->media_featured_image_source = $media_featured_image_source;
	}

	/**
	 * There might be post types that do not support a thumbnail (featured image) thus for those post types this source
	 * will need to be excluded and not show up in the offered dynamic sources. This method checks wether if current
	 * post types include a featured image source.
	 *
	 * @param array|string $post_types
	 * @return bool
	 */
	public function should_include_featured_image_source( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		$include_featured_image_source = false;
		foreach ( $post_types as $post_type ) {
			$include_featured_image_source |= post_type_supports( $post_type, 'thumbnail' );
		}
		return $include_featured_image_source;
	}

	/**
	 * Excludes the Featured Image source from the registered dynamic sources.
	 *
	 * @param array $sources_for_registration
	 * @return array
	 */
	public function exclude_featured_image_source_from_registration( $sources_for_registration ) {
		return array_filter(
			$sources_for_registration,
			function( $item ) {
				if ( $item instanceof \Toolset\DynamicSources\Sources\MediaFeaturedImageData ) {
					return false;
				}
				return true;
			}
		);
	}

	/**
	 * Adapt sources for registration to the present post type available sources.
	 *
	 * @param SourceContext $source_context
	 * @param array $sources_for_registration
	 * @return array
	 */
	public function adapt_sources_for_post_type( $source_context, $sources_for_registration ) {
		$identity_post = $this->identity_post_factory->create_identity_post( $source_context->get_post_types() );

		if ( $this->should_include_featured_image_source( $identity_post->get_post_types() ) ) {
			return $this->include_featured_image_source_from_registration( $sources_for_registration );
		}

		return $this->exclude_featured_image_source_from_registration( $sources_for_registration );
	}

	/**
	 * Includes the Featured Image source from the registered dynamic sources.
	 *
	 * @param array        $sources_for_registration
	 * @return array
	 */
	public function include_featured_image_source_from_registration( $sources_for_registration ) {
		$source_already_exist = false;

		foreach ( $sources_for_registration as $source_for_registration ) {
			$source_already_exist |= $source_for_registration instanceof \Toolset\DynamicSources\Sources\MediaFeaturedImageData;
		}

		if ( ! $source_already_exist ) {
			$sources_for_registration[] = $this->media_featured_image_source;
		}

		return $sources_for_registration;
	}

	/**
	 * Register all post providers available within the given source context.
	 *
	 * @param SourceContext $source_context
	 *
	 * @return PostProvider[]
	 */
	public function register_post_providers( SourceContext $source_context ) {
		$identity_post = $this->identity_post_factory->create_identity_post( $source_context->get_post_types() );
		$post_providers = array_filter(
			apply_filters(
				'toolset/dynamic_sources/filters/register_post_providers',
				array( $identity_post->get_unique_slug() => $identity_post ),
				$source_context
			),
			function( $post_provider ) {
				return $post_provider instanceof PostProvider;
			}
		);

		return $post_providers;
	}

	/**
	 * @param string[]|string $post_type
	 * @param null|int $view_id
	 *
	 * @return SourceContext
	 */
	public function build_source_context( $post_type, $view_id = null ) {
		if ( $view_id ) {
			$source_context = $this->post_type_source_context_factory
				->create_view_source_context( $post_type, $view_id );
		} else {
			$source_context = $this->post_type_source_context_factory->create_post_type_source_context( $post_type );
			/**
			 * Filter that allows altering the SourceContext object before it is used.
			 *
			 * @param SourceContext $source_context
			 *
			 * @return SourceContext
			 */
			$source_context = apply_filters(
				'toolset/dynamic_sources/filters/source_context',
				$source_context
			);
		}

		if( ! $source_context instanceof SourceContext ) {
			throw new \InvalidArgumentException();
		}

		return $source_context;
	}
}
