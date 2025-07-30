<?php

namespace OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain;

use OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Common\Domain\TranslationService;

/**
 * Class CustomSearchFilter
 *
 * Registers the label of the Search input, no matter if a core filter (like "Title") or Types field filters,
 * to the page translation package and takes care of replacing the the translated string in the translated post.
 *
 * @package OTGS\Toolset\Views\Models\Translation\RegisterAndStore\Block\Domain
 *
 * @since TB 1.3
 */
class CustomSearchFilter implements ITranslatableBlock{
	/** @var Block */
	private $block;

	/** @var TranslationService  */
	private $translation_service;

	/** @var string[] */
	private $shortcodes_with_translatable_content = [
		'wpv-control-post-product-onsale',
		'wpv-control-post-product-price',
	];

	public function __construct( Block $block, TranslationService $translation_service) {
		$this->block = $block;
		$this->translation_service = $translation_service;
	}

	/**
	 * Register label of the search filters.
	 *
	 * @inheritDoc
	 */
	public function register_strings_to_translate( $strings_to_translate = [] ) {
		while( $line = $this->block->content_lines()->next() ) {
			$strings_to_translate = $this->add_filter_label( $strings_to_translate, $line );
			$strings_to_translate = $this->add_filter_content( $strings_to_translate, $line );
		}

		return $strings_to_translate;
	}

	/**
	 * Store translated labels to translated post.
	 *
	 * @inheritDoc
	 */
	public function store_translated_strings( \WP_Block_Parser_Block $block, $translations, $lang ) {
		while( $line = $this->block->content_lines()->next() ) {
			$this->store_filter_label( $block, $translations, $lang, $line );
			$this->store_filter_content( $block, $translations, $lang, $line );
		}

		return $block;
	}

	/**
	 * Find filter label and adds it to translation.
	 *
	 * @param $strings_to_translate
	 * @param $line
	 *
	 * @return array
	 */
	private function add_filter_label( $strings_to_translate, $line ) {
		while( $filter_label = $this->find_label( $line ) ) {
			// Add filter label to translation.
			$strings_to_translate[] = $this->translation_service->get_line_object(
				$filter_label,
				__( 'Custom search filter label', 'wpv-views' ),
				$this->block->name()
			);

			// Remove everything from $line before and including $filter_label.
			$line_without_label = substr( $line, strpos( $line, $filter_label ) + strlen( $filter_label ) );

			if( $line_without_label === $line ) {
				// Make sure not running in an endless loop.
				break;
			}

			$line = $line_without_label;
		}


		return $strings_to_translate;
	}

	/**
	 * Find filter content and adds it to translation.
	 *
	 * @param $strings_to_translate
	 * @param $line
	 *
	 * @return array
	 */
	private function add_filter_content( $strings_to_translate, $line ) {
		while ( $filter_content = $this->find_content( $line ) ) {
			// Add filter label to translation.
			$strings_to_translate[] = $this->translation_service->get_line_object(
				$filter_content,
				__( 'Custom search filter label', 'wpv-views' ),
				$this->block->name()
			);

			// Remove everything from $line before and including $filter_content.
			$line_without_content = substr( $line, strpos( $line, $filter_content ) + strlen( $filter_content ) );

			if ( $line_without_content === $line ) {
				// Make sure not running in an endless loop.
				break;
			}

			$line = $line_without_content;
		}


		return $strings_to_translate;
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param $translations
	 * @param $lang
	 * @param $line
	 */
	private function store_filter_label( \WP_Block_Parser_Block $block, $translations, $lang, $line ) {
		if( ! $filter_label = $this->find_label( $line ) ) {
			return;
		}

		$block_has_attributes = property_exists( $block, 'attrs' ) && is_array( $block->attrs );
		$original_line = $line;
		$translated_line = $line;
		$translated_block_attr_label_text = [];
		$translated_block_attr_content = $block_has_attributes && isset( $block->attrs['content'] )
				? $block->attrs['content']
				: false;

		// Loop over labels.
		while( $filter_label = $this->find_label( $line ) ) {
			$filter_label_translated = $this->translation_service->get_translated_text_by_translations(
				$filter_label,
				$translations,
				$this->block->name(),
				$lang
			);

			if( ! empty( $filter_label_translated ) ) {
				$translated_block_attr_label_text[] = $filter_label_translated;

				if( $translated_block_attr_content ) {
					$translated_block_attr_content = preg_replace(
						'#(<label .*?class=".*?wpv-custom-search-filter.*?>)('.$filter_label.')(</label>)#ism',
						'$1' . $filter_label_translated . '$3',
						$translated_block_attr_content
					);
				}

				$translated_line  = preg_replace(
					'#(<label .*?class=".*?wpv-custom-search-filter.*?>)('.$filter_label.')(</label>)#ism',
					'$1' . $filter_label_translated . '$3',
					$translated_line
				);
			}

			// Remove everything from $line before and including $filter_label.
			$line_without_label = substr( $line, strpos( $line, $filter_label ) + strlen( $filter_label ) );

			if( $line_without_label === $line ) {
				// Make sure not running in an endless loop.
				break;
			}

			$line = $line_without_label;
		}

		if( $original_line === $translated_line ) {
			// No translations found.
			return;
		}

		if( $block_has_attributes ) {
			if( isset( $block->attrs['labelText'] ) ) {
				$block->attrs['labelText'] = $translated_block_attr_label_text;
			}

			if( isset( $block->attrs['content'] ) ) {
				$block->attrs['content'] = $translated_block_attr_content;
			}
		}

		// WPML replaces > with &gt; which kills our relationships.
		$block->innerHTML = preg_replace(
			"/(.*ancestors='.*)?&gt;(.*)?'(.*)/",
			"$1>$2'$3",
			$block->innerHTML
		);

		$block->innerHTML = str_replace( $original_line, $translated_line, $block->innerHTML );
	}

	/**
	 * @param \WP_Block_Parser_Block $block
	 * @param $translations
	 * @param $lang
	 * @param $line
	 */
	private function store_filter_content( \WP_Block_Parser_Block $block, $translations, $lang, $line ) {
		if ( ! $filter_content = $this->find_content( $line ) ) {
			return;
		}

		$original_line = $line;
		$translated_line = $line;

		// Loop over labels.
		while ( $filter_content = $this->find_content( $line ) ) {
			$filter_content_translated = $this->translation_service->get_translated_text_by_translations(
				$filter_content,
				$translations,
				$this->block->name(),
				$lang
			);

			if ( ! empty( $filter_content_translated ) ) {
				foreach ( $this->shortcodes_with_translatable_content as $shortcode ) {
					$translated_line  = preg_replace(
						'#(\['.$shortcode.'.*?\])('.preg_quote($filter_content, '\\').')(\[/'.$shortcode.'\])#ism',
						'$1' . $filter_content_translated . '$3',
						$translated_line
					);
				}
			}

			// Remove everything from $line before and including $filter_content.
			$line_without_content = substr( $line, strpos( $line, $filter_content ) + strlen( $filter_content ) );

			if ( $line_without_content === $line ) {
				// Make sure not running in an endless loop.
				break;
			}

			$line = $line_without_content;
		}

		if ( $original_line === $translated_line ) {
			// No translations found.
			return;
		}

		if ( false !== strpos( $block->innerHTML, '—' ) ) {
			// innerHTML contains unescaped dashes, that both $original_line and $translated_line got escaped as &mdash;
			// Let's normalize all of them as escaped anyway.
			$block->innerHTML = str_replace( '—', '&mdash;', $block->innerHTML );
			$original_line = str_replace( '—', '&mdash;', $original_line );
			$translated_line = str_replace( '—', '&mdash;', $translated_line );
		}

		$block->innerHTML = str_replace( $original_line, $translated_line, $block->innerHTML );
	}

	/**
	 * @param string $line Short text, using preg_match() on them is totally fine.
	 *
	 * @return string
	 */
	private function find_label( $line ) {
		if( ! is_string( $line ) ) {
			throw new \InvalidArgumentException( '$text must be a string.' );
		}

		if( preg_match( '#\[wpml-string.*?\](.*?)\[\/wpml-string\]#ism', $line ) ) {
			// Old structure using the wpml-string shhortcode.
			return '';
		}

		if( preg_match( '#<label .*?class=".*?wpv-custom-search-filter.*?>(.*?)</label>#ism', $line, $matches ) ) {
			return $matches[1];
		}

		// No label found.
		return '';
	}

	/**
	 * @param string $line Short text, using preg_match() on them is totally fine.
	 * @return string
	 */
	private function find_content( $line ) {
		foreach ( $this->shortcodes_with_translatable_content as $shortcode ) {
			if ( preg_match( '#\['.$shortcode.'(.*?)\](.*?)\[\/'.$shortcode.'\]#ism', $line, $matches ) ) {
				return $matches[2];
			}
		}

		// No content found.
		return '';
	}
}
