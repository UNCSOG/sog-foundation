<?php
namespace ToolsetCommonEs\Rest\Route\ShortcodeRender;

use ToolsetCommonEs\Library\WordPress\Actions;

class WithMeta {
	protected $meta_data = array();

	protected $shortcode;

	/** @var Actions */
	private $wp_actions;

	/**
	 * WithMeta constructor.
	 *
	 * @param Actions $wp_actions
	 */
	public function __construct( Actions $wp_actions ) {
		$this->wp_actions = $wp_actions;

		$this->register_meta_filters();
	}

	public function get_response_data( $current_post_id, $shortcode ) {
		$this->meta_data = array();

		return array(
			// todo use shortcode id if available instead of current_post_id
			'content' => $this->get_content( $current_post_id, $shortcode ),
			'meta' => $this->meta_data
		);
	}

	protected function get_content( $post_id, $shortcode ) {
		global $post;
		$post = \WP_Post::get_instance( $post_id );

		$content = do_shortcode( $shortcode );

		if( strpos( $content, '[' ) !== false ) {
			$content = do_shortcode( $content );
		}

		return $content;
	}

	private function register_meta_filters() {
		$this->wp_actions->add_action( 'wpv_before_shortcode_post_body', array( $this, 'wpv_content_template_meta' ) );
		$this->wp_actions->add_filter( 'wpv_filter_wpv_view_shortcode_output', array( $this, 'wpv_view_meta' ), 10, 2 );
		$this->wp_actions->add_filter( 'types_field_shortcode_parameters', array( $this, 'types_meta' ), 10, 2 );
	}

	public function wpv_content_template_meta() {
		$shortcode = is_null($this->shortcode) ? '' : $this->shortcode;
		if( preg_match( '#view_template=[\"\'](.*?)[\"\']#', $shortcode, $ct ) ) {
			if( $post = get_page_by_path( $ct[1], OBJECT, 'view-template' ) ) {
				$this->meta_data['post_title'] = $post->post_title;
				$this->meta_data['post_edit_link'] = admin_url( 'admin.php?page=ct-editor&ct_id=' . $post->ID );
			}
		};
	}
	public function wpv_view_meta( $content, $id ) {
		// collect meta of view
		$meta_data = $this->wp_actions->apply_filters( 'wpv_filter_wpv_get_view_settings', array(), $id );
		$this->meta_data = array_merge( $this->meta_data, $meta_data );
		$this->meta_data['post_title'] = get_the_title( $id );
		$this->meta_data['post_edit_link'] = admin_url( 'admin.php?page=views-editor&view_id=' . $id );

		// return original content
		return $content;
	}

	public function types_meta( $params, $field_meta ) {
		// For legacy fields $field_meta can be null: manually populate expected entries.
		// See Types_Field_Type_Legacy and toolsetblocks-1449.
		$field_meta = is_array( $field_meta ) ? $field_meta : array(
			'type' => '',
			'slug' => '',
			'title' => '',
		);
		$field_categories = $this->wp_actions->apply_filters( 'tces_get_categories_for_field_type', array(), $field_meta['type'] );
		$field_options = array_key_exists( 'data', $field_meta ) &&
						 is_array( $field_meta['data'] ) &&
						 array_key_exists( 'options', $field_meta['data'] ) ?
			$field_meta[ 'data' ][ 'options' ] :
			null;

		$for_toolset_settings = array(
			'label' => array_key_exists( 'title', $field_meta ) ? $field_meta['title'] : '',
			'value' => array_key_exists( 'slug', $field_meta ) ? $field_meta['slug'] : '',
			'categories' => $field_categories,
			'type' => array_key_exists( 'type', $field_meta ) ? $field_meta['type'] : '',
			'fieldOptions' => $field_options,
		);
		// end to do
		$field_meta[ 'toolset_settings' ] = $for_toolset_settings;
		$this->meta_data = array_merge( $this->meta_data, $field_meta );

		// return original paramters
		return $params;
	}
}
