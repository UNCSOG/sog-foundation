<?php

class CRED_Ajax_Handler_Delete_Association extends Toolset_Ajax_Handler_Abstract{

	function process_call( $arguments ) {
		$this->ajax_begin( array(
			'nonce' => CRED_Ajax::CALLBACK_DELETE_ASSOCIATION,
			'is_public' => true,
		) );

		if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing feature.', 'wp-cred' ) ), false );
			return;
		}

		$relationship_slug = toolset_getpost( 'relationship' );

		if ( empty( $relationship_slug ) ) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing relationship name.', 'wp-cred' ) ), false );
			return;
		}

		$related_item_one = toolset_getpost( 'related_item_one' );
		$related_item_two = toolset_getpost( 'related_item_two' );

		if (
			empty( $related_item_one )
			|| empty( $related_item_two )
		) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing relationship.', 'wp-cred' ) ), false );
			return;
		}

		if (
			! current_user_can( 'edit_post', $related_item_one )
			|| ! current_user_can( 'edit_post', $related_item_two )
		) {
			$this->ajax_finish( array( 'message' => __( 'Wrong or missing capabilities.', 'wp-cred' ) ), false );
			return;
		}

		$redirect = toolset_getpost( 'redirect' );

		$results = array(
			'redirect' => is_numeric( $redirect )
				? get_permalink( $redirect )
				: $redirect
		);

		$relationship_repository = Toolset_Relationship_Definition_Repository::get_instance();
		$relationship_definition = $relationship_repository->get_definition( $relationship_slug );
		$relationship_driver = $relationship_definition->get_driver();

		$association_query = new Toolset_Association_Query_V2();
		$association_query->add( $association_query->relationship_slug( $relationship_slug ) );

		$condition_one = $association_query->do_and(
			$association_query->element_id_and_domain( $related_item_one, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Parent() ),
			$association_query->element_id_and_domain( $related_item_two, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child() )
		);
		$condition_two = $association_query->do_and(
			$association_query->element_id_and_domain( $related_item_two, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Parent() ),
			$association_query->element_id_and_domain( $related_item_one, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child() )
		);

		$association_query->add( $association_query->do_or( $condition_one, $condition_two ) );
		$association_query->limit( 2 );

		$associations = $association_query->get_results();

		foreach ( $associations as $association ) {
			$relationship_driver->delete_association( $association );
		}

		$this->ajax_finish( $results, true );
	}

}
