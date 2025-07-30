<?php

use OTGS\Toolset\Views\Models\ContentTemplate\UsageCondition;
use OTGS\Toolset\Views\Models\ContentTemplate\UsagePostType;
use OTGS\Toolset\Views\Models\ContentTemplate\UsageSettings;

/**
 * Wrapper for a Content Template.
 *
 * @since 1.8
 *
 * @property-read array $assigned_single_post_types
 * @property-read string $content_raw
 * @property-read string $description
 * @property-read string $description_raw
 * @property-read bool $is_owned_by_view
 * @property-read int $loop_output_id
 * @property-read null|object $wpml_element_language_details
 * @property-read null|string $wpml_language
 * @property-read int $wpml_trid
 */
class WPV_Content_Template_Embedded extends WPV_Post_Object_Wrapper {


    /**
     * Content template post type.
     */
    const POST_TYPE = 'view-template';

	const CONTENT_TEMPLATE_PREVIEW_POST_META_KEY = 'tb_preview_post';

	/**
	 * @var Toolset_Constants
	 */
	private $constants;

	/** @var \WPV_WordPress_Archive_Frontend */
	private $wpv_wordpress_archive_frontend;

    /**
     * Constructor. Create an instance from Content Template ID or WP_Post object representing a CT.
     *
     * Please note that WP_Post object will be validated and an exception is thrown on error.
     * However, if only an ID is provided, no such validation takes place here (in order to avoid potentionally
     * unnecessary database query). So, the ID must be validated before (by WPV_Content_Template_Embedded::is_valid() or
     * by other means), otherwise the behaviour of this object is undefined.
     *
     * @param int|WP_Post $content_template CT ID (integer) or a WP_Post object.
     * @param null|Toolset_Constants $constants
	 * @param null|WPV_WordPress_Archive_Frontend $wpv_wordpress_archive_frontend
     *
     * @throws InvalidArgumentException when provided argument is not a WP_Post instance representing a CT or an
     * integer that *might* be a CT ID.
     *
     * @since 2.6.4 The constructor started using another parameter for the Toolset_Constants.
     */
    public function __construct( $content_template, Toolset_Constants $constants = null, \WPV_WordPress_Archive_Frontend $wpv_wordpress_archive_frontend = null ) {
	    $this->constants = $constants
		    ? $constants
		    : new Toolset_Constants();

	    if ( null === $wpv_wordpress_archive_frontend ) {
			global $WPV_view_archive_loop;
			$wpv_wordpress_archive_frontend = $WPV_view_archive_loop;
		}
		$this->wpv_wordpress_archive_frontend = $wpv_wordpress_archive_frontend;

        if( $content_template instanceof WP_Post ) {
            // Let's check that we indeed have a valid post and CT post type
            if( WPV_Content_Template_Embedded::is_wppost_ct( $content_template ) ) {
                // Store the data we got;
                $this->object_id = $content_template->ID;
                $this->post = clone $content_template;
            } else {
                throw new InvalidArgumentException( "Invalid WP_Post object provided (not a Content Template): " . print_r( $content_template, true ) );
            }
        } elseif( is_numeric( $content_template ) && $content_template > 0 ) {
            // We assume (!) this is a valid View ID.
            $this->object_id = $content_template;
        } else {
            throw new InvalidArgumentException( "Invalid argument provided (not a CT or ID): " . print_r( $content_template, true ) );
        }
    }


    /**
     * Get the post object representing this Content Template.
     *
     * @return WP_Post Post object.
     *
     * @throws InvalidArgumentException if the post object cannot be retrieved or is invalid.
     */
    public function &post() {

        if( null == $this->post ) {
            // Requesting WP_Post object, but we haven't got it yet.
			$post = get_post( $this->object_id );
	        if( false != $post && WPV_Content_Template_Embedded::is_wppost_ct( $post ) ) {
		        $post = $post->filter( 'edit' );
		        $this->post = $post;
	        }
	        else {
		        throw new InvalidArgumentException( 'Invalid Content Template ID' );
	        }
        }

        return $this->post;
    }


    /* ************************************************************************* *\
            Postmeta
    \* ************************************************************************* */


    // Note: When adding new postmeta, don't forget to update wpml-config.xml!

    /**
     * Postmeta key for description of a CT.
     *
     * YES, there is a typo. We know. Don't touch it.
     *
     * @since unknown
     */
    const POSTMETA_DESCRIPTION_KEY = '_wpv-content-template-decription';


    /**
     * Output mode. Can have two values:
     * - "WP_mode" means "Auto-insert paragraphs"
     * - "raw_mode" means "Manual paragraphs"
     *
     * @since unknown
     */
    const POSTMETA_OUTPUT_MODE = '_wpv_view_template_mode';


    /**
     * ID of the View that uses this Content Template as a Loop Template.
     *
     * Zero or missing if no such View exsts.
     *
     * @since 1.8
     */
    const POSTMETA_LOOP_OUTPUT_ID = '_view_loop_id';

    const POSTMETA_TEMPLATE_EXTRA_CSS = '_wpv_view_template_extra_css';

    const POSTMETA_TEMPLATE_EXTRA_JS = '_wpv_view_template_extra_js';

    const POSTMETA_EDIT_LOCK = '_edit_lock';

    const POST_TEMPLATE_BINDING_POSTMETA_KEY = '_views_template';

	const POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE = '_toolset_user_editors_editor_choice';


    /**
     * @var array Default postmeta for the Content Template.
     *
     * Note that this array should contain all postmeta keys a CT can have. It is being used to iterate over them
     * in several places.
     */
    protected static $postmeta_defaults = array(
        WPV_Content_Template_Embedded::POSTMETA_DESCRIPTION_KEY => '',
        WPV_Content_Template_Embedded::POSTMETA_LOOP_OUTPUT_ID => 0,
        WPV_Content_Template_Embedded::POSTMETA_OUTPUT_MODE => 'raw_mode',
        WPV_Content_Template_Embedded::POSTMETA_TEMPLATE_EXTRA_CSS => '',
        WPV_Content_Template_Embedded::POSTMETA_TEMPLATE_EXTRA_JS => '',
	    WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE => '',
        //WPV_Content_Template_Embedded::POSTMETA_EDIT_LOCK
    );


    /**
     * Return array of default post meta.
     *
     * This is a workaround about not being able to reliably use self:: because of PHP 5.2.
     *
     * @return array Default post meta.
     */
    public function get_postmeta_defaults() {
        return WPV_Content_Template_Embedded::$postmeta_defaults;
    }


    /* ************************************************************************* *\
            Custom methods
    \* ************************************************************************* */


    /**
     * Get information about loops that have this CT assigned for archives.
     *
     * Take a look at $WPV_view_archive_loop->get_archive_loops() for better understanding of what is being returned.
     *
     * @param string $loop_type Relevant loop type: "post_type", "taxonomy" or "both".
     *
     * @return array Array of loop information.
     */
    public function get_assigned_loops( $loop_type ) {

        if( 'both' == $loop_type  ) {
            return array_merge(
                $this->get_assigned_loops( 'post_type' ),
                $this->get_assigned_loops( 'taxonomy' )
            );
        }

        $loops = $this->wpv_wordpress_archive_frontend->get_archive_loops( $loop_type, false, true );
        $selected_loops = array();
        foreach( $loops as $loop ) {
            if( $loop['ct'] == $this->id ) {
                $selected_loops[] = $loop;
            }
        }

        return $selected_loops;
    }


    /**
     * Get information about loops that have this CT assigned for single posts.
     *
     * Take a look at $WPV_view_archive_loop->get_archive_loops() for better understanding of what is being returned.
     *
     * @return array Array of loop information.
     */
    public function get_assigned_single_post_types() {
        global $WPV_view_archive_loop;

        // Get information about CT assignment and don't exclude any post types.
        $loops = $WPV_view_archive_loop->get_archive_loops( 'post_type', false, true, true );

        $selected_loops = array();
        foreach( $loops as $loop ) {
            if( $loop['single_ct'] == $this->id ) {
                $selected_loops[] = $loop;
            }
        }
        return $selected_loops;
    }


	/**
	 * @return array Array containing post types containing a UsageCondition assignment.
	 */
	public function get_assigned_single_post_types_with_conditions() {
		// Get information about CT assignment and don't exclude any post types.
		$loops = $this->wpv_wordpress_archive_frontend->get_archive_loops( 'post_type', false, true, true );

		$selected_loops = array();
		foreach ( $loops as $loop ) {
			/** @var UsagePostType $usage_post_type */
			$usage_post_type = $loop[ \WPV_WordPress_Archive_Frontend::KEY_LOOP_SINGLE_CT_CONDITIONS ];
			$usage_settings = $usage_post_type->getContentTemplateSettings( $this->id );
			if ( $usage_settings instanceof UsageSettings ) {
				$loop['usage_settings'] = $usage_settings;
				$selected_loops[] = $loop;
			}
		}
		return $selected_loops;
	}


	/**
	 * @return string[] Slugs of post types where the content template is assigned as a single template.
	 *
	 * @since 2.8.5
	 */
	public function get_assigned_single_post_type_slugs() {
		return array_map( function( $loop_info ) {
			return toolset_getarr( $loop_info, 'post_type_name' );
		}, $this->get_assigned_single_post_types() );
    }


    /**
     * Get dissident posts for this template.
     *
     * When a post type has this Content Template set as a single post template, posts with
     * other template assigned are considered "dissident".
     *
     * @param null|string|array $post_types An array of post type slugs, one post type slug or null to
     *     get dissident posts for all post types.
     * @param string $output_format Determines what return value will be:
     *     - 'by_post_type' will produce an associative array where keys are post types and values
     *         are arrays of dissident post IDs.
     *     - 'flat_array' will produce an array of dissident post IDs
     *     - 'count' will return a single number - count of dissident posts of given types.
     *
	 * @deprecated - since the new Conditional Content Template Assignments mechanism.
	 *
     * @return array|null Null on error, otherwise determined by $output_format.
     */
    public function get_dissident_posts( $post_types = null, $output_format = 'by_post_type' ) {

        if( null == $post_types ) {
            $post_types = $this->assigned_single_post_types;
        }

        global $wpdb;

        // Notice the left join, wich will give us also posts without this postmeta (that's also
        // why the extra check for NULL is needed).
        $query = "SELECT %s
            FROM {$wpdb->posts} AS posts LEFT JOIN {$wpdb->postmeta} AS meta
              ON (
                posts.ID = meta.post_id
                AND meta.meta_key = '_views_template'
              )
            WHERE
              posts.post_status != 'auto-draft'
              AND posts.post_type IN ( %s )
              AND (
                meta.meta_value != {$this->id}
                OR meta.meta_value IS NULL
              )
            ";

        return WPV_Content_Template_Embedded::query_assigned_posts( $post_types, $output_format, $query );

    }

	public function has_dissident_posts( $post_types = null, $output_format = 'by_post_type' ) {

        if( null == $post_types ) {
            $post_types = $this->assigned_single_post_types;
        }

        global $wpdb;

        // Notice the left join, wich will give us also posts without this postmeta (that's also
        // why the extra check for NULL is needed).
        $query = "SELECT %s
            FROM {$wpdb->posts} AS posts LEFT JOIN {$wpdb->postmeta} AS meta
              ON (
                posts.ID = meta.post_id
                AND meta.meta_key = '_views_template'
              )
            WHERE
              posts.post_status != 'auto-draft'
              AND posts.post_type IN ( %s )
              AND (
                meta.meta_value != {$this->id}
                OR meta.meta_value IS NULL
              )
			LIMIT 1
            ";

        return WPV_Content_Template_Embedded::query_assigned_posts( $post_types, $output_format, $query );

    }

	public function kill_dissident_posts( $post_types = array() ) {
		if ( ! empty( $post_types ) ) {
			global $wpdb;

			$post_types			= array_map( 'sanitize_text_field', $post_types );
			$post_types_flat	= '"' . implode( '", "', $post_types ) . '"';

			$posts_to_update = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}
					WHERE post_type IN ( {$post_types_flat} )
					AND post_type != %s",
					'view-template'
				)
			);

			foreach ( $posts_to_update as $post_updating ) {
				WPV_Content_Template_Embedded::assign_ct_to_post_object( $post_updating, $this->id );
			}

		}
	}


    /**
     * Get posts that are using this template.
     *
     * This can be useful for querying actual posts, disregarding the assigned CT for post types in Views settings.
     *
     * @param null|string|array $post_types Can be one of the following:
	 *     - an array of post type slugs
	 *     - one post type slug
	 *     - null - to get posts for all post types which have this CT assigned as a single post template
	 *     - '*' - to get posts of all post types
     * @param string $output_format Determines what return value will be:
     *     - 'by_post_type' will produce an associative array where keys are post types and values
     *         are arrays of post IDs.
     *     - 'flat_array' will produce an array of post IDs
     *     - 'count' will return a single number - count of posts of given types.
     * @param null|int $limit The number of posts to get.
     *
     * @return array|null Null on error, otherwise determined by $output_format.
     *
     * @since 1.9
     */
    function get_posts_using_this( $post_types = null, $output_format = 'flat_array', $limit = null ) {

        if( null == $post_types ) {
            $post_types = $this->assigned_single_post_types;
        }

		$all_post_types = ( '*' == $post_types );

		$post_type_query = $all_post_types ? '' : 'AND posts.post_type IN ( %s )';

		$limit = null !== $limit ?
			'LIMIT ' . absint( $limit ) :
			'';

        global $wpdb;

        // As opposed to get_dissident_posts(), there is no LEFT JOIN, which should exclude posts without
        // assigned CT right away.
        $query = "SELECT %s
            FROM {$wpdb->posts} AS posts JOIN {$wpdb->postmeta} AS meta
              ON (
                  posts.ID = meta.post_id
                  AND meta.meta_key = '_views_template'
              )
            WHERE
              posts.post_status != 'auto-draft'
              {$post_type_query}
              AND meta.meta_value = {$this->id}
            {$limit}";

        return WPV_Content_Template_Embedded::query_assigned_posts( $post_types, $output_format, $query );
    }


    /**
     * Get link to Translation Editor in WPML Translation Management.
     *
     * If WPML and WPML Translation Management is active, this method will generate an URL to page for editing
     * translation of this CT. If no such translation exists yet, it will be created when the page is loaded.
     *
     * Requires WPML Core and WPML TM plugins to be active.
     *
     * @param string $language_code Code of the language CT should be translated into.
     * @return null|string URL or null if it cannot be obtained.
     * @since 1.10
	 * @todo Switch to use the wpml_get_link_to_edit_translation WPML API hook
     */
    function get_wpml_tm_link( $language_code ) {

        // Fail if there's no trid or WPML TM is not active
        $trid = $this->wpml_trid;
        if( 0 == $trid ) {
            return null;
        }
        $wpv_wpml_integration = WPV_WPML_Integration_Embedded::get_instance();
        if( ! $wpv_wpml_integration->is_wpml_tm_loaded() ) {
            return null;
        }

        $job_id = apply_filters(
            'wpml_translation_job_id',
            null,
            array( 'trid' => $trid, 'language_code' => $language_code )
        );

        if( $job_id ) {
            $url = add_query_arg(
                array(
                    'page' => 'wpml-translation-management/menu/translations-queue.php',
                    'job_id' => $job_id
                ),
                admin_url( 'admin.php' )
            );
        } else {
            $url = add_query_arg(
                array(
                    'page' => 'wpml-translation-management/menu/translations-queue.php',
                    'trid' => $trid,
                    'language_code' => $language_code,
                    'source_language_code' => apply_filters( 'wpml_default_language', '' )
                ),
                admin_url( 'admin.php' )
            );
        }
        return esc_url( $url );
	}

	/**
	 * Get information about the standard editor usage by the Content Template.
	 *
	 * @return bool
	 */
	public function is_using_standard_editor() {
		$current_user_editor = $this->get_postmeta( WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE );
		// When a Content Template is built with the Views Content Template builder the value of the
		// "WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE" post meta is "basic" while when it's
		// created through a Layouts cell or the Toolset dashboard the value of the post meta is empty.
		// Content Templates built through the Layouts cell or Toolset dashboard should be considered as Content Template using the default editor.
		return (
			Toolset_User_Editors_Editor_Basic::BASIC_SCREEN_ID === $current_user_editor
			|| '' === $current_user_editor
		);
	}

	/**
	 * Check whether the current Content Template should print frontend extra CSS and JS.
	 * It should if it is using the basic or the gutenberg user editors.
	 *
	 * @return bool
	 * @since 2.8
	 */
	public function should_include_frontend_extra_assets() {
		$current_user_editor = $this->get_postmeta( WPV_Content_Template_Embedded::POST_TEMPLATE_USER_EDITORS_EDITOR_CHOICE );
		return (
			$this->is_using_standard_editor()
			|| Toolset_User_Editors_Editor_Gutenberg::GUTENBERG_SCREEN_ID === $current_user_editor
		);
	}

	/**
	 * Content Template's Extra CSS public getter.
	 *
	 * @return string The Content Template's extra CSS
	 */
	public function get_template_extra_css() {
		return $this->_get_template_extra_css();
	}

	/**
	 * Content Template's Extra JS public getter.
	 *
	 * @return string The Content Template's extra JS
	 */
	public function get_template_extra_js() {
		return $this->_get_template_extra_js();
	}


    /* ************************************************************************* *\
            Static methods
    \* ************************************************************************* */


    /**
     * Create an instance of WPV_Content_Template_Embedded from Content Template ID or a WP_Post object.
     *
     * See WPV_View_Embedded constructor for details.
     *
     * @param int|WP_Post $ct CT ID or a WP_Post object.
     *
     * @return null|WPV_Content_Template_Embedded
     */
    public static function get_instance( $ct ) {
        try {
            $ct = new WPV_Content_Template_Embedded( $ct );
            return $ct;
        } catch( Exception $e ) {
            return null;
        }
    }


    /**
     * For a given object, determine if it's a valid WP_Post object representing a Content Template.
     *
     * @param mixed $post Value to check.
     *
     * @return bool True if $post is a valid WP_Post object representing a CT, false otherwise.
     */
    public static function is_wppost_ct( $post ) {
        return ( ( $post instanceof WP_Post ) && ( $post->ID > 0 ) && ( WPV_Content_Template_Embedded::POST_TYPE == $post->post_type ) );
    }

	/**
	 * @param $post_id
	 * @param $ct_id
	 * @param string $prev_value
	 *
	 * @return mixed|void
	 * Programmatically assign Content Template assignment to $post object and runs relative filter
	 */
	public static final function assign_ct_to_post_object( $post_id, $ct_id, $prev_value = '' ){
		if( $prev_value === '' || $prev_value === false ){
			$prev_value = get_post_meta( $post_id, self::POST_TEMPLATE_BINDING_POSTMETA_KEY, true );
		}

		$ret = update_post_meta( $post_id, self::POST_TEMPLATE_BINDING_POSTMETA_KEY, $ct_id, $prev_value );

		return apply_filters( 'wpv_filter_assign_ct_to_post_object', $ret, $post_id, $ct_id, $prev_value );
	}

	/**
	 * @param $post_id
	 * @param string $prev_value
	 *
	 * @return mixed|void
	 * Programmatically removes Content Template assignment from $post object and runs relative filter
	 */
	public static final function remove_ct_assignment_from_post_object( $post_id, $prev_value = '' ){
		if( $prev_value === '' || $prev_value === false ){
			$prev_value = get_post_meta( $post_id, self::POST_TEMPLATE_BINDING_POSTMETA_KEY, true );
		}

		$ret = update_post_meta( $post_id, self::POST_TEMPLATE_BINDING_POSTMETA_KEY, 0, $prev_value );

		return apply_filters( 'wpv_filter_remove_ct_assignment_from_post_object', $ret, $post_id, $prev_value );
	}


    /**
     * Determine whether CT with given ID exists.
     *
     * @param int $ct_id ID of the CT to check.
     *
     * @return bool True if post with given ID exists and if it's a CT.
     */
    public static function is_valid( $ct_id ) {
        /* Note: This should not cause a redundant database query. Post objects are cached by WP core, so this one was
         * either already loaded or it has to be loaded now and will be reused in the future. */
        return WPV_Content_Template_Embedded::is_wppost_ct( WP_Post::get_instance( $ct_id ) );
    }


    /**
     * Determine whether given Content Template name is already used as a post slug or post title.
     *
     * @param string $name CT name to check.
     * @param int $except_id CT id to exclude from checking.
     * @param array &$collision_data See WPV_Post_Object_Wrapper::is_name_used_base for details.
     *
     * @return bool True if name is already used, false otherwise.
     *
     * @since 1.9
     */
    public static function is_name_used( $name, $except_id = 0, &$collision_data = null ) {
        return WPV_Post_Object_Wrapper::is_name_used_base( $name, WPV_Content_Template_Embedded::POST_TYPE, $except_id, $collision_data );
    }


    /**
     * Get posts that are using any Content Template.
     *
     * @param string|array $post_types An array of post type slugs or one post type slug.
     * @param string $output_format Determines what return value will be:
     *     - 'by_post_type' will produce an associative array where keys are post types and values
     *         are arrays of post IDs.
     *     - 'flat_array' will produce an array of post IDs
     *     - 'count' will return a single number - count of posts of given types.
     *
     * @return array|null Null on error, otherwise determined by $output_format.
     *
     * @since 1.9
     */
    static function get_posts_using_content_template_by_type( $post_types, $output_format = 'flat_array' ) {
        global $wpdb;

        // As opposed to get_dissident_posts(), there is no LEFT JOIN, which should exclude posts without
        // assigned CT right away.
        $query = "SELECT %s
            FROM {$wpdb->posts} AS posts JOIN {$wpdb->postmeta} AS meta
              ON (
                  posts.ID = meta.post_id
                  AND meta.meta_key = '_views_template'
              )
            WHERE
              posts.post_status != 'auto-draft'
              AND posts.post_type IN ( %s )
              AND meta.meta_value != 0";

        return WPV_Content_Template_Embedded::query_assigned_posts( $post_types, $output_format, $query );
    }


    /**
     * Look for a Content Template by it's name.
     *
     * That means by slug (post_name) and title (post_title). Names should be unique among both columns and all CTs,
     * but in case there is more a CT with matching slug and another CT with matching title, the one with slug will
     * be preferred.
     *
     * If there are more duplicate values, *something* will be returned. But we're not dealing with it further
     * because that would be an extremely rare and invalid state (basically meaning some kind of data corruption).
     *
     * This should work in the same way as the original method WPV_Template::get_template_id(), but with one less query
     * (usually there were two queries executed because CT titles are used more often than slugs).
     *
     * Also, found IDs are being cached.
     *
     * @param string $post_name Content Template name (title or slug).
     * @return int Matching Content Template ID or zero if none was found.
     *
     * @since 1.9
     */
    static function get_template_id_by_name( $post_name ) {

        static $template_id_cache = array();

        if ( isset( $template_id_cache[ $post_name ] ) ) {

            // Just retrieve the item from cache
            $post_id = $template_id_cache[$post_name];

        } else {

            // No template ID in cache, query it.

            global $wpdb;

            // We are adding a virtual column 'priority' that will contain '0' if post was matched
            // by it's slug and '1' if by it's title. Then we will sort results by priority and use the first one.
            // Notice the "LIMIT 2", which allows us to do this (with the limitation described above) but still
            // keeps the query as lightweight as possible.
            /** @noinspection SqlResolve */
            $query = $wpdb->prepare(
                "SELECT ID, IF( post_name = %s, '0', '1' ) AS priority
                FROM {$wpdb->posts}
                WHERE post_type = %s
                  AND (
                    post_name = %s
                    OR post_title = %s
                  )
                ORDER BY priority
                LIMIT 2",
                $post_name,
                WPV_Content_Template_Embedded::POST_TYPE,
                $post_name,
                $post_name
            );

            $results = $wpdb->get_results( $query );

            // Use first result or zero if nothing was found.
            if( !empty( $results ) ) {
                $first_result = $results[0];
                $post_id = $first_result->ID;
            } else {
                $post_id = 0;
            }

            $template_id_cache[ $post_name ] = $post_id;

        }

        return $post_id;
    }


    /**
     * Get a Content Template by it's name.
     *
     * See get_template_id_by_name() for details.
     *
     * @param string $post_name Content Template name.
     * @return null|WPV_Content_Template_Embedded Content Template or null if it wasn't found or couldn't be loaded.
     *
     * @since 1.9
     */
    static function get_template_by_name( $post_name ) {
        return WPV_Content_Template_Embedded::get_instance( WPV_Content_Template_Embedded::get_template_id_by_name( $post_name ) );
    }


	/**
	 * Properly query Content Templates.
	 *
	 * Runs the WP_Query, ensuring it has proper arguments (post type, ordering by title, published) and
	 * language (if WPML is active and CTs are translatable).
	 *
	 * @param array $args Additional arguments for WP_Query. They will override the defaults.
	 * @param bool $force_all_languages If true, don't handle CT translations at all (all languages will be returned).
	 * @param string $return_what Can have following values:
	 *      - 'posts': Return array of WP_Post objects
	 *      - 'query': Return the WP_Query object with results.
	 *      - 'ct': Return array of WPV_Content_Template_Embedded instances.
	 * @return array|mixed See $return_what parameter.
	 * @since 1.10
	 */
    static function query( $args = array(), $force_all_languages = false, $return_what = 'posts' ) {

        $default_args = array(
            'post_type' => WPV_Content_Template_Embedded::POST_TYPE,
            'order' => 'ASC',
            'orderby' => 'title',
            'post_status' => 'publish',
			'posts_per_page' => -1
        );

        $args = wp_parse_args( $args, $default_args );

		// If needed, switch WPML to default language, run the query, switch back.
		$default_language = apply_filters( 'wpml_default_language', '' );
		$current_language = apply_filters( 'wpml_current_language', '' );

        $switch_languages = ( ! $force_all_languages );

        if( $switch_languages ) {
            //changes to the default language
			do_action( 'wpml_switch_language', $default_language );
        }

        $query = new WP_Query( $args );

        if( $switch_languages ) {
            //changes to the current language
			do_action( 'wpml_switch_language', $current_language );
        }

        switch( $return_what ) {
            case 'query':
                return $query;
            case 'ct':
                $posts = $query->get_posts();
                $results = array();
                foreach ($posts as $post) {
                    $ct = WPV_Content_Template_Embedded::get_instance($post);
                    if (null != $ct) {
                        $results[] = $ct;
                    }
                }
                return $results;
            case 'posts':
            default:
                $posts = $query->get_posts();
                return $posts;
        }
    }


    /**
     * Cache for get_wpml_element_type(). Do not use directly.
     * @type null|string
     * @since 1.10
     */
    static $wpml_element_type = null;


    /**
     * Get "element type" value for Content Templates.
     *
     * Works correctly only if WPML is active.
     *
     * @return string
     * @since 1.10
     */
    static function get_wpml_element_type() {
        if( null == WPV_Content_Template_Embedded::$wpml_element_type ) {
            WPV_Content_Template_Embedded::$wpml_element_type = apply_filters( 'wpml_element_type', WPV_Content_Template_Embedded::POST_TYPE );
        }
        return WPV_Content_Template_Embedded::$wpml_element_type;
    }


    /**
     * Determine whether Content Templates as a post type are translatable by WPML.
     *
     * @return bool True if CTs are translatable, false if they're not or WPML is not active.
     * @since 1.10
     */
    static function is_translatable() {
        global $sitepress;
        if( isset( $sitepress ) ) {
            $translatable_documents = $sitepress->get_translatable_documents();
            return in_array( WPV_Content_Template_Embedded::POST_TYPE, array_keys( $translatable_documents ) );
        }
        return false;
    }


    /* ************************************************************************* *\
            Custom getters
    \* ************************************************************************* */


    /**
     * @return string CT description.
     */
    protected function _get_description() {
        return $this->get_postmeta( WPV_Content_Template_Embedded::POSTMETA_DESCRIPTION_KEY );
    }


    /**
     * @return string CT description without HTML entity encoding.
     * @since 1.9
     */
    protected function _get_description_raw() {
        return html_entity_decode( $this->description );
    }


    /**
     * @return bool True if this CT has a non-empty description.
     */
    protected function _get_has_description() {
        return ( ! empty( $this->description ) );
    }


    /**
     * @return int ID of a View/WPA that uses this CT as loop output template ("owns" this CT).
     * Zero if no owner exists.
     */
    protected function _get_loop_output_id() {
        return (int) $this->get_postmeta( WPV_Content_Template_Embedded::POSTMETA_LOOP_OUTPUT_ID );
    }


    /**
     * True if this CT has an owner View/WPA (@see _get_loop_output_id()).
     * @return bool
     */
    protected function _get_is_owned_by_view() {
        return ( 0 != $this->loop_output_id );
    }


    /**
     * Determines whether trashing this CT is allowed.
     * @return bool
     */
    protected function _get_can_be_trashed() {
        return ( $this->is_published && ! $this->is_owned_by_view );
    }


    /**
     * Output mode: 'WP_mode' for automatically inserted paragraphs or 'raw_mode' for manual paragraphs.
     * @return string
     * @since 1.9
     */
    protected function _get_output_mode() {
        $output_mode = $this->get_postmeta( WPV_Content_Template_Embedded::POSTMETA_OUTPUT_MODE );
        return $output_mode;
    }


    /**
     * @return array Array of post types that have this CT assigned as a single post template.
     * @since 1.9
     */
    protected function _get_assigned_single_post_types() {
        $loops = $this->get_assigned_single_post_types();
        $post_type_names = array();
        foreach( $loops as $loop_info ) {
            $post_type_names[] = $loop_info['post_type_name'];
        }
        return $post_type_names;
    }


    /**
     * @return array Array of post types that have this CT assigned as an archive template.
     * @since 1.9
     */
    protected function _get_assigned_post_archives() {
        $loops = $this->get_assigned_loops( 'post_type' );
        $post_type_names = array();
        foreach( $loops as $loop_info ) {
            $post_type_names[] = $loop_info['post_type_name'];
        }
        return $post_type_names;
    }


    /**
     * @return array Array of taxonomy slugs that have this CT assigned as an archive template.
     * @since 1.9
     */
    protected function _get_assigned_taxonomy_archives() {
        $loops = $this->get_assigned_loops( 'taxonomy' );
        $archive_slugs = array();
        foreach( $loops as $loop_info ) {
            $archive_slugs[] = $loop_info['slug'];
        }
        return $archive_slugs;
    }


    /**
     * @return string Template extra CSS code.
     * @since 1.9
     */
    protected function _get_template_extra_css() {
        $value = $this->get_postmeta( WPV_Content_Template_Embedded::POSTMETA_TEMPLATE_EXTRA_CSS );

        // At least avoid type errors.
        if( ! is_string( $value ) ) {
            $value = '';
        }

        return $value;
    }


    /**
     * @return string Template extra JS code.
     * @since 1.9
     */
    protected function _get_template_extra_js() {
        $value = $this->get_postmeta( WPV_Content_Template_Embedded::POSTMETA_TEMPLATE_EXTRA_JS );

        // At least avoid type errors.
        if( ! is_string( $value ) ) {
            $value = '';
        }

        return $value;
    }


    /**
     * Get dissident posts for this CT, organized by post type.
     *
     * See get_dissident_posts() for details.
     *
     * @return array
     * @since 1.9
     */
    protected function _get_dissident_posts() {
        return $this->get_dissident_posts();
    }


    /**
     * Counterpart of _set_content_raw() in WPV_Content_Template.
     *
     * @return string
     * @since 1.9
     */
    protected function _get_content_raw() {
        return $this->content;
    }


    /**
     * Get language details of this CT.
     *
     * According to https://onthegosystems.myjetbrains.com/youtrack/issue/wpmlcore-1781#comment=102-39877
     * the object should contain element_id, trid, language_code and source_language_code.
     *
     * @return null|object Object with language details or null if it can't be obtained.
     * @since 1.10
     */
    protected function _get_wpml_element_language_details() {
        return apply_filters(
            'wpml_element_language_details',
            null,
            array( 'element_id' => $this->id, 'element_type' => WPV_Content_Template_Embedded::get_wpml_element_type() )
        );
    }


    /**
     * Get trid of this CT if applicable.
     *
     * Try to get trid for this Content Template from WPML if it's active.
     *
     * @return int Valid trid or zero.
     * @since 1.10
     */
    protected function _get_wpml_trid() {
        $lang_details = $this->wpml_element_language_details;
        if( null == $lang_details ) {
            return 0;
        } else {
            return $lang_details->trid;
        }
    }


    /**
     * Get array of WPML translation data for the CT.
     *
     * See https://onthegosystems.myjetbrains.com/youtrack/issue/views-76#comment=102-30188 for description of the
     * returned value.
     *
     * @return array|null WPML translation information or null if it can't be obtained.
     * @since 1.10
     */
    protected function _get_wpml_translations() {
        $trid = $this->wpml_trid;
        if( 0 == $trid ) {
            return null;
        }

        global $sitepress;
        if( isset( $sitepress ) ) {
            $translations = $sitepress->get_element_translations( $trid, WPV_Content_Template_Embedded::get_wpml_element_type(), false, true );
            return $translations;
        }

		return null;
    }


    /**
     * Get language code of this Content Template.
     *
     * Works only with WPML active.
     *
     * @return null|string Language code or null if it can't be obtained.
     * @since 1.10
     */
    protected function _get_wpml_language() {
        return apply_filters( 'wpml_element_language_code', null, array( 'element_id' => $this->id, 'element_type' => WPV_Content_Template_Embedded::get_wpml_element_type() ) );
    }


    /**
     * Determine if this Content Template has a non-default language.
     *
     * Works only with WPML active. Without it, true is always returned.
     *
     * @return bool
     * @since 1.10
     */
    protected function _get_has_wpml_default_language() {
        global $sitepress;

        if( isset( $sitepress ) ) {
            return ( $sitepress->get_default_language() == $this->wpml_language );
        } else {
            return true;
        }
    }




    /* ************************************************************************* *\
            Helper functions
    \* ************************************************************************* */


    /**
     * Helper function for querying assigned posts by their types.
     *
     * It processes arguments that are common for get_dissident_posts(), get_posts_using_this() and
     * get_posts_using_content_template_by_type(), modifies given query and formats the output appropriately.
     *
     * @param string|array $post_types Array of post type slugs, one post type slug or "*" for not querying by post types.
     * @param string $output_format Determines what return value will be:
     *     - 'by_post_type' will produce an associative array where keys are post types and values
     *         are arrays of post IDs.
     *     - 'flat_array' will produce an array of post IDs
     *     - 'count' will return a single number - count of posts of given types.
     *
     * @param string $query Query string with exactly two "%s" placeholders (unless $post_types is "*", in which case
	 *    only one placeholder must be present):
     *    1. select clause (something like "SELECT %s FROM ...")
     *    2. list of post types (e.g. "... IN ( %s ) ...")
     *
     *    Following conditions must be also met:
     *    - "posts" alias must be defined for the wp_posts table.
     *
     * @return array|null Null on error, otherwise determined by $output_format.
     *
     * @since 1.9
     */
    private static function query_assigned_posts( $post_types, $output_format, $query ) {

        $query_post_types = ( '*' != $post_types );

        if( $query_post_types ) {
			if ( is_string( $post_types ) ) {
				$post_types = array( $post_types );
			}

			if ( ! is_array( $post_types ) ) {
				return null;
			}

	        // Querying by post types but no post types provided - no results (but no error technically speaking).
	        if( empty( $post_types ) ) {
		        return array();
	        }

        }

        if( !in_array( $output_format, array( 'count', 'by_post_type', 'flat_array' ) ) ) {
            return null;
        }

        global $wpdb;

        switch( $output_format ) {
            case 'count':
                $select_clause = 'COUNT(1)';
                break;
            case 'flat_array':
                $select_clause = 'posts.ID as id';
                break;
            case 'by_post_type':
                $select_clause = 'posts.ID as id, posts.post_type as post_type';
                break;
            default:
                // we will never get here
                return null;
        }

		if( $query_post_types ) {
			// todo sanitize
			$post_types_flat = '"' . implode( '", "', $post_types ) . '"';

			$query = sprintf( $query, $select_clause, $post_types_flat );
		} else {
			$query = sprintf( $query, $select_clause );
		}

        switch( $output_format ) {
            case 'count':
                return $wpdb->get_var( $query );
            case 'flat_array':
                return $wpdb->get_col( $query );
            case 'by_post_type':
                $results = $wpdb->get_results( $query );
                $results_by_post_type = array();
                foreach( $results as $result ) {
                    if( !array_key_exists( $result->post_type, $results_by_post_type ) ) {
                        $results_by_post_type[ $result->post_type ] = array();
                    }
                    $results_by_post_type[ $result->post_type ][] = $result->id;
                }
                return $results_by_post_type;
            default:
                // we will never get here
                return null;
        }
    }
}
