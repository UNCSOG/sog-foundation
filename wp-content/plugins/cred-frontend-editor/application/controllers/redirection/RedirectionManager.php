<?php

namespace OTGS\Toolset\CRED\Controller\Redirection;

/**
 * Control the redirection after form submission.
 *
 * Note that when forms are submitted without AJAX, all post-submit actions are considered a redirection.
 */
class RedirectionManager {

	const ACTION_MESSAGE = 'message';
	const ACTION_FORM = 'form';

	const ACTION_POST = 'post';
	const ACTION_CUSTOM_POST = 'custom_post';
	const ACTION_PAGE = 'page';

	/** @var int */
	private $object_id;

	/** @var int */
	private $form_id;

	/** @var \CRED_Form_Data */
	private $form_data;

	/** @var bool */
	private $is_ajax;

	/** @var RedirectionHelper */
	private $redirection_helper;

	/**
	 * Redirection manager constructor.
	 *
	 * @param int $object_id
	 * @param int $form_id
	 * @param \CRED_Form_Data $form_data
	 * @param bool $is_ajax
	 * @param null|RedirectionHelper $redirection_helper
	 */
	public function __construct(
		$object_id,
		$form_id,
		\CRED_Form_Data $form_data,
		$is_ajax,
		$redirection_helper = null
	) {
		$this->object_id = $object_id;
		$this->form_id = $form_id;
		$this->form_data = $form_data;
		$this->is_ajax = $is_ajax;
		$this->redirection_helper = ( null === $redirection_helper )
			? new RedirectionHelper()
			: $redirection_helper;
	}

	/**
	 * Get a list of actions that trigger natural redirections,
	 * because they aim to load another page.
	 *
	 * @return string[]
	 */
	public function get_native_redirection_actions() {
		return array(
			self::ACTION_POST,
			self::ACTION_CUSTOM_POST,
			self::ACTION_PAGE,
		);
	}

	/**
	 * Get a list of actions that trigger natural reload pseudo-redirections,
	 * to re-print the form or just to display a message.
	 *
	 * @return string[]
	 */
	public function get_reload_redirection_actions() {
		return array(
			self::ACTION_MESSAGE,
			self::ACTION_FORM,
		);
	}

	/**
	 * Localized permalink given a post ID.
	 *
	 * @param int $target_id
	 * @param string|null $target_type
	 * @return string
	 */
	private function get_permalink( $target_id, $target_type = null ) {
		$target_type = ( null === $target_type ) ? \get_post_type( $target_id ) : $target_type;
		$target_id = apply_filters( 'translate_object_id', (int) $target_id, $target_type, true, null );
		return get_permalink( $target_id );
	}

	/**
	 * Build an URL to redirect to.
	 *
	 * @param array $add_query_args
	 * @param array $remove_query_args
	 * @return string
	 * @todo Review this to avod using $_SERVER and use add/remove_query_arg.
	 */
	private function get_current_url( $add_query_args = array(), $remove_query_args = array() ) {
		$request_uri = esc_html( $_SERVER["REQUEST_URI"] );
		$query_vars = array();

		if ( ! empty( $add_query_args ) ) {
			$request_uri = explode('?', $request_uri, 2);
			$request_uri = $request_uri[0];

			parse_str( $_SERVER['QUERY_STRING'], $query_vars );
			if ( empty( $query_vars ) ) {
				$query_vars = array();
			}

			foreach ( $add_query_args as $key => $value) {
				$query_vars[ $key ] = $value;
			}
		}

		if ( ! empty( $remove_query_args ) ) {
			foreach ( $query_vars as $key => $value ) {
				if ( isset( $remove_query_args[ $key ] ) ) {
					unset( $query_vars[ $key ] );
				}
			}
		}

		if ( ! empty( $query_vars ) ) {
			$request_uri .= '?' . http_build_query( $query_vars, '', '&' );
		}

		return $request_uri;
	}

	/**
	 * Get the URL to redirect to, based on the action.
	 *
	 * @param string $action
	 * @return string|false
	 */
	public function get_redirection_url( $action ) {
		switch ( $action ) {
			case self::ACTION_MESSAGE:
				if ( $this->is_ajax ) {
					// Fake a GET parameter and do nothing more.
					// Hence the form message will be used.
					$_GET[ '_success_message' ] = $this->redirection_helper->get_unique_id( $this->form_id );
					return false;
				}
				// Generate the URL to reload to.
				// Hence a CRED_Generic_Response will be generated and shown.
				return $this->get_current_url(
					array(
						'_tt' => $this->redirection_helper->get_time(),
						'_success_message' => $this->redirection_helper->get_unique_id( $this->form_id ),
						'_target' => $this->object_id,
					)
				) . '#cred_form_' . $this->redirection_helper->get_unique_id( $this->form_id );
			case self::ACTION_FORM:
				if ( $this->is_ajax ) {
					// Return false.
					// Hence the form message will be used.
					return false;
				}
				// Re-printing the form without AJAX means redirecting to a crafted URL with specific parameters.
				return $this->get_current_url(
					array(
						'_tt' => $this->redirection_helper->get_time(),
						'_success' => $this->redirection_helper->get_unique_id( $this->form_id ),
						'_target' => $this->object_id,
					)
				) . '#cred_form_' . $this->redirection_helper->get_unique_id( $this->form_id );
			case self::ACTION_POST:
				// Redirect to the post that the form just submitted.
				return $this->get_permalink( $this->object_id );
			case self::ACTION_CUSTOM_POST:
				// Redirect to a custom post.
				$fields = $this->form_data->getFields();
				$redirect_post_id = (int) toolset_getarr( $fields['form_settings']->form, 'action_post', 0 );
				if ( 0 !== $redirect_post_id ) {
					return $this->get_permalink( $redirect_post_id );
				}
				return false;
			case self::ACTION_PAGE:
				$fields = $this->form_data->getFields();
				$redirect_page_id = (int) toolset_getarr( $fields['form_settings']->form, 'action_page', 0 );
				if ( 0 !== $redirect_page_id ) {
					return $this->get_permalink( $redirect_page_id );
				}
				return false;
		}

		/**
		 * Let third parties define a redirection URL based on the current action.
		 *
		 * @param bool|string URL to redirect to, false otherwise.
		 * @param string Action to execute.
		 * @return bool|string
		 */
		return apply_filters( 'cred_get_form_submit_custom_redirect_url', false, $action );
	}

}
