<?php

namespace OTGS\Toolset\Views\Controller\Shortcode\Resolver;

/**
 * Generate and store IResolver instances on demand.
 *
 * @since 3.3.0
 */
class Store {

	/** @var array */
	private $pantry = array();

	/**
	 * Generate a resolver.
	 *
	 * @param string $resolver Resolver slug.
	 * @return IResolver
	 * @codeCoverageIgnore
	 */
	private function generate_resolver( $resolver ) {
		$dic = apply_filters( 'toolset_dic', false );

		switch ( $resolver ) {
			case AlternativeSyntax::SLUG:
				$this->pantry[ $resolver ] = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\AlternativeSyntax' );
				break;
			case Formatting::SLUG:
				$this->pantry[ $resolver ] = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\Formatting' );
				break;
			case Iterators::SLUG:
				$this->pantry[ $resolver ] = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\Iterators' );
				break;
			case Internals::SLUG:
				$this->pantry[ $resolver ] = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\Internals' );
				break;
			case Conditionals::SLUG:
				$this->pantry[ $resolver ] = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\Conditionals' );
				break;
			case HtmlAttributes::SLUG:
				$this->pantry[ $resolver ] = $dic->make( '\OTGS\Toolset\Views\Controller\Shortcode\Resolver\HtmlAttributes' );
				break;
			default:
				$this->pantry[ $resolver ] = null;
				break;
		}

		return $this->pantry[ $resolver ];
	}

	/**
	 * Get a resolver.
	 *
	 * @param string $resolver Resolver slug.
	 * @return IResolver|null
	 * @codeCoverageIgnore
	 */
	public function get_resolver( $resolver ) {
		if ( array_key_exists( $resolver, $this->pantry ) ) {
			return $this->pantry[ $resolver ];
		}

		return $this->generate_resolver( $resolver );
	}

}
