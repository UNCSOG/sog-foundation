<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Delete\Gui;

use OTGS\Toolset\CRED\Model\Shortcode\Delete\Association as Shortcode;

/**
 * Association delete shortcode GUI class.
 *
 * @since m2m
 */
class Association extends \CRED_Shortcode_Base_GUI {
	
	/**
	* @var array
	*/
	private $parameters;
	
	/**
	* @var array
	*/
	private $ovverides;
	
	/**
	* @var string
	*/
	private $pagenow;
	
	/**
	* @var string
	*/
	private $page;
	
	/**
	 * @van Toolset_Ajax
	 */
	private $toolset_ajax;
	
	private function can_register() {
		if ( 'views-editor' == toolset_getget( 'page' ) ) {
			return true;
		}
		return false;
	}
	
	/**
	 * Register the shortcode in the GUI API.
	 *
	 * @param $cred_shortcodes
	 *
	 * @return array
	 *
	 * @sincem2m
	 */
	public function register_shortcode_dynamic_data( $cred_shortcodes ) {
		$cred_shortcodes[ Shortcode::SHORTCODE_NAME ] = array(
			'callback' => array( $this, 'get_shortcode_data' )
		);
		return $cred_shortcodes;
	}
	
	/**
	 * Get the shortcode attributes data.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	public function get_shortcode_data( $parameters = array(), $overrides = array(), $pagenow = '', $page = '' ) {
		
		$this->parameters = $parameters;
		$this->overrides  = $overrides;
		$this->pagenow    = $pagenow;
		$this->page       = $page;
		
		$this->toolset_ajax = \Toolset_Ajax::get_instance();

		if (
			'admin.php' === $this->pagenow 
			&& 'views-editor' === $this->page
		) {
			$parameters['role_items'] = isset( $parameters['role_items'] ) ? $parameters['role_items'] : '$fromViews';
		}
		
		$data = array(
			'attributes' => array(
				'options' => $this->get_shortcode_general_options(),
				'styleOptions' => $this->get_shortcode_style_options() 
			),
			'parameters' => $parameters
		);
		
		return $data;
	}
	
	/**
	 * Get the shortcode general attributes data.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	private function get_shortcode_general_options() {
		$query = new \Toolset_Relationship_Query_V2();
		$query->add( $query->origin( \Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ) );
		$relationship_definitions = $query->get_results();
		$relationship_options = array(
			'' => __( 'Select one relationship', 'wp-cred' )
		);
		foreach ( $relationship_definitions as $relationship_definition ) {
			$relationship_options[ $relationship_definition->get_slug() ] = $relationship_definition->get_display_name();
		}
		
		$related_item_options = array();
		$related_item_one_default = '$current';
		if (
			'admin.php' === $this->pagenow 
			&& 'views-editor' === $this->page
		) {
			// In the Views edit page, populate the options and default value 
			// with the ability to get the post set as parent in the relationship query filter
			$related_item_options['$fromfilter'] = __( 'The post set in the post relationship query filter', 'wp-cred' );
			$related_item_one_default = '$fromfilter';
		}
		$related_item_options['$current'] = __( 'The current post', 'wp-cred' );
		$related_item_options['toolsetCombo'] = __( 'Another specific post', 'wp-cred' );
		
		$section = array(
			'header' => __( 'Options', 'wp-cred' ),
			'fields' => array(
				'relationship' => array( 
					'label' => __( 'Delete this relationship', 'wp-cred' ),
					'type' => 'select',
					'options' => $relationship_options,
					'required' => true
				),
				'redirect' => array(
					'label' => __( 'After deleting the relationship...', 'wp-cred' ),
					'type' => 'radio',
					'options' => array(
						'none' => __( 'Do nothing', 'wp-cred' ),
						'self' => __( 'Reload the current page', 'wp-cred' ),
						'toolsetCombo' => __( 'Redirect to another page', 'wp-cred' )
					),
					'defaultValue' => 'none'
				),
				'toolsetCombo:redirect' => array(
					'type' => 'ajaxSelect2',
					'action' => $this->toolset_ajax->get_action_js_name( \Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ),
					'nonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ),
					'placeholder' => __( 'Search for a page', 'wp-cred' ),
					'hidden' => true
				)
			)
		);
		
		return $section;
	}
	
	/**
	 * Get the shortcode style attributes data.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	private function get_shortcode_style_options() {
		return array(
			'header' => __( 'Style options', 'wp-cred' ),
			'fields' => array(
				'htmltagCombo' => array(
					'type' => 'group',
					'fields' => array(
						'type' => array(
							'label' => __( 'Display element as...', 'wp-cred' ),
							'type' => 'select',
							'options' => array(
								'link' => __( 'Display as a link', 'wp-cred' ),
								'button' => __( 'Display as a button', 'wp-cred' )
							),
							'defaultValue' => 'link'
						),
						'content' => array(
							'label' => __( 'Label', 'wp-cred' ),
							'type' => 'content',
							'defaultValue' => __( 'Delete this relationship', 'wp-cred' )
						)
					)
				),
				'attributesCombo' => array(
					'type' => 'group',
					'fields' => array(
						'class' => array( 
							'label' => __( 'Class', 'wp-cred' ),
							'type' => 'text'
						),
						'style' => array( 
							'label' => __( 'Style', 'wp-cred' ),
							'type' => 'text'
						)
					),
					'description' => __( 'Include specific classnames, or add your own inline styles.', 'wp-cred' )
				)
			)
		);
	}
	
	/**
	 * Include the shortcode in the Toolset Forms dialog, inside the 'cred-extra' group.
	 *
	 * @param array  $group_data
	 * @param string $group_id
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	public function filter_shortcode_group_before_register( $group_data, $group_id ) {
		if ( ! $this->can_register() ) {
			return $group_data;
		}
		if ( 'cred-extra' === $group_id ) {
			$group_data['fields'][ Shortcode::SHORTCODE_NAME ] = array(
				'name'		=> __( 'Delete relationship', 'wp-cred' ),
				'shortcode'	=> Shortcode::SHORTCODE_NAME,
				'callback'	=> $this->get_shortcode_callback( Shortcode::SHORTCODE_NAME, __( 'Delete relationship', 'wp-cred' ) )
			);
		}
		
		return $group_data;
	}
	
}