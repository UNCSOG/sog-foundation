<?php
/**
*   Access XML Processor
*   handles import-export to/from XML
*
**/
final class Access_XML_Processor
{
    public static $use_zip_if_available=true;

    private static $add_CDATA=false;
    private static $root='access';
    private static $filename='';

	/*
	 * @since 2.2
	 * Get minimal role from array of roles
	 * for compatibility with old versions
	 */
	private static function get_minimal_role( $roles ){
		$access_roles = \OTGS\Toolset\Access\Models\UserRoles::get_instance ();
        $minimal_role = $minimal_level = '';
        if ( isset($roles[0]) ){
        	$minimal_role = $roles[0];
        	$minimal_level = $level = str_replace( 'level_', '', $access_roles->role_to_level($roles[0]) );
		}
        for( $i=0; $i<count($roles); $i++ ){
			$level = str_replace( 'level_', '', $access_roles->role_to_level($roles[$i]) );
			if ( $level < $minimal_level ){
				$minimal_level = $level;
				$minimal_role = $roles[$i];
			}
		}
		return $minimal_role;
	}

	private static function safe_xml_string( $string ) {
		if( ! defined( 'ENT_XML1' ) ) {
			// PHP 5.3 compatibility https://gist.github.com/slav123/6805169
			$replace = 	array(
				'"'=> "&quot;",
				"&" => "&amp;",
				"'"=> "&apos;",
				"<" => "&lt;",
				">"=> "&gt;"
			);
			return strtr( $string, $replace );
		}

		// phpcs:ignore PHPCompatibility.Constants.NewConstants.ent_xml1Found
		return htmlspecialchars( $string, ENT_XML1, 'UTF-8' );
    }

    private static function arrayToXml($array, $depth, $parent)
    {
        global $wpdb;
        $output = '';

        foreach ($array as $key => $value)
        {
           	if ( $key == 'types' ){
           		$output .= str_repeat(' ', $depth * 4)."<types>\r\n";
				foreach ($value as $types_key => $types_value){
					if ( $types_key == '_custom_read_errors' || $types_key == '_custom_read_errors_value' ||
						$types_key == '_archive_custom_read_errors' || $types_key == '_archive_custom_read_errors_value' ){
						if ( is_array($types_value) ){
							$output .= str_repeat(' ', $depth * 8)."<item>\r\n";
							$output .= str_repeat(' ', $depth * 12)."<item_name>". $types_key ."</item_name>\r\n";
							$output .= str_repeat(' ', $depth * 12)."<post_types>\r\n";
							foreach ($types_value as $post_type => $permissions){
								if ( isset($permissions['permissions']['read']) ){
									$output .= str_repeat(' ', $depth * 16)."<item>\r\n";
									$output .= str_repeat( ' ', $depth * 20 ) . "<item_name>" . self::safe_xml_string( $post_type ) . "</item_name>\r\n";
									foreach ($permissions['permissions']['read'] as $role => $action){
									    if ( $types_key == '_custom_read_errors_value' && $action != '' ){
                                              $error_type = $value['_custom_read_errors'][ $post_type ]['permissions']['read'][ $role ];
                                              if ( $error_type == 'error_layouts' ){
                                                    $action = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_layout_slug( $action );
                                              }
                                              if ( $error_type == 'error_ct' ){
                                                    $action = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_content_template_slug( $action );
                                              }

                                        }
                                        if ( $types_key == '_archive_custom_read_errors_value' && $action != '' ){
                                              $error_type = $value['_archive_custom_read_errors'][ $post_type ]['permissions']['read'][ $role ];
                                              if ( $error_type == 'error_layouts' ){
                                                    $action = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_layout_slug( $action );
                                              }
                                              if ( $error_type == 'error_ct' ){
                                                    $action = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_views_archive_slug( $action );
                                              }
                                        }
										$output .= str_repeat( ' ', $depth * 24 ) . '<' . $role . '>' . $action . '</' . $role . ">\r\n";
									}
									$output .= str_repeat( ' ', $depth * 16 ) . "</item>\r\n";
								}
							}

							if ( isset($types_value['permissions']) && is_array($types_value['permissions']) ){
									$output .= str_repeat( ' ', $depth * 12 ) . "<permissions>\r\n";
									foreach ($types_value['permissions'] as $action => $role){
										$output .= str_repeat(' ', $depth * 16)."<$action>\r\n";
										$output .= str_repeat(' ', $depth * 20)."<role>". $role['role'] ."</role>\r\n";
										if ( isset( $role['users'] ) ){
											$output .= str_repeat(' ', $depth * 20)."<users>\r\n";
											foreach ($role['users'] as $index => $value){
												$output .= str_repeat( ' ', $depth * 20 ) . "<users_item>" . $value . "</users_item>\r\n";
											}
											$output .= str_repeat(' ', $depth * 20)."</users>\r\n";
										}
										$output .= str_repeat(' ', $depth * 16)."</$action>\r\n";
									}
									$output .= str_repeat( ' ', $depth * 12 ) . "</permissions>\r\n";
							}
							$output .= str_repeat(' ', $depth * 12)."</post_types>\r\n";
							$output .= str_repeat(' ', $depth * 8)."</item>\r\n";
						}


					}else{
						if ( is_array($types_value) ){
							$output .= str_repeat(' ', $depth * 8)."<item>\r\n";
							$output .= str_repeat( ' ', $depth * 12 ) . "<item_name>" . $types_key . "</item_name>\r\n";
							$output .= str_repeat(' ', $depth * 12)."<item_mode>". $types_value['mode'] ."</item_mode>\r\n";
							if ( isset($types_value['title']) ){
								$output .= str_repeat( ' ', $depth * 12 ) . "<item_title>" . self::safe_xml_string( $types_value['title'] ) . "</item_title>\r\n";
							}
							if ( isset($types_value['permissions']) && is_array($types_value['permissions']) ){
									$output .= str_repeat( ' ', $depth * 12 ) . "<permissions>\r\n";
									foreach ($types_value['permissions'] as $action => $role){
										$output .= str_repeat(' ', $depth * 16)."<$action>\r\n";
										//TODO
										$output .= str_repeat(' ', $depth * 20)."<role>". self::get_minimal_role($role['roles']) ."</role>\r\n";
										//Add roles
										if ( isset( $role['roles'] ) ){
											$output .= str_repeat(' ', $depth * 20)."<roles>\r\n";
											foreach ($role['roles'] as $roles_index => $roles_value){
												$output .= str_repeat(' ', $depth * 24)."<role>". $roles_value ."</role>\r\n";
											}
											$output .= str_repeat(' ', $depth * 20)."</roles>\r\n";
										}
										if ( isset( $role['users'] ) ){
											$output .= str_repeat(' ', $depth * 20)."<users>\r\n";
											foreach ( $role['users'] as $users_index => $users_value ){
												$output .= str_repeat( ' ', $depth * 24 ) . "<users_item>" . $users_value . "</users_item>\r\n";
											}
											$output .= str_repeat(' ', $depth * 20)."</users>\r\n";
										}
										$output .= str_repeat(' ', $depth * 16)."</$action>\r\n";
									}
									$output .= str_repeat(' ', $depth * 12)."</permissions>\r\n";
							}

                            //Export custom groups
							if ( strpos($types_key,'wpcf-custom-group-') === 0){
								$posts = $wpdb->get_results( $wpdb->prepare( "SELECT posts.ID,posts.post_name from {$wpdb->posts} as posts,{$wpdb->postmeta} as postmeta WHERE postmeta.meta_key='_wpcf_access_group' AND postmeta.meta_value='%s' AND postmeta.post_id=posts.ID", $types_key ));
								if ( count($posts) > 0 ){
									$output .= str_repeat( ' ', $depth * 12 ) . "<group_posts>\r\n";
									foreach( $posts as $temp_post ) :
										$output .= str_repeat(' ', $depth * 16)."<item>". $temp_post->post_name ."</item>\r\n";
									endforeach;
									$output .= str_repeat( ' ', $depth * 12 ) . "</group_posts>\r\n";
								}
							}
                            //export wpml groups
                            if ( strpos($types_key,'wpcf-wpml-group-') === 0){
								$output .= str_repeat( ' ', $depth * 12) . "<group_post_type>\r\n";
									$output .= str_repeat(' ', $depth * 16)."<item>". $types_value['post_type'] ."</item>\r\n";
								$output .= str_repeat( ' ', $depth * 12 ) . "</group_post_type>\r\n";
                                $output .= str_repeat(' ', $depth * 12)."<group_languages>\r\n";
                                    if ( isset($types_value['languages']) && !empty($types_value['languages']) ){
                                        foreach( $types_value['languages'] as $lang => $lang_val ){
                                            $output .= str_repeat(' ', $depth * 16)."<item>". $lang ."</item>\r\n";
                                        }
                                    }
								$output .= str_repeat( ' ', $depth * 12 ) . "</group_languages>\r\n";
							}
							$output .= str_repeat( ' ', $depth * 8 ) . "</item>\r\n";
						}
					}
				}
				$output .= str_repeat(' ', $depth * 4)."</types>\r\n";
			}//End Types

			if ( $key == 'taxonomies' ){
           		$output .= str_repeat(' ', $depth * 4)."<taxonomies>\r\n";
				foreach ($value as $types_key => $types_value){
					if ( is_array($types_value) ){
							$output .= str_repeat(' ', $depth * 8)."<item>\r\n";
							$output .= str_repeat(' ', $depth * 12)."<item_name>". $types_key ."</item_name>\r\n";
							$output .= str_repeat(' ', $depth * 12)."<item_mode>". $types_value['mode'] ."</item_mode>\r\n";
							if ( isset($types_value['permissions']) && is_array($types_value['permissions']) ){
									$output .= str_repeat(' ', $depth * 12)."<permissions>\r\n";
									foreach ($types_value['permissions'] as $action => $role){
										$output .= str_repeat(' ', $depth * 16)."<$action>\r\n";
										if ( isset( $role['roles'] ) ){
											$output .= str_repeat(' ', $depth * 20)."<role>". self::get_minimal_role($role['roles']) ."</role>\r\n";
											$output .= str_repeat(' ', $depth * 20)."<roles>\r\n";
											foreach ($role['roles'] as $index => $value){
												$output .= str_repeat(' ', $depth * 24)."<role>". $value ."</role>\r\n";
											}
											$output .= str_repeat(' ', $depth * 20)."</roles>\r\n";
										}
										if ( isset( $role['users'] ) ){
											$output .= str_repeat(' ', $depth * 20)."<users>\r\n";
											foreach ($role['users'] as $index => $value){
												$output .= str_repeat(' ', $depth * 24)."<users_item>". $value ."</users_item>\r\n";
											}
											$output .= str_repeat(' ', $depth * 20)."</users>\r\n";
										}
										$output .= str_repeat(' ', $depth * 16)."</$action>\r\n";
									}
									$output .= str_repeat(' ', $depth * 12)."</permissions>\r\n";
							}

							$output .= str_repeat(' ', $depth * 8)."</item>\r\n";
						}
				}
				$output .= str_repeat(' ', $depth * 4)."</taxonomies>\r\n";
			}//End Taxonomies

			if ( $key == 'third_party' ){
           		$output .= str_repeat(' ', $depth * 4)."<third_party>\r\n";

				foreach ($value as $types_key => $types_value){
					if ( is_array($types_value) ){
						$output .= str_repeat(' ', $depth * 8)."<$types_key>\r\n";
						foreach ($types_value as $group => $permissions){
							$output .= str_repeat(' ', $depth * 12)."<item>\r\n";
							$group_name = preg_replace("/__FIELDS_GROUP_|__USERMETA_FIELDS_GROUP_/","",$group);
							$output .= str_repeat(' ', $depth * 16)."<item_name>". $group_name ."</item_name>\r\n";
							if ( isset($permissions['mode']) ){
								$output .= str_repeat(' ', $depth * 16)."<item_mode>". $permissions['mode'] ."</item_mode>\r\n";
							}
							if ( isset($permissions['permissions']) && is_array($permissions['permissions']) ){
									$output .= str_repeat(' ', $depth * 16)."<permissions>\r\n";
									foreach ($permissions['permissions'] as $action => $role){
										if ( strpos($action,'create_posts_with_cred_') === 0 || strpos($action,'edit_other_posts_with_cred_') === 0 || strpos($action,'edit_own_posts_with_cred_') === 0 ){
											$cred_form = preg_replace("/create_posts_with_cred_|edit_other_posts_with_cred_|edit_own_posts_with_cred_/","",$action);
											$output .= str_repeat(' ', $depth * 20)."<form_action>\r\n";
											$output .= str_repeat(' ', $depth * 24)."<form_name>". $cred_form ."</form_name>\r\n";
											$output .= str_repeat(' ', $depth * 24)."<form_value>". str_replace( $cred_form, '', $action) ."</form_value>\r\n";
											//Add roles
											if ( isset( $role['roles'] ) ){
												$output .= str_repeat(' ', $depth * 24)."<form_role>". self::get_minimal_role($role['roles']) ."</form_role>\r\n";
												$output .= str_repeat(' ', $depth * 24)."<form_roles>\r\n";
												foreach ($role['roles'] as $index => $value){
													$output .= str_repeat(' ', $depth * 28)."<role>". $value ."</role>\r\n";
												}
												$output .= str_repeat(' ', $depth * 24)."</form_roles>\r\n";
											}
											if ( isset( $role['users'] ) ){
												$output .= str_repeat(' ', $depth * 24)."<form_users>\r\n";
												foreach ($role['users'] as $index => $value){
													$output .= str_repeat(' ', $depth * 28)."<users_item>". $value ."</users_item>\r\n";
												}
												$output .= str_repeat(' ', $depth * 24)."</form_users>\r\n";
											}
											$output .= str_repeat(' ', $depth * 20)."</form_action>\r\n";
										}else{
											$action = preg_replace("/".$group_name."/","",$action);
											$output .= str_repeat(' ', $depth * 20)."<$action>\r\n";

											//Add roles
											if ( isset( $role['roles'] ) ){
												$output .= str_repeat(' ', $depth * 24)."<role>". self::get_minimal_role($role['roles']) ."</role>\r\n";
												$output .= str_repeat(' ', $depth * 24)."<roles>\r\n";
												foreach ($role['roles'] as $index => $value){
													$output .= str_repeat(' ', $depth * 28)."<role>". $value ."</role>\r\n";
												}
												$output .= str_repeat(' ', $depth * 24)."</roles>\r\n";
											}
											if ( isset( $role['users'] ) ){
												$output .= str_repeat(' ', $depth * 24)."<form_users>\r\n";
												foreach ($role['users'] as $index => $value){
													$output .= str_repeat(' ', $depth * 28)."<users_item>". $value ."</users_item>\r\n";
												}
												$output .= str_repeat(' ', $depth * 24)."</form_users>\r\n";
											}
											$output .= str_repeat(' ', $depth * 20)."</$action>\r\n";
										}
									}
									$output .= str_repeat(' ', $depth * 16)."</permissions>\r\n";
							}

							$output .= str_repeat(' ', $depth * 12)."</item>\r\n";
						}
						$output .= str_repeat(' ', $depth * 8)."</$types_key>\r\n";
					}
				}
				$output .= str_repeat(' ', $depth * 4)."</third_party>\r\n";
			}//End Groups and Forms

			if ( $key == 'access_custom_caps' ){
           		$output .= str_repeat(' ', $depth * 4)."<access_custom_caps>\r\n";
				foreach ($value as $types_key => $types_value){
                    if ( !empty($types_key) ){
						$output .= str_repeat(' ', $depth * 8)."<$types_key>". $types_value ."</$types_key>\r\n";
                    }
				}
				$output .= str_repeat(' ', $depth * 4)."</access_custom_caps>\r\n";
			}//End Custom caps


			if ( $key == 'access_custom_roles' ){
           		$output .= str_repeat(' ', $depth * 4)."<access_custom_roles>\r\n";
				foreach ($value as $types_key => $types_value){
					if ( is_array($types_value) ){
							$output .= str_repeat(' ', $depth * 8)."<item>\r\n";
							$output .= str_repeat(' ', $depth * 12)."<item_name>". $types_key ."</item_name>\r\n";
							$output .= str_repeat(' ', $depth * 12)."<item_title>". $types_value['name'] ."</item_title>\r\n";

							if ( isset($types_value['capabilities']) && is_array($types_value['capabilities']) ){
									$output .= str_repeat(' ', $depth * 12)."<capabilities>\r\n";
									foreach ($types_value['capabilities'] as $cap => $val){
										if ( !empty($cap) ){
                                            $output .= str_repeat(' ', $depth * 16)."<$cap>". $val ."</$cap>\r\n";
                                        }
									}
									$output .= str_repeat(' ', $depth * 12)."</capabilities>\r\n";
							}

							$output .= str_repeat(' ', $depth * 8)."</item>\r\n";
						}
				}
				$output .= str_repeat(' ', $depth * 4)."</access_custom_roles>\r\n";
			}//End Custom Roles

        }

        return $output;
    }

    private static function toXml($array, $root_element)
    {
        if (empty($array)) return "";
        $xml = "";
        $xml .= "<?xml version=\"1.0\" encoding=\"". get_option('blog_charset'). "\"?>\r\n";
        $xml .= "<$root_element>\r\n";
        $xml .= self::arrayToXml($array[$root_element], 1, $root_element);
        $xml .="</$root_element>";
        return $xml;
    }

    public static function get_error_type ( $data, $key, $post_type, $role ){
        for ( $i=0, $lim = count( $data ); $i < $lim; $i++ ){
            if ( $data[ $i ]['item_name'] == $key ){
                if ( isset( $data[ $i ]['post_types']['item']['item_name'] ) ){
                    $temp = $data[ $i ]['post_types']['item'];
                    $data[ $i ]['post_types']['item'] = array( $temp );
                }
                for ( $j=0; $j < count( $data[ $i ]['post_types']['item'] ); $j++ ){
                    if ( $data[ $i ]['post_types']['item'][ $j ]['item_name'] == $post_type ){
                        if ( isset($data[ $i ]['post_types']['item'][ $j ][ $role ] ) ){
                            return $data[ $i ]['post_types']['item'][ $j ][ $role ];
                        }
                    }
                }
            }
        }
    }

	private static function ArraytoSettings($data)
    {
    	global $wpdb;
	    $access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance ();
	    $access_roles = \OTGS\Toolset\Access\Models\UserRoles::get_instance ();
        $new_settings = array();
		if ( !is_array($data) ){
			return $data;
		}

		$access_settings = \OTGS\Toolset\Access\Models\Settings::get_instance ();
		foreach ($data as $data_key => $data_value){
			//Custom roles
			if ( $data_key == 'access_custom_roles' && isset($data_value['item']) && is_array($data_value['item']) && count($data_value['item']) > 0 ){
				if ( !isset($data_value['item'][0]) ){
					$temp = $data_value['item'];
					unset($data_value['item']);
					$data_value['item'][0] = $temp;
				}
				$new_settings['access_custom_roles'] = array();
				for ( $i=0, $lim = count($data_value['item']); $i<$lim; $i++){
					$key = 	$data_value['item'][$i]['item_name'];
					$new_settings['access_custom_roles'][$key] = array();
					$new_settings['access_custom_roles'][$key]['name'] = $data_value['item'][$i]['item_name'];
					$new_settings['access_custom_roles'][$key]['title'] = $data_value['item'][$i]['item_title'];
					if ( isset($data_value['item'][$i]['capabilities']) && is_array($data_value['item'][$i]['capabilities']) ){
						$new_settings['access_custom_roles'][$key]['capabilities'] = array();
						foreach ($data_value['item'][$i]['capabilities'] as $action => $role){
							$new_settings['access_custom_roles'][$key]['capabilities'][$action] = $role;
						}
					}
				}
			}
		}


		//Custom roles

		if (isset($new_settings['access_custom_roles']) && !empty($new_settings['access_custom_roles']) )
        {
        	$data['access_custom_roles'] = $new_settings['access_custom_roles'];
        	$access_roles = $access_settings->getAccessRoles();
			foreach ($data['access_custom_roles'] as $role => $role_info){
				$role_name = '';
				if ( isset($role_info['name']) ){
					$role_name = $role_info['name'];
				}
				if ( isset($role_info['title'])){
					$role_name = $role_info['title'];
				}

				if ( empty($role_name) ){
					continue;
				}
				$capabilities = $role_info['capabilities'];
				$success = add_role($role, $role_name, $capabilities);
				if (!is_null($success))
		        {
		            $access_roles[$role]=array(
		                'name'=> $role_name,
		                'caps'=> $capabilities
		            );
		            $access_settings->updateAccessRoles($access_roles);
		        }
			}
			$access_settings->order_wp_roles ( true );
		}

		$new_settings = array();
		foreach ($data as $data_key => $data_value){
			//Types
			if ( $data_key == 'types' && isset($data_value['item']) && is_array($data_value['item']) && count($data_value['item']) > 0 ){
				$new_settings['types'] = array();
                if ( !isset($data_value['item'][0]) ){
                    $temp = $data_value['item'];
                    unset($data_value['item']);
                    $data_value['item'][0] = $temp;
                }
				for ( $i=0, $lim = count($data_value['item']); $i<$lim; $i++){
					$types_value = $data_value['item'][$i];
					$key = $types_value['item_name'];
					$error_types = $archive_error_types = array();
					if ( $key == '_custom_read_errors_value' ){
					    $error_types = $types_value;
                    }
					//Custom Errors
					if ( $key == '_custom_read_errors' || $key == '_custom_read_errors_value' ||
						$key == '_archive_custom_read_errors' || $key == '_archive_custom_read_errors_value' ){
						$new_settings['types'][$key] = array();
						if ( isset($types_value['post_types']['item']) && is_array($types_value['post_types']['item']) ){
						    if ( isset( $types_value['post_types']['item']['item_name'] ) ){
						        $temp = $types_value['post_types']['item'];
						        $types_value['post_types']['item'] = array( $temp );
                            }
							for ( $j=0, $types_lim = count($types_value['post_types']['item']); $j<$types_lim; $j++){
								if ( isset($types_value['post_types']['item'][$j]['item_name']) ){
                                    $sup_key = $types_value['post_types']['item'][$j]['item_name'];
                                    foreach ($types_value['post_types']['item'][$j] as $role => $action){
                                        if ( $role != 'item_name' ){
                                            if ( $key == '_custom_read_errors_value' && $action != '' ){
                                                  $error_type = self::get_error_type( $data_value['item'], '_custom_read_errors', $sup_key, $role );
                                                  if ( $error_type == 'error_layouts' ){
                                                        $action = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_layout_slug( $action, 1 );
                                                  }
                                                  if ( $error_type == 'error_ct' ){
                                                        $action = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_content_template_slug( $action, 1 );
                                                  }

                                            }

                                        if ( $key == '_archive_custom_read_errors_value' && $action != '' ){
                                              $error_type = self::get_error_type( $data_value['item'], '_archive_custom_read_errors', $sup_key, $role );
                                              if ( $error_type == 'error_layouts' ){
                                                    $action = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_layout_slug( $action, 1 );
                                              }
                                              if ( $error_type == 'error_ct' ){
                                                    $action = \OTGS\Toolset\Access\Models\GuiCommon::get_instance()->get_views_archive_slug( $action, 1 );
                                              }
                                        }
                                            $new_settings['types'][ $key ][ $sup_key ]['permissions']['read'][ $role ] = $action;
                                        }
                                    }
                                }
							}
						}
					}
					//Post types and groups
					else{
						$new_settings['types'][$key] = array();
						if ( isset( $types_value['item_mode'] ) ){
							$new_settings['types'][$key]['mode'] =  $types_value['item_mode'];
						}
						if ( isset( $types_value['item_title'] ) ){
							$new_settings['types'][$key]['title'] =  $types_value['item_title'];
						}
						if ( isset($types_value['permissions']) && is_array($types_value['permissions']) ){
							$new_settings['types'][$key]['permissions'] = array();
							foreach ($types_value['permissions'] as $action => $role){
								$new_settings['types'][$key]['permissions'][$action]['roles'] = self::get_roles_array( $role );
								if ( isset($role['users']) ){
									$new_settings['types'][$key]['permissions'][$action]['users'] = $role['users'];
								}
							}
						}

						// Assign  custom groups to posts
						if ( isset($types_value['group_posts']['item'])  ){
							if ( !is_array($types_value['group_posts']['item']) ){
								$types_value['group_posts']['item'] = array($types_value['group_posts']['item']);
							}
							if ( !isset($types_value['group_posts']['item'][0]) ){
								$temp = $types_value['group_posts']['item'];
								$types_value['group_posts']['item'] = '';
								$types_value['group_posts']['item'][] = $temp;
							}
							for ( $j=0, $types_lim = count($types_value['group_posts']['item']); $j<$types_lim; $j++){
								$post_id = $wpdb->get_var( $wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_name = %s", $types_value['group_posts']['item'][$j]) );
								if ( $post_id > 0 ){
									update_post_meta($post_id, '_wpcf_access_group', $key);
								}
							}
						}

                        if ( isset($types_value['group_post_type']) ){
							$new_settings['types'][$key]['post_type'] = $types_value['group_post_type']['item'];
						}

                        if ( isset($types_value['group_languages']['item'])  ){

							if ( is_array($types_value['group_languages']['item']) ){
								if ( !isset($types_value['group_languages']['item'][0]) ){
									$temp = $types_value['group_languages']['item'];
									$types_value['group_languages']['item'] = '';
									$types_value['group_languages']['item'][] = $temp;
								}
								for ( $j=0, $types_lim = count($types_value['group_languages']['item']); $j<$types_lim; $j++){
									$new_settings['types'][$key]['languages'][$types_value['group_languages']['item'][$j]] = 1;
								}
							}else{
								$new_settings['types'][$key]['languages'][$types_value['group_languages']['item']] = 1;
							}
						}

					}//end post types
				}
			}//end types

			//Taxonomies
			if ( $data_key == 'taxonomies' && isset($data_value['item']) && is_array($data_value['item']) && count($data_value['item']) > 0 ){
				$new_settings['taxonomies'] = array();
				if ( !isset($data_value['item'][0]) ){
					$temp = $data_value['item'];
					$data_value['item'] = array();
					$data_value['item'][] = $temp;
				}
				for ( $i=0, $lim = count($data_value['item']); $i<$lim; $i++){
					if ( !isset($data_value['item'][$i]['item_name']) ){
						continue;
					}
					$key = 	$data_value['item'][$i]['item_name'];
					$new_settings['taxonomies'][$key] = array();
					$new_settings['taxonomies'][$key]['mode'] = $data_value['item'][$i]['item_mode'];
					if ( isset($data_value['item'][$i]['permissions']) && is_array($data_value['item'][$i]['permissions']) ){
						$new_settings['taxonomies'][$key]['permissions'] = array();
						foreach ($data_value['item'][$i]['permissions'] as $action => $role){
							$new_settings['taxonomies'][$key]['permissions'][$action]['roles'] = self::get_roles_array( $role );
							if ( isset($role['users']) ){
								$new_settings['taxonomies'][$key]['permissions'][$action]['users'] = $role['users'];
							}
						}
					}
				}
			}

			//Fileds groups/cred forms
			if ( $data_key == 'third_party' && is_array($data_value) ){
				$new_settings['third_party'] = array();
				foreach ($data_value as $group_type => $items){
					//Usermeta groups
					if ( $group_type == '__USERMETA_FIELDS' && is_array($items) ){
						if ( !isset($items['item'][0]) ){
							$temp = $items['item'];
							unset($items);
							$items['item'][0] = $temp;
						}
						$new_settings['third_party'][$group_type] = array();
						$items = $items['item'];
						for ( $i=0, $lim = count($items); $i<$lim; $i++){
							if ( ! isset( $items[ $i ]['item_mode'] ) ){
								continue;
							}
							$key = $items[$i]['item_name'];
							$group_name = '__USERMETA_FIELDS_GROUP_'.$items[$i]['item_name'];
							$new_settings['third_party'][$group_type][$group_name] = array();
							$new_settings['third_party'][$group_type][$group_name]['mode'] = $items[$i]['item_mode'];
							if ( isset($items[$i]['permissions']) && is_array($items[$i]['permissions']) ){
								$new_settings['third_party'][$group_type][$group_name]['permissions'] = array();
								foreach ($items[$i]['permissions'] as $action => $role){
									$new_settings['third_party'][$group_type][$group_name]['permissions'][$action.$key]['roles'] = self::get_roles_array( $role );
									if ( isset($role['form_users']) ){
										$new_settings['third_party'][$group_type][$group_name]['permissions'][$action.$key]['users'] = $role['form_users']['users_item'];
									}
								}
							}
						}
					}

					//Postmeta groups
					if ( $group_type == '__FIELDS' && is_array($items) ){
						if ( !isset($items['item'][0]) ){
							$temp = $items['item'];
							unset($items);
							$items['item'][0] = $temp;
						}
						$new_settings['third_party'][$group_type] = array();
						$items = $items['item'];
						for ( $i=0, $lim = count($items); $i<$lim; $i++){
							if ( ! isset( $items[ $i ]['item_mode'] ) ){
								continue;
							}
							$key = $items[$i]['item_name'];
							$group_name = '__FIELDS_GROUP_'.$items[$i]['item_name'];
							$new_settings['third_party'][$group_type][$group_name] = array();
							$new_settings['third_party'][$group_type][$group_name]['mode'] = '';
							if ( isset($items[$i]['item_mode']) ) {
								$new_settings['third_party'][$group_type][$group_name]['mode'] = $items[$i]['item_mode'];
							}
							if ( isset($items[$i]['permissions']) && is_array($items[$i]['permissions']) ){
								$new_settings['third_party'][$group_type][$group_name]['permissions'] = array();
								foreach ($items[$i]['permissions'] as $action => $role){
									$new_settings['third_party'][$group_type][$group_name]['permissions'][$action.$key]['roles'] = self::get_roles_array( $role );
									if ( isset($role['form_users']) ){
										$new_settings['third_party'][$group_type][$group_name]['permissions'][$action.$key]['users'] = $role['form_users']['users_item'];
									}
								}
							}
						}
					}

					//Forms
					if ( $group_type == '__CRED_CRED' && is_array($items) && isset($items['item']['item_name']) ){

						$key = $items['item']['item_name'];

						$new_settings['third_party']['__CRED_CRED'][$key] = array();
						$new_settings['third_party']['__CRED_CRED'][$key]['mode'] = $items['item']['item_mode'];

						if ( isset($items['item']['permissions']) && is_array($items['item']['permissions']) ){
							$new_settings['third_party'][$key]['permissions'] = array();
							$new_settings['third_party']['__CRED_CRED'][$key]['permissions'] = array();
							foreach ($items['item']['permissions'] as $action => $role){
								if ( $action == 'form_action' ){
									if ( !isset($role[0]) ){
										$temp = $role;
										unset($role);
										$role[0] = $temp;
									}
									for ( $i=0, $lim = count($role); $i<$lim; $i++){
										if ( isset($role[$i]['form_roles']['role']) ){
                                            $new_settings['third_party']['__CRED_CRED'][$key]['permissions'][$role[$i]['form_value'].$role[$i]['form_name']]['roles'] = $role[$i]['form_roles']['role'];
                                        }elseif ( isset( $role[ $i ]['form_role'] ) ){
                                            $new_settings['third_party']['__CRED_CRED'][$key]['permissions'][$role[$i]['form_value'].$role[$i]['form_name']]['roles'] =
                                            $access_roles->get_roles_by_minimal_role( $role[$i]['form_role'] );
                                        }
										if ( isset($role[$i]['form_users']) ){
											$new_settings['third_party']['__CRED_CRED'][$key]['permissions'][$role[$i]['form_value'].$role[$i]['form_name']]['users'] = $role[$i]['form_users']['users_item'];
										}
									}
								}else{
									$new_settings['third_party']['__CRED_CRED'][$key]['permissions'][$action]['roles'] = self::get_roles_array( $role );
									if ( isset($role['form_users']) ){
										$new_settings['third_party'][$key]['permissions'][$action]['users'] = $role['form_users']['users_item'];
										$new_settings['third_party']['__CRED_CRED'][$key]['permissions'][$action]['users'] = $role['form_users']['users_item'];
									}
								}
							}
						}

					}

					if ( $group_type == '__CRED_CRED_USER' && is_array($items) ){

						$key = $items['item']['item_name'];

						$new_settings['third_party']['__CRED_CRED_USER'][$key] = array();
						$new_settings['third_party']['__CRED_CRED_USER'][$key]['mode'] = $items['item']['item_mode'];
						if ( isset($items['item']['permissions']) && is_array($items['item']['permissions']) ){
							$new_settings['third_party']['__CRED_CRED_USER'][$key]['permissions'] = array();
							foreach ($items['item']['permissions'] as $action => $role){
								if ( $action == 'form_action' ){
									if ( !isset($role[0]) ){
										$temp = $role;
										unset($role);
										$role[0] = $temp;
									}
									for ( $i=0, $lim = count($role); $i<$lim; $i++){
										if ( isset($role[$i]['form_roles']['role']) ){
                                            $new_settings['third_party']['__CRED_CRED_USER'][$key]['permissions'][$role[$i]['form_value'].$role[$i]['form_name']]['roles'] = $role[$i]['form_roles']['role'];
                                        }else{
                                            $new_settings['third_party']['__CRED_CRED_USER'][$key]['permissions'][$role[$i]['form_value'].$role[$i]['form_name']]['roles'] =
                                            $access_roles->get_roles_by_minimal_role( $role[$i]['form_role'] );
                                        }
										if ( isset($role[$i]['form_users']) ){
											$new_settings['third_party']['__CRED_CRED_USER'][$key]['permissions'][$role[$i]['form_value'].$role[$i]['form_name']]['users'] = $role[$i]['form_users']['users_item'];
										}
									}
								}else{
									$new_settings['third_party']['__CRED_CRED_USER'][$key]['permissions'][$action]['roles'] = self::get_roles_array( $role );
									if ( isset($role['form_users']) ){
										$new_settings['third_party']['__CRED_CRED_USER'][$key]['permissions'][$action]['users'] = $role['form_users']['users_item'];
									}
								}
							}
						}
					}//End cred user form

				}
			}

			if ( $data_key == 'access_custom_roles' && isset($data_value['item']) && is_array($data_value['item']) && count($data_value['item']) > 0 ){
				if ( !isset($data_value['item'][0]) ){
					$temp = $data_value['item'];
					unset($data_value['item']);
					$data_value['item'][0] = $temp;
				}
				$new_settings['access_custom_roles'] = array();
				for ( $i=0, $lim = count($data_value['item']); $i<$lim; $i++){
					$key = 	$data_value['item'][$i]['item_name'];
					$new_settings['access_custom_roles'][$key] = array();
					$new_settings['access_custom_roles'][$key]['name'] = $data_value['item'][$i]['item_name'];
					$new_settings['access_custom_roles'][$key]['title'] = $data_value['item'][$i]['item_title'];
					if ( isset($data_value['item'][$i]['capabilities']) && is_array($data_value['item'][$i]['capabilities']) ){
						$new_settings['access_custom_roles'][$key]['capabilities'] = array();
						foreach ($data_value['item'][$i]['capabilities'] as $action => $role){
							$new_settings['access_custom_roles'][$key]['capabilities'][$action] = $role;
						}
					}
				}
			}

			//Custom caps
			if ( $data_key == 'access_custom_caps' && is_array($data_value) ){
				$new_settings['access_custom_caps'] = $data_value;
			}
		}
        return $new_settings;
    }

	/**
	 * Method to generate an array of roles. For older Access versions generate roles array by minimal role
	 *
	 * @param array|string $roles_array
	 *
	 * @return array
	 * @since 2.4
	 */
	private static function get_roles_array( $roles_array ) {
		$access_roles = \OTGS\Toolset\Access\Models\UserRoles::get_instance ();
		if ( isset( $roles_array['roles']['role'] ) ){
			//Create array if $roles_array['roles']['role'] is string
			if ( ! is_array( $roles_array['roles']['role'] ) ){
				$roles_array['roles']['role'] = array( $roles_array['roles']['role'] );
			}
			return $roles_array['roles']['role'];
		}else if ( isset( $roles_array['role'] ) ) {
			return $access_roles->get_roles_by_minimal_role( $roles_array['role'] );
		}
		return array();
	}

    private static function toArray($element)
    {
        $element = is_string($element) ? htmlspecialchars_decode(trim($element), ENT_QUOTES) : $element;
        if (!empty($element) && is_object($element))
        {
            $element = (array) $element;
        }
        if (empty($element))
        {
            $element = '';
        }
        if (is_array($element))
        {
            foreach ($element as $k => $v)
            {
                $v = is_string($v) ? htmlspecialchars_decode(trim($v), ENT_QUOTES) : $v;
                if (empty($v))
                {
                    $element[$k] = '';
                    continue;
                }
                $add = self::toArray($v);
                if (!empty($add))
                {
                    $element[$k] = $add;
                }
                else
                {
                    $element[$k] = '';
                }
                // numeric arrays when -> toXml take '_item' suffixes
                // do reverse process here, now it is generic
                if (is_array($element[$k]) && isset($element[$k][$k.'_item']))
                {
                    $element[$k] = array_values((array)$element[$k][$k.'_item']);
                }
            }
        }

        if (empty($element))
        {
            $element = '';
        }

        return $element;
    }

    public static function getSelectedSettingsForExport($settings=array(), $options=array(), &$mode)
    {
        if (empty($settings))
            return array();

        $data=array();
        $access_settings=array();
        $settings_class = \OTGS\Toolset\Access\Models\Settings::get_instance ();

        foreach ((array)$settings as $set)
        {
            switch($set)
            {
                case 'types':
                    $access_settings['types'] = $settings_class->get_types_settings ( true, true );
                    break;
                case 'taxonomies':
                    $access_settings['taxonomies'] = $settings_class->get_tax_settings ( true, true );
                    break;
                case 'third_party':
                    $access_settings['third_party'] = $settings_class->get_third_party_asettings ( true, true );
                    break;
                case 'all':
                    $access_settings['types'] = $settings_class->get_types_settings ( true, true );
                    $access_settings['taxonomies'] = $settings_class->get_tax_settings ( true, true );
                    $access_settings['third_party'] = $settings_class->get_third_party_asettings ( true, true );
                    break;
            }
        }

        // apply some filters for 3rd-party custom capabilities
        if (isset($access_settings['third_party']) && !empty($access_settings['third_party']))
        {
            foreach ($access_settings['third_party'] as $area=>$data)
            {
                $access_settings['third_party'][$area]=apply_filters('access_export_custom_capabilities_'.$area, $access_settings['third_party'][$area], $area);
            }
        }

		// custom caps
		$custom_caps = get_option('wpcf_access_custom_caps');
		if ( is_array($custom_caps) && count($custom_caps) > 0 ){
			foreach ($custom_caps as $cap => $cap_info){
				$access_settings['access_custom_caps'][$cap]=$cap_info;
			}
		}
		global $wp_roles;
		$roles = $wp_roles->roles;
		$add_custom_roles = false;
		foreach ($roles as $role => $details){
			if ( isset($details['capabilities']['wpcf_access_role']) ){
				$add_custom_roles = true;
				$access_settings['access_custom_roles'][$role] = $details;
			}
		}


        $mode='access';
        if ('all'==$settings)
        {
            $mode='all-access-settings';
        }
        else
        {
            $mode='selected-access-settings';
        }

        if (!empty($access_settings))
        {
            $data[self::$root] = $access_settings;
        }
        return $data;
    }

    private static function output($xml, $ajax, $mode)
    {
        $sitename = sanitize_key(get_bloginfo('name'));
        if (!empty($sitename)) {
            $sitename .= '-';
        }

        $filename = $sitename . $mode . '-' . date('Y-m-d') . '.xml';

        $data=$xml;

        if (self::$use_zip_if_available && class_exists('ZipArchive'))
        {
            $zipname = $filename . '.zip';
            $zip = new ZipArchive();
            $tmp='tmp';
            // http://php.net/manual/en/function.tempnam.php#93256
            if (function_exists('sys_get_temp_dir'))
                $tmp=sys_get_temp_dir();
            $file = tempnam($tmp, "zip");
            $zip->open($file, ZipArchive::OVERWRITE);

            $res = $zip->addFromString($filename, $xml);
            $zip->close();
            $data = file_get_contents($file);
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=" . $zipname);
            header("Content-Type: application/zip");
            header("Content-length: " . strlen($data) . "\n\n");
            header("Content-Transfer-Encoding: binary");
            if ($ajax)
                header("Set-Cookie: __AccessExportDownload=true; path=/");
            echo $data;
            unset($data);
            unset($xml);
            unlink($file);
            die();
        }
        else
        {
            // download the xml.
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-Description: File Transfer");
            header("Content-Disposition: attachment; filename=" . $filename);
            header("Content-Type: application/xml");
            header("Content-length: " . strlen($data) . "\n\n");
            if ($ajax)
                header("Set-Cookie: __AccessExportDownload=true; path=/");
            echo $data;
            unset($data);
            unset($xml);
            die();
        }
    }

    private static function readXML($file)
    {
        $data = array();
        $info = pathinfo($file['name']);
        if ( !isset($info['extension']) ){
        	return;
		}
        $is_zip = $info['extension'] == 'zip' ? true : false;
        if ($is_zip)
        {
					if ( class_exists( 'ZipArchive' ) ) {
						$zip = new \ZipArchive();
						if ($zip->open(urldecode($file['tmp_name'])) !== false) {
							$xml_array = $zip->getFromIndex( 0 );
							$zip->close();
			
							return $xml_array;
						} else {
							return new WP_Error('could_not_open_file', __('Unable to open zip file', 'wpcf-access'));
						}
					} else {
						return new WP_Error('could_not_open_file', __('Unable to open zip file', 'wpcf-access'));
					}
        }
        else
        {
            $fh = fopen($file['tmp_name'], 'r');
            if ($fh)
            {
                $data = fread($fh, $file['size']);
                fclose($fh);
            }
        }

        if (!empty($data))
        {

            if (!function_exists('simplexml_load_string'))
            {
                return new WP_Error('xml_missing', __('The Simple XML library is missing.','wpcf-access'));
            }
			$use_errors = libxml_use_internal_errors(true);

            $xml = simplexml_load_string($data);
            libxml_clear_errors();
			libxml_use_internal_errors($use_errors);


            if (!$xml)
            {
                return new WP_Error('not_xml_file', sprintf(__('The XML file (%s) could not be read.','wpcf-access'), $file['name']));
            }

            $import_data = self::toArray($xml);

			$import_data = self::ArraytoSettings($import_data);


            /*taccess_log($import_data);
            taccess_log(TAccess_loader::get('MODEL/Access')->getAccessTypes());
            $import_data=array();*/

            unset($xml);
            return $import_data;

        }
        else
        {
            return new WP_Error('could_not_open_file', __('Could not read the import file.','wpcf-access'));
        }
        return new WP_Error('unknown error', __('Unknown error during import','wpcf-access'));
    }

    private static function importSettings($data, $options=array())
    {
        $access_settings_class = \OTGS\Toolset\Access\Models\Settings::get_instance ();
        $results=array(
            'new'=>0,
            'updated'=>0,
            'deleted'=>0,
            'failed'=>0,
            'errors'=>array()
        );

        $dataTypes=isset($data['types']);
        $dataTax=isset($data['taxonomies']);
        $data3=isset($data['third_party']);
		$custom_caps=isset($data['access_custom_caps']);
		$custom_roles=isset($data['access_custom_roles']);

        $diff=array();
        $intersect=array();

        $access_settings=array(
            'types' => $access_settings_class->get_types_settings ( true, true ),
            'taxonomies' => $access_settings_class->get_tax_settings  ( true, true ),
            'third_party' => $access_settings_class->get_third_party_asettings  ( true, true )
        );

        if ($dataTypes)
        {
            $diff['types']=array_diff_key(is_array($data['types']) ? $data['types'] : array(), is_array($access_settings['types']) ? $access_settings['types'] : array());
            $intersect['types']=array_intersect_key(is_array($data['types']) ? $data['types'] : array(), is_array($access_settings['types']) ? $access_settings['types'] : array());
        }
        if ($dataTax)
        {
            $diff['taxonomies']=array_diff_key(is_array($data['taxonomies']) ? $data['taxonomies'] : array(), is_array($access_settings['taxonomies']) ? $access_settings['taxonomies'] : array());
            $intersect['taxonomies']=array_intersect_key(is_array($data['taxonomies']) ? $data['taxonomies'] : array(), is_array($access_settings['taxonomies']) ? $access_settings['taxonomies'] : array());
        }

        // apply filters for custom 3rd-party capabilities
        if ($data3)
        {
            $diff['third_party']=array();
            $intersect['third_party']=array();
            if(is_array($data['third_party'])){
                foreach ($data['third_party'] as $area=>$adata){
                    $data['third_party'][$area]=apply_filters('access_import_custom_capabilities_'.$area, $data['third_party'][$area], $area);
                    if (isset($access_settings['third_party'][$area]))
                    {
                        $diff['third_party'][$area] = (is_array($data['third_party'][$area])) ? array_diff_key($data['third_party'][$area], $access_settings['third_party'][$area]) : array();
                        $intersect['third_party'][$area] = (is_array($data['third_party'][$area])) ? array_intersect_key($data['third_party'][$area], $access_settings['third_party'][$area]) : array();
                    }
                    else
                    {
                        $diff['third_party'][$area]=$data['third_party'][$area];
                        $intersect['third_party'][$area]=array();
                    }
                }
            }
        }
        //taccess_log(array('Before', $access_settings, $diff, $intersect));

        // import / merge extra settings
        // Types
        if ($dataTypes)
        {
            $access_settings['types']=array_merge(is_array($access_settings['types']) ? $access_settings['types'] : array(), is_array($diff['types']) ? $diff['types'] : array());
            $results['new']+=count($diff['types']);
        }

        // Taxonomies
        if ($dataTax)
        {
            $access_settings['taxonomies']=array_merge(is_array($access_settings['taxonomies']) ? $access_settings['taxonomies'] : array(), is_array($diff['taxonomies']) ? $diff['taxonomies'] : array());
            $results['new']+=count($diff['taxonomies']);
        }

        // Custom caps
        if ($custom_caps)
        {
        	$existing_custom_caps = get_option('wpcf_access_custom_caps');
			if ( empty($existing_custom_caps) || !is_array($existing_custom_caps) ){
				$existing_custom_caps = array();
			}
        	if (isset($options['access-overwrite-existing-settings'])){
           		$new_custom_caps = array_merge($data['access_custom_caps'], $existing_custom_caps);
		 	}else{
		   		$new_custom_caps = $data['access_custom_caps'];
		   	}
			update_option( 'wpcf_access_custom_caps', $new_custom_caps);
		}

		//Custom roles
		if ($custom_roles)
        {
        	$access_roles = $access_settings_class->getAccessRoles ();
			foreach ($data['access_custom_roles'] as $role => $role_info){
				if (isset($options['access-overwrite-existing-settings'])){
					remove_role($role);
				}
				$role_name = '';
				if ( isset($role_info['name']) ){
					$role_name = $role_info['name'];
				}
				if ( isset($role_info['title'])){
					$role_name = $role_info['title'];
				}

				if ( empty($role_name) ){
					continue;
				}
				$capabilities = $role_info['capabilities'];
				$success = add_role($role, $role_name, $capabilities);
				if (!is_null($success))
		        {
		            $access_roles[$role]=array(
		                'name'=> $role_name,
		                'caps'=> $capabilities
		            );
		            $access_settings_class->updateAccessRoles ( $access_roles );
		        }
			}

		}


		// Third-Party
        if ($data3)
        {
            if (!isset($access_settings['third_party'])){
                $access_settings['third_party']=array();
            }

            foreach ($diff['third_party'] as $area=>$adata)
            {
                if (isset($access_settings['third_party'][$area])){

                    $access_settings_array = (is_array($access_settings['third_party'][$area])) ? $access_settings['third_party'][$area] : array();
                    $diff_array = (is_array($diff['third_party'][$area])) ? $diff['third_party'][$area] : array();

                    $access_settings['third_party'][$area] = array_merge($access_settings_array, $diff_array);
                }else{
                    $access_settings['third_party'][$area]=$diff['third_party'][$area];
                    $results['new']+=count($diff['third_party'][$area]);
                }
            }
        }

        //taccess_log(array('Import Extra', $access_settings, $diff, $intersect));

        // overwrite existing settings
        if (isset($options['access-overwrite-existing-settings']))
        {
            if ($dataTypes)
            {
                $access_settings['types']=array_merge($access_settings['types'], $intersect['types']);
                $results['updated']+=count($intersect['types']);
            }
            if ($dataTax)
            {
                $access_settings['taxonomies']=array_merge($access_settings['taxonomies'], $intersect['taxonomies']);
                $results['updated']+=count($intersect['taxonomies']);
            }
            if ($data3)
            {
                foreach ($access_settings['third_party'] as $area=>$adata)
                {
                    if (isset($intersect['third_party'][$area]))
                    {
                        $access_settings['third_party'][$area]=array_merge(is_array($access_settings['third_party'][$area]) ? $access_settings['third_party'][$area] : array(), is_array($intersect['third_party'][$area]) ? $intersect['third_party'][$area] : array());
                        $results['updated']+=count($intersect['third_party'][$area]);
                    }
                }
            }
        }

        //taccess_log(array('Overwrite', $access_settings, $diff, $intersect));

        // remove not imported settings
        if (isset($options['access-remove-not-included-settings']))
        {
            if ($dataTypes)
            {
                $tmp=count($access_settings['types']);
                $access_settings['types']=array_intersect_key(is_array($access_settings['types']) ? $access_settings['types'] : array(), is_array($data['types']) ? $data['types'] : array());
                $results['deleted']+=$tmp-count($access_settings['types']);
            }
            if ($dataTax)
            {
                //taccess_log(array($access_settings['taxonomies'], $data['taxonomies']));
                $tmp=count($access_settings['taxonomies']);
                $access_settings['taxonomies']=array_intersect_key(is_array($access_settings['taxonomies']) ? $access_settings['taxonomies'] : array(), is_array($data['taxonomies']) ? $data['taxonomies'] : array());
                $results['deleted']+=$tmp-count($access_settings['taxonomies']);
                //taccess_log(array($access_settings['taxonomies'], $data['taxonomies']));
            }
            if ($data3)
            {
                foreach ($access_settings['third_party'] as $area=>$adata)
                {
                    if (!isset($data['third_party'][$area]))
                    {
                        //$tmp=count($access_settings['third_party'][$area]);
                        //$access_settings['third_party']=array_diff_key($access_settings['third_party'], $data['third_party']);
                        $results['deleted']+=1; //$tmp-count($access_settings['third_party'][$area]);
                        unset($access_settings['third_party'][$area]);
                    }
                }
            }
        }

        //taccess_log(array('Remove', $access_settings, $diff, $intersect));

        // update settings
        $access_settings_class->updateAccessTypes ( $access_settings['types'] );
        $access_settings_class->updateAccessTaxonomies ( $access_settings['taxonomies'] );
        $access_settings_class->updateAccessThirdParty ( $access_settings['third_party'] );
        return $results;
    }

    public static function exportToXML($settings, $ajax=false)
    {
        $mode='forms';
        $data=self::getSelectedSettingsForExport($settings, array(), $mode);
        $xml=self::toXml($data, self::$root);
        self::output($xml, $ajax, $mode);
    }

    public static function exportToXMLString($settings, $options=array())
    {
        $mode='access';
        // add hashes as extra
        $data=self::getSelectedSettingsForExport($settings, $options, $mode);
        $xml=self::toXml($data,self::$root);
        return $xml;
    }

    public static function importFromXML($file, $options=array())
    {
        $dataresult=self::readXML($file);
        if ($dataresult!==false && !is_wp_error($dataresult))
        {
           $results = self::importSettings($dataresult, $options);
           return $results;
        }
        else
        {
            return $dataresult;
        }
    }

    public static function importFromXMLString($xmlstring, $options=array())
    {
        if (!function_exists('simplexml_load_string'))
        {
            return new WP_Error('xml_missing', __('The Simple XML library is missing.','wpcf-access'));
        }

        $use_errors = libxml_use_internal_errors(true);
		$xml = simplexml_load_string($xmlstring);
        libxml_clear_errors();
		libxml_use_internal_errors($use_errors);
        $dataresult=self::toArray($xml);

		if ( isset($dataresult['types']['item']) || isset($dataresult['taxonomies']['item']) || isset($dataresult['third_party']['__CRED_CRED']['item'])
			|| isset($dataresult['third_party']['__CRED_CRED_USER']['item']) || isset($dataresult['third_party']['__FIELDS']['item']) || isset($dataresult['third_party']['__USERMETA_FIELDS']['item']) ){
			//Import new files
            $dataresult = self::ArraytoSettings($dataresult);

		}

        if (false!==$dataresult && !is_wp_error($dataresult))
        {
           $results = self::importSettings($dataresult, $options);
           return $results;
        }
        else
        {
            return $dataresult;
        }
    }
}
