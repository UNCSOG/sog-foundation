<?php

namespace OTGS\Toolset\Layouts\ClassesAuto\Gutenberg\PrivateLayout;

/**
 * Class WPDD_Gutenberg_Editor_Condition_Post_Editor
 * @since 2.5.2
 * @author Riccardo Strobbia
 * A class to detect if we are in a Gutenberg editor page either to create or edit a post
 */
class ConditionPostEditor extends \Toolset_Condition_Plugin_Gutenberg_Active {

	/**
	 * @var string
	 */
	protected $page = 'post.php';
	/**
	 * @var string
	 */
	protected $page_new = 'post-new.php';
	/**
	 * @var string
	 */
	protected $query_var = 'action';
	/**
	 * @var string
	 */
	protected $var_value = 'edit';
	/**
	 * @var string
	 *  Action added in Gutenberg editor page only: https://github.com/WordPress/gutenberg/issues/1316
	 */
	protected $default_action = 'the_post';
	/**
	 * @var string
	 */
	private $pagenow;
	/**
	 * @var array
	 */
	private $exclude_post_types = array( 'view', 'view-template', 'wpa-helper' );

	/**
	 * Condition_Post_Editor constructor.
	 *
	 * @param $pagenow
	 */
	public function __construct( $pagenow ) {
		$this->pagenow = $pagenow;
	}

	/**
	 * @return bool
	 */
	public function is_met() {
		if ( ! parent::is_met() ) {
			return false;
		}

		if ( ! did_action( $this->default_action ) ) {
			return false;
		}

		if ( $this->pagenow === $this->page_new ) {
			return true;
		}

		if (
			$this->pagenow === $this->page
			&& $this->var_value === toolset_getget( $this->query_var )
			&& $this->can_post_type_have_private_layout()
		) {
			return true;
		}

		return false;
	}

	/**
	 * Make this condition fail so we do not add a button to generate a private layout
	 * when editing a View, CT or WPA.
	 *
	 * @return bool
	 * @since 2.6.3
	 */
	private function can_post_type_have_private_layout() {
		$post_type = get_post_type( toolset_getget( 'post' ) );

		if ( false === $post_type ) {
			return false;
		}

		return ( ! in_array( $post_type, $this->exclude_post_types, true ) );
	}
}
