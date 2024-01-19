<?php

class CRED_Posts_Controller extends CRED_Abstract_Controller {

	/**
	 * @param $get
	 * @param $post
	 *
	 * @since 1.9.3
	 */
	public function get_option_count_by_post_type( $get, $post ) {
		if ( ! current_user_can( CRED_CAPABILITY ) ) {
			$output = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wp-cred' ),
			);
			wp_send_json_error( $output );
		}

		if ( ! isset( $post['post_type'] ) ) {
			$output = array(
				'type' => 'post_type',
				'message' => __( 'Wrong or missing post_type.', 'wp-cred' ),
			);
			wp_send_json_error( $output );
		}

		if ( isset( $post['post_type'] ) ) {
			$post_type = sanitize_text_field( $post['post_type'] );
		}

		$results = CRED_Field_Utils::get_instance()->get_count_posts( $post_type );

		if ( isset( $results ) && is_numeric($results) ) {
			$output = array(
				'count' => (int) $results,
			);
			wp_send_json_success( $output );
		} else {
			$output = array(
				'type' => 'wrong_result',
				'message' => __( 'Error while retrieving result.', 'wp-cred' ),
			);
			wp_send_json_error( $output );
		}
	}

	/**
     * Function used in ajax calls to retrieve the option posts list by post_type
     *
	 * @param $get
	 * @param $post
	 *
	 * @since 1.9.3
	 */
	public function get_option_list_by_post_type( $get, $post ) {
		if ( ! current_user_can( CRED_CAPABILITY ) ) {
			$output = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wp-cred' ),
			);
			wp_send_json_error( $output );
		}

		if ( ! isset( $post['post_type'] ) ) {
			$output = array(
				'type' => 'post_type',
				'message' => __( 'Wrong or missing post_type.', 'wp-cred' ),
			);
			wp_send_json_error( $output );
		}

		if ( isset( $post['post_type'] ) ) {
			$post_type = sanitize_text_field( $post['post_type'] );
		}

		/**
		 * cred_select2_ajax_get_posts_by_post_type_query_limit
         *
         * Filter used to set the limit to the query used to retrieve posts by a given post_type
         *
         * @param int $limit
         *
         * @since 1.9.4
		 */
		$limit = apply_filters( 'cred_select2_ajax_get_posts_by_post_type_query_limit', 20 );

		$results = CRED_Field_Utils::get_instance()->get_posts_by_post_type( $post_type, $limit );

		if ( isset( $results ) && ! empty( $results ) ) {
			$output = array();
			if ( is_array( $results ) ) {
				foreach ( $results as $result ) {
					$output[] = array(
						'text' => $result->post_title,
						'id' => $result->ID,
					);
				}
			}
			wp_send_json_success( $output );
		} else {
			$output = array(
				'type' => 'wrong_result',
				'message' => __( 'Error while retrieving result.', 'wp-cred' ),
			);
			wp_send_json_error( $output );
		}
	}

	/**
	 * Suggest Function callback used in CRED Post Forms settings redirect to toolset_select2
	 *
	 * @param $get
	 * @param $post
	 *
	 * @since 1.9.3
	 */
	public function suggest_posts_by_title( $get, $post ) {
		if ( ! current_user_can( CRED_CAPABILITY ) ) {
			$output = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wp-cred' ),
			);
			wp_send_json_error( $output );
		}

		if ( ! isset( $get['q'] ) ) {
			$output = array(
				'type' => 'q',
				'message' => __( 'Wrong or missing q.', 'wp-cred' ),
			);
			wp_send_json_error( $output );
		}

		$post_type = null;
		if ( isset( $get['post_type'] ) ) {
			$post_type = sanitize_text_field( $get['post_type'] );
		}

		/**
		 * cred_select2_ajax_get_posts_by_post_type_query_limit
         *
         * Filter used to handle the limit of get potential parents query
		 * during an ajax call by a select2 ajax component
		 *
		 * @param int $limit
		 *
		 * @since 1.9.4
		 */
		$limit = apply_filters( 'cred_select2_ajax_get_posts_by_post_type_query_limit', 20 );

		$q = sanitize_text_field( $get['q'] );

		$results = CRED_Field_Utils::get_instance()->get_potential_posts( $post_type, $limit, $q );

		if ( isset( $results ) && ! empty( $results ) ) {
			$output = array();
			if ( is_array( $results ) ) {
				foreach ( $results as $result ) {
					$output[] = array(
						'text' => $result->ID." ".$result->post_title,
						'id' => $result->ID,
					);
				}
				wp_send_json_success( $output );
			}
		} else {
			$output = array(
				'type' => 'wrong_result',
				'message' => __( 'Error while retrieving result.', 'wp-cred' ),
			);
			wp_send_json_error( $output );
		}
	}

	public function getPosts( $get, $post ) {
		if ( ! current_user_can( CRED_CAPABILITY ) ) {
			wp_die();
		}

		if ( ! isset( $get['form_id'] ) || ! is_numeric( $get['form_id'] ) ) {
			echo '';
			die();
		}

		$form_id = intval( $get['form_id'] );
		$fm = CRED_Loader::get( 'MODEL/Forms' );
		$form_settings = $fm->getFormCustomField( $form_id, 'form_settings' );
		if ( ! $form_settings ) {
			echo '';
			die();
		}
		//print_r($form_settings);
		$post_type = ( isset( $form_settings->post ) && isset( $form_settings->post['post_type'] ) ) ? $form_settings->post['post_type'] : '';
		$post_query = new WP_Query( array( 'post_type' => $post_type, 'posts_per_page' => - 1 ) );
		ob_start();
		if ( $post_query->have_posts() ) {
			while ( $post_query->have_posts() ) {
				$post_query->the_post();
				?>
                <option value="<?php esc_attr(the_ID()); ?>"><?php the_title(); ?></option>
				<?php
			}
		}
		$output = ob_get_clean();
		echo $output;
		die();
	}

	public function getUsers( $get, $post ) {
		if ( ! current_user_can( CRED_CAPABILITY ) ) {
			wp_die();
		}

		if ( ! isset( $get['form_id'] ) || ! is_numeric( $get['form_id'] ) ) {
			echo '';
			die();
		}

		$form_id = intval( $get['form_id'] );
		$fm = CRED_Loader::get( 'MODEL/UserForms' );
		$users = $fm->getUsers();

		ob_start();
		foreach ( $users as $user ) {
			?>
            <option value="<?php echo esc_attr($user->ID); ?>"><?php echo $user->data->user_nicename; ?></option>
			<?php
		}
		$output = ob_get_clean();
		echo $output;
		die();
	}

	public function suggestUserByName( $get, $post ) {
		if ( ! current_user_can( CRED_CAPABILITY ) ) {
			wp_die();
		}

		if ( ! isset( $get['q'] ) ) {
			echo '';
			die();
		}

		$q = sanitize_text_field( $get['q'] );
		$results = CRED_Loader::get( 'MODEL/UserFields' )->suggestUserByName( $q, 20 );

		$output = '';
		$results2 = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $userdata ) {
				$result = $userdata->data;
				$results2[] = array( 'display' => $result->user_login, 'val' => $result->ID );
			}
			$output = json_encode( $results2 );
		}
		echo $output;
		die();
	}

	public function suggestPagePostsByTitle( $get, $post ) {
		if ( ! current_user_can( CRED_CAPABILITY ) ) {
			wp_die();
		}

		if ( ! isset( $get['q'] ) ) {
			echo '';
			die();
		}

		$q = sanitize_text_field( $get['q'] );
		$results = CRED_Loader::get( 'MODEL/Fields' )->suggestPostsByTitle( $q, array( 'page', 'post' ), 20 );
		$output = '';
		$results2 = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$results2[] = array( 'display' => $result->post_title, 'val' => $result->ID );
			}
			$output = json_encode( $results2 );
		}
		echo $output;
		die();
	}

	public function suggestPostsByTitle( $get, $post ) {
		if ( ! current_user_can( CRED_CAPABILITY ) ) {
			wp_die();
		}

		if ( ! isset( $get['q'] ) ) {
			echo '';
			die();
		}

		$post_type = null;
		if ( isset( $get['cred_post_type'] ) ) {
			$post_type = sanitize_text_field( $get['cred_post_type'] );
		}

		//Force to search post
		if ( $post_type == null ) {
			$post_type = 'post';
		}

		$q = sanitize_text_field( $get['q'] );
		$results = CRED_Loader::get( 'MODEL/Fields' )->suggestPostsByTitle( $q, $post_type, 20 );
		$output = '';
		/* foreach ($results as $result)
		  $output.=$result->post_title."\n"; */
		$results2 = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$results2[] = array( 'display' => $result->post_title, 'val' => $result->ID );
			}
			$output = json_encode( $results2 );
		}
		echo $output;
		die();
	}

	public function suggestPostsTitleByTitle( $get, $post ) {
		if ( ! current_user_can( CRED_CAPABILITY ) ) {
			wp_die();
		}

		if ( ! isset( $get['q'] ) ) {
			echo '';
			die();
		}

		$post_type = null;
		if ( isset( $get['cred_post_type'] ) ) {
			$post_type = sanitize_text_field( $get['cred_post_type'] );
		}
		$q = sanitize_text_field( $get['q'] );
		$results = CRED_Loader::get( 'MODEL/Fields' )->suggestPostsByTitle( $q, $post_type, 20 );
		$output = '';
		$results2 = array();
		if ( is_array( $results ) ) {
			foreach ( $results as $result ) {
				$results2[] = array( 'display' => $result->post_title, 'val' => get_permalink( $result->ID ) );
			}
			$output = json_encode( $results2 );
		}
		echo $output;
		die();
	}

	public function deletePost( $get, $post ) {
		global $current_user;

		/* $return_codes=array(
		  '101'=>'Success',
		  '202'=>'Failure',
		  '404'=>'No post'
		  '505'=>'No permision',
		  ); */

		if (
			! array_key_exists( '_wpnonce', $get ) ||
			! array_key_exists( '_cred_link_id', $get ) ||
			! array_key_exists( 'cred_action', $get ) ||
			! wp_verify_nonce( $get['_wpnonce'], $get['_cred_link_id'] . '_' . $get['cred_action'] )
		) {
			die( 'Security check' );
		}

		$jsfuncs = array();
		$redirect_url = false;
		if ( ! isset( $get['cred_post_id'] ) ) {
			//echo json_encode(false);
			//$jsfuncs['alert']=array("'".esc_js(__('No post defined','wp-cred'))."'");
			$jsfuncs['parent._cred_cred_delete_post_handler'] = array( 'false', '""', '""', '404' );
			echo $this->renderJsFunction( $jsfuncs );
			die();
		}

		$post_id = intval( $get['cred_post_id'] );
		$post = get_post( $post_id );
		if ( $post ) {
			if ( ! current_user_can( 'delete_own_posts_with_cred' ) && $current_user->ID == $post->post_author ) {
				$jsfuncs['parent._cred_cred_delete_post_handler'] = array( 'false', '""', '""', '505' );
				echo $this->renderJsFunction( $jsfuncs );
				die();
				//die('<strong>'.__('Do not have permission (own)','wp-cred').'</strong>');
			}
			if ( ! current_user_can( 'delete_other_posts_with_cred' ) && $current_user->ID != $post->post_author ) {
				$jsfuncs['parent._cred_cred_delete_post_handler'] = array( 'false', '""', '""', '505' );
				echo $this->renderJsFunction( $jsfuncs );
				die();
				//die('<strong>'.__('Do not have permission (other)','wp-cred').'</strong>');
			}
			$action = apply_filters( 'cred_delete_action', $get['cred_action'], $post_id );

			$result = false;
			$redirect_url = false;
			if ( $action && in_array( $action, array( 'delete', 'trash' ) ) ) {

				if ( isset( $get['redirect'] ) && is_numeric( $get['redirect'] ) ) {
					$p = get_post( $get['redirect'] );
					if ( $p ) {
						$redirect_url = '"' . get_permalink( $p->ID ) . '"';
					}
				}

				if ( ( ! $redirect_url || ! isset( $redirect_url ) || $redirect_url == 'false' ) &&
					array_key_exists( '_cred_url', $get ) && ! empty( $get['_cred_url'] ) ) {
					$redirect_url = urldecode( $get['_cred_url'] );
				}

				if ( $redirect_url ) {
					$redirect_url = apply_filters( 'cred_redirect_after_delete_action', $redirect_url, $post_id );
				}

				if ( $redirect_url ) {
					$redirect_url = '"' . preg_replace( "/\"/", "", $redirect_url ) . '"';
				} else {
					$redirect_url = 'false';
				}

				$fm = CRED_Loader::get( 'MODEL/Forms' );
				if ( $get['cred_action'] == 'delete' ) {
					$result = $fm->deletePost( $post_id, true );
				}  // delete
                elseif ( $get['cred_action'] == 'trash' ) {
					$result = $fm->deletePost( $post_id, false ); // trash
					$result = true;
				} else {
					$jsfuncs['parent._cred_cred_delete_post_handler'] = array( 'false', '""', '""', '505' );
					echo $this->renderJsFunction( $jsfuncs );
					die();
				}
			}
			//echo json_encode($result);

			if ( $result ) {
				if ( array_key_exists( '_cred_link_id', $get ) ) {
					$jsfuncs['parent._cred_cred_delete_post_handler'] = array( 'false', '"' . urldecode( $get['_cred_link_id'] ) . '"', $redirect_url, '101' );
				} else {
					$jsfuncs['parent._cred_cred_delete_post_handler'] = array( 'false', '""', $redirect_url, '101' );
				}
				//$jsfuncs['alert']=array("'".esc_js(__('Post deleted','wp-cred'))."'");
			} else {
				if ( array_key_exists( '_cred_link_id', $get ) ) {
					$jsfuncs['parent._cred_cred_delete_post_handler'] = array( 'false', '"' . urldecode( $get['_cred_link_id'] ) . '"', $redirect_url, '202' );
				} else {
					$jsfuncs['parent._cred_cred_delete_post_handler'] = array( 'false', '""', $redirect_url, '202' );
				}
				//$jsfuncs['alert']=array("'".esc_js(__('Post delete failed','wp-cred'))."'");
			}
		}
		echo $this->renderJsFunction( $jsfuncs );
		die();
	}

}
