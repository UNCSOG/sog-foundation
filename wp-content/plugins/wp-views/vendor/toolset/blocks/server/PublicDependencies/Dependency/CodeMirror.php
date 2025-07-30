<?php

namespace ToolsetBlocks\PublicDependencies\Dependency;

/**
 * CodeMirror dependency
 *
 * @since 2.0.0
 */
class CodeMirror implements IGeneral {

	/**
	 * CodeMirror is not loaded directly. This just makes sure codemirror is a registered script.
	 * Add as dependency to load.
	 */
	public function load_dependencies() {
		// not needed if others already registered it
		if ( ! wp_script_is( 'codemirror', 'registered' ) ) {
			wp_register_script(
				'codemirror',
				TB_URL . 'public/vendor/codemirror/codemirror.js',
				array(),
				'5.46.0'
			);
		}

		// CodeMirror Style Mode 'htmlmixed'
		// IMPORTANT to make codemirror a dependency of the mode and not the other way around. Because codemirror
		// must be present before the mode is loaded. This also means the final script should have the used mode
		// as dependecy and not codemirror.
		if ( ! wp_script_is( 'codemirror-xml', 'registered' ) ) {
			wp_register_script(
				'codemirror-xml',
				TB_URL . 'public/vendor/codemirror/xml.js',
				array( 'codemirror' ),
				'5.46.0'
			);
		}
		if ( ! wp_script_is( 'codemirror-javascript', 'registered' ) ) {
			wp_register_script(
				'codemirror-javascript',
				TB_URL . 'public/vendor/codemirror/javascript.js',
				array( 'codemirror' ),
				'5.46.0'
			);
		}
		if ( ! wp_script_is( 'codemirror-css', 'registered' ) ) {
			wp_register_script(
				'codemirror-css',
				TB_URL . 'public/vendor/codemirror/css.js',
				array( 'codemirror' ),
				'5.46.0'
			);
		}
		if ( ! wp_script_is( 'codemirror-matchbracket', 'registered' ) ) {
			wp_register_script(
				'codemirror-matchbracket',
				TB_URL . 'public/vendor/codemirror/matchbracket.js',
				array( 'codemirror' ),
				'5.46.0'
			);
		}
		if ( ! wp_script_is( 'codemirror-htmlmixed', 'registered' ) ) {
			wp_register_script(
				'codemirror-htmlmixed',
				TB_URL . 'public/vendor/codemirror/htmlmixed.js',
				array( 'codemirror-xml', 'codemirror-javascript', 'codemirror-css', 'codemirror-matchbracket', 'codemirror' ),
				'5.46.0'
			);
		}

		// css
		if ( ! wp_style_is( 'codemirror', 'registered' ) ) {
			wp_register_style(
				'codemirror',
				TB_URL . 'public/vendor/codemirror/codemirror.css',
				array(),
				'5.46.0'
			);
		}
		// Prevent issues with themes styling the 'pre' tags in the blocks editor, like Storefront.
		wp_add_inline_style(
			'codemirror',
			'.editor-styles-wrapper .CodeMirror pre {'
				. 'padding: 0 4px;'
				. '-moz-border-radius: 0; -webkit-border-radius: 0; border-radius: 0;'
				. 'border-width: 0;'
				. 'background: transparent;'
				. 'font-family: inherit;'
				. 'font-size: inherit;'
				. 'margin: 0;'
				. 'white-space: pre;'
				. 'word-wrap: normal;'
				. 'line-height: inherit;'
				. 'color: inherit;'
				. 'z-index: 2;'
				. 'position: relative;'
				. 'overflow: visible;'
				. '-webkit-tap-highlight-color: transparent;'
				. '-webkit-font-variant-ligatures: contextual;'
				. 'font-variant-ligatures: contextual;'
			. '}'
			. '.editor-styles-wrapper .CodeMirror-wrap pre {'
				. 'word-wrap: break-word;'
				. 'white-space: pre-wrap;'
				. 'word-break: normal;'
			. '}'
			. '.editor-styles-wrapper .CodeMirror-rtl pre { direction: rtl; }'
			. '.editor-styles-wrapper .CodeMirror-measure pre { position: static; }'
		);
	}
}
