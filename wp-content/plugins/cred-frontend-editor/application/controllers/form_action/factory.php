<?php

namespace OTGS\Toolset\CRED\Controller\FormAction;

/**
 * Factory to generate form action controllers.
 *
 * @since 2.1.2
 */
class Factory {

	/**
	 * Build a post form controller for message actions.
	 *
	 * @return \OTGS\Toolset\CRED\Controller\Forms\Post\Action\Message
	 *
	 * @since 2.1.2
	 */
	public function get_for_post_message() {
		return new \OTGS\Toolset\CRED\Controller\Forms\Post\Action\Message();
	}

	/**
	 * Build an user form controller for message actions.
	 *
	 * @return \OTGS\Toolset\CRED\Controller\Forms\User\Action\Message
	 * .2
	 * @since 2.1
	 */
	public function get_for_user_message() {
		return new \OTGS\Toolset\CRED\Controller\Forms\User\Action\Message();
	}

	/**
	 * Returns the message controller for a form type
	 *
	 * @param string $form_type Form type
	 * @return \OTGS\Toolset\CRED\Controller\FormAction\Message\Base
	 */
	public function get_message_controller_by_form_type( $form_type ) {
		switch( $form_type ) {
			case \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE:
				return $this->get_for_post_message();
			case \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE:
				return $this->get_for_user_message();
		}
	}
}
