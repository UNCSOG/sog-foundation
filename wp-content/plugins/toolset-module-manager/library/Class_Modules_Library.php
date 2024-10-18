<?php
class Class_Modules_Library {

	function __construct()
	{

	 if ( ! ini_get('allow_url_fopen') ) {
		 echo '<p>'. __('To make the Module Manager Library work, you need to enable the <strong>allow_url_fopen</strong> option in your server\'s PHP configuration or contact your server administrator','module-manager') . '</p>';
		 return;
	 }
     //Retrieve modules XML
	 $modules_library_published=$this->retrieve_modules_xml_library_refwptypes();

     //Process modules library inputs
     if (!(empty($modules_library_published))) {

     	$modules_library_processed=$this->process_modules_library_inputs($modules_library_published);

     	if (!(empty($modules_library_processed))) {
     	//Retrieve unique module categories
     	$unique_module_categories=$this->get_module_active_module_categories_from_library($modules_library_processed);

     	} else {

     	wp_die('Modules library processed is empty.');

     	}
     	//Retrieved installed modules
     	$currently_installed_modules=$this->get_installed_modules_in_database();

     	if (isset($_GET['module_cat'])) {

     		$module_category_for_display=trim($_GET['module_cat']);
     		$valid_input=$this->validate_the_get_mm($module_category_for_display);
     		if ($valid_input) {
     			//Filter by category
     			$modules_library_processed_filtered=$this->filter_module_library_categories($modules_library_processed,$module_category_for_display);

     			//Render filtered modules library
     			$modules_library_rendered=$this->render_modules_library($modules_library_processed_filtered,$unique_module_categories,$module_category_for_display,$currently_installed_modules);
     		}
     	} else {
     		//Render modules library
     		if (!(empty($modules_library_processed))) {

	  	   	$modules_library_rendered=$this->render_modules_library($modules_library_processed,$unique_module_categories,$module_category_for_display='',$currently_installed_modules);

 	  	  }

     	}

	} else {

	wp_die('Modules library published variable is empty.');

	}


	}

	function validate_the_get_mm($string) {

		if (empty($string)) {
			return false;
		} else {
			$aValid = array('-', '_', ' ');
			if(!ctype_alpha(str_replace($aValid, '', $string))) {
				return false;
			} else {
				return true;
			}
		}

	}
	function retrieve_modules_xml_library_refwptypes() {

		if (defined('MODMAN_LIBRARYXML_PATH')) {

			if ($this->is_user_internet_connected_referencesites()) {
				stream_context_set_default( array(
						'http' => array(
							'timeout' => 1200
						),
						'ssl' => array(
							'verify_peer' => false,
							'verify_peer_name' => false,
						)
					)
				);
				$modules_xml_exported = file_get_contents( MODMAN_LIBRARYXML_PATH );

		    	if ( ! empty( $modules_xml_exported ) ) {
		    		$sites_modules_index_library = json_decode( $modules_xml_exported );

		    	} else {
		    		wp_die( __( '<div class="error">Unable to retrieve contents from the server. Please report this.</div>' ) );
		    	}

		    	if ( $sites_modules_index_library ) {
					return $sites_modules_index_library;
		    	}
			} else {
				wp_die(__('<div class="error">Make sure you are connected to the Internet to access our modules library server.</div>'));
			}
		}
	}

	/**
	 * @param $modules_library_published
	 *
	 * @return mixed
	 */
	function process_modules_library_inputs ( $modules_library_published ) {
		$modules_library_array = array();
		foreach ( $modules_library_published as $key => $site ) {
			foreach ( $site->modules as $index => $module ) {
				$modules_library_array[] = (array) $module;
			}
		}
		$modules_library_array_clean = json_decode( json_encode( $modules_library_array ), true );

		return $modules_library_array_clean;
	}

	function render_modules_library($modules_library_processed,$unique_module_categories,$module_category_for_display,$currently_installed_modules) {
		//Define Install path
		$install_script_path=MODMAN_PLUGIN_URL.'/library/install_module_library.php';
		$toolset_all_disabled = mm_check_toolset_plugins( 'all_disabled' );
		$toolset_all_installed = mm_check_toolset_plugins( 'all_installed' );
		$toolset_all_full_types_views = mm_check_toolset_plugins( 'full_types_views' );
		$toolset_any_embedded = mm_check_toolset_plugins( 'any_embedded' );

		// Compose $installed_modules_data with the actual data for installed modules, needed to display links to read-only summaries for its elements
		if (!(empty($currently_installed_modules)) && (is_array($currently_installed_modules))) {
			$installed_modules_data = ModMan_Loader::get('MODEL/Modules')->getModules();//print_r($installed_modules_data);
		} else {
			$installed_modules_data = array();
		}
		// Include the pagination class
        include 'pagination.class.php';

		?>
		<section id="modules-manager-library-list-content">

        <?php $this->display_categories_on_top_of_library($unique_module_categories,$module_category_for_display); ?>

		<ul class="modules-list">
		<?php
		// If we have an array with items
		if (count($modules_library_processed)) {

        //modules per page
        $modules_per_page=10;

		// Create the pagination object
		$library_pagination = new pagination($modules_library_processed, (isset($_GET['library_page']) ? intval($_GET['library_page']) : 1), $modules_per_page);

		// Decide if the first and last links should show
		$library_pagination ->setShowFirstAndLast(false);

		// You can overwrite the default separator
		$library_pagination ->setMainSeparator('  ');

		// Parse through the pagination class
		$productPages = $library_pagination ->getResults();

		// If we have items
		if (count($productPages) != 0) {
		echo $pageNumbers = '<div id="library_pagination_numbers">'.$library_pagination->getLinks($_GET).'</div>';

			//Loop through the published modules in reference sites
			foreach ($productPages as $key=>$modules_rendered) {
		?>
		  	<li class="modules-list-item">
		  	<div class="thumb">
		  	<a href="<?php echo esc_attr( $modules_rendered['moduleimagelarge']);?>" class="thickbox">
			    <img src="<?php echo esc_attr( $modules_rendered['moduleimage'] );?>" alt="<?php echo esc_attr( $modules_rendered['name'] );?>"></a>
		  	</div>
		  	<div class="modules-list-item-content">
			<h3 class="post-title"><?php echo esc_html( $modules_rendered['name'] );?></h3>
          	<p class="post-categories">

     		<?php
     		$retrieved_categories_list=array();
     		//Remove pagination arguments from URL
     		$without_paginated_url_inpage=remove_query_arg('library_page', $this->get_admin_url_custom());

     		if ($without_paginated_url_inpage) {
     		   if ($modules_rendered['modulecategories']) {
     			foreach ($modules_rendered['modulecategories'] as $category_key=>$category_values) {
                    if ((is_array($category_values)) && (!(empty($category_values)))) {
       					foreach ($category_values as $key_inner=>$category_value) {
                    	    //Form category hyperlink
                     	   $category_value=trim($category_value);
                     	   $category_url_modules_inpage=add_query_arg( 'module_cat',$category_value,$without_paginated_url_inpage);
                     	   $category_inpage_link = '<a href="' . esc_attr( $category_url_modules_inpage ) .'">' . esc_html( $category_value ) . '</a>';
                    	   $retrieved_categories_list[]=$category_inpage_link;
       					}
       			    } else {
                    //Text not an array!
 							//Form category hyperlink
							$category_value=trim($category_values);
							$category_url_modules_inpage=add_query_arg( 'module_cat',$category_value,$without_paginated_url_inpage);
							$category_inpage_link = '<a href="' . esc_attr( $category_url_modules_inpage ) .'">' . esc_html( $category_value ) . '</a>';
							$retrieved_categories_list[]=$category_inpage_link;

                    }
     			}
     			} else {

                wp_die('Module categories does not exist in Modules rendered variable.');

                }
     		$comma_separated_categories = implode(" ", $retrieved_categories_list);
     		echo $comma_separated_categories;
     		} else {

            wp_die('Remove_query_arg fails');

            }
     		?>
          	</p>
          	<div class="entry"><p><?php echo esc_html( $modules_rendered['description'] );?></p>
     		</div>
            <?php
                $doclink_exists = isset( $modules_rendered['moduledoclink'] ) && ! empty( $modules_rendered['moduledoclink'] );
                $livedemolink_exists = isset( $modules_rendered['modulelivedemo'] ) && ! empty( $modules_rendered['modulelivedemo'] );
                if ( $doclink_exists || $livedemolink_exists ) {
            ?>
                <div class="entry">
                    <p>
                        <?php if( $doclink_exists ) {?>
                            <a href="<?php echo esc_attr( $modules_rendered['moduledoclink'] );?>" target="_blank" style="margin-right:15px;">
	                            <?php _e('Documentation','module-manager');?></a>
                        <?php } ?>
                        <?php if( $livedemolink_exists ) {?>
                            <a href="<?php echo esc_attr( $modules_rendered['modulelivedemo'] );?>" target="_blank" class="modman_livedemo">
	                            <?php _e('Live demo','module-manager');?></a>
                        <?php } ?>
                    </p>
                </div>
            <?php } ?>
			<ul class="checked">
     		<?php
     		$retrieved_attributes_list=array();
     		foreach ($modules_rendered['moduleattributes'] as $attribute_key=>$attribute_values) {
                if ((is_array($attribute_values)) && (!(empty($attribute_values)))) {
       				foreach ($attribute_values as $key_inner_attribute=>$attribute_value) {
       					$retrieved_attributes_list[]=$attribute_value;
       				}
       			} else {
                //string not array!
                        $retrieved_attributes_list[]=$attribute_values;
                }
     		}
     		foreach ($retrieved_attributes_list as $final_attribute_key=>$final_attribute_value) {
     		?>
				<li><?php echo esc_html( $final_attribute_value );?></li>
     		<?php } ?>
			</ul>
            <?php
            $this->generate_action_buttons ( $currently_installed_modules, $modules_rendered, $install_script_path );
            ?>
            </div>
			</li>
			<?php
            }
			echo $pageNumbers;
			?>
			</ul>
			</section>
			<?php

	   }
	  }

	}

	/**
	 * @param $currently_installed_modules array
	 * @param $modules_rendered array
	 * @param $install_script_path string
	 */
	public function generate_action_buttons ( $currently_installed_modules, $modules_rendered, $install_script_path ) {
		$clean_module_name = $this->stripoffdates_from_modulefilename_mm( $modules_rendered['path'] );

		$required_plugins_active = $this->is_required_plugins_active( $modules_rendered );
		$action_type = 'new';
		$action_text = __('Install','module-manager');
		if ( ! empty( $currently_installed_modules ) && is_array( $currently_installed_modules ) && in_array( $clean_module_name,$currently_installed_modules ) ) {
			$action_type = 'update';
			$action_text = __('Update','module-manager');
		}

		if ( ! empty( $required_plugins_active['plugins_bad'] ) ) {
			$this->missed_plugins_message( $required_plugins_active, $action_text, $modules_rendered );
		} else{
			$this->generate_install_button( $modules_rendered, $install_script_path, $action_type, $action_text );
		}


	}

	/**
	 * Generate inactive install button with a help popup
	 * @param $required_plugins_active array
	 * @param $action_text string
	 * @param $modules_rendered array
	 */
	public function missed_plugins_message( $required_plugins_active, $action_text, $modules_rendered ){

		$plugins = '<ul>';
		$additional_plugins = '';
		foreach( $required_plugins_active['plugins_ok'] as $plugin ) {
			if ( self::is_toolset_plugin ( $plugin['name'], true ) ) {
				$plugins .= '<li class="modman-plugin-installed"><i class="fa fa-check"></i> ' . esc_html( $plugin['name'] ) . ' (' . esc_html( $plugin['version'] ) . ')</a>';
			} else {
				$additional_plugins .= '<li class="modman-plugin-installed"><i class="fa fa-check"></i> ' . esc_html( $plugin['name'] ) . ' (' . esc_html( $plugin['version'] ) . ')</a>';
			}
		}

		foreach( $required_plugins_active['plugins_bad'] as $plugin ) {
			if ( self::is_toolset_plugin ( $plugin['name']. true ) ) {
				$plugins .= '<li class="modman-plugin-missed"><i class="fa fa-close"></i> ' . esc_html( $plugin['name'] ) . ' (' . esc_html( $plugin['version'] ) . ')</a>';
			} else {
				$additional_plugins .= '<li class="modman-plugin-missed"><i class="fa fa-close"></i> ' . esc_html( $plugin['name'] ) . ' (' . esc_html( $plugin['version'] ) . ')</a>';
			}
		}
		$plugins .= '</ul>';

		if ( ! empty( $additional_plugins ) ) {
			$plugins .= '<p>' . __('Other required plugins','module-manager') . ': '.
			'<ul>' . $additional_plugins . '</ul>';
		}

		if ( $required_plugins_active['plugins_bad'] && ! $required_plugins_active['views_lite_installed'] ) {
			$message = '<p>' . __('To use this module, you need to install and activate the following plugins','module-manager') . ': ';
			$message .= $plugins;
			if ( $required_plugins_active['missed_toolset_plugins'] ) {
				$message .= '<hr><p class="aligncenter">' . __('Do you need to upgrade your Toolset account?','module-manager') . '</p>' .
				'<p class="aligncenter"><a href="https://toolset.com/account/renew-or-upgrade-your-account/?utm_source=plugin&utm_medium=gui&utm_campaign=modulmanager" target="_blank" class="button button-primary-toolset modman-toolset-button">' .
				__( 'Upgrade to 3 Sites', 'module-manager' ) . '</a></p>';
			}


		} else {
			$message = '<p>' . __( 'To use this module, you need to have the full version of','module-manager' ) .
			          ' <a href="https://toolset.com/?utm_source=plugin&utm_medium=gui&utm_campaign=modulmanager" class="modman-link-out" target="_blank">' . __( 'Toolset plugins','module-manager' )
			           . ' <i class="fa fa-external-link"></i></a></p>'.
			          '<p>' . __( 'If you already bought Toolset, please install and activate:','module-manager' ) . '</p>'.
		               $plugins;
			if ( $required_plugins_active['missed_toolset_plugins'] ) {
				$message .= '<hr><p class="aligncenter">' . __ ( 'Otherwise, you  can buy Toolset with an exclusive discount for WPML clients.', 'module-manager' ) . '</p>' .
				'<p class="aligncenter"><a href="https://wpml.org/account/downloads/#toolset" target="_blank" class="button button-primary-toolset modman-toolset-button">' .
				__ ( 'Buy Toolset (30% discount)', 'module-manager' ) . '</a></p>';
			}
		}



		?>
		<p class="module-download-button">
			<span class="module-help-icon js-module-help-icon fa fa-question-circle" data-title="<?php _e('Required plugins','module-manager')?>" data-content="<?php echo esc_html( $message )?>"></span>
			<a href="#disabled" class="module-download-button-disabled"><?php echo $action_text ?></a> </p>
		<?php
	}

	/**
	 * Generate Insta/Update button
	 *
	 * @param $modules_rendered array
	 * @param $install_script_path string
	 * @param $action_type string
	 * @param $action_text string
	 */
	public function generate_install_button( $modules_rendered, $install_script_path, $action_type, $action_text ) {
		$install_url = $install_script_path . '?module_path='.$modules_rendered['path'] . '&mode_install=' . $action_type .
		               '&mm_install_name=' . trim( $modules_rendered['name'] );
		?>
		<p class="module-download-button">
			<a href="<?php echo esc_attr( $install_url ) ?>"><?php echo $action_text ?></a></p>
		<?php
	}

	/**
	 * @param $modules_info | array
	 *
	 * @return array|bool
	 */
	public function is_required_plugins_active( $modules_info ) {
		if ( ! isset( $modules_info['module_plugins'] ) ) {
			return false;
		}

		$plugins = get_plugins();
		$active_plugins = get_option( 'active_plugins', array() );
		if( is_multisite() ) {
			$active_sitewide_plugins = get_site_option ( 'active_sitewide_plugins' );
			$active_sitewide_plugins = array_keys ( $active_sitewide_plugins );
			$active_plugins = array_merge ( $active_plugins, $active_sitewide_plugins );
		}

		$active_plugins_info = array();
		foreach( $active_plugins as $plugin ) {
			$plugin_base = basename ( $plugin );
			if ( ! isset( $plugins[ $plugin ] ) ) {
				continue;
			}
			$plugin_info = $plugins[ $plugin ];

			if ( 'plugin.php' === $plugin_base ) {
				$active_plugins_info[ $plugin_info['Name'] ] = $plugin_info;
			} else {
				$active_plugins_info[ $plugin_base ] = $plugin_info;
			}
		}

		$plugins_match = array(
				'plugins_ok' => array(),
				'plugins_bad' => array(),
				'views_full_installed' => false,
				'views_lite_installed' => false,
				'missed_toolset_plugins' => false
		);
		foreach( $modules_info['module_plugins'] as $plugin ) {
			$plugin_base = basename ( $plugin['file'] );

			if ( 'plugin.php' === $plugin_base ) {
				$plugin_base = $plugin['title'];
			}
			$is_toolset = self::is_toolset_plugin( $plugin['title'] );
			if ( isset( $active_plugins_info[ $plugin_base ] ) ) {

				$active_plugins_info[ $plugin_base ]['Version'] = preg_replace( "/[a-zA-Z\-]+/",'', $active_plugins_info[ $plugin_base ]['Version']);
				if ( version_compare($active_plugins_info[ $plugin_base ]['Version'], $plugin['version'], '>=') ) {
					$plugins_match['plugins_ok'][$plugin['title']] = array(
						'name' => $active_plugins_info[ $plugin_base ]['Name'],
						'version' => $plugin['version'],
						'url' => $plugin['url']
					);
					if ( $plugin_base == 'wp-views.php' ) {

						if ( function_exists ('wpv_is_views_lite') && wpv_is_views_lite() ) {
							$plugins_match['views_lite_installed'] = true;
							if ( $modules_info['module_is_full_views_required'] ) {
								$plugins_match['missed_toolset_plugins'] = true;
								unset( $plugins_match['plugins_ok'][$plugin['title']] );
								$plugins_match['plugins_bad'][] = array(
									'name' => $plugin['title'],
									'version' => $plugin['version'],
									'url' => $plugin['url']
								);
							}
						} else {
							$plugins_match['views_full_installed'] = true;
						}
					}
				} else {
					$plugins_match['plugins_bad'][] = array(
						'name' => $plugin['title'],
						'version' => $plugin['version'],
						'url' => $plugin['url']
					);
					$plugins_match['missed_toolset_plugins'] = $is_toolset ? true : $plugins_match['missed_toolset_plugins'];
				}
			} else {
				$plugins_match['plugins_bad'][] = array(
						'name' => $plugin['title'],
						'version' => $plugin['version'],
						'url' => $plugin['url']
				);
				$plugins_match['missed_toolset_plugins'] = $is_toolset ? true : $plugins_match['missed_toolset_plugins'];
			}
		}

		if ( isset( $modules_info['module_is_m2m_relationships_required'] ) && $modules_info['module_is_m2m_relationships_required'] ) {
			if ( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
				$plugins_match['plugins_bad'][] = array(
					'name'    => __( 'Your post relationships need to be migrated before you can use this module', 'module-manager' ),
					'version' => __( 'check Toolset->Relationships', 'module-manager' ),
					'url'     => ''
				);
			}
		}


		return $plugins_match;
	}

	/**
	 * @param string $plugin_title
	 * @param bool $check_all_plugins
	 *
	 * @return bool
	 */
	public function is_toolset_plugin( $plugin_title, $check_all_plugins = false ){
		$is_toolset = false;
		$skip_plugins = array( 'Toolset Views', 'Toolset Types' );
		if ( strpos ( $plugin_title, 'Toolset' ) === 0 ) {
			if ( ! in_array ( $plugin_title, $skip_plugins ) || $check_all_plugins ) {
				$is_toolset = true;
			}
		}
		return $is_toolset;
	}

	function get_module_active_module_categories_from_library($modules_library_processed) {

       $complete_categories_list=array();

       foreach ($modules_library_processed as $key=>$modules_categories_rendered) {

			foreach ($modules_categories_rendered['modulecategories'] as $module_category_key=>$module_category_values) {
				if ((is_array($module_category_values)) && (!(empty($module_category_values)))) {
                	foreach ($module_category_values as $module_key_inner=>$module_category_value) {
						$complete_categories_list[]=trim($module_category_value);
					}
				} else {
                        $complete_categories_list[]=trim($module_category_values);
                }
			}

      }
      //Get unique categories
      $unique_categories_array=array_unique($complete_categories_list);

      if ((is_array($unique_categories_array)) && (!(empty($unique_categories_array)))) {

           return $unique_categories_array;

      } else {

      print_r('Complete categories list');
      print_r('<br />');
      print_r($complete_categories_list);
      print_r('<br />');
      wp_die('Unable to generate unique categories.');

      }
  	}

  	 function display_categories_on_top_of_library($unique_module_categories,$module_category_for_display) {
    ?>
		<div class="modules-list-categories">

		<ul>
		<?php //Loop through the unique categories

        //Remove pagination arguments from URL
        $without_paginated_url=remove_query_arg('library_page', $this->get_admin_url_custom());

        if ( $without_paginated_url ) {
	        ?>
            <li class="module-category-all<?php echo ( ! isset( $_GET['module_cat'] ) ? ' active ' : '' ); ?>">
	            <a href="admin.php?page=ModuleManager_Modules&tab=library"><?php _e ( 'All', 'module-manager' ) ?></a>
            </li>
	        <?php
	        if ( ! ( empty( $unique_module_categories ) ) ) {
		        foreach ( $unique_module_categories as $k => $v ) {
                    if ( empty( $v ) ) {
                        continue;
                    }
			        $category_url_modules = add_query_arg ( 'module_cat', $v, $without_paginated_url );

                    $image = '';

                    if ( file_exists ( MODMAN_ASSETS_PATH . '/images/' .sanitize_title( $v ) . '.svg' ) ){
                         $image = MODMAN_ASSETS_URL . '/images/' .sanitize_title( $v ) . '.svg';
                    }
			        ?>
                    <li <?php echo ( isset( $_GET['module_cat'] ) && $_GET['module_cat'] == trim ( $v )  ? 'class="active"' : '' ); ?>>
                        <a href="<?php echo esc_attr( $category_url_modules ); ?>">
                            <?php if ( ! empty( $image ) ) :?>
                                <img src="<?php echo $image; ?>" alt="<?php echo esc_attr( $v ); ?>" class="mm-category-icon img-svg" width="21" height="17" />
                            <?php endif;?>
                            <span class="mm-category-title"><?php echo esc_html( $v ); ?></span>
                        </a>
                    </li>
			        <?php
		        }
	        } else {

		        wp_die ( 'Unique module categories are empty.' );

	        }
        } else {

	        wp_die ( 'Remove_query_arg fails.' );

        }
				?>
		</ul>
		</div>
     <?php

     }
     function filter_module_library_categories($modules_library_processed,$module_category_for_display) {

     	$filtered_content_by_categories=array();

     	foreach ($modules_library_processed as $key=>$modules_categories_for_filtering) {
    		$specific_module_categories=array();
     		foreach ($modules_categories_for_filtering['modulecategories'] as $module_category_key_filtered=>$module_category_values_filtered) {
     			 if (is_array($module_category_values_filtered) && (!(empty($module_category_values_filtered)))) {
                 	foreach ($module_category_values_filtered as $module_key_inner_filtered=>$module_category_value_filtered) {
                 	$specific_module_categories[]=trim($module_category_value_filtered);
     			 	}
     			 } else {
                 //String not an array!
                    $specific_module_categories[]=trim($module_category_values_filtered);
                 }

     		}
     		//Check requested category is in array if not unset
     		if (!(in_array($module_category_for_display,$specific_module_categories))) {
     			unset($modules_library_processed[$key]);
     		}

     	}
     	//Finally get content array filtered by categories
     	$filtered_content_by_categories=$modules_library_processed;

     	if ((is_array($filtered_content_by_categories)) && (!(empty($filtered_content_by_categories)))) {

     		return $filtered_content_by_categories;

     	}
     }
     function get_installed_modules_in_database() {

		//Load modules from dB
		global $wpdb;
		$modules_db_table=$wpdb->prefix."options";
		@$modules_installed_from_db= $wpdb->get_var("SELECT option_value FROM $modules_db_table where option_name='modman_modules'");
		$modules_installed_from_db_unserialized=unserialize($modules_installed_from_db);

		if ((!(empty($modules_installed_from_db_unserialized))) && is_array($modules_installed_from_db_unserialized)) {
            $modules_installed_array=array();
			foreach ($modules_installed_from_db_unserialized as $key=>$value) {

				//Define module name
				//Get lowercase
				$lowercase_key=trim(strtolower($key));
				$modules_installed_array[]=str_replace(' ', '', $lowercase_key);

			}
		}

		if ((!(empty($modules_installed_array))) && is_array($modules_installed_array)) {

          return $modules_installed_array;

        }

     }
     function stripoffdates_from_modulefilename_mm($module_url_passed) {

     	preg_match("/.*([0-9]{4}-[0-9]{2}-[0-9]{2}).*/", $module_url_passed, $matches);
     	$output_no_dates=str_ireplace($matches[1],'',$module_url_passed);

     	//Get base name from URL
     	$basename_of_zip=basename($output_no_dates);

        //Get compressed name only
     	$compressed_file_name = substr($basename_of_zip, 0, -12);

     	return $compressed_file_name;

     }
     function get_admin_url_custom() {

		if (!isset($_SERVER['REQUEST_URI']))
		{
			$_SERVER['REQUEST_URI'] = substr($_SERVER['PHP_SELF'],1 );
			if (isset($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'].='?'.$_SERVER['QUERY_STRING'];
            }
            $get_admin_url_custom=$_SERVER['REQUEST_URI'];

            return $get_admin_url_custom;
		} else {

			$get_admin_url_custom=$_SERVER['REQUEST_URI'];

			return $get_admin_url_custom;

        }

     }
     function is_user_internet_connected_referencesites() {

		$connected=@fsockopen("ref.toolset.com",80);
		$is_conn=false;

		if ($connected) {

			$is_conn=true;

		} else {

			//Emerson: START Not connected online, check if running the reference site locally
			if (defined('MODMAN_ORIGINATING_HOST')) {

				$modulemanager_originating_host=MODMAN_ORIGINATING_HOST;
				if ($modulemanager_originating_host != 'ref.toolset.com') {

					//Running reference site locally
					$connected=@fsockopen($modulemanager_originating_host,80);
					if ($connected) {
						$is_conn=true;
					} else {
						$is_conn= false;
					}
				}

			}

		}

		return $is_conn;

     }
}
?>
