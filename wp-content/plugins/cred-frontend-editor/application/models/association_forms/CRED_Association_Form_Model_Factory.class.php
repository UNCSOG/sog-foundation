<?php
class CRED_Association_Form_Model_Factory {

	const CLASS_PREFIX = 'CRED_Association_Form_%s';

	/**
	 * Instantiate a model by its name and arguments.
	 *
	 * @param string $name
	 * @param array $args
	 * 
	 * @since 2.1
	 */
	public function build( $name, $args = null ) {
		$class_name = $this->build_class_name( $name );

		if ( class_exists( $class_name ) ) {
			return new $class_name( $args );
		} else {
			throw new Exception( sprintf( 'Class with name %s does not exist!', $class_name ) );
		}
	}

	/**
	 * @param string
	 *
	 * @return string
	 */
	protected function build_class_name( $name ) {
		return sprintf( self::CLASS_PREFIX, $name );
	}
}