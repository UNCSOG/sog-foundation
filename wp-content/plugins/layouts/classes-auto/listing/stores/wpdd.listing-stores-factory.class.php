<?php

/**
 * Class WPDD_Listing_Stores_Factory
 */
class WPDD_Listing_Stores_Factory{

	const ROOT = 'WPDD_Listing_Page_Store_%s';
	/**
	 * @var string
	 */
	protected $group;
	protected $status = 'publish';
	protected $name;

	/**
	 * WPDD_Listing_Store_Controller_Factory constructor.
	 *
	 * @param $group valid values are: post_types, archives, single, free, parents
	 * @param string $status valid values are: publish, trash
	 */
	public function __construct( $group, $status = 'publish' ) {

		if( ! $group ){
			throw new InvalidArgumentException( sprintf('$group constructor argument should be a valid non-empty string, %s given', gettype( $group ) === 'string' ? 'empty string' : gettype( $group ) ) );
		}

		$this->group = $group;
		$this->status = $status;
		$this->name = $this->build_name( $this->group );
	}

	/**
	 * @return WPDD_Listing_Store_Controller_Interface
	 * @throws Exception
	 */
	public function build(){
		$class = $this->get_name();

		if( ! $class || ! class_exists( $class ) ){
			throw new Exception( sprintf('%s is an invalid class name, we cannot build any store with it!', $class  ) );
		}

		$instance = new $class( $this->get_status() );

		if( ! $instance instanceof WPDD_Listing_Page_Store_Interface ){
			throw new Exception( sprintf('Build class should be an instance of WPDD_Listing_Page_Store_Abstract, %s was built instead!', get_class( $instance )  ) );
		}

		return $instance;
	}

	/**
	 * @param $slug
	 *
	 * @return null|string
	 */
	public function build_name( $slug ){

		if( ! $slug ){
			return null;
		}

		if( false !== strpos( $slug, '_' ) ){
			$suffix = implode('_', array_map('ucfirst', explode('_', $slug ) ) );
		} elseif( false !== strpos( $slug, '-' ) ){
			$suffix = implode('_', array_map('ucfirst', explode('-', $slug ) ) );
		} else {
			$suffix = ucfirst( $slug );
		}

		if( ! $suffix ){
			return null;
		}

		return sprintf( self::ROOT, $suffix );
	}

	/**
	 * @return string|valid
	 */
	public function get_group(){
		return $this->group;
	}

	/**
	 * @return string
	 */
	public function get_status(){
		return $this->status;
	}

	/**
	 * @return null|string
	 */
	public function get_name(){
		return $this->name;
	}
}