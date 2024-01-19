<?php

/**
 * Class CRED_Post_Data is responsible to get all Form Fields database value
 * and store everything on a PostData Object
 */
class CRED_Post_Data implements ICRED_Object_Data {

	/**
	 * @param $post_id
	 *
	 * @return null|object|WP_Error
	 */
	public function get_post_data( $post_id ) {
		if ( ! $post_id
			|| ! is_numeric( $post_id ) ) {
			return null;
		}

		$fm = CRED_Loader::get( 'MODEL/Forms' );
		$post_data = $fm->getPost( $post_id );

		if ( $post_data
			&& isset( $post_data[0] ) ) {
			$current_post = $post_data[0];
			$current_fields = isset( $post_data[1] ) ? $post_data[1] : array();
			if ( isset( $current_post->post_title ) ) {
				$current_fields['post_title'] = array( $current_post->post_title );
			}
			if ( isset( $current_post->post_content ) ) {
				$current_fields['post_content'] = array( $current_post->post_content );
			}
			if ( isset( $current_post->post_excerpt ) ) {
				$current_fields['post_excerpt'] = array( $current_post->post_excerpt );
			}
			if ( isset( $current_post->post_parent ) ) {
				$current_fields['post_parent'] = array( $current_post->post_parent );
			}

			return (object) array(
				'post'       => $current_post,
				'fields'     => $current_fields,
				'taxonomies' => isset( $post_data[2] ) ? $post_data[2] : array(),
				'extra'      => isset( $post_data[3] ) ? $post_data[3] : array(),
			);
		}

		return $this->error( __( 'Post does not exist', 'wp-cred' ) );
	}

	/**
	 * @param string $msg
	 *
	 * @return WP_Error
	 */
	protected function error( $msg = '' ) {
		return new  WP_Error( 'error', $msg );
	}

	/**
	 * @param $user_id
	 *
	 * @return null|object|WP_Error
	 */
	public function get_user_data( $user_id ) {
		if ( ! $user_id
			|| ! is_numeric( $user_id ) ) {
			return null;
		}

		$fm = CRED_Loader::get( 'MODEL/UserFields' );
		$fields = $fm->getFields( array() );

		$current_user_data = get_userdata( $user_id );
		$current_user_nickname = get_user_meta( $user_id, 'nickname', true );

		if (
			false === $current_user_data
			|| ! isset( $current_user_nickname )
			|| empty( $current_user_nickname ) ) {
			return $this->error( __( 'User does not exist', 'wp-cred' ) );
		}

		$current_user_data->data->nickname = $current_user_nickname;

		if ( $current_user_data ) {
			$data = (array) $current_user_data->data;

			$all_fields = array();

			/*
			 * FORM FIELDS
			 */
			foreach ( $fields['form_fields'] as $key => $value ) {
				if ( $key == 'user_pass' ) {
					continue;
				}
				if ( isset( $data[ $key ] ) ) {
					$all_fields[ $key ][] = $data[ $key ];
				}
			}

			/*
			 * CUSTOM FIELDS
			 */
			foreach ( $fields['custom_fields'] as $key => $value ) {
				if ( ! isset( $value['meta_key'] ) ) {
					$all_fields[ $key ][] = "";
					continue;
				}

				$user_meta = get_user_meta( $user_id, $value['meta_key'], ! ( isset( $value['data']['repetitive'] ) && $value['data']['repetitive'] == 1 ) );
				$all_fields[ $value['meta_key'] ][] = $user_meta;
			}

			$data = (object) $data;
			$data->post_type = 'user';

			return (object) array(
				'user'   => $data,
				'fields' => $all_fields,
			);
		}

		return $this->error( __( 'User does not exist', 'wp-cred' ) );
	}

}
