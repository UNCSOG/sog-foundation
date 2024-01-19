<?php

/**
 * Modifier for the query to populate the post forms ancestor selectors based on the author of options.
 *
 * @since m2m
 */
class CRED_Potential_Association_Query_Filter_Posts_Author_For_Post_Ancestor
	extends Toolset_Potential_Association_Query_Filter_Posts_Author {
	
	/**
	 * @var int
	 */
	protected $form_id;
	
	/**
	 * @var string
	 */
	protected $field_name;
	
	function __construct( $form_id, $field_name ) {
		$this->form_id = $form_id;
		$this->field_name = $field_name;
	}
	
	/**
	 * Maybe filter the list of available posts to connect to a given post by their post author.
	 *
	 * Decides whether a filter by post author needs to be set by cascading a series of filters:
	 * - cred_force_author_in_ancestor_in_post_form_{form_id} | gets also the field ancestor name
	 * - cred_force_author_in_{field_name}_ancestor_in_post_form_{$form_id}
	 * - cred_force_author_in_ancestor_in_post_form | gets also the form ID and the field ancestor name
	 * - cred_force_author_in_related_post
	 *
	 * Those filters should return either a post author ID or the keyword '$current', which is a placeholder
	 * for the currently logged in user; in case no user is logged in, we force empty query results.
	 *
	 * @param mixed $force_author
	 *
	 * @return mixed
	 *
	 * @since m2m
	 */
	protected function filter_by_plugin( $force_author ) {
		/**
		 * Force a post author on a specific post form ancestor selectors.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_ancestor_in_post_form_' . $this->form_id,
			$force_author,
			$this->field_name
		);
		/**
		 * Force a post author on a specific post form and a specific ancestor selector.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_' . $this->field_name . '_ancestor_in_post_form_' . $this->form_id,
			$force_author
		);
		/**
		 * Force a post author on all post forms ancestor selectors.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_ancestor_in_post_form',
			$force_author,
			$this->form_id,
			$this->field_name
		);
		/**
		 * Force a post author on all CRED interfaces to set a related post.
		 *
		 * This is also used in the frontend post forms when setting a related post.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_related_post',
			$force_author
		);
		
		return $force_author;
	}
	
}