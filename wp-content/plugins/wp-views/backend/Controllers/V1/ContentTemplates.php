<?php

namespace OTGS\Toolset\Views\Controllers\V1;

use OTGS\Toolset\Common\WpQueryFactory;
use OTGS\Toolset\Views\Model\Wordpress\Wpdb;
use OTGS\Toolset\Views\Models\ContentTemplate\UsageCondition;
use OTGS\Toolset\Views\Services\ContentTemplateService;
use Toolset\DynamicSources\ToolsetSources\CustomFieldService;

/**
 * ContentTemplate API Endpoint, used to general action on CT usages.
 */
class ContentTemplates extends Base {

	const ROUTE_AVAILABLE_USAGES = '/content-template/available-usages';
	const ROUTE_CLEAN_ASSIGNED_POSTS = '/content-template/post-type/(?P<slug>\w+).*/clean-assigned-posts';

	/**
	 * This is the list of allowed custom field types to be used as Content Template condition subjects.
	 *
	 * @var array
	 */
	const ALLOWED_CUSTOM_FIELD_TYPES = [ 'textfield', 'numeric', 'phone', 'wysiwyg', 'url', 'textarea', 'radio', 'checkbox' ];

	/**
	 * @var ContentTemplateService
	 */
	private $content_template_service;

	/**
	 * @var \Toolset_Post_Type_Repository
	 */
	private $post_type_repository;

	/**
	 * @var CustomFieldService
	 */
	private $custom_field_service;

	/**
	 * @var \WPV_Ajax
	 */
	private $wpv_ajax;

	/**
	 * @var WpQueryFactory
	 */
	private $wp_query_factory;

	/**
	 * @var array
	 */
	private $custom_field_options;

	/**
	 * @param ContentTemplateService $content_template_service
	 * @param \Toolset_Post_Type_Repository $post_type_repository
	 * @param CustomFieldService $custom_field_service
	 * @param \WPV_Ajax $wpv_ajax
	 * @param WpQueryFactory $wp_query_factory
	 */
	public function __construct(
		ContentTemplateService $content_template_service,
		\Toolset_Post_Type_Repository $post_type_repository,
		CustomFieldService $custom_field_service,
		\WPV_Ajax $wpv_ajax,
		WpQueryFactory $wp_query_factory
	) {
		$this->content_template_service = $content_template_service;
		$this->post_type_repository = $post_type_repository;
		$this->custom_field_service = $custom_field_service;
		$this->wpv_ajax = $wpv_ajax;
		$this->wp_query_factory = $wp_query_factory;
	}


	/**
	 * @inheritDoc
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			self::ROUTE_AVAILABLE_USAGES,
			array(
				array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_available_usages' ),
					'permission_callback' => array( $this, 'can_edit_view' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			self::ROUTE_CLEAN_ASSIGNED_POSTS,
			array(
				array(
					'methods' => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'clean_post_type_assigned_posts' ),
					'args' => array(
						'slug' => array(
							'required' => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'permission_callback' => array( $this, 'can_edit_view' ),
				),
			)
		);
	}


	/**
	 * Returns an array of post type slug indexed array with a boolean value
	 * representing if it has posts with custom template usage in it's metadata.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return string[]
	 */
	public function clean_post_type_assigned_posts( $request ) {

		$post_type_slug = $request->get_param( 'slug' );

		if ( ! $post_type_slug ) {
			return new \WP_Error( 'no_post_type_slug_received', 'No valid Post Type slug was received.', array( 'status' => 404 ) );
		}
		$this->content_template_service->clean_post_type_assigned_posts( $post_type_slug );
		return array( 'status' => 'success' );
	}


	/**
	 * Gets information related to the Content Template Usages.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return array|\WP_Error
	 */
	public function get_available_usages( $request ) {
		$post_types = $this->post_type_repository->get_all();
		$available_usages = [];
		foreach ( $post_types as $key => $post_type ) {
			if ( $post_type->is_public() ) {
				$available_usages[] = $this->get_available_usage_for_post_type( $post_type );
			}
		}

		return $available_usages;
	}

	/**
	 * @param \IToolset_Post_Type $post_type
	 *
	 * @return array
	 */
	private function get_available_usage_for_post_type( \IToolset_Post_Type $post_type ) {
		$native_fields = $this->get_available_native_fields();
		$taxonomy_fields = $this->get_available_taxonomy_fields_for_post_type( $post_type );
		$custom_fields = $this->get_available_custom_fields_for_post_type( $post_type );

		return [
			'slug' => $post_type->get_slug(),
			'display_name' => $post_type->get_label(),
			'has_assigned_posts' => $this->has_post_type_assigned_posts( $post_type ),
			'options' => array_merge( $native_fields, $taxonomy_fields, $custom_fields ),
			'preview_urls' => $this->get_post_type_preview_urls( $post_type ),
		];
	}

	/**
	 * Check if a post type has posts with content templates specifically assigned.
	 *
	 * @param \Toolset_Post_Type_Registered|\Toolset_Post_Type $post_type
	 *
	 * @return bool
	 */
	private function has_post_type_assigned_posts( $post_type ) {
		$args = array(
			'post_type' => $post_type->get_slug(),
			'meta_query' => array(
				'_views_template_clause' => array(
					'key' => ContentTemplateService::META_KEY_CUSTOM_TEMPLATE,
					'compare' => 'EXISTS',
				),
			),
			'posts_per_page' => 1,
		);
		$query = $this->wp_query_factory->create( $args );
		return $query->have_posts();
	}

	/**
	 * Returns 3 posts for a post type with it's respective preview urls.
	 *
	 * @param \Toolset_Post_Type_Registered|\Toolset_Post_Type $post_type
	 *
	 * @return array - collection of preview links formaated for Post Selector
	 */
	private function get_post_type_preview_urls( $post_type ) {
		$preview_posts_query = $this->wp_query_factory->create(
			array(
				'post_type' => $post_type->get_slug(),
				'posts_per_page' => 3,
				'orderby' => 'modified',
				'order' => 'DESC',
			)
		);

		$preview_urls = [];
		if ( $preview_posts_query->have_posts() ) {
			$preview_posts = $preview_posts_query->get_posts();
			foreach ( $preview_posts as $preview_post ) {
				$preview_urls[] = [
					'label' => $preview_post->post_title,
					'value' => $preview_post->ID,
					'guid' => get_permalink( $preview_post ),
				];
			}
		}

		return $preview_urls;
	}

	/**
	 * Returns a meta key for a custom field.
	 *
	 * @param string $custom_field_slug
	 *
	 * @return string|null
	 */
	private function get_custom_field_meta_key( $custom_field_slug ) {
		if ( null === $this->custom_field_options ) {
			$this->custom_field_options = get_option( \Toolset_Field_Definition_Factory_Post::FIELD_DEFINITIONS_OPTION );
		}
		if (
			isset( $this->custom_field_options[ $custom_field_slug ] )
			&& isset( $this->custom_field_options[ $custom_field_slug ]['meta_key'] )
		) {
			return $this->custom_field_options[ $custom_field_slug ]['meta_key'];
		}
		return null;
	}

	/**
	 * Returns an array of fields representing the available custom fields for a given post type
	 *
	 * @param \IToolset_Post_Type $post_type
	 *
	 * @return array
	 */
	private function get_available_custom_fields_for_post_type( \IToolset_Post_Type $post_type ) {
		$group_slugs = $this->custom_field_service->get_group_slugs_by_type( $post_type->get_slug() );
		$fields = [];
		foreach ( $group_slugs as $group_slug ) {
			$group_model = $this->custom_field_service->create_group_model( $group_slug );

			if ( null !== $group_model ) {
				$options = [];
				foreach ( $group_model->get_field_definitions() as $field_definition ) {
					$meta_key = $this->get_custom_field_meta_key( $field_definition->get_slug() );
					if (
						in_array( $field_definition->get_type_slug(), self::ALLOWED_CUSTOM_FIELD_TYPES, true )
						&& null !== $meta_key
					) {
						$options[] = [
							'label' => $field_definition->get_name(),
							'value' => $meta_key,
							'type' => UsageCondition::SOURCE_CUSTOM_FIELD,
						];
					}
				}

				$fields[] = [
					'label' => $group_model->get_display_name(),
					'options' => $options,
				];
			}
		}

		return $fields;
	}


	/**
	 * Returns an array of fields representing the available taxonomies for a given post type
	 *
	 * @param \IToolset_Post_Type $post_type
	 *
	 * @return array
	 */
	private function get_available_taxonomy_fields_for_post_type( \IToolset_Post_Type $post_type ) {
		$taxonomies = $this->post_type_repository->get_post_type_taxonomies( $post_type );

		$fields = [];
		if ( count( $taxonomies ) > 0 ) {
			$options = [];
			foreach ( $taxonomies as $taxonomy ) {
				$options[] = [
					'label' => $taxonomy->label,
					'value' => $taxonomy->name,
					'type' => UsageCondition::SOURCE_TAXONOMY,
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'ajax_action' => $this->wpv_ajax->get_action_js_name( \WPV_Ajax::CALLBACK_GET_TAXONOMY_TERMS ),
					'ajax_nonce' => wp_create_nonce( \WPV_Ajax::CALLBACK_GET_TAXONOMY_TERMS ),
				];
			}

			$fields[] = [
				'label' => __( 'Taxonomies', 'wpv-views' ),
				'options' => $options,
			];
		}

		return $fields;
	}


	/**
	 * Get default native fields.
	 *
	 * @return array[]
	 */
	private function get_available_native_fields() {
		return [
			[
				'label' => __( 'Native Fields', 'wpv-views' ),
				'options' => [
					[
						'label' => __( 'Post Title', 'wpv-views' ),
						'value' => 'post_title',
						'type' => UsageCondition::SOURCE_NATIVE_FIELD,
					],
					[
						'label' => __( 'Post Slug', 'wpv-views' ),
						'value' => 'post_name',
						'type' => UsageCondition::SOURCE_NATIVE_FIELD,
					],
				],
			],
		];
	}
}
