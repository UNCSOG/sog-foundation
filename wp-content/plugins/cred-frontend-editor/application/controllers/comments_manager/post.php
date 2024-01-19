<?php

namespace OTGS\Toolset\CRED\Controller\CommentsManager;

/**
 * Comments manager for post forms.
 *
 * @since 2.4
 */
class Post implements ICommentsManager {

	/**
	 * Store whether individual forms, indexed by ID, should hide comments or not.
	 *
	 * @var bool[]
	 */
	private $cache = array();

	/**
	 * @var \OTGS\Toolset\CRED\Model\Forms\DataFactory
	 */
	private $forms_data_factory;

	/**
	 * Constructor
	 *
	 * @param \OTGS\Toolset\CRED\Model\Forms\DataFactory $forms_data_factory
	 */
	public function __construct(
		\OTGS\Toolset\CRED\Model\Forms\DataFactory $forms_data_factory
	) {
		$this->forms_data_factory = $forms_data_factory;
	}

	/**
	 * Check whether a given form should hide comments.
	 *
	 * @param int $form_id
	 * @return bool
	 * @since 2.4
	 */
	public function maybe_disable_comments( $form_id ) {
		$cached_result = toolset_getarr( $this->cache, $form_id, null );

		if ( null !== $cached_result ) {
			return $cached_result;
		}

		$form_data = $this->forms_data_factory->get_form_data( $form_id, \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE );

		if ( null === $form_data ) {
			return false;
		}

		$this->cache[ $form_id ] = $form_data->hasHideComments();

		return $this->cache[ $form_id ];
	}
}
