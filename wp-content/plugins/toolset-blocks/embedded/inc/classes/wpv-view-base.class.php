<?php
/**
 * Base class for 'view' post type, that means Views and WPAs.
 *
 * Contains code common for both, mostly related to "view query mode", a value determining what kind of object
 * it is.
 *
 * @since 1.8
 *
 * @property-read bool $has_loop_template
 * @property int $loop_template_id
 * @property mixed $loop_included_ct_ids
 * @property string $loop_meta_html
 * @property-read array $loop_settings
 * @property-read array $view_settings
 * @property-read string $query_mode
 * @property-read string $query_type
 * @property array $view_data
 * @property int $view_data_id
 * @property array $view_data_general_settings
 * @property int $view_data_preview_id
 * @property int $view_data_parent_post_id
 * @property int $view_data_initial_parent_post_id
 * @property string $view_data_view_template
 */
abstract class WPV_View_Base extends WPV_Post_Object_Wrapper {


    /* ************************************************************************* *\
            Postmeta
    \* ************************************************************************* */


    /**
     * View post type slug.
     */
    const POST_TYPE = 'view';


    const POSTMETA_DESCRIPTION = '_wpv_description';


    const POSTMETA_LOOP_TEMPLATE_ID = '_view_loop_template';


    /**
     * Array with View settings (used also by WPA).
     *
     * For documentation of particular elements see comments at those constants:
     * - VIEW_SETTINGS_CSS
     * - VIEW_SETTINGS_JS
     *
     * Note that this list is not complete and there might be other settings specific
     * to Views or WPAs only.
     *
     * @since 1.10
     */
    const POSTMETA_VIEW_SETTINGS = '_wpv_settings';


    /**
     * Array with loop settings (former layout settings; used also by WPA).
     *
     * For documentation of particular elements see comments at those constants:
     * - LOOP_SETTINGS_META_HTML
     *
     * Note that this list is not complete and there might be other settings specific
     * to Views or WPAs only.
     *
     * @since 1.10
     */
    const POSTMETA_LOOP_SETTINGS = '_wpv_layout_settings';

	/**
	 * Array with View data regarding the View/WPA block.
	 */
	const POSTMETA_VIEW_DATA = '_wpv_view_data';

	const VIEW_DATA_GENERAL = 'general';
	const VIEW_DATA_ID = 'id';
	const VIEW_DATA_GENERAL_PARENT_POST_ID = 'parent_post_id';
	const VIEW_DATA_GENERAL_INITIAL_PARENT_POST_ID = 'initial_parent_post_id';
	const VIEW_DATA_GENERAL_PREVIEW_ID = 'preview_id';
	const VIEW_DATA_GENERAL_VIEW_TEMPLATE = 'view_template';

	/**
	 * Cache for View data fetched from the database. Null if not initialized.
	 *
	 * @var null|array
	 */
	protected $view_data_cache = null;

    /**
     * Default postmeta values common for Views and WPAs.
     *
     * Note that this should contain all postmeta keys they can have, but it doesn't (yet).
     *
     * @todo Add missing default values.
     * @todo Add description to default values.
     * @var array
     */
    protected static $postmeta_defaults = array(
        WPV_View_Base::POSTMETA_DESCRIPTION => '',
        WPV_View_Base::POSTMETA_VIEW_SETTINGS => array(
            WPV_View_Base::VIEW_SETTINGS_CSS => '',
            WPV_View_Base::VIEW_SETTINGS_JS => '',
            WPV_View_Base::VIEW_SETTINGS_QUERY_MODE => 'normal',
			\WPV_Filter_Manager::SETTING_KEY => [],
        ),
        WPV_View_Base::POSTMETA_LOOP_SETTINGS => array( // todo incomplete
            WPV_View_Base::LOOP_SETTINGS_META_HTML => '', // todo this is not a valid default value
            WPV_View_Base::LOOP_SETTINGS_INCLUDED_CT_IDS => ''
        ),
		self::POSTMETA_VIEW_DATA => array( // todo incomplete
			self::VIEW_DATA_ID => 0,
			self::VIEW_DATA_GENERAL => array( // todo incomplete
				self::VIEW_DATA_GENERAL_PREVIEW_ID => 0,
				self::VIEW_DATA_GENERAL_PARENT_POST_ID => 0,
				self::VIEW_DATA_GENERAL_INITIAL_PARENT_POST_ID => 0,
				self::VIEW_DATA_GENERAL_VIEW_TEMPLATE => '',
			),
		),
    );


    /**
     * Get default postmeta common for the View and WPA.
     * @return array
     */
    protected function get_postmeta_defaults() {
        return WPV_View_Base::$postmeta_defaults;
    }



    /* ************************************************************************* *\
            Constants and static methods
    \* ************************************************************************* */


    /**
     * Determine whether View/WPA with given ID exists.
     *
     * @param int $view_id ID of the View to check.
     *
     * @return bool True if post with given ID exists and if it's a View.
     */
    public static function is_valid( $view_id ) {
        /* Note: This should not cause a redundant database query. Post objects are cached by WP core, so this one was
         * either already loaded or it has to be loaded now and will be reused in the future. */
        return WPV_View_Base::is_wppost_view( WP_Post::get_instance( $view_id ) );
    }


    /**
     * For a given object, determine if it's a valid WP_Post object representing a View/WPA.
     *
     * @param mixed $post Value to check.
     *
     * @return bool True if $post is a valid WP_Post object representing a View/WPA, false otherwise.
     */
    public static function is_wppost_view( $post ) {
        return ( ( $post instanceof WP_Post ) && ( $post->ID > 0 ) && ( WPV_View_Base::POST_TYPE == $post->post_type ) );
    }


    /**
     * Determine if the object is used as a WordPress Archive.
     *
     * We cannot rely only on the value of "view query mode" stored in postmeta, because some filters need to be
     * applied along the way. Current implementation causes a get_post_meta() call.
     *
     * @todo can this be done better, without another query or filters?
     *
     * @param int $view_id ID of the object ('view' post type).
     *
     * @return bool True if it is a WPA, false otherwise.
     */
    public static function is_archive_view( $view_id ) {
        global $WP_Views;
        return $WP_Views->is_archive_view( $view_id );
    }


    /**
     * Create an appropriate wrapper for View or WPA post object.
     *
     * Decides by self::is_archive_view() if it's a WPA. Then it checks whether the full version of the wrapper exist,
     * and instantiates it or falls back to the embedded version.
     *
     * @param int|WP_Post $view Post ID or post object.
     *
     * @return null|WPV_View_Embedded|WPV_WordPress_Archive_Embedded|WPV_View|WPV_WordPress_Archive The appropriate wrapper or null on error.
     */
    public static function get_instance( $view ) {
        // http://stackoverflow.com/questions/2559923/shortest-way-to-check-if-a-variable-contains-positive-integer-using-php
        if( (int)$view == $view && (int)$view > 0 ) {
            $post = WP_Post::get_instance( $view );
        } else {
            $post = $view;
        }

        if( ! WPV_View_Base::is_wppost_view( $post ) ) {
            return null;
        }

        try {
            if ( WPV_View_Base::is_archive_view( $post->ID ) ) {
                if( class_exists( 'WPV_WordPress_Archive' ) ) {
                    return new WPV_WordPress_Archive( $post );
                } else {
                    return new WPV_WordPress_Archive_Embedded( $post );
                }
            } else {
                if( class_exists( 'WPV_View' ) ) {
                    return new WPV_View( $post );
                } else {
                    return new WPV_View_Embedded( $post );
                }
            }
        } catch( Exception $ex ) {
            return null;
        }
    }


    /**
     * Determine whether given View name is already used as a post slug or post title.
     *
     * @param string $name View name to check.
     *
     * @param int $except_id The View ID to exclude from checking.
     * @param array &$collision_data (since 1.10) If there is a name collision, this will be set to an array:
     *     - id: ID of the other post
     *     - colliding_field: Where has the collision with $name happened: 'post_title', 'post_name' or 'both'
     *     - post_title: Title of the other post
     *
     * @return bool True if name is already used, false otherwise.
     *
     * @since 1.9
     */
    public static function is_name_used( $name, $except_id = 0, &$collision_data = null ) {
        return WPV_Post_Object_Wrapper::is_name_used_base( $name, WPV_View_Base::POST_TYPE, $except_id, $collision_data );
    }


    /**
     * Generate an unique title for a View/WPA based on a candidate value.
     *
     * @param string $title_candidate Non-blank (e.g. not only whitespace) title candidate.
     * @param int $except_id View/WPA id that should be excluded from the uniqueness check.
     * @return null|string An unique title or null if the input was invalid.
     *
     * @since 1.9
     */
    public static function get_unique_title( $title_candidate, $except_id = 0 ) {
        return WPV_Post_Object_Wrapper::get_unique_title_base( WPV_View_Base::POST_TYPE, $title_candidate, $except_id );
    }


    /**
     * Create new post of the View type.
     *
     * Used for the create() methods for Views and WPAs.
     *
     * @param string $title New post title. Must be unique.
     * @param bool   $args  View/WPA creation arguments
     * @return int ID of the new post.
     * @throws WPV_RuntimeExceptionWithMessage
     * @throws RuntimeException
     * @since 1.10
     */
    protected static function create_post( $title, $args ) {

        // Ensure unique non-empty title
        if( empty( $title ) ) {
            $title = __( 'Unnamed View', 'wpv-views' );
        }

        WPV_View_Base::validate_title( $title );

		$proposed_name = sanitize_text_field( sanitize_title( $title ) );

		if ( empty( $proposed_name ) ) {
			$proposed_name = WPV_View_Base::POST_TYPE . '-rand-' . uniqid();
		}

	    /**
	     * Filters the default post content for the created View.
	     *
	     * @param string $default_content
	     * @param array  $args
	     *
	     * @return array
	     */
		$post_content = apply_filters( 'wpv_filter_view_default_post_content', "[wpv-filter-meta-html]\n[wpv-layout-meta-html]", $args );

        // Create the post
        $post_data = array(
            'post_type'	=> WPV_View_Base::POST_TYPE,
            'post_title' => $title,
			'post_name' => $proposed_name,
            'post_status' => 'publish',
            'post_content' => $post_content,
		);
	    if (
			isset( $args['create_draft'] ) &&
			$args['create_draft']
		) {
			$post_data['post_status'] = 'draft';
		}
        $post_id = wp_insert_post( $post_data );
        if( 0 == $post_id ) {
            throw new RuntimeException( 'cannot wp_insert_post' );
        }

        return $post_id;
    }


    /**
     * Validate new View/WPA title.
     *
     * Throws an exception with an user-friendly message if the title value is not valid:
     * - contains invalid characters
     * - is not unique among existing View/WPA titles and slugs.
     *
     * @param string $value New title.
     * @param int $view_id ID of a View/WPA that should be skipped during checking uniqueness (use this when
     *     changing title of existing View/WPA).
     * @return string Sanitized value that can be used safely.
     * @throws WPV_RuntimeExceptionWithMessage
     * @since 1.10
     */
    protected static function validate_title( $value, $view_id = 0 ) {

        $sanitized_value = sanitize_text_field( $value );

        // Check if the original value contains something that shouldn't be there.
        // We tolerate whitespace at the beginning and end, ergo the trim (but we will
        // work with the trimmed value from now on).
        if( trim( $value ) != $sanitized_value ) {
            throw new WPV_RuntimeExceptionWithMessage(
                '_validate_title failed: invalid characters',
                __( 'The title can not contain any tabs, line breaks or HTML code.', 'wpv-views' )
            );
        }

        if( empty( $sanitized_value ) ) {
            throw new WPV_RuntimeExceptionWithMessage(
                '_validate_title failed: empty value',
                __( 'You can not leave the title empty.', 'wpv-views' )
            );
        }

        $collision_data = array();
        if( WPV_View_Base::is_name_used( $sanitized_value, $view_id, $collision_data ) ) {
	        $view_query_mode = WPV_View_Base::is_archive_view( $collision_data['id'] ) ? __( 'WordPress Archive', 'wpv-views' ) : __( 'View', 'wpv-views' );
            switch( $collision_data['colliding_field'] ) {
                case 'post_name':
                    $exception_message = sprintf(
                        __( 'Another %1$s (%2$s) already uses this slug. Please use another name.', 'wpv-views' ),
	                    $view_query_mode,
                        sanitize_text_field( $collision_data['post_title'] )
                    );
                    break;
                case 'post_title':
                    $exception_message = sprintf( __( 'Another %1$s already uses this name. Please use another name.', 'wpv-views' ), $view_query_mode );
                    break;
                case 'both':
                    $exception_message = sprintf( __( 'Another %1$s already uses this name and slug. Please use another name.', 'wpv-views' ), $view_query_mode );
                    break;
                default:
                    // Should never happen
                    $exception_message = sprintf( __( 'Another %1$s already uses this name and slug. Please use another name.', 'wpv-views' ), $view_query_mode );
                    break;
            }
            //$exception_message = print_r( $collision_data, true );
            throw new WPV_RuntimeExceptionWithMessage(
                '_validate_title failed: name is already being used for another CT',
                $exception_message,
                WPV_RuntimeExceptionWithMessage::EXCEPTION_VALUE_ALREADY_USED
            );
        }

        return $sanitized_value;
    }



    /* ************************************************************************* *\
            Methods
    \* ************************************************************************* */


    /**
     * Class constructor. Create an instance from View ID or WP_Post object representing a View.
     *
     * Please note that WP_Post object will be validated and an exception is thrown on error.
     * However, if only an ID is provided, no such validation takes place here (in order to avoid potentionally
     * unnecessary database query). So, the ID must be validated before (by WPV_View_Base::is_valid() or by other
     * means), otherwise the behaviour of this object is undefined. Also note that "view query mode" is not checked
     * here. If you are not certain about it's value, use self::create().
     *
     * @param int|WP_Post $view View ID or a WP_Post object.
     *
     * @throws InvalidArgumentException when provided argument is not a WP_Post instance representing a View or an
     * integer that *might* be a View ID.
     */
    public function __construct( $view ) {
        if( $view instanceof WP_Post ) {
            // Let's check that we indeed have a valid post and View post type
            if( WPV_View_Base::is_wppost_view( $view ) ) {
                // Store the data we got;
                $this->object_id = $view->ID;
                $this->post = clone $view;
            } else {
                throw new InvalidArgumentException( "Invalid WP_Post object provided (not a View): " . print_r( $view, true ) );
            }
        } elseif( is_numeric( $view ) && $view > 0 ) {
            // We assume (!) this is a valid View ID.
            $this->object_id = $view;
        } else {
            throw new InvalidArgumentException( "Invalid argument provided (not a View or ID): " . print_r( $view, true ) );
        }
    }


    /**
     * Get the post object representing this View.
     *
     * @return WP_Post Post object.
     *
     * @throws InvalidArgumentException if the post object cannot be retrieved or is invalid.
     */
    protected function &post() {

        if( null == $this->post ) {
            // Requesting WP_Post object, but we haven't got it yet.
            $post = WP_Post::get_instance( $this->object_id );
            if( WPV_View_Base::is_wppost_view( $post ) ) {
                $this->post = $post;
            } else {
                throw new InvalidArgumentException( 'Invalid View ID' );
            }
        }

        return $this->post;
    }


    /**
     * @var null|array Cache for View settings.
     */
    protected $views_settings_cache = null;


    /**
     * Obtain View settings. Optional caching.
     *
     * The proper way to obtain View settings is through $WP_Views->get_view_settings(), which applies some filters
     * on it. We may not need to apply them more than once.
     *
     * @param bool $use_cached If true, prefer cached version. Otherwise no caching.
     *
     * @todo review
     *
     * @return array View settings.
     */
    protected function get_view_settings( $use_cached = false ) {
        if( !$use_cached || ( null == $this->views_settings_cache ) ) {
            global $WP_Views;
            $this->views_settings_cache = $WP_Views->get_view_settings( $this->object_id );
        }

        return $this->views_settings_cache;
    }


	/**
	 * Determine if this is a View and not a WPA.
	 * @return bool
	 * @since 1.12
	 */
    public function is_a_view() {
        return false;
    }


	/**
	 * Determine if this is a WPA and not a View
	 * @return bool
	 * @since 1.12
	 */
    public function is_a_wordpress_archive() {
        return false;
    }


    /* ************************************************************************* *\
            Custom getters and setters and validators
    \* ************************************************************************* */


    /**
     * Set View description.
     *
     * @param string $value New description. It will be sanitized before saving.
     *
     * @since 1.10
     */
    protected function _set_description( $value ) {
        $sanitized_value = sanitize_text_field( $value );
        $this->update_postmeta( WPV_View_Base::POSTMETA_DESCRIPTION, $sanitized_value );
    }


    /**
     * View description.
     *
     * @return string
     */
    protected function _get_description() {
        return esc_html( $this->get_postmeta( WPV_View_Base::POSTMETA_DESCRIPTION ) );
    }


    /**
     * Get cached(!) version of View settings array.
     *
     * Please use this only when you are sure you will not break anything by caching.
     *
     * @deprecated Deprecated in favor of _get_view_settings().
     *
     * @return array View settings.
     */
    protected function _get_settings() {
        return $this->get_view_settings( true );
    }



    /**
     * @return string Label for the object depending on "view query mode". Empty string when it's invalid.
     */
    protected function _get_query_mode_display_name() {
        switch( $this->query_mode ) {
            case 'normal':
                return __( 'View', 'wpv-views' );
            case 'archive':
            case 'layouts-loop':
                return __( 'WordPress Archive', 'wpv-views' );
            default:
                // should never happen
                return '';
        }
    }


    protected function _validate_title( $value ) {

        return WPV_View_Base::validate_title( $value, $this->id );

    }


    /**
     * Post title setter.
     *
     * See _validate_title().
     *
     * @param string $value New post title.
     * @throws Exception, WPV_RuntimeExceptionWithMessage
     * @since 1.9
     */
    protected function _set_title( $value ) {

        $value = $this->_validate_title( $value );

        $result = $this->update_post( array( 'post_title' => $value ) );

        if( $result instanceof WP_Error ) {
            throw new Exception( '_set_title failed: WP_Error' );
        }
    }


    /**
     * Post slug validation.
     *
     * Accepts a non-empty value containing only lowercase letters, numbers or dashes.
     *
     * @param string $value New post slug.
     * @return string Sanitized value safe to be used.
     * @throws WPV_RuntimeExceptionWithMessage
     * @since 1.9
     */
    protected function _validate_slug( $value ) {

        $sanitized_value = sanitize_title( $value );
        if( $value != $sanitized_value ) {
            throw new WPV_RuntimeExceptionWithMessage(
                '_validate_slug failed: invalid characters',
                __( 'The slug can only contain lowercase latin letters, numbers or dashes.', 'wpv-views' )
            );
        }

        if( empty( $sanitized_value ) ) {
            throw new WPV_RuntimeExceptionWithMessage(
                '_validate_slug failed: empty value',
                __( 'You can not leave the slug empty.', 'wpv-views' )
            );
        }

        $collision_data = array();
        if( WPV_View_Base::is_name_used( $sanitized_value, $this->id, $collision_data ) ) {
            switch( $collision_data['colliding_field'] ) {
                case 'post_name':
                    $exception_message = sprintf(
                        __( 'Another item (%s) with that slug already exists. Please use another slug.', 'wpv-views' ),
                        sanitize_text_field( $collision_data['post_title'] )
                    );
                    break;
                case 'post_title':
                    $exception_message = __( 'Another item already uses this slug value as its title. Please use another slug.', 'wpv-views' );
                    break;
                case 'both':
                    $exception_message = __( 'Another item already uses this slug value as its slug and title. Please use another slug.', 'wpv-views' );
                    break;
                default:
                    $exception_message = __( 'Another item with that slug or title already exists. Please use another slug.', 'wpv-views' );
                    break;
            }
            throw new WPV_RuntimeExceptionWithMessage(
                '_validate_slug failed: name is already being used for another View/WPA',
                $exception_message,
                WPV_RuntimeExceptionWithMessage::EXCEPTION_VALUE_ALREADY_USED
            );
        }

        return $sanitized_value;
    }


    /**
     * Post slug (a.k.a. post_name) setter.
     *
     * See _validate_slug().
     *
     * @param string $value New post slug.
     * @throws Exception, WPV_RuntimeExceptionWithMessage
     * @since 1.9
     */
    protected function _set_slug( $value ) {

        $sanitized_value = $this->_validate_slug( $value );

        $result = $this->update_post( array( 'post_name' => $sanitized_value ) );

        if( $result instanceof WP_Error ) {
            throw new Exception( '_set_title failed: WP_Error' );
        }
    }


    /* ************************************************************************* *\
        Loop templates
    \* ************************************************************************* */


    /**
     * @return bool True if this View/WPA uses a CT as a Loop Template.
     */
    protected function _get_has_loop_template() {
        return ( $this->loop_template_id > 0 );
    }


    /**
     * @return int ID of the CT used as a Loop Template or zero if no such CT exists.
     */
    protected function _get_loop_template_id() {
        return (int) $this->get_postmeta( WPV_View_Base::POSTMETA_LOOP_TEMPLATE_ID );
    }


    protected function _set_loop_template_id( $value ) {
        $this->update_postmeta( WPV_View_Base::POSTMETA_LOOP_TEMPLATE_ID, (int) $value );
    }


    /**
     * Delete a CT used as a loop template.
     *
     * Deletes the Content Template and removes references to it from
     * loop_template_id and loop_included_ct_ids properties.
     *
     * @param int $ct_id Content Template ID.
     * @return bool True if the operation was successful, false otherwise.
     * @since 1.10
     */
    public function delete_unused_loop_template( $ct_id ) {
        $ct_id = (int) $ct_id;
        if( $ct_id < 1 ) {
            return false;
        }

        wp_delete_post( $ct_id, true );
        $this->loop_template_id = 0;

        $included_ct_ids = $this->loop_included_ct_ids;
        $reg_templates = explode( ',', $included_ct_ids );
        if ( in_array( $ct_id, $reg_templates ) ) {
            $delete_key = array_search( $ct_id, $reg_templates );
            unset( $reg_templates[$delete_key] );
            $this->loop_included_ct_ids = implode( ',', $reg_templates );
        }

        return true;
    }


    /**
     * Create new Content Template and set it as this View's Loop template.
     *
     * Note that this method doesn't care about existing Loop template. That should be handled separately before it
     * is called.
     *
     * @param string $title Valid title for the Content Template (will be adjusted if not unique).
     * @param string $content Content of the CT
     * @throws RuntimeException
     * @return WPV_Content_Template Newly created CT.
     * @since 1.10
     */
    public function create_loop_template( $title, $content = '[wpv-post-link]' ) {

        $ct = WPV_Content_Template::create( $title, true );
        if( ! $ct instanceof WPV_Content_Template ) {
            throw new RuntimeException( 'couldn\'t create the loop template' );
        }

        $ct->defer_after_update_actions();

        // Update Loop and content of the Loop template
        $ct->content = $content;

        $this->loop_meta_html = str_replace(
            '<wpv-loop>',
            sprintf( "<wpv-loop>\n\t\t\t[wpv-post-body view_template=\"%s\"]", $ct->title ),
            $this->loop_meta_html
        );

        // Create bindings between View and Loop template
        $this->loop_included_ct_ids = $ct->id;

        $this->loop_template_id = $ct->id;
        $ct->loop_output_id = $this->id;

        $ct->resume_after_update_actions();

        return $ct;
    }



    /* ************************************************************************* *\
            View settings
    \* ************************************************************************* */

    /* For the sake of brevity we're referring to POSTMETA_VIEW_SETTINGS as "View settings"
     * although they are used by both Views and WPAs.
     *
     * Individual settings may differ for both types of objects. Here are defined only the
     * common ones.
     *
     * Also note there's a mechanism for avoiding redundant database updates if more settings
     * are changed in a row. The intended usage is following:
     * - Call $this->begin_modifying_view_settings().
     * - Then make all desired changes of View settings.
     * - Call $this->finish_modifying_view_settings().
     *
     * For this to work, setters and getters for the settings should use get_view_setting()
     * and set_view_setting().
     */


    /**
     * View settings key for additional CSS code for the loop.
     *
     * @since 1.10
     */
    const VIEW_SETTINGS_CSS = 'layout_meta_html_css';


    /**
     * View settings key for additional JS code for the loop.
     *
     * @since 1.10
     */
    const VIEW_SETTINGS_JS = 'layout_meta_html_js';


    /**
     * Obsolete setting that will be removed if present.
     *
     * @since 1.10
     */
    const VIEW_SETTINGS_FILTER_STATE = 'filter_meta_html_state';


    /**
     * "View query mode" setting indicating whether this is a View or a WPA.
     *
     * Allowed values are:
     * - normal
     * - archive
     * - layouts-loop
     */
    const VIEW_SETTINGS_QUERY_MODE = 'view-query-mode';


	/**
	 * Type of content that is being queried.
	 *
	 * Allowed values are:
	 * - posts
	 * - taxonomy
	 * - users
	 *
	 * Values are stored as a first element of an array (for historical reasons, I assume).
	 * For WPAs, only 'posts' is valid.
	 */
    const VIEW_SETTINGS_QUERY_TYPE = 'query_type';

	/**
	 * Post Relationship Filter View Settings.
	 */
	const VIEW_SETTINGS_POST_RELATIONSHIP_MODE = 'post_relationship_mode';
	const VIEW_SETTINGS_POST_RELATIONSHIP_SHORTCODE_ATTRIBUTE = 'post_relationship_shortcode_attribute';
	const VIEW_SETTINGS_POST_RELATIONSHIP_URL_PARAMETER = 'post_relationship_url_parameter';
	const VIEW_SETTINGS_POST_RELATIONSHIP_SLUG = 'post_relationship_slug';

	/**
	 * ID Filter View Settings
	 */
	const VIEW_SETTINGS_ID_FILTER_ID_IN_OR_OUT = 'id_in_or_out';
	const VIEW_SETTINGS_ID_FILTER_ID_MODE = 'id_mode';
	const VIEW_SETTINGS_ID_FILTER_POST_IDS_URL = 'post_ids_url';
	const VIEW_SETTINGS_ID_FILTER_POST_IDS_SHORTCODE = 'post_ids_shortcode';
	const VIEW_SETTINGS_ID_FILTER_POST_ID_IDS_LIST = 'post_id_ids_list';

	/**
	 * Taxonomy Filter View Settings
	 */
	const VIEW_SETTINGS_TAXONOMY_FILTER_TAXONOMY_RELATIONSHIP = 'taxonomy_relationship';

    /**
     * Defines whether View settings are in the process of being modified.
     *
     * If true, no setting will be saved into database until finish_modifying_view_settings() is called.
     *
     * @var bool
     * @since 1.10
     */
    protected $are_view_settings_being_modified = false;


    /**
     * Indicates that when finish_modifying_view_settings() is called, View settings should be updated.
     *
     * @var bool
     * @since 1.10
     */
    protected $is_view_settings_update_needed = false;


    /**
     * Cache for View settings fetched from the database and eventually modified. Null if not initialized.
     *
     * @var null|array
     * @since 1.10
     */
    protected $view_settings_cache = null;


    /**
     * Get View settings.
     *
     * Uses cached settings if available. For the description of View settings refer to VIEW_SETTING_* constants
     * that define individual settings.
     *
     * @return array
     * @since 1.10
     */
    protected function _get_view_settings() {
        if( null == $this->view_settings_cache ) {
            $this->view_settings_cache = $this->get_postmeta( WPV_View_Base::POSTMETA_VIEW_SETTINGS );
        }
        return $this->view_settings_cache;
    }


    /**
     * Indicate that multiple View settings are going to be changed in order to prevent redundant database queries.
     *
     * After calling this method, no setting will be saved into database until finish_modifying_view_settings() is called.
     *
     * @since 1.10
     */
    public function begin_modifying_view_settings() {
        $this->are_view_settings_being_modified = true;
    }


    /**
     * Indicate that the changes of View settings has ended.
     *
     * If database update is needed, it will be performed now.
     *
     * @since 1.10
     */
    public function finish_modifying_view_settings() {
        if( $this->are_view_settings_being_modified ) {
            $this->are_view_settings_being_modified = false;
            if( $this->is_view_settings_update_needed ) {
                $this->update_view_settings();
            }
        }
    }


	/**
	 * Add or update several View settings at once, like when saving a query filter.
	 *
	 * @param array $settings Settings as key->value pairs.
	 *
	 * @since m2m
	 */
	public function set_view_settings( $settings ) {
		$this->view_settings;
		foreach ( $settings as $setting_key => $setting_value ) {
			if ( $setting_value === toolset_getarr( $this->view_settings_cache, $setting_key ) ) {
				continue;
			}
			$this->set_view_setting( $setting_key, $setting_value );
		}
	}


	/**
	 * Remove one or several View settings at once, like when deleting a query filter.
	 *
	 * @param string|array $setting_keys Setting keys.
	 *
	 * @since m2m
	 */
	public function delete_view_settings( $setting_keys ) {
		if ( ! is_array( $setting_keys ) ) {
			$setting_keys = array( $setting_keys );
		}
		foreach ( $setting_keys as $setting_key ) {
			$this->delete_view_setting( $setting_key );
		}
	}


    /**
     * Indicate that one or more settings have been changed and need updating.
     *
     * If begin_modifying_view_settings() was called before, this will do nothing but indicate View settings
     * need to be updated. Otherwise the update will be executed immediately.
     *
     * @since 1.10
     */
    protected function view_settings_update_needed() {
        if( $this->are_view_settings_being_modified ) {
            $this->is_view_settings_update_needed = true;
        } else {
            $this->update_view_settings();
        }
    }


    /**
     * Get current value for an individual View setting.
     *
     * Read the value from View settings cache, use value from postmeta defaults or an empty string
     * if neither are defined.
     *
     * @param string $setting_key Setting key.
     * @return mixed Setting value.
     * @since 1.10
     */
    protected function get_view_setting( $setting_key ) {
        $view_settings = $this->view_settings;
        $default = $this->get_postmeta_defaults();
        $default = wpv_getarr( $default[ WPV_View_Base::POSTMETA_VIEW_SETTINGS ], $setting_key, '' );
        return wpv_getarr( $view_settings, $setting_key, $default );
    }


    /**
     * Update an individual View setting.
     *
     * Store it's value in cache and indicate that update is needed.
     *
     * @param string $setting_key Setting key.
     * @param string $value Setting value.
     * @since 1.10
     */
    protected function set_view_setting( $setting_key, $value ) {
        $this->view_settings;
        $this->view_settings_cache[ $setting_key ] = $value;
        $this->view_settings_update_needed();
    }

	/**
     * Delete an individual View setting, and indicate that update is needed.
     *
     * @param string $setting_key Setting key.
	 *
     * @since m2m
     */
    protected function delete_view_setting( $setting_key ) {
        $this->view_settings;
		if ( isset( $this->view_settings_cache[ $setting_key ] ) ) {
			unset( $this->view_settings_cache[ $setting_key ] );
		}
        $this->view_settings_update_needed();
    }


    /**
     * Update View settings in database.
     *
     * Resets the $is_view_settings_update_needed flag.
     *
     * @since 1.10
     */
    protected function update_view_settings() {
        $this->is_view_settings_update_needed = false;
        if( null != $this->view_settings_cache ) {

            // Remove deprecated setting
            if( isset( $this->view_settings_cache[ WPV_View_Base::VIEW_SETTINGS_FILTER_STATE ] ) ) {
                unset( $this->view_settings_cache[ WPV_View_Base::VIEW_SETTINGS_FILTER_STATE ] );
            }

            $this->update_postmeta( WPV_View_Base::POSTMETA_VIEW_SETTINGS, $this->view_settings_cache );
        }
    }


    /**
     * Get extra CSS code for the Loop section.
     *
     * This is a View setting.
     *
     * @return string
     * @since 1.10
     */
    protected function _get_css() {
        return $this->get_view_setting( WPV_View_Base::VIEW_SETTINGS_CSS );
    }


    protected function _set_css( $value ) {
        $this->set_view_setting( WPV_View_Base::VIEW_SETTINGS_CSS, $value );
    }


    /**
     * Get extra JS code for the Loop section.
     *
     * This is a View setting.
     *
     * @return string
     * @since 1.10
     */
    protected function _get_js() {
        return $this->get_view_setting( WPV_View_Base::VIEW_SETTINGS_JS );
    }


    protected function _set_js( $value ) {
        $this->set_view_setting( WPV_View_Base::VIEW_SETTINGS_JS, $value );
    }

	/**
	 * Get query filters.
	 *
	 * @return mixed[]
	 */
	protected function _get_filters() {
		return toolset_ensarr( $this->get_view_setting( \WPV_Filter_Manager::SETTING_KEY ) );
	}

	/**
	 * Set query filters.
	 *
	 * @param mixed[] $value
	 */
	protected function _set_filters( $value ) {
		$value = toolset_ensarr( $value );
		$this->set_view_setting( \WPV_Filter_Manager::SETTING_KEY, $value );
	}


    /**
     * Get "query mode", a value determining what kind of object this is.
     *
     * Allowed values are 'normal', 'archive' and 'layouts-loop'. Any other value will default to 'normal'.
     *
     * @return string
     * @since 1.8
     */
    protected function _get_query_mode() {
        $query_mode = $this->get_view_setting( WPV_View_Base::VIEW_SETTINGS_QUERY_MODE );
        if( !in_array( $query_mode, array( 'normal', 'archive', 'layouts-loop' ) ) ) {
            $query_mode = 'normal';
        }
        return $query_mode;
    }


	/**
	 * Type of content that is being queried.
	 *
	 * @return string Defaults to 'posts', see WPV_View_Base::VIEW_SETTINGS_QUERY_TYPE.
	 * @since 1.12
	 */
	protected function _get_query_type() {
		return 'posts';
	}


    /* ************************************************************************* *\
        Loop
    \* ************************************************************************* */

    /* Individual settings may differ for Views and WPAs. Here are defined only the
     * common ones.
     *
     * Also note there's a mechanism for avoiding redundant database updates if more settings
     * are changed in a row. The intended usage is following:
     * - Call $this->begin_modifying_loop_settings().
     * - Then make all desired changes of loop settings.
     * - Call $this->finish_modifying_loop_settings().
     *
     * For this to work, setters and getters for the settings should use get_loop_setting() and set_loop_setting().
     */


    /**
     * Loop settings key for the actual Loop.
     *
     * This setting contains the "meta html" code.
     *
     * @since 1.10
     */
    const LOOP_SETTINGS_META_HTML = 'layout_meta_html';

    /*
     * Loop settings (that need to be documented).
     */
    const LOOP_SETTINGS_STYLE = 'style';
    const LOOP_SETTINGS_TABLE_COLUMN_COUNT = 'table_cols';
    const LOOP_SETTINGS_BS_COLUMN_COUNT = 'bootstrap_grid_cols';
    const LOOP_SETTINGS_BS_GRID_CONTAINER = 'bootstrap_grid_container';
    const LOOP_SETTINGS_BS_ROW_CLASS = 'bootstrap_grid_row_class';
    const LOOP_SETTINGS_BS_INDIVIDUAL = 'bootstrap_grid_individual';
    const LOOP_SETTINGS_INCLUDE_FIELD_NAMES = 'include_field_names';
    const LOOP_SETTINGS_FIELDS = 'fields';
    const LOOP_SETTINGS_REAL_FIELDS = 'real_fields';
    const LOOP_SETTINGS_LIST_SEPARATOR = 'list_separator';


    /**
     * This loop setting contains IDs of Content Templates included in the Loop
     * as a comma-separated string (without spaces).
     *
     * @since 1.10
     */
    const LOOP_SETTINGS_INCLUDED_CT_IDS = 'included_ct_ids';


    /**
     * Defines whether loop settings are in the process of being modified.
     *
     * If true, no setting will be saved into database until finish_modifying_loop_settings() is called.
     *
     * @var bool
     * @since 1.10
     */
    protected $are_loop_settings_being_modified = false;


    /**
     * Indicates that when finish_modifying_loop_settings() is called, loop settings should be updated.
     *
     * @var bool
     * @since 1.10
     */
    protected $is_loop_settings_update_needed = false;


    /**
     * Cache for loop settings fetched from the database and eventually modified. Null if not initialized.
     *
     * @var null|array
     * @since 1.10
     */
    protected $loop_settings_cache = null;


    /**
     * Indicate that multiple loop settings are going to be changed in order to prevent redundant database queries.
     *
     * After calling this method, no setting will be saved into database until finish_modifying_loop_settings() is called.
     *
     * @since 1.10
     */
    public function begin_modifying_loop_settings() {
        $this->are_loop_settings_being_modified = true;
    }


    /**
     * Indicate that the changes of loop settings has ended.
     *
     * If database update is needed, it will be performed now.
     *
     * @since 1.10
     */
    public function finish_modifying_loop_settings() {
        if( $this->are_loop_settings_being_modified ) {
            $this->are_loop_settings_being_modified = false;
            if( $this->is_loop_settings_update_needed ) {
                $this->update_loop_settings();
            }
        }
    }


    /**
     * Indicate that one or more loop settings have been changed and need updating.
     *
     * If begin_modifying_loop_settings() was called before, this will do nothing but indicate loop settings
     * need to be updated. Otherwise the update will be executed immediately.
     *
     * @since 1.10
     */
    protected function loop_settings_update_needed() {
        if( $this->are_loop_settings_being_modified ) {
            $this->is_loop_settings_update_needed = true;
        } else {
            $this->update_loop_settings();
        }
    }


    /**
     * Update loop settings in database.
     *
     * Resets the $is_loop_settings_update_needed flag.
     *
     * @since 1.10
     */
    protected function update_loop_settings() {
        $this->is_loop_settings_update_needed = false;
        if( null != $this->loop_settings_cache ) {
            $this->update_postmeta( WPV_View_Embedded::POSTMETA_LOOP_SETTINGS, $this->loop_settings_cache );
        }
    }


    /**
     * Get current value for an individual loop setting.
     *
     * Read the value from loop settings cache, use value from postmeta defaults or an empty string
     * if neither are defined.
     *
     * @param string $setting_key Setting key.
     * @return mixed Setting value.
     * @since 1.10
     */
    protected function get_loop_setting( $setting_key ) {
        $loop_settings = $this->loop_settings;
        $default = $this->get_postmeta_defaults();
        $default = wpv_getarr( $default[ WPV_View_Base::POSTMETA_LOOP_SETTINGS ], $setting_key, '' );
        return wpv_getarr( $loop_settings, $setting_key, $default );
    }


    /**
     * Update an individual loop setting.
     *
     * Store it's value in cache and indicate that update is needed.
     *
     * @param string $setting_key Setting key.
     * @param string $value Setting value.
     * @since 1.10
     */
    protected function set_loop_setting( $setting_key, $value ) {
        $this->loop_settings;
        $this->loop_settings_cache[ $setting_key ] = $value;
        $this->loop_settings_update_needed();
    }


    /**
     * Get array of loop settings.
     *
     * Uses cached settings if available. For the description of loop settings refer to LOOP_SETTING_* constants
     * that define individual settings.
     *
     * @return array
     * @since 1.10
     */
    protected function _get_loop_settings() {
        if( null == $this->loop_settings_cache ) {
            $this->loop_settings_cache = $this->get_postmeta( WPV_View_Embedded::POSTMETA_LOOP_SETTINGS );
        }
        return $this->loop_settings_cache;
    }


    /**
     * Get the Loop itself.
     *
     * A.k.a. "loop meta HTML".
     *
     * This is a loop setting.
     *
     * @return string
     * @since 1.10
     */
    protected function _get_loop_meta_html() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_META_HTML );
    }


    /**
     * Validate "loop meta html" (content of the Loop editor) before saving it to database.
     *
     * Perform syntax check to ensure mandatory elements are all present exactly once and in the right order.
     * If that's not the case, throw an exception containing a message - this time very user-friendly one,
     * with thorough description of what's wrong and with minimal demo content.
     *
     * @param string $value The value to be sanitized. It *must* have added slashes (especially before quotes), otherwise
     *     the validation has undefined result.
     * @return string The same value if validation has passed.
     * @throws WPV_RuntimeExceptionWithMessage if validation fails.
     * @since 1.10
     */
    protected function _validate_loop_meta_html( $value ) {

        // List of separate elements to match, each with a match pattern and label and indent level for display purposes.
        $elements = array(
            array( 'label' => '[wpv-layout-start]', 'pattern' => "\\[wpv-layout-start\\]", 'indent' => 0 ),
            array( 'label' => '[wpv-items-found]', 'pattern' => "\\[wpv-items-found\\]", 'indent' => 1 ),
            array( 'label' => esc_html( '<!-- wpv-loop-start -->' ), 'pattern' => "<!--\\ wpv-loop-start\\ -->", 'indent' => 1 ),
            array( 'label' => esc_html( '<wpv-loop>' ), 'pattern' => "\\<wpv-loop(\\s+[a-z]+\\=\\\\\\\"[a-z0-9]*\\\\\\\")*\\s*\\>", 'indent' => 1 ),
            array( 'label' => esc_html( '</wpv-loop>' ), 'pattern' => "<\\/wpv-loop>", 'indent' => 1 ),
            array( 'label' => esc_html( '<!-- wpv-loop-end -->' ), 'pattern' => "<!--\\ wpv-loop-end\\ -->", 'indent' => 1 ),
            array( 'label' => '[/wpv-items-found]', 'pattern' => "\\[\\/wpv-items-found\\]", 'indent' => 1 ),
            array( 'label' => '[wpv-layout-end]', 'pattern' => "\\[wpv-layout-end\\]", 'indent' => 0 )
        );

        $this->validate_meta_html_content( $value, __( 'Loop', 'wpv-views' ), $elements );

        return $value;
    }


    /**
     * Update loop meta html.
     *
     * This is a loop setting, so the actual update may be deferred.
     * See WPV_View_Embedded::begin_modifying_loop_settings() for details.
     *
     * @param string $value The value to be sanitized. It *must* have added slashes (especially before quotes), otherwise
     *     the validation has undefined result.
     * @throws WPV_RuntimeExceptionWithMessage if validation fails.
     * @since 1.10
     */
    protected function _set_loop_meta_html( $value ) {

        // Validate or throw.
        $value = $this->_validate_loop_meta_html( $value );

        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_META_HTML, $value );

		/**
		 * Fires once the value for the Loop editor for a View has been updated,
		 * but before it has been saved.
		 *
		 * @since 2.3.0
		 *
		 * @param string 	$value		Value of the Filter editor.
		 * @param int		$this->id	View ID.
		 *
		 * @note This replaces one call used here before: 'wpv_register_wpml_strings'
		 */
		do_action( 'wpv_action_wpv_after_set_loop_meta_html', $value, $this->id );
    }


    /**
     * Validate generic meta HTML content.
     *
     * Based on given match patterns, perform syntax check to ensure mandatory elements are all present exactly once
     * and in the right order. If that's not the case, throw an exception containing a message - this time very
     * user-friendly one, with thorough description of what's wrong and with minimal demo content.
     *
     * @param string $content The value to be sanitized. It *must* have added slashes (especially before quotes),
     *     otherwise the validation has undefined result.
     * @param string $field_name Display name of the field (e.g. "Loop") that will be used
     *     in generated error messages.
     * @param array $elements (
     *         Definition of syntax elements that must be present exactly once in the content. Order of those elements
     *         in this array defines the required order of elements and also how error messages will be generated.
     *
     *         @type string $label The element as it should be displayed in an error message.
     *         @type string $pattern Regex match pattern (without //) to match this particular element.
     *         @type int $indent Indentation level for the "demo code" that will be rendered in the error message.
     *     )
     *
     * @throws WPV_RuntimeExceptionWithMessage if validation fails.
     *
     * @since 1.10
     */
    protected function validate_meta_html_content( $content, $field_name, $elements ) {
        // Check which elements are missing or present too many times.
        $elements_too_many = array();
        $elements_missing = array();
        $is_correct_order = true;
        $previous_element_end_offset = -1;
        foreach( $elements as $element ) {
            $matches = null;
            $match_count = preg_match_all( "/{$element['pattern']}/", $content, $matches, PREG_OFFSET_CAPTURE );

            if( $match_count > 1 ) {

                $elements_too_many[] = $element;

            } else if( 0 == $match_count ) {

                $elements_missing[] = $element;

            } else {

                // Check order of the elements only if none are missing (it would fail)
                $element_offset = $matches[0][0][1];
                $element_length = strlen( $matches[0][0][0] );

                if( $previous_element_end_offset > $element_offset ) {
                    $is_correct_order = false;
                    break;
                }
                $previous_element_end_offset = $element_offset + $element_length;
            }
        }

        $elements_with_problems = array_merge( $elements_too_many, $elements_missing );
        $some_elements_have_problems = ( !empty( $elements_with_problems ) );

        // Throw an exception with a user-readable message if validation didn't pass.
        if( ! $is_correct_order ) {
            $error_message =
                sprintf(
                    '<p>%s</p>%s<p>%s</p>',
                    sprintf(
                        __( 'The %s cannot be saved because required elements are not in correct order.', 'wpv-views' ),
                        $field_name
                    ),
                    $this->generate_demo_meta_html_content( $field_name, $elements ),
                    __( 'Please fix the problem and click on Update again.', 'wpv-views' )
                );
            throw new WPV_RuntimeExceptionWithMessage(
                'validate_meta_html_content: incorrect element order',
                $error_message
            );
        } else if( $some_elements_have_problems ) {

            // List which elements have what problems
            $element_errors = array();
            foreach( $elements_missing as $element ) {
                $element_errors[] = sprintf( __( '%s is missing or malformed.', 'wpv-views' ), "<code>{$element['label']}</code>" );
            }
            foreach( $elements_too_many as $element ) {
                $element_errors[] = sprintf( __( '%s is used more than once.', 'wpv-views' ), "<code>{$element['label']}</code>" );
            }

            $error_message = sprintf(
                '<p>%s</p><ul><li>%s</li></ul>%s<p>%s</p>',
                sprintf(
                    __( 'The %s cannot be saved because some required elements missing or are present more than once. Please make sure that your code contains those elements in correct order:', 'wpv-views' ),
                    $field_name
                ),
                implode( '</li><li>', $element_errors ),
                $this->generate_demo_meta_html_content( $field_name, $elements, $elements_with_problems ),
                __( 'Please fix the problem and click on Update again.', 'wpv-views' )
            );
            throw new WPV_RuntimeExceptionWithMessage(
                'validate_meta_html_content: too much or few elements',
                $error_message
            );
        }
    }


    /**
     * Helper method to generate demo content for error messages in validate_meta_html_content().
     *
     * @param string $field_name Display name of the field (e.g. "Loop") that will be used
     *     in generated error messages.
     * @param array $all_elements Definition of mandatory syntax elements. See validate_meta_html_content().
     * @param array $highlight_elements Subset of $all_elements. Those elements will be rendered in "strong" tags.
     *
     * @return string Rendered HTML.
     *
     * @since 1.10
     */
    private function generate_demo_meta_html_content( $field_name, $all_elements, $highlight_elements = array() ) {
        $list_items = array();
        foreach( $all_elements as $element ) {
            if( in_array( $element, $highlight_elements ) ) {
                $element_label = sprintf( '<strong>%s</strong>', $element['label'] );
            } else {
                $element_label = $element['label'];
            }
            $list_items[] = sprintf( '%s%s', str_repeat( "&nbsp;", $element['indent'] * 4 ), $element_label );
        }
        $demo_content = sprintf(
            '<p>%s</p><p><code>%s</code></p>',
            sprintf(
                __( 'This is a minimal example of %s with mandatory elements that you can use as a reference:', 'wpv-views' ),
                $field_name
            ),
            implode( '<br />', $list_items )
        );
        return $demo_content;
    }


    /* Loop options. These need
     * - documentation
     * - sanitization
     * - some of them perhaps also renaming
     *
     * Everything @since 1.10
     */

    protected function _get_loop_style() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_STYLE );
    }


    protected function _get_loop_table_column_count() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_TABLE_COLUMN_COUNT );
    }


    protected function _get_loop_bs_column_count() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_BS_COLUMN_COUNT );
    }


    protected function _get_loop_bs_grid_container() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_BS_GRID_CONTAINER );
    }


    protected function _get_loop_row_class() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_BS_ROW_CLASS );
    }


    protected function _get_loop_bs_individual() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_BS_INDIVIDUAL );
    }


    protected function _get_loop_include_field_names() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_INCLUDE_FIELD_NAMES );
    }


    protected function _get_loop_fields() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_FIELDS );
    }


    protected function _get_loop_real_fields() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_REAL_FIELDS );
    }


    protected function _get_loop_included_ct_ids() {
        return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_INCLUDED_CT_IDS );
    }


    protected function _set_loop_style( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_STYLE, sanitize_text_field( $value ) );
    }

	protected function _get_list_separator() {
		// We can skip sanitization for this specific field for two reasons:
		//    * Even if we sanitize it, the user will be able to change it back to the unsanitized version in the Loop
		//      so there will be inconsistencies between the saved meta value and the value that appears on the editor.
		//    * WordPress will sanitize the contents of the Loop editor, which will include the skipped fields below,
		//      as the field is saved in the database as part of a serialized array.
		return $this->get_loop_setting( WPV_View_Base::LOOP_SETTINGS_LIST_SEPARATOR );
	}

    protected function _set_loop_table_column_count( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_TABLE_COLUMN_COUNT, sanitize_text_field( $value ) );
    }


    protected function _set_loop_bs_column_count( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_BS_COLUMN_COUNT, sanitize_text_field( $value ) );
    }


    protected function _set_loop_bs_grid_container( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_BS_GRID_CONTAINER, sanitize_text_field( $value ) );
    }


    protected function _set_loop_row_class( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_BS_ROW_CLASS, sanitize_text_field( $value ) );
    }


    protected function _set_loop_bs_individual( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_BS_INDIVIDUAL, sanitize_text_field( $value ) );
    }


    protected function _set_loop_include_field_names( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_INCLUDE_FIELD_NAMES, sanitize_text_field( $value ) );
    }


    protected function _set_loop_fields( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_FIELDS, $value );
    }


    protected function _set_loop_real_fields( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_REAL_FIELDS, $value );
    }


    protected function _set_loop_included_ct_ids( $value ) {
        $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_INCLUDED_CT_IDS, $value );
    }

    protected function _set_list_separator( $value ) {
	    // We can skip sanitization for this specific field for two reasons:
	    //    * Even if we sanitize it, the user will be able to change it back to the unsanitized version in the Loop
	    //      so there will be inconsistencies between the saved meta value and the value that appears on the editor.
	    //    * WordPress will sanitize the contents of the Loop editor, which will include the skipped fields below,
	    //      as the field is saved in the database as part of a serialized array.
	    $this->set_loop_setting( WPV_View_Base::LOOP_SETTINGS_LIST_SEPARATOR, $value );
    }


    /* ************************************************************************* *\
        Loop rendering (static)
    \* ************************************************************************* */


    /**
     * Generate default loop settings (former layout settings) for a View, based on chosen loop style
     *
     * @param string $style Loop style name, which must be one of the following values:
     *     - table
     *     - bootstrap-grid
     *     - table_of_fields
     *     - ordered_list
     *     - un_ordered_list
     *     - unformatted
     *     - empty (since 1.10): Ignores fields and renders just an empty <wpv-loop></wpv-loop>
     *
     * @param array $fields (
     *         Array of definitions of fields that will be present in the loop. If an element is not present, empty
     *         string is used instead.
     *
     *         @type string $prefix Prefix, text before shortcode.
     *         @type string $shortcode The shortcode ('[shortcode]').
     *         @type string $suffix Text after shortcode.
     *         @type string $field_name Field name.
     *         @type string $header_name Header name.
     *         @type string $row_title Row title <TH>.
     *     )
     *
     * @param array $args(
     *         Additional arguments.
     *
     *         @type bool $include_field_names If the loop style is table_of_fields, determines whether the rendered
     *             loop will contain table header with field names. Optional. Default is true.
     *
     *         @type int $tab_column_count Number of columns for the bootstrap-grid style. Optional. Default is 1.
     *         @type int $bootstrap_column_count Number of columns for the table style. Optional. Default is 1.
     *         @type int $bootstrap_version Version of Bootstrap. Mandatory for bootstrap-grid style, irrelephant
     *             otherwise. Must be 2 or 3.
     *         @type bool $add_container Argument for bootstrap-grid style. If true, enclose rendered html in a
     *             container div. Optional. Default is false.
     *         @type bool $add_row_class Argument for bootstrap-grid style. If true, a "row" class will be added to
     *             elements representing rows. For Bootstrap 3 it is added anyway. Optional. Default is false.
     *         @type bool $render_individual_columns Argument for bootstrap-grid style. If true, a wpv-item shortcode
     *             will be rendered for each singular column. Optional. Default is false.
     *
     *         @type bool $render_only_wpv_loop If true, only the code that should be within "<!-- wpv-loop start -->" and
     *             "<!-- wpv-loop end -->" tags is rendered. Optional. Default is false.
     *
     *         @type bool $use_loop_template Determines whether a Content Template will be used for field shortcodes.
     *             If true, the content of the CT will be returned in the 'ct_content' element and the loop will
     *             contain shortcodes referencing it. In such case the argument loop_template_title is mandatory. Optional.
     *             Default is false.
     *
     *         @type string $loop_template_title Title of the Content Template that should contain field shortcodes. Only
     *             relevant if use_loop_template is true, and in such case it is mandatory.
     *     )
     *
     * @return  null|array Null on error. Otherwise an array containing following elements:
     *     array(
     *         @type array loop_output_settings Loop settings for a View, as they should be stored in the database:
     *             array(
     *                 @type string $style
     *                 @type string $layout_meta_html
     *                 @type int $table_cols
     *                 @type int $bootstrap_grid_cols
     *                 @type string $bootstrap_grid_container '1' or ''
     *                 @type string $bootstrap_grid_row_class '1' or ''
     *                 @type string $bootstrap_grid_individual '1' or ''
     *                 @type string $include_field_names '1' or ''
     *                 @type array $fields
     *                 @type array $real_fields
     *             )
     *         @type string ct_content Content of the Content Template (see use_loop_template argument for more info) or
     *             an empty string.
     *     )
     *
     * @since 1.10
     * @since 2.6.4 This method was deprecated and it was replaced by this method inside a non static class.
     *              It became a wrapper that creates an instance of \OTGS\Toolset\Views\ViewLoopOutputGenerator and
     *              calls the "generate" method.
     *
     * @deprecated Use the "generate" method of the "\OTGS\Toolset\Views\View\LoopOutputGenerator()" class instead.
     */
    static function generate_loop_output( $style = 'empty', $fields = array(), $args = array() ) {
    	$loop_output_generator = new \OTGS\Toolset\Views\View\LoopOutputGenerator();
    	return $loop_output_generator->generate( $style, $fields, $args );
    }



    /* ************************************************************************* *\
            Generic View/WPA duplication support
    \* ************************************************************************* */


	/**
	 * Create a duplicate of this View.
	 *
	 * Clone the View and it's postmeta. If there is a Loop Template assigned, duplicate that as well and update
	 * references (in the appropriate postmeta, in shortcodes in loop, etc.) in the duplicated View.
	 *
	 * @param string $new_post_title Title of the new View. Must not be used in any existing View or WPA.
	 * @param bool $adjust_duplicate_title If true, the title might get changed in order to ensure it's uniqueness.
     *     Otherwise, if $title is not unique, the duplication will fail.
	 * @return bool|int ID of the new View or false on error.
	 * @since 1.11
	 */
	public function duplicate( $new_post_title, $adjust_duplicate_title = false ) {

		// Sanitize and validate
		$new_post_slug = sanitize_text_field( sanitize_title( $new_post_title ) );
		$sanitized_title = sanitize_text_field( $new_post_title );

		if( empty( $sanitized_title ) ) {
			return false;
		}

		// Ensure title uniqueness (or fail)
        $is_title_unique = ! WPV_View_Base::is_name_used( $sanitized_title );

		if( !$is_title_unique ) {
			if( $adjust_duplicate_title ) {
				$sanitized_title = WPV_View_Base::get_unique_title( $sanitized_title );
			} else {
				// Non-unique title & we're not allowed to re-use it -> fail
				return null;
			}
		}

		// Clone existing View post object
		$new_post = (array) clone $this->post();
		$new_post['post_title'] = $sanitized_title;

		$keys_to_unset = array( 'ID', 'post_name', 'post_date', 'post_date_gmt' );
		foreach( $keys_to_unset as $key ) {
			unset( $new_post[ $key ] );
		}

		if ( empty( $new_post_slug ) ) {
			$new_post_slug = WPV_View_Base::POST_TYPE . '-rand-' . uniqid();
		}

		$new_post['post_name'] = $new_post_slug;

		$new_post_id = wp_insert_post( $new_post );

		// Clone existing View/WPA postmeta
		$postmeta_keys_to_copy = array_keys( $this->get_postmeta_defaults() );
		$new_postmeta_values = array();
		foreach ( $postmeta_keys_to_copy as $key ) {
			$new_postmeta_values[ $key ] = $this->get_postmeta( $key );
		}

		// If this View has a loop Template, we need to clone it and adjust the layout settings.
		if ( $this->has_loop_template ) {
			$new_postmeta_values = $this->duplicate_loop_template( $new_postmeta_values, $new_post_id, $sanitized_title );
		}

		// Update postmeta of the new View.
		foreach ( $new_postmeta_values as $meta_key => $meta_value ) {
			update_post_meta( $new_post_id, $meta_key, $meta_value );
		}

		return $new_post_id;
	}


    /**
     * Duplicate a loop template of a View and update references to it.
     *
     * @param array $new_postmeta_values Array of postmeta of the View.
     * @param int $new_post_id ID of the View.
     * @param string $new_post_title Post title of the View.
     *
     * @return array Updated array of postmeta values of the View.
     */
    protected function duplicate_loop_template( $new_postmeta_values, $new_post_id, $new_post_title ) {

        // This will throw an exception if the original CT can't be accessed
        $original_ct = new WPV_Content_Template( $this->loop_template_id );

        // Clone the Content Template acting as a Loop template
        $cloned_ct = $original_ct->duplicate( sprintf( __( 'Loop item in %s', 'wpv-views' ), $new_post_title ), true );

        if( null == $cloned_ct ) {
            throw new RuntimeException( 'unable to clone loop template' );
        }

        // Cloning was successful.

        // Create reference from new View to new Loop template.
        $new_postmeta_values[ WPV_View_Base::POSTMETA_LOOP_TEMPLATE_ID ] = $cloned_ct->id;

        // Create reference from new Loop template to new View.
        $cloned_ct->loop_output_id = $new_post_id;

        // Process inline Content templates if there are any.
        $inline_templates = wpv_getarr( $new_postmeta_values[ WPV_View_Base::POSTMETA_LOOP_SETTINGS ], WPV_View_Base::LOOP_SETTINGS_INCLUDED_CT_IDS, '' );
        if ( !empty( $inline_templates ) ) {
            $inline_templates = explode( ',', $inline_templates );

            // Go through all inline templates (referenced in original View) and if we find a reference
            // to original Loop template, we will replace it with new one.
            foreach ( $inline_templates as $inline_template_key => $inline_template_id ) {

                if ( $inline_template_id == $this->loop_template_id ) {
                    // Replace with new Loop template.
                    $inline_templates[ $inline_template_key ] = $cloned_ct->id;
                }
            }

            // Update the array of inline Content templates.
            $new_postmeta_values[ WPV_View_Base::POSTMETA_LOOP_SETTINGS ][ WPV_View_Base::LOOP_SETTINGS_INCLUDED_CT_IDS ] = implode( ',', $inline_templates );
        }


        // Replace name of the old Loop template with new name in Loop.
        $loop_output = wpv_getarr( $new_postmeta_values[ WPV_View_Base::POSTMETA_LOOP_SETTINGS ], WPV_View_Base::LOOP_SETTINGS_META_HTML, '' );
        if ( !empty( $loop_output ) ) {

            // Search and replace Loop template references. Replace all occurences of title and slug by new slugs.
			$new_slug_replacement = sprintf( 'view_template="%s"', sanitize_text_field( $cloned_ct->slug ) );

            $new_loop_output = str_replace( sprintf( 'view_template="%s"', $original_ct->title ), $new_slug_replacement, $loop_output );
            $new_loop_output = str_replace( sprintf( 'view_template="%s"', $original_ct->slug ), $new_slug_replacement, $new_loop_output );

            // Save new value
            $new_postmeta_values[ WPV_View_Base::POSTMETA_LOOP_SETTINGS ][ WPV_View_Base::LOOP_SETTINGS_META_HTML ] = $new_loop_output;
        }

        return $new_postmeta_values;
    }

	/**
	 * Get array of View data related to the View block.
	 *
	 * Uses cached data if available.
	 *
	 * @return array
	 */
	protected function _get_view_data() {
		if( null == $this->view_data_cache ) {
			$this->view_data_cache = $this->get_postmeta( self::POSTMETA_VIEW_DATA );
		}
		return $this->view_data_cache;
	}

	/**
	 * Set View data related to the View block.
	 *
	 * @param mixed $value New set of View block data.
	 */
	protected function _set_view_data( $value ) {
		$this->update_postmeta( self::POSTMETA_VIEW_DATA, $value );
	}

	/**
	 * Get current value for an individual View data setting.
	 *
	 * @param string $setting_key Setting key.
	 *
	 * @return mixed Setting value.
	 */
	protected function get_view_data_setting( $setting_key ) {
		$view_data = $this->view_data;
		$default = $this->get_postmeta_defaults();
		$default = toolset_getarr( $default[ self::POSTMETA_VIEW_DATA ], $setting_key, '' );
		return toolset_getarr( $view_data, $setting_key, $default );
	}

	/**
	 * Get current View data ID, which under normal circumstances is the ID of the View block preview post.
	 *
	 * @return int|string
	 */
	protected function _get_view_data_id() {
		return $this->get_view_data_setting( self::VIEW_DATA_ID );
	}

	/**
	 * Get current View data general block settings.
	 *
	 * @return mixed
	 */
	protected function _get_view_data_general_settings() {
		return $this->get_view_data_setting( self::VIEW_DATA_GENERAL );
	}

	/**
	 * Get current value for an individual View data general block setting.
	 *
	 * @param string $setting_key Setting key.
	 *
	 * @return mixed Setting value.
	 */
	protected function get_view_data_general_setting( $setting_key = null ) {
		$view_data_general = $this->view_data_general_settings;
		$default = $this->get_postmeta_defaults();
		$default = toolset_getarr( $default[ self::VIEW_DATA_GENERAL ], $setting_key, '' );
		return toolset_getarr( $view_data_general, $setting_key, $default );
	}

	/**
	 * Get current View block preview ID.
	 *
	 * @return mixed
	 */
	protected function _get_view_data_preview_id() {
		return $this->get_view_data_general_setting( self::VIEW_DATA_GENERAL_PREVIEW_ID );
	}

	/**
	 * Get current View block parent post ID, which under normal circumstances is the ID of the post that hosts the View block.
	 *
	 * @return mixed
	 */
	protected function _get_view_data_parent_post_id() {
		return $this->get_view_data_general_setting( self::VIEW_DATA_GENERAL_PARENT_POST_ID );
	}

	/**
	 * Get current View block initial parent post ID, which under normal circumstances is the ID of the post that hosts
	 * the View block and is the same with the parent post ID unless the View has been imported.
	 *
	 * @return mixed
	 */
	protected function _get_view_data_initial_parent_post_id() {
		return $this->get_view_data_general_setting( self::VIEW_DATA_GENERAL_INITIAL_PARENT_POST_ID );
	}

	/**
	 * Get current View block view template markup.
	 *
	 * @return mixed
	 */
	protected function _get_view_data_view_template() {
		return $this->get_view_data_general_setting( self::VIEW_DATA_GENERAL_VIEW_TEMPLATE );
	}
}
