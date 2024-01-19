<?php

namespace OTGS\Toolset\Views\Services;

/**
 * Handles all the Query Filter related interaction for Views in the editor.
 */
class QueryFilterService {
	const QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_ALL = 'all';

	const QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_NOT_SET = 'not-set';

	/** @var \WP_REST_Request */
	private $rest_reqeuest = null;

	/** @var \Toolset_Constants */
	private $constants;

	/**
	 * QueryFilterService constructor.
	 *
	 * @param \Toolset_Constants|null $constants
	 */
	public function __construct(
		\Toolset_Constants $constants = null
	) {
		$this->constants = $constants;
	}

	/**
	 * Gets the private $rest_request variable.
	 *
	 * @return \WP_REST_Request|null
	 *
	 * @codeCoverageIgnore
	 */
	public function get_rest_request() {
		return $this->rest_reqeuest;
	}

	/**
	 * Initializes the QueryFilterService class by hooking the needed callbacks.
	 */
	public function init() {
		add_filter( 'rest_pre_dispatch', array( $this, 'capture_rest_request' ), 10, 3 );

		add_filter( 'wpv_filter_wpv_get_top_current_post', array( $this, 'adjust_top_current_post_for_view_preview' ), PHP_INT_MAX );

		add_filter( 'wpv_view_settings', array( $this, 'maybe_adjust_view_settings_for_query_filter_injection' ), 10, 2 );

		add_filter( 'wpv_filter_wpv_get_view_settings_for_query_filter_rendering', array( $this, 'maybe_adjust_view_settings_for_query_filter_injection' ), 10, 2 );

		add_filter( 'wpv_filter_wpv_get_view_settings', array( $this, 'maybe_adjust_view_settings_for_query_filter_injection' ), 10, 2 );
	}

	/**
	 * Used to capture the REST Request, when applicable, in order to use this request object later to determine the top
	 * current post for a View. This will allow the proper application of the selected Query Filters in the View editor
	 * preview.
	 *
	 * @param  mixed            $response Current response, either response or `null` to indicate pass-through.
	 * @param  \WP_REST_Server   $handler  ResponseHandler instance (usually WP_REST_Server).
	 * @param  \WP_REST_Request  $request  The request that was used to make current response.
	 *
	 * @return \WP_REST_Response Modified response, either response or `null` to indicate pass-through.
	 */
	public function capture_rest_request( $response, $handler, $request ) {
		$this->rest_reqeuest = $request;
		return $response;
	}

	/**
	 * Adjusts the top current post of a View in order to allow proper application of the selected Query Filters in the
	 * View editor preview.
	 *
	 * @param null|\WP_Post $top_current_post
	 *
	 * @return null|\WP_Post
	 */
	public function adjust_top_current_post_for_view_preview( $top_current_post ) {
		if (
			$this->constants->defined( 'REST_REQUEST' ) &&
			$this->constants->constant( 'REST_REQUEST' ) &&
			$this->rest_reqeuest
		) {
			$params = $this->rest_reqeuest->get_params();
			$parent_post_id = toolset_getnest( $params, array( 'general', 'parent_post_id' ), false );
			if (
				! $parent_post_id ||
				\WPV_Content_Template_Embedded::POST_TYPE === get_post_type( $parent_post_id )
			) {
				$this->rest_reqeuest = null;
				return $top_current_post;
			}

			return get_post( $parent_post_id );
		}

		return $top_current_post;
	}

	/**
	 * Adjusts the View's settings post meta to achieve automatic query filter generation for the Relation Query Filter.
	 *
	 * @param array    $view_settings
	 * @param null|int $view_id
	 *
	 * @return array
	 */
	public function maybe_adjust_view_settings_for_query_filter_injection( $view_settings, $view_id = null ) {
		if ( ! $view_id ) {
			return $view_settings;
		}

		$view_data = get_post_meta( $view_id, Bootstrap::BLOCK_VIEW_DATA_POST_META_KEY, true );
		$content_selection = toolset_getarr( $view_data, 'content_selection', array() );
		$allow_multiple_post_types = toolset_getarr( $content_selection, 'allowMultiplePostTypes', false );
		$post_type_relationship_slug = toolset_getarr( $content_selection, 'postTypeRelationship', self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_NOT_SET );

		if (
			! $allow_multiple_post_types &&
			! in_array(
				$post_type_relationship_slug,
				/**
				 * Filters the post relationship slugs blacklist to allow automatic query filter generation.
				 *
				 * @param array The array of blacklisted post relationship slugs.
				 */
				apply_filters(
					'wpv_filter_post_relationship_slugs_blacklist_for_automatic_query_filter_generation',
					array( self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_NOT_SET, self::QUERY_FILTER_INJECTION_RELATIONSHIP_SLUG_ALL )
				),
				true
			)
		) {
			$view_settings[ \WPV_View_Base::VIEW_SETTINGS_POST_RELATIONSHIP_MODE ] = array( 'top_current_post' );
			$view_settings[ \WPV_View_Base::VIEW_SETTINGS_POST_RELATIONSHIP_SHORTCODE_ATTRIBUTE ] = 'wpvrelatedto';
			$view_settings[ \WPV_View_Base::VIEW_SETTINGS_POST_RELATIONSHIP_URL_PARAMETER ] = 'wpv-relationship-filter';
			$view_settings[ \WPV_View_Base::VIEW_SETTINGS_POST_RELATIONSHIP_SLUG ] = $post_type_relationship_slug;

			// Setting the following View Setting to true to mark that this query filter has been autogenerated, in order
			// to later adjust it's description and UI (remove "Edit" and "Delete" buttons).
			// Also this is going to be used to remove it from the list of offered query filters to be created.
			$view_settings[ \WPV_Filter_Manager::SETTING_KEY ] = toolset_getarr( $view_settings, \WPV_Filter_Manager::SETTING_KEY, array() );
			$view_settings[ \WPV_Filter_Manager::SETTING_KEY ][ \WPV_Filter_Post_Relationship::SLUG ]  = array(
				\WPV_Filter_Manager::EDITOR_MODE => \WPV_Filter_Manager::FILTER_MODE_FROM_CONTENT_SELECTION,
			);
		}

		return $view_settings;
	}
}
