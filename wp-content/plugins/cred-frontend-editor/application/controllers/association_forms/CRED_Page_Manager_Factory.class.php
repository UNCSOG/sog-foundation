<?php
class CRED_Page_Manager_Factory{
	const CLASS_PREFIX = 'CRED_Association_Form_%s_Page';


	public function build( $name, $model = null, $helper = null, $repository = null ){
		$class_name = $this->build_class_name( $name );

		if( class_exists( $class_name ) ){
			return new $class_name( $model, $helper, $repository );
		} else {
			throw new Exception( sprintf( 'Class with name %s does not exist!', $class_name ));
		}
	}

	/**
	 * @param $type
	 *
	 * @return string
	 */
	private function build_class_name( $name ){
		return sprintf(self::CLASS_PREFIX, $name );
	}
}