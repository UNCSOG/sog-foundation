<?php

namespace ToolsetBlocks;

use ToolsetCommonEs\Library\WordPress\Actions;

/**
 * Class FrontendAssets
 *
 * @package ToolsetBlocks
 */
class FrontendAssets {
	const BUNDLED_JS = TB_URL . 'public/js/frontend.js';
	const BUNDLED_CSS = TB_URL . 'public/css/style.css';

	/**
	 * FrontendAssets constructor.
	 *
	 * @param Actions $wp_actions
	 */
	public function __construct( Actions $wp_actions ) {
		if ( defined( 'TB_SCRIPT_STYLE_LAZY_LOAD' ) && ! TB_SCRIPT_STYLE_LAZY_LOAD ) {
			// Lazy load for script and style file disabled.
			$wp_actions->wp_enqueue_script(
				'toolset-blocks',
				self::BUNDLED_JS,
				[],
				TB_VER,
				true
			);
			$wp_actions->wp_enqueue_style(
				'toolset-blocks',
				self::BUNDLED_CSS,
				[],
				TB_VER
			);

			return;
		}

		// Lazy load script and style.
		$wp_actions->add_action( 'wp_head', function() {
			$this->lazy_load_frontend_script_style();
		} );
	}

	/**
	 * Load frontend css/js lazy. For all used blocks the css will be loaded on block rendering.
	 * The reason for loading also the complete file lazy, is the possibility
	 * of new blocks loaded via ajax afterwards (like Views ajax pagination).
	 *
	 * Includes a fallback for browsers not supporting javascript.
	 */
	private function lazy_load_frontend_script_style() {
		/** @codingStandardsIgnoreStart */
		$script = '
			<script>
			window.addEventListener("load",function(){
				var c={script:false,link:false};
				function ls(s) {
					if(![\'script\',\'link\'].includes(s)||c[s]){return;}c[s]=true;
					var d=document,f=d.getElementsByTagName(s)[0],j=d.createElement(s);
					if(s===\'script\'){j.async=true;j.src=\'' . self::BUNDLED_JS . '?v=' . TB_VER . '\';}else{
					j.rel=\'stylesheet\';j.href=\'' . self::BUNDLED_CSS . '?v=' . TB_VER . '\';}
					f.parentNode.insertBefore(j, f);
				};
				function ex(){ls(\'script\');ls(\'link\')}
				window.addEventListener("scroll", ex, {once: true});
				if ((\'IntersectionObserver\' in window) && (\'IntersectionObserverEntry\' in window) && (\'intersectionRatio\' in window.IntersectionObserverEntry.prototype)) {
					var i = 0, fb = document.querySelectorAll("[class^=\'tb-\']"), o = new IntersectionObserver(es => {
						es.forEach(e => {
							o.unobserve(e.target);
							if (e.intersectionRatio > 0) { ex();o.disconnect();}else{
								i++;if(fb.length>i){o.observe(fb[i])}}
						})
					});
					if (fb.length) {
						o.observe(fb[i])
					}
				}
			})
			</script>';
		echo preg_replace( '/\s+/', ' ', $script );
		echo '
	<noscript>
		<link rel="stylesheet" href="'.self::BUNDLED_CSS.'">
	</noscript>';
		/** @codingStandardsIgnoreEnd */
	}
}
