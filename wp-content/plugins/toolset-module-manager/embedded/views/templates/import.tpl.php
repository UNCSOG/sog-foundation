<?php
// deny access
if( !defined('ABSPATH') ) die('Security check');
if(!current_user_can(MODMAN_CAPABILITY)) die('Access Denied');
?>
<?php
$shared_import_menu= ModuleManager::modulemanager_can_implement_unified_menu();
if ( true === $shared_import_menu) {
	$url = admin_url( 'admin.php?page=toolset-export-import&tab=modules_import' );
	$mm_url = admin_url( 'admin.php?page=ModuleManager_Modules' );
}

function modman_generate_module_header ( $module_title, $module_description ){
    ?>
    <div class="import-module-header">
    <h2>
		<?php printf( __('Importing module: <strong>%s</strong>','module-manager'), $module_title ); ?>
    </h2>
    <p>
        <?php echo ModuleManager::sanitizeTags ( stripslashes( $module_description ) ) ?>
    </p>

	<?php
    //
	$toolset_all_full = mm_check_toolset_plugins( 'all_installed' );
	if (!($toolset_all_full)) {
		?>
        <p>
            <strong>
				<?php _e('To prevent importing issues, please make sure that all the Toolset plugins required for the imported Module(s) are activated.','module-manager');?>
            </strong>
        </p>
	<?php }?>

    <p>
        <h3><?php _e('You can choose which elements of the Module you want to import:','module-manager'); ?></h3>
    </p>
    </div>
    <?php
}
/**
 * Module Manager 1.6.6
 * Checking null import
 */
$null_import	= false;
if ( isset( $_GET['step'] ) ) {
	//Step set
	//Check for null import
	if ( ( isset( $_FILES['import-file']['error']) && $_FILES['import-file']['error']>0 ) ) {
		//Null import
		$null_import	= true;
	}
}
if ( true === $null_import ) {
	if ( isset( $_GET['step'] ) ) {
		unset( $_GET['step'] );
		echo "<div class='error'><p>".__('Upload Error: data to import not set or not valid','module-manager')."</p></div>";
	}
}
?>
<div class="import-module">


	<?php
	$step = ( isset( $_GET['step'] ) ? intval ( $_GET['step'] ) : 0 );

    $plugins_title = array (
        'cred'              => __ ( 'Post Forms', 'module-manager' ),
        'cred-user'         => __ ( 'User Forms', 'module-manager' ),
        'taxonomies'        => __ ( 'Taxonomies', 'module-manager' ),
        'dd_layouts'        => __ ( 'Layouts', 'module-manager' ),
        'groups'            => __ ( 'Post Fields Groups', 'module-manager' ),
        'm2m_relationships' => __ ( 'Relationships', 'module-manager' ),
        'view-templates'    => __ ( 'Content Templates', 'module-manager' ),
        'views'             => __ ( 'Views', 'module-manager' ),
        'types'             => __ ( 'Post Types', 'module-manager' ),
        'demo_content'      => __ ( 'Import Demo Content', 'module-manager' )
    );
    // which step of the process
    switch ( $step ) {
		case 1:
			?>


			<?php

			if ( (
				     isset( $_POST[ 'modman-import-field' ] ) &&
				     wp_verify_nonce ( $_POST[ 'modman-import-field' ], 'modman-import-action' ) &&
				     isset( $_FILES[ 'import-file' ] )
			     ) || ( ( isset( $_GET[ 'import-file' ] ) ) ) ) {
				if ( isset( $_GET[ 'import-file' ] ) ) {

					//This is installed via Modules library, retrieve this and to assign to $_FILES array

					$_FILES[ 'import-file' ] = $_GET[ 'import-file' ];

					//Retrieve installation mode
					$final_import_installation_mode = $_GET[ 'mode_install_import' ];

					//Retrieve module name imported
					$final_import_installation_name = $_GET[ 'mm_install_name_import' ];

				}
				if ( ( isset( $_FILES[ 'import-file' ][ 'error' ] ) && $_FILES[ 'import-file' ][ 'error' ] > 0 ) ) {
					?>
                    <div class='error'><p><?php _e ( 'Upload Error', 'module-manager' ); ?></p></div>
					<?php
				} else {
					$info = ModuleManager::importModuleStepByStep ( 1, array (
						'file' => $_FILES[ 'import-file' ]
					) );
					if ( is_wp_error ( $info ) ) {
						?>
                        <div class='error'><p><?php echo $info->get_error_message ( $info->get_error_code () ); ?></p></div>
						<?php
					} else {
						$step = 2;
						$url  .= '&step=' . $step;
                        $module_title = ( isset( $info[ MODMAN_MODULE_INFO ][ 'name' ] ) ? $info[ MODMAN_MODULE_INFO ][ 'name' ] : '' );
                        $module_description = ( isset( $info[ MODMAN_MODULE_INFO ][ 'description' ] ) ? $info[ MODMAN_MODULE_INFO ][ 'description' ] : '' );
                        modman_generate_module_header( $module_title, $module_description );
						?>

                        <form name="modman-import-form" action="<?php echo $url; ?>" method="post">
                            <div class="import-module-group">
                                <input type="hidden" name="info"
                                       value="<?php echo $info[ MODMAN_MODULE_TMP_FILE ]; ?>"/>
								<?php wp_nonce_field ( 'modman-import-action-2', 'modman-import-field-2' ); ?>
                                <div class='import-module-contents'>

									<?php
									$modules_html = '';
									$demo_content_html = '';
									foreach ( $info as $section_id => $items ) {
										$temp_content = '';
										if ( ! in_array ( $section_id, array (
											MODMAN_MODULE_INFO,
											MODMAN_MODULE_TMP_FILE
										) )
										) {

									            $temp_content .= '<div class="import-module-contents-item">';
												if ( 'demo_content' !== $section_id ) :
													$temp_content .= '<h4>';

														if ( isset( $sections[ $section_id ] ) ) {
															$temp_content .= $sections[ $section_id ][ 'title' ];
														} else {
															$temp_content .= isset( $plugins_title[ $section_id ] ) ? $plugins_title[ $section_id ] : $section_id;
														}

                                                    $temp_content .= '</h4>';
												endif;


												foreach ( $items as $item ) {
													$checked = '';
													if ( isset( $item[ 'exists' ] ) && $item[ 'exists' ] || ( ! ( isset( $item[ 'is_different' ] ) && $item[ 'is_different' ] ) ) ) {
														$checked = 'checked="checked"';
													}
													if ( 'demo_content' === $section_id && strpos ( $item[ 'id' ], 'relationships_data_' ) === 0 ){
													    $demo_content_html .= '<input type="hidden"
													              name="items[' . $section_id . '][' . ( isset( $item['id'] )  ? $item[ 'id' ] : '' ) . ']"
													              value="1" />';
													    continue;
                                                    }

                                                    $temp_content .= '<p>';
                                                    $temp_content .= '<input type="checkbox"
                                                                   name="items[' . $section_id . '][' . ( isset( $item['id'] )  ? $item[ 'id' ] : '' ) . ']"
                                                                    value="1" ' . $checked . ' />';

													$temp_content .= '<span class="import-module-info">';

                                                        if ( isset( $item[ 'exists' ] ) && $item[ 'exists' ] ) {
                                                            if ( isset( $item[ 'is_different' ] ) && $item[ 'is_different' ] ) {

                                                                $temp_content .= '<i class="icon-refresh"></i>
                                                                <span class="import-module-name">' . $item[ 'title' ] . '</span>' .
                                                                __( ' &ndash; A different version is installed, it will be overwritten', 'module-manager' );
                                                            } else {

												                $temp_content .= '<i class="icon-check"></i>
                                                                <span class="import-module-name">' . $item[ 'title' ] . '</span>' .
                                                                __( ' &ndash; The same version is installed', 'module-manager' );
                                                            }
                                                        } else {
	                                                        $temp_content .= '<i class="icon-plus"></i>';
                                                            if ( isset( $item[ 'title' ] ) ) {
												                $temp_content .= '<span class="import-module-name">' . $item[ 'title' ] . '</span>';
                                                            }

                                                            if ( 'demo_content' === $section_id ) {
	                                                            $temp_content .= __( ' &ndash; new posts will be added', 'module-manager' );
                                                            } else {
	                                                            $temp_content .= __( ' &ndash; A new item will be added', 'module-manager' );
                                                            }
                                                        }

                                                    $temp_content .= '</span></p>';


												}

                                                $temp_content .= '</div>';
											if ( 'demo_content' === $section_id ) {
												$demo_content_html .= $temp_content;
											} else {
												$modules_html .= $temp_content;
											}
										}

									 } ?>
									<p>
										<input type="checkbox"  checked disabled> <?php _e( 'Structure', 'module-manager' ) ?>
										(<a href="#javascript" checked class="js-toggle-module-section toggle-module-section" data-area="modules"><?php _e( 'choose what', 'module-manager' ) ?></a>)
									</p>
									<div class="js-modules-list-area modules-list-area hidden">
										<?php echo $modules_html ?>
									</div>
									<?php if ( ! empty( $demo_content_html )): ?>
										<p>
											<input type="checkbox"  checked class="js-modman-enable-demo-content"> <?php _e( 'Demo content', 'module-manager' ) ?>
											(<a href="#javascript" class="js-toggle-module-section toggle-module-section" data-area="demo-content"><?php _e( 'choose what', 'module-manager' ) ?></a>)
										</p>
										<div class="js-demo-content-list-area modules-list-area hidden">
											<?php echo $demo_content_html ?>
										</div>
									<?php endif;?>


<?php
/*************************************************
 * On Import check plugin versions MM version >=1.1
**************************************************/

//Retrieved imported_section_id from the module package
if ((is_array($info)) && (!(empty($info)))) {

     $imported_section_ids=array();

     foreach ($info as $k=>$v) {
     	if (($k!=MODMAN_MODULE_INFO) && ($k!=MODMAN_MODULE_TMP_FILE)) {

     	$imported_section_ids[$k]=$k.'_plugin_version';

     }
     }
}

/** PLUGIN VERSION COMPATIBILITY WARNINGS AND IMPORT MESSAGES -START */
//Loop through the plugin versions and compare with the imported site
if ((is_array($imported_section_ids)) && (!(empty($imported_section_ids)))) {

  $version_problem_detected=array();

  $custom_import_messages= array();

  foreach ($imported_section_ids as $imported_section_id=>$imported_section_id_plugin_version) {
				if ( $imported_section_id == 'cred'){
				    $imported_section_id = 'forms';
                }
	if (isset($info[MODMAN_MODULE_INFO][$imported_section_id_plugin_version])) {

		$exported_pluginversion_used=$info[MODMAN_MODULE_INFO][$imported_section_id_plugin_version];

		//Get plugin version on import site
		$imported_pluginversion_used=apply_filters('wpmodules_import_pluginversions_'.$imported_section_id,$imported_section_id_plugin_version);

		//Get custom messages
		$imported_message_custom=apply_filters('wpmodules_import_plugin_messages_'.$imported_section_id,'', $info);

		if (!(empty($imported_message_custom))) {
			//Has message to show on import
			$custom_import_messages[$imported_section_id] = $imported_message_custom;
		}
		$imported_pluginversion_used_original = $imported_pluginversion_used;
		$exported_pluginversion_used = trim( preg_replace( "/[^0-9.]/", '', $exported_pluginversion_used ) );
		$imported_pluginversion_used = trim( preg_replace( "/[^0-9.]/", '', $imported_pluginversion_used ) );

		//Compare versions
		if ((version_compare($imported_pluginversion_used,$exported_pluginversion_used,'<')) || (($imported_pluginversion_used==$imported_section_id_plugin_version))) {

         	//Version problem issue

         	if ($imported_pluginversion_used==$imported_section_id_plugin_version) {
            //Old plugins that don't have the hook
            $imported_pluginversion_used='';
         	}
         	$version_problem_detected[ $imported_section_id ] = array( $exported_pluginversion_used => $imported_pluginversion_used_original );

		}

	}


  }
 //Categorize plugin version issues
if ((is_array($version_problem_detected)) && (!(empty($version_problem_detected)))) {

$define_plugin_category_modules=array('groups'=>'Types','types'=>'Types','taxonomies'=>'Types','views'=>'Views','view-templates'=>'Views','cred'=>'FORMS','dd_layouts' =>'Layouts');

$get_plugin_category=array();

	foreach ($version_problem_detected as $section_id_problem_key=>$section_id_plugin_versions) {

		//Get plugin category
		if (array_key_exists($section_id_problem_key, $define_plugin_category_modules)) {

          $get_plugin_category[$define_plugin_category_modules[$section_id_problem_key]]=$section_id_plugin_versions;

        }
	}

}

if ((is_array($version_problem_detected)) && (!(empty($version_problem_detected)))) {

//Generate feedback messages for plugin version issues
if ((is_array($get_plugin_category)) && (!(empty($get_plugin_category)))) {

$feedback_messages_plugin_versions=array();

	foreach ($get_plugin_category as $problem_plugin=>$problem_version_array) {

        foreach ($problem_version_array as $export_version_reported=>$import_version_reported) {

          if (empty($import_version_reported)) {

          $feedback_messages_plugin_versions[]="This module was created with $problem_plugin $export_version_reported and you have an older version installed or not activated.<br />Please <b>update</b> and <b>activate</b> $problem_plugin plugin to the correct version to avoid any problems during the import of this module.";

          } else {

          $feedback_messages_plugin_versions[]="This module was created with $problem_plugin $export_version_reported and you only have $problem_plugin $import_version_reported installed.<br />Please <b>update</b> and <b>activate</b> $problem_plugin plugin to the correct version to avoid any problems during the import of this module.";

          }
        }
	}
}

	//Feedback the user for any issues on plugin versions if found
	if ( isset( $feedback_messages_plugin_versions ) && is_array( $feedback_messages_plugin_versions )
	        && ! empty( $feedback_messages_plugin_versions ) ) {

		foreach ($feedback_messages_plugin_versions as $key_message=>$report_message) {

	?>
	    <div class="error"><?php echo $report_message;?></div>
	<?php
		}

	}
}
//Feedback the user for any custom plugin import messages
if ((is_array($custom_import_messages)) && (!(empty($custom_import_messages)))) {
	foreach ($custom_import_messages as $key_custom_message=>$report_custom_message) {
		?>
	    <div class="error"><?php echo $report_custom_message;?></div>
	<?php
		}
	}
}
/** PLUGIN VERSION COMPATIBILITY WARNINGS AND IMPORT MESSAGES -END */

?>
								</div>
							</div> <!-- import-module-group -->
							<input type="submit" class="button button-primary button-large" value="<?php echo esc_attr(__('Import selected items','module-manager')); ?>" />
						</form>

						<?php
					}
				}
			}
			else
			{
				?>
					<div class='error'><p><?php _e('No File was uploaded','module-manager'); ?></p></div>
				<?php
			}
			break;

		case 2:
			?>
				</div> <!-- .import-module-header -->
			<?php
			if (
				isset($_POST['modman-import-field-2']) &&
				wp_verify_nonce( $_POST['modman-import-field-2'], 'modman-import-action-2' ) &&
				isset($_POST['items']) && isset($_POST['info'])
			)
			{
				// get import results
				$results=ModuleManager::importModuleStepByStep(2, array(
									'info'=>$_POST['info'],
									'items'=>$_POST['items']
									));
				if (!is_wp_error($results))
				{
					$hasError=false;
					$import_output='';
					// print info AFTER the module manager message
					ob_start();
					if (isset($results[MODMAN_MODULE_INFO]))
					{
						?>
							<div class="import-module-group import-module-info">
								<h2><?php printf( __('The <strong>%s</strong> module was imported successfully','module-manager'), $results[ MODMAN_MODULE_INFO ][ 'name' ] )?></h2>
							</div>
						<?php
						//unset($results[MODMAN_MODULE_INFO]);
					}
					?>

					<div class="import-module-group">
						<p><?php _e('Import details:','module-manager'); ?></p>
                        <table class="modman-import-details">
                            <thead>
                                <tr>
                                    <td><?php _e('Element type','module-manager')?></td>
                                    <td><?php _e('Number','module-manager')?></td>
                                    <td><?php _e('Status','module-manager')?></td>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ( true === $shared_import_menu) {
	                            if ( ! ( isset( $sections ) ) ) {
		                            $sections=ModMan_Loader::get('MODEL/Modules')->getRegisteredSections();
	                            }
                            }

                            foreach ($results as $section_id=>$data) {
	                            if ( $section_id == MODMAN_MODULE_INFO ) {
		                            continue;
	                            }
                                if( $section_id === 'demo_content' ) {
	                                $sections[ $section_id ]['title'] = __( 'Demo content - number of posts', 'module-manager' );
                                }

	                            if ( isset( $data['updated'] ) && $data['updated'] > 0 ) {
                                    echo '<tr>' .
                                         '<td>' . $sections[ $section_id ]['title'] . '</td>' .
                                         '<td>' . $data['updated'] . '</td>'.
                                         '<td><strong>' . __( 'overwritten', 'module-manager' ) . '</strong></td>' .
                                         '</tr>';
	                            }
	                            if ( isset( $data['new'] ) && $data['new'] > 0 ) {
		                            echo '<tr>' .
                                         '<td>' . $sections[ $section_id ]['title'] . '</td>' .
		                                 '<td>' . $data['new'] . '</td>'.
		                                 '<td><strong>' . __( 'created', 'module-manager' ) . '</strong></td>' .
                                         '</tr>';
	                            }
	                            if ( isset( $data['failed'] ) && $data['failed'] > 0 ) {
		                            echo '<tr>' .
                                         '<td>' . $sections[ $section_id ]['title'] . '</td>' .
		                                 '<td>' . $data['failed'] . '</td>'.
		                                 '<td>' . __( '<strong>failed to import (already exist)', 'module-manager' ) . '</td>' .
                                         '</tr>';

	                            }
	                            if ( isset( $data['errors'] ) && ! empty( $data['errors'] ) ) {
		                            $hasErrors = true;
		                            echo '<tr>';
		                            foreach ( $data['errors'] as $err ) {
			                            ?>
                                        <td colspan="3"><div class='error'><p><?php echo $err; ?></p></div></td>
			                            <?php
		                            }
		                            echo '</tr>';
	                            }

                            }
                            ?>
                            </tbody>
                        </table>


					<?php
					$import_output=ob_get_clean();
					echo $import_output;
					if ( ! empty( $results[ MODMAN_MODULE_INFO ][ 'instructions' ] ) ) {
						echo '<div class="modman_instruction"><h2>' . __( 'What to do next?', 'module-manager' ) . '</h2>' .
                             $results[ MODMAN_MODULE_INFO ][ 'instructions' ] . '</div>';
                    }

                    if ( ! empty( $results[ MODMAN_MODULE_INFO ][ 'documentation' ] ) ) {
					    echo '<p>' . __('Learn more: ','module-manager') .
                             '<a href="' . $results[ MODMAN_MODULE_INFO ][ 'documentation' ] . '" class="modman_outer_link" target="_blank">' . sprintf( __('%s module documentation','module-manager'), $results[ MODMAN_MODULE_INFO ][ 'name' ]  ) . '</a></p>';
                    } else {
	                    echo '<p>' . __('Learn more: ','module-manager') .
	                         '<a href="https://toolset.com/home/module-manager/?utm_source=plugin&utm_medium=gui&utm_campaign=modulmanager" class="modman_outer_link" target="_blank">' . __('Module Manager documentation','module-manager') . '</a></p>';
                    }
				}
				else
				{
					?>
						<div class='error'><p><?php $results->get_error_message($results->get_error_code()); ?></p></div>
					<?php
				}
			}
			elseif (!isset($_POST['items']))
			{
				?>
					<div class='error'><p><?php _e('No items were imported.','module-manager'); ?></p></div>
				<?php
			}
			else
			{
				?>
					<div class='error'><p><?php _e('No Module information was given','module-manager'); ?></p></div>
				<?php
			}
			break;

		default:
			$step=1;
			$url.='&step='.$step;
			?>

				<form name="modman-import-form" enctype="multipart/form-data" action="<?php echo $url; ?>" method="post">
				<?php wp_nonce_field('modman-import-action','modman-import-field'); ?>
				<?php
				$toolset_all_disabled_import = mm_check_toolset_plugins( 'all_disabled' );
				$toolset_all_installed_import = mm_check_toolset_plugins( 'all_installed' );
				$toolset_all_full_types_views_import = mm_check_toolset_plugins( 'full_types_views' );
				$toolset_any_embedded_import = mm_check_toolset_plugins( 'any_embedded' );
				?>
				<?php if (((!($toolset_all_disabled_import)) && ($toolset_all_installed_import)) || ($toolset_all_full_types_views_import) || ($toolset_any_embedded_import)) {?>
                    <p>
	                    <?php _e('You can choose what elements you want to import, depending on what already exists in your WordPress installation.','module-manager'); ?>
                    </p>
                    <p>
                        <strong><?php _e('To prevent importing issues, please make sure that all the Toolset plugins required for the imported Module(s) are activated.','module-manager'); ?></strong>
                    </p>
				<table class="widefat" id="modman_import_table">
				<thead>
					<tr>
						<th><?php _e('Import Module','module-manager'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<label for="upload-modules-file"><?php _e('Select the module .zip file to upload:&nbsp;','module-manager'); ?></label>

							<input type="file" id="upload-modules-file" name="import-file" />

							<input id="modman-import" class="button-primary" type="submit" value="<?php echo esc_attr(__('Import','module-manager')); ?>" name="import" />
							</td>
						</tr>
					</tbody>
				</table>
				<?php } else {?>

					<p style="color:red">
						<?php _e( 'Importing disabled, please activate Toolset plugins to proceed.', 'module-manager' ); ?>
					</p>

				<?php }?>
				</form>
			<?php
			break;
	} ?>
<?php
$we_are_step1_2 = false;
if ( ( isset( $_GET['page'] ) ) && ( isset( $_GET['tab'] ) ) && ( isset( $_GET['step'] ) ) ) {
	$loaded_page	= $_GET['page'];
	$loaded_tab		= $_GET['tab'];
	$loaded_step	= intval( $_GET['step'] );
	if ( ( 'toolset-export-import' == $loaded_page ) && ( 'modules_import' == $loaded_tab ) && ( ( 1 == $loaded_step ) || ( 2 == $loaded_step ) ) ) {
		$we_are_step1_2 = true;
	}
}
if ( false === $we_are_step1_2 ) {
	//Two divs needed
?>
</div></div>
<?php
} elseif ( true === $we_are_step1_2 ) {
	//Only one div needed
?>
</div>
<?php
}
?>
