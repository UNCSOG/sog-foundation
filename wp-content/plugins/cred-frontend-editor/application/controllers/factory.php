<?php

namespace OTGS\Toolset\CRED\Controller;

/**
 * Form controllers factory.
 * 
 * @since 2.1
 */
class Factory {
	const CLASS_PATTERN = 'OTGS\Toolset\CRED\Controller\Forms\%s\%s';
	const LEGACY_CLASS_PATTERN = 'CRED_%s_Form_%s';

	/**
	 * Build a post form controller for a given context.
	 *
	 * @param string $domain
	 * @param string $name
	 * @param object|null $model_factory Association forms demand a model factory
     * @param object|null $helper Association forms demand an API helper which we are forced to pass here
	 * 
	 * @return object
	 * 
	 * @since 2.1
	 */
	public function build( $domain, $name, $model_factory = null, $helper = null ) {
		$domain = ucfirst( $domain );
		$name = ucfirst( $name );
		
		$class_name = $this->build_class_name( $domain, $name );
		if ( class_exists( $class_name ) ) {
			return new $class_name( $model_factory, $helper );
		}
		
		$legacy_class_name = $this->build_legacy_class_name( $domain, $name );
		if ( class_exists( $legacy_class_name ) ) {
			return new $legacy_class_name( $model_factory, $helper );
		}

		throw new \Exception( sprintf( 'No class with names %s or %s!', $class_name, $legacy_class_name ) );
	}

	/**
	 * Build a class name following a pattern.
	 * 
	 * @param string $domain
	 * @param string $name
	 *
	 * @return string
	 * 
	 * @since 2.1
	 */
	private function build_class_name( $domain, $name ) {
		return sprintf( self::CLASS_PATTERN, $domain, $name );
	}

	/**
	 * Build the classname for a model given a domain and name.
	 * 
	 * @param string $domain
	 * @param string $name
	 *
	 * @return string
	 * 
	 * @since 2.1
	 */
	private function build_legacy_class_name( $domain, $name ) {
		return sprintf( self::LEGACY_CLASS_PATTERN, $domain, $name );
	}
}