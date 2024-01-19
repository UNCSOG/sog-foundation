<?php

/**
 * Modifier for the query to populate the association forms roles selectors based on the author of options.
 *
 * @since m2m
 */
class CRED_Potential_Association_Query_Filter_Posts_Author_For_Association_Role
	extends Toolset_Potential_Association_Query_Filter_Posts_Author {
	
	/**
	 * @var int
	 */
	protected $form_id;
	
	/**
	 * @var string
	 */
	protected $role;
	
	function __construct( $form_id, $role ) {
		$this->form_id = $form_id;
		$this->role = $role;
	}
	
	/**
	 * Maybe filter the list of available posts to connect to a given post by their post author.
	 *
	 * Decides whether a filter by post author needs to be set by cascading a series of filters:
	 * - cred_force_author_in_relationship_form_{form_id} | gets also the role name
	 * - cred_force_{role}_author_in_relationship_form_{form_id}
	 * - cred_force_author_in_relationship_form | gets also the form ID and the role name
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
		 * Force a post author on a specific relationship form roles selectors.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_relationship_form_' . $this->form_id,
			$force_author,
			$this->role
		);
		/**
		 * Force a post author on a specific relationship form and a specific role selector.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_' . $this->role . '_author_in_relationship_form_' . $this->form_id,
			$force_author
		);
		/**
		 * Force a post author on all relationship forms roles selectors.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'cred_force_author_in_relationship_form',
			$force_author,
			$this->form_id,
			$this->role
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