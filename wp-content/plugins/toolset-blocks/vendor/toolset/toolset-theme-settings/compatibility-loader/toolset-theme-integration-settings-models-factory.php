<?php

/**
 * Class Toolset_Theme_Integration_Settings_Models_Factory
 * @author: Riccardo
 * @since: 2.5
 * Singleton factory class to programmatically build Models from Theme Settings JSON based on settings type
 */
class Toolset_Theme_Integration_Settings_Models_Factory{

	private static $instance;

	const CLASS_PREFIX = 'Toolset_Theme_Integration_Settings_Model_';

	/**
	 * @param $type
	 * @param $item
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function build( $type, $name ) {
		$class_name = $this->build_class_name( $type );

		if( class_exists( $class_name ) ){
			return new $class_name( $type, $name );
		} else {
			throw new Exception( sprintf( '%s does not exist!', $class_name) );
		}
	}

	/**
	 * @param $type
	 *
	 * @return string
	 */
	private function build_class_name( $type ){
		return self::CLASS_PREFIX . $type;
	}

	/**
	 * @return Toolset_Theme_Integration_Settings_Collections_Factory
	 */
	public static function getInstance(  )
	{
		if (!self::$instance)
		{
			self::$instance = new Toolset_Theme_Integration_Settings_Models_Factory(  );
		}

		return self::$instance;
	}

	/**
	 * for tests purposes only, to be used in Test::tearDown() method when testing this class
	 */
	public static function tearDown(){
		self::$instance = null;
	}
}