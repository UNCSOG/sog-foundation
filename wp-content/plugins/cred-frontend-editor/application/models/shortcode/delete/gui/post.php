<?php

namespace OTGS\Toolset\CRED\Model\Shortcode\Delete\Gui;

use OTGS\Toolset\CRED\Model\Shortcode\Delete\Post as Shortcode;

/**
 * Post delete shortcode GUI class.
 *
 * @since 2.6
 */
class Post extends \CRED_Shortcode_Base_GUI {

	/**
	 * Register the shortcode in the GUI API.
	 *
	 * @param array $cred_shortcodes List of shortcodes with their registered attributes GUI data
	 * @return array
	 * @since 2.6
	 */
	public function register_shortcode_data( $cred_shortcodes ) {
		$cred_shortcodes[ Shortcode::SHORTCODE_NAME ] = array(
			'attributes' => array(
				'options' => array(
                    'header' => __( 'Options', 'wp-cred' ),
                    'fields' => array(
						'type' => array(
							'label' => __( 'Display handle', 'wp-cred' ),
							'type' => 'radio',
							'options' => array(
								'link' => __( 'Display as a link', 'wp-cred' ),
								'button' => __( 'Display as a button', 'wp-cred' ),
							),
							'defaultValue' => 'link',
						),
                        'action' => array(
							'label' => __( 'Action to perform', 'wp-cred' ),
							'type' => 'radio',
							'options' => array(
								'trash' => __( 'Trash the post', 'wp-cred' ),
								'delete' => __( 'Delete the post', 'wp-cred' ),
							),
							'defaultForceValue' => 'trash',
						),
						'text' => array(
							'label' => __( 'Handle text', 'wp-cred' ),
							'type' => 'content',
							'required' => true,
							'defaultValue' => __( 'Delete', 'wp-cred' ),
							'description' => __( 'Use placeholders like %TITLE% or %ID%.', 'wp-cred' ),
						),
						'onsuccess' => array(
							'label' => __( 'After deleting the post...', 'wp-cred' ),
							'type' => 'radio',
							'options' => array(
								'' => __( 'Do nothing', 'wp-cred' ),
								'self' => __( 'Reload the current page', 'wp-cred' ),
								'toolsetCombo' => __( 'Redirect to another page', 'wp-cred' ),
							),
							'defaultForceValue' => 'self',
						),
						'toolsetCombo:onsuccess' => array(
							'type' => 'ajaxSelect2',
							'action' => 'toolset_select2_suggest_posts_by_title',
							'nonce' => wp_create_nonce( \Toolset_Ajax::CALLBACK_SELECT2_SUGGEST_POSTS_BY_TITLE ),
							'placeholder' => __( 'Search for a page to redirect to', 'wp-cred' ),
							'hidden' => true,
						),
                    ),
				),
				'styleOptions' => array(
					'header' => __( 'Style options', 'wp-cred' ),
					'fields' => array(
						'class' => array(
							'label' => __( 'Class', 'wp-cred' ),
							'type' => 'text',
							'description' => __( 'Space-separated list of classnames that will be added to the anchor HTML tag.', 'wp-cred' ),
						),
						'style' => array(
							'label' => __( 'Style', 'wp-cred' ),
							'type' => 'text',
							'description' => __( 'Inline styles that will be added to the anchor HTML tag.', 'wp-cred' ),
						),
					),
				),
				'post-selection' => array(
					'label' => __( 'Post selection', 'wp-cred' ),
					'header' => __( 'Post selection', 'wp-cred' ),
					'fields' => array(
						'item' => array(
							'label' => __( 'Post to delete', 'wp-cred' ),
							'type' => 'postSelector',
						),
					),
				),
			),
		);
		return $cred_shortcodes;
	}

}
