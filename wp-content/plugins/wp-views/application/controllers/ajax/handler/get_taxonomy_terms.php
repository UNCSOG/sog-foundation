<?php

use OTGS\Toolset\Views\Model\Wordpress\REST\Taxonomy;
use OTGS\Toolset\Views\Model\Wordpress\REST\Terms;
use const OTGS\Toolset\Views\UserCapabilities\EDIT_VIEWS;

/**
 * Ajax handler for internal access to WP Taxonomy terms API
 */
class WPV_Ajax_Handler_Get_Taxonomy_Terms extends Toolset_Ajax_Handler_Abstract {

	/**
	 * @var Taxonomy
	 */
	private $wordpress_rest_taxonomy;


	/**
	 * WPV_Ajax_Handler_Get_View_Block_Preview constructor.
	 *
	 * @param \WPV_ajax $ajax_manager
	 * @param Taxonomy|null $wordpress_rest_taxonomy
	 */
	public function __construct(
		\WPV_ajax $ajax_manager,
		Taxonomy $wordpress_rest_taxonomy = null
	) {
		parent::__construct( $ajax_manager );
		$this->wordpress_rest_taxonomy = $wordpress_rest_taxonomy ?: new Taxonomy( $_GET );
	}

	/**
	 * Process ajax call, gets the action and executes the proper method.
	 *
	 * @param array $arguments Original action arguments.
	 *
	 * @return void
	 */
	public function process_call( $arguments ) {

		$this->ajax_begin( array(
			'nonce' => WPV_Ajax::CALLBACK_GET_TAXONOMY_TERMS,
			'parameter_source' => 'get',
			'capability_needed' => EDIT_VIEWS,
		) );

		$data = array( 'terms' => [] );

		$items = $this->wordpress_rest_taxonomy->get_items();

		foreach ( $items as $item ) {
			if ( isset( $item['name'] ) ) {
				$item['name'] = html_entity_decode( $item['name'] );
			}
			$data['terms'][] = $item;
		}

		$this->ajax_finish(
			$data,
			true
		);
	}

}
