<?php

namespace OTGS\Toolset\Views\Controller;

/**
 * Plugin cache controller.
 *
 * This sould be the main cache manager for Toolse Views.
 *
 * @since 2.8.1
 * @todo Port here everything inside \WPV_Cache
 */
class Cache {

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\Meta\Post
	 */
	private $postmeta_cache = null;

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\Meta\Term
	 */
	private $termmeta_cache = null;

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\Meta\User
	 */
	private $usermeta_cache = null;

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\Media
	 */
	private $media_cache;

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\MetaFilters\Post $postmeta_filters_cache
	 */
	private $postmeta_filters_cache;

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\Views\Invalidator $views_cache_invalidator
	 */
	private $views_cache_invalidator;

	/**
	 * @var \OTGS\Toolset\Views\Controller\Cache\Meta\Gui
	 */
	private $cache_gui = null;

	/**
	 * Constructor.
	 *
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\Post $postmeta_cache
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\Term $termmeta_cache
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\User $usermeta_cache
	 * @param \OTGS\Toolset\Views\Controller\Cache\Media $media_cache
	 * @param \OTGS\Toolset\Views\Controller\Cache\MetaFilters\Post $postmeta_filters_cache
	 * @param \OTGS\Toolset\Views\Controller\Cache\Meta\Gui $cache_gui
	 * @since 2.8.1
	 */
	public function __construct(
		\OTGS\Toolset\Views\Controller\Cache\Meta\Post $postmeta_cache,
		\OTGS\Toolset\Views\Controller\Cache\Meta\Term $termmeta_cache,
		\OTGS\Toolset\Views\Controller\Cache\Meta\User $usermeta_cache,
		\OTGS\Toolset\Views\Controller\Cache\Media $media_cache,
		\OTGS\Toolset\Views\Controller\Cache\MetaFilters\Post $postmeta_filters_cache,
		\OTGS\Toolset\Views\Controller\Cache\Views\Invalidator $views_cache_invalidator,
		\OTGS\Toolset\Views\Controller\Cache\Meta\Gui $cache_gui
	) {
		$this->postmeta_cache = $postmeta_cache;
		$this->termmeta_cache = $termmeta_cache;
		$this->usermeta_cache = $usermeta_cache;

		$this->media_cache = $media_cache;

		$this->postmeta_filters_cache = $postmeta_filters_cache;

		$this->views_cache_invalidator = $views_cache_invalidator;

		$this->cache_gui = $cache_gui;
	}

	/**
	 * Initialize the plugin cache management.
	 *
	 * @since 2.8.1
	 */
	public function initialize() {
		$this->postmeta_cache->initialize();
		$this->termmeta_cache->initialize();
		$this->usermeta_cache->initialize();

		$this->media_cache->initialize();

		$this->postmeta_filters_cache->initialize();

		$this->views_cache_invalidator->initialize();

		$this->cache_gui->initialize();
	}

}
