<?php

namespace OTGS\Toolset\CRED\Model;

/**
 * Form models factory.
 * 
 * @since 2.1
 */
class Factory {
	const CLASS_PATTERN = 'CRED_%s_Form_%s';

	/**
	 * Instantiate a model by its domain, name and arguments.
	 *
	 * @param string $domain
	 * @param string $name
	 * @param array $args
	 * 
	 * @since 2.1
	 */
	public function build( $domain, $name, $args = null ) {
		$class_name = $this->build_class_name( $domain, $name );

		if ( class_exists( $class_name ) ) {
			return new $class_name( $args );
		} else {
			throw new \Exception( sprintf( 'Class with name %s does not exist!', $class_name ) );
		}
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
	protected function build_class_name( $domain, $name ) {
		return sprintf( self::CLASS_PATTERN, $domain, $name );
	}
}