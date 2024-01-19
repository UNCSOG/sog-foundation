<?php

namespace ToolsetCommonEs\Library\WordPress;

class Actions {

	/**
	 * @param string   $tag             The name of the action to which the $function_to_add is hooked.
	 * @param callable $function_to_add The name of the function you wish to be called.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 *                                  Lower numbers correspond with earlier execution,
	 *                                  and functions with the same priority are executed
	 *                                  in the order in which they were added to the action.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 * @return true Will always return true.
	 */
	public function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return add_action( $tag, $function_to_add, $priority, $accepted_args );
	}

	/**
	 * @param string   $tag             The name of the filter to hook the $function_to_add callback to.
	 * @param callable $function_to_add The callback to be run when the filter is applied.
	 * @param int      $priority        Optional. Used to specify the order in which the functions
	 *                                  associated with a particular action are executed. Default 10.
	 *                                  Lower numbers correspond with earlier execution,
	 *                                  and functions with the same priority are executed
	 *                                  in the order in which they were added to the action.
	 * @param int      $accepted_args   Optional. The number of arguments the function accepts. Default 1.
	 * @return true
	 */
	public function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		return add_filter( $tag, $function_to_add, $priority, $accepted_args );
	}

	/**
	 * @return bool
	 * @param string   $tag                The filter hook to which the function to be removed is hooked.
	 * @param callable $function_to_remove The name of the function which should be removed.
	 * @param int      $priority           Optional. The priority of the function. Default 10.
	 * @return bool    Whether the function existed before it was removed.
	 */
	public function remove_filter( $tag, $function_to_remove, $priority = 10 ) {
		return remove_filter(  $tag, $function_to_remove, $priority );
	}

	public function apply_filters( ...$args ) {
		return apply_filters( ...$args );
	}

	/**
	 * @param string $tag    The name of the action to be executed.
	 * @param mixed  ...$arg Optional. Additional arguments which are passed on to the
	 *                       functions hooked to the action. Default empty.
	 */
	public function do_action( $tag, ...$arg ) {
		return do_action( $tag, ...$arg );
	}

	/**
	 * Retrieve the name of the current filter or action.
	 *
	 * @global array $wp_current_filter Stores the list of current filters with the current one last
	 *
	 * @return string Hook name of the current filter or action.
	 */
	public function current_filter() {
		return current_filter();
	}

	/**
	 * Enqueue a CSS stylesheet.
	 *
	 * Registers the style if source provided (does NOT overwrite) and enqueues.
	 *
	 * @see WP_Dependencies::add()
	 * @see WP_Dependencies::enqueue()
	 * @link https://www.w3.org/TR/CSS2/media.html#media-types List of CSS media types.
	 *
	 * @since 2.6.0
	 *
	 * @param string           $handle Name of the stylesheet. Should be unique.
	 * @param string           $src    Full URL of the stylesheet, or path of the stylesheet relative to the WordPress root directory.
	 *                                 Default empty.
	 * @param string[]         $deps   Optional. An array of registered stylesheet handles this stylesheet depends on. Default empty array.
	 * @param string|bool|null $ver    Optional. String specifying stylesheet version number, if it has one, which is added to the URL
	 *                                 as a query string for cache busting purposes. If version is set to false, a version
	 *                                 number is automatically added equal to current installed WordPress version.
	 *                                 If set to null, no version is added.
	 * @param string           $media  Optional. The media for which this stylesheet has been defined.
	 *                                 Default 'all'. Accepts media types like 'all', 'print' and 'screen', or media queries like
	 *                                 '(orientation: portrait)' and '(max-width: 640px)'.
	 */
	public function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		return wp_enqueue_style( $handle, $src, $deps, $ver, $media );
	}

	/**
	 * Enqueue a script.
	 *
	 * Registers the script if $src provided (does NOT overwrite), and enqueues it.
	 *
	 * @see WP_Dependencies::add()
	 * @see WP_Dependencies::add_data()
	 * @see WP_Dependencies::enqueue()
	 *
	 * @since 2.1.0
	 *
	 * @param string           $handle    Name of the script. Should be unique.
	 * @param string           $src       Full URL of the script, or path of the script relative to the WordPress root directory.
	 *                                    Default empty.
	 * @param string[]         $deps      Optional. An array of registered script handles this script depends on. Default empty array.
	 * @param string|bool|null $ver       Optional. String specifying script version number, if it has one, which is added to the URL
	 *                                    as a query string for cache busting purposes. If version is set to false, a version
	 *                                    number is automatically added equal to current installed WordPress version.
	 *                                    If set to null, no version is added.
	 * @param bool             $in_footer Optional. Whether to enqueue the script before </body> instead of in the <head>.
	 *                                    Default 'false'.
	 */
	public function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
		return wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
	}
}
