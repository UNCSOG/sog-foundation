<?php

namespace OTGS\Toolset\CRED\Controller\CommentsManager;

/**
 * Comments manager repository.
 *
 * @since 2.4
 */
class Repository {

	/**
	 * Cache controllers per form type.
	 *
	 * @var array
	 */
	private $cache = array();

	/**
	 * @var \OTGS\Toolset\CRED\Model\Forms\DataFactory
	 */
	public $forms_data_factory;

	/**
	 * Class constructor.
	 */
	public function __construct( \OTGS\Toolset\CRED\Model\Forms\DataFactory $forms_data_factory ) {
		$this->cache = array(
			\OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE => null,
			\OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE => null,
			\CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE => null,
		);
		$this->forms_data_factory = $forms_data_factory;
	}

	/**
	 * Get a comments manager controller per form type.
	 *
	 * @param \WP_Post $form
	 * @return ICommentsManager
	 * @since 2.4
	 */
	public function get_controller( \WP_Post $form ) {
		$cached_controller = toolset_getarr( $this->cache, $form->post_type, null );

		if ( null !== $cached_controller ) {
			return $cached_controller;
		}

		return $this->cache_controller( $form );
	}

	/**
	 * Generate a cached controller per form type.
	 *
	 * @param \WP_Post $form
	 * @return ICommentsManager
	 * @since 2.4
	 */
	private function cache_controller( \WP_Post $form ) {
		switch ( $form->post_type ) {
			case \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE:
				$this->cache[ $form->post_type ] = new Post( $this->forms_data_factory );
				break;
			case \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE:
				$this->cache[ $form->post_type ] = new User( $this->forms_data_factory );
				break;
			case \CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE:
				$this->cache[ $form->post_type ] = new Association();
				break;
		}

		return toolset_getarr( $this->cache, $form->post_type, null );
	}

}
