<?php

abstract class WPDD_Listing_Store_Controller_Abstract implements WPDD_Listing_Store_Controller_Interface{
	protected $id = 0;
	protected $title;
	protected $group_slug = null;
	protected $store_factory;
	protected $store = null;
	protected $status = 'publish';
	protected $args = array();

	/**
	 * WPDD_Listing_Store_Controller_Abstract constructor.
	 *
	 * @param WPDD_Listing_Stores_Factory|null $store_factory
	 * @param null $group_slug
	 * @param string $status
	 * @param array $args
	 */
	public function __construct( WPDD_Listing_Stores_Factory $store_factory = null, $group_slug = null, $status = 'publish', $args = array() ) {
		$this->group_slug = $group_slug;
		$this->status = $status;
		$this->args = $args;
		$this->set_store_factory( $store_factory );
	}

	/**
	 * @return array
	 */
	public function get_layouts_list() {
		return array(
			array(
				'id'         => $this->id,
				'name'       => $this->title,
				'kind'       => 'Group',
				'items'      => $this->get_layouts_list_items(),
				'group_slug' => $this->group_slug
			),
			array(
				'id'         => $this->id * 10,
				'name'       => sprintf( __( "%s parents", 'ddl-layouts' ), $this->title ),
				'kind'       => 'Hidden Group',
				'items'      => $this->maybe_get_current_layouts_parents_list(),
				'group_slug' => sprintf('%s_parents', $this->group_slug )
			)
		);
	}

	/**
	 * @return array
	 */
	public function get_layouts_list_items(){
		$store = $this->get_store();

		if( ! $store ) return array();

		return $store->get_list();
	}

	/**
	 * @return null
	 */
	public function build_store_and_return_it(){
		$factory = $this->get_store_factory();

		if( ! $factory ) return null;

		try{
			return $factory->build();
		} catch( Exception $exception ){
			error_log( $exception->getMessage() );
			return null;
		}
	}

	/**
	 * @return array
	 */
	public function maybe_get_current_layouts_parents_list(){
		$store = $this->get_store();

		if( ! $store ) return array();

		return $store->get_current_layouts_parent_list();
	}

	/**
	 * @param null $store_factory
	 *
	 * @return null|WPDD_Listing_Stores_Factory
	 */
	public function set_store_factory( $store_factory = null ){
		if( null !== $store_factory ){
			$this->store_factory = $store_factory;
		} else {
			try{
				$this->store_factory = new WPDD_Listing_Stores_Factory( $this->group_slug, $this->status );
			} catch( InvalidArgumentException $exception ){
				error_log( $exception->getMessage() );
				$this->store_factory = null;
			}
		}
		return $this->store_factory;
	}

	/**
	 * @return null/WPDD_Listing_Page_Store_Interface
	 */
	public function get_store(){
		if( null === $this->store ){
			$this->store = $this->build_store_and_return_it();
		}
		return $this->store;
	}

	/**
	 * @return mixed/WPDD_Listing_Stores_Factory
	 */
	public function get_store_factory(){
		return $this->store_factory;
	}

	public function get_title(){
		return $this->title;
	}

	public function get_group_slug(){
		return $this->group_slug;
	}

	public function get_id(){
		return $this->id;
	}
}