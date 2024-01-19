<?php

namespace OTGS\Toolset\CRED\Controller\Forms;

use OTGS\Toolset\CRED\Controller\Factory as ControllerFactory;
use OTGS\Toolset\CRED\Model\Factory as ModelFactory;


/**
 *Fforms main controller.
 *
 * @since 2.1
 */
class Main {

	const DOMAIN = '';

	const SHORTCODE_NAME_FORM_FIELD = 'cred_field';

	/**
	 * @var \ControllerFactory
	 *
	 * @since 2.1
	 */
	protected $controller_factory = null;

	/**
	 * @var \ModelFactory
	 *
	 * @since 2.1
	 */
	protected $model_factory = null;

	/**
	 * @var boolean
	 *
	 * @since 2.1
	 */
	protected $condition_post_request = false;

	/**
	 * @var boolean
	 *
	 * @since 2.1
	 */
	protected $condition_front_end = false;

	/**
	 * @var boolean
	 *
	 * @since 2.1
	 */
	protected $condition_ajax_request = false;

	/**
	 * @var boolean
	 *
	 * @since 2.1
	 */
	protected $condition_back_end = false;

	public function __construct(
        ControllerFactory $controller_factory,
        ModelFactory $model_factory
    ) {
		$this->controller_factory = $controller_factory;
		$this->model_factory = $model_factory;
	}

	/**
	 * Initialize the forms controller.
	 *
	 * @since 2.1
	 */
	public function initialize() {
		add_action( 'init', array( $this, 'run' ) );
	}

	/**
	 * Initialize forms.
	 *
	 * @since 2.1
	 */
	public function run() {
		$this->add_hooks();
		$this->set_routing_conditions();
		return $this->route();
	}

	/**
	 * Load the right controller depending on the current condition.
	 *
	 * @return OTGS\Toolset\CRED\Controller\Forms\Base|null
	 *
	 * @since 2.1
	 */
	protected function route() {
		try {

			$controller = null;

			if (  $this->condition_ajax_request ) {
				return $controller;
			}
			else if ( $this->condition_back_end ) {
				$controller = $this->controller_factory->build( static::DOMAIN, 'Backend', $this->model_factory );
			} else if ( $this->condition_front_end && ! $this->condition_post_request ) {
				$controller = $this->controller_factory->build( static::DOMAIN, 'Front_End', $this->model_factory );
			} else if ( $this->condition_front_end && $this->condition_post_request ) {
				$this->controller_factory->build( static::DOMAIN, 'Post_Request', $this->model_factory );
				$controller = $this->controller_factory->build( static::DOMAIN, 'Front_End', $this->model_factory );
			}
			return $controller;
		} catch( \Exception $exception ) {
			error_log( $exception->getMessage() );
			return null;
		}
	}

	/**
	 * Add controller hooks.
     *
     * @since 2.1
	 */
	protected function add_hooks() {
		// Add hooks if necessary here
	}

	/**
	 * Set the condition on whether this is an AJAX request.
	 *
	 * @since 2.1
	 */
    protected function set_condition_is_ajax_request() {
        $this->condition_ajax_request = ( defined( 'DOING_AJAX' ) && DOING_AJAX );
    }

	/**
	 * Set the condition on whether loading the backend request.
	 *
	 * @since 2.1
	 */
	protected function set_condition_is_back_end() {
		if (
            is_admin()
            && ! $this->condition_ajax_request
        ) {
			$this->condition_back_end = true;
		}
	}

	/**
	 * Set the conditions depending on the current request.
	 *
	 * @since 2.1
	 */
	protected function set_routing_conditions() {
        $this->set_condition_is_ajax_request();
		$this->set_condition_is_back_end();
	}
}
