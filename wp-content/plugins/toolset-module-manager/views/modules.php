<?php
// deny access
if( !defined('ABSPATH') ) die('Security check');
if(!current_user_can(MODMAN_CAPABILITY)) die('Access Denied');
?>
<?php
$shared_menu_modules = ModuleManager::modulemanager_can_implement_unified_menu();
?>
<div id="module-manager-wrap" class="wrap">
    <h2><?php _e('Module Manager','module-manager'); ?></h2><br />

    <?php
	$toolset_any_embedded = mm_check_toolset_plugins( 'any_embedded' );
	$toolset_all_disabled = mm_check_toolset_plugins( 'all_disabled' );
	$toolset_all_full = mm_check_toolset_plugins( 'all_full' );
	$toolset_all_full_types_views = mm_check_toolset_plugins( 'full_types_views' );
	$toolset_all_installed = mm_check_toolset_plugins( 'all_installed' );
	$has_cred_in_module=mm_check_has_cred_in_module();
    /* Show define modules tab only when any Toolset plugins full versions are activated*/

    if (( $toolset_any_embedded ) || ($toolset_all_disabled)) {
    	$mtabs=array(
			'library' => __('Modules Library','module-manager'),
    	    'import' => __('Import Modules','module-manager')
    	);
    	if ( true === $shared_menu_modules ) {
    		unset( $mtabs['import'] );
    	}
		?>
        <?php if ($toolset_all_disabled) {?>
		<div class="error">
			<p>
				<?php _e( 'Exporting and importing modules disabled. You will need to activate at least Toolset Types and Toolset Views plugins in order to enable this feature.', 'module-manager' ); ?>
			</p>
		</div>
		<?php } elseif ((!($toolset_all_installed)) && (!($toolset_any_embedded))) {?>
		<div class="error">
			<p>
				<?php _e( 'Exporting and importing modules disabled. You will need to activate at least Toolset Types and Toolset Views plugins in order to enable this feature.', 'module-manager' ); ?>
			</p>
		</div>
		<?php } else {?>
		<div class="error">
			<p>
				<?php _e( 'Exporting modules are disabled. You are using embedded versions of the Toolset plugins.', 'module-manager' ); ?>
			</p>
		</div>
		<?php }?>
		<?php
    } else {
    	if (($toolset_all_full) || ($toolset_all_full_types_views)) {
    		$mtabs=array(
				'modules' => __('Define Modules','module-manager'),
				'library' => __('Modules Library','module-manager'),
				'import' => __('Import Modules','module-manager')
    		);
    		if ( true === $shared_menu_modules ) {
    			unset( $mtabs['import'] );
    		}
			if (($toolset_all_full_types_views) && ($has_cred_in_module)) { ?>
			<div class="error">
			<p>
				<?php _e( 'Warning: Exporting modules with CRED form does not work because CRED is deactivated. Please activate.', 'module-manager' ); ?>
			</p>
			</div>
		<?php }

    	} else {
    		$mtabs=array(
    				'library' => __('Modules Library','module-manager'),
    				'import' => __('Import Modules','module-manager')
    		);
    		if ( true === $shared_menu_modules ) {
    			unset( $mtabs['import'] );
    		}
    	?>
    	<div class="error">
			<p>
				<?php _e( 'Exporting and importing modules disabled. You will need to activate at least Toolset Types and Toolset Views plugins in order to enable this feature.', 'module-manager' ); ?>
			</p>
		</div>
		<?php
    	}
	}

    if (!isset($_GET['tab']) || !in_array($_GET['tab'],array_keys($mtabs))) {
    	if (( $toolset_any_embedded ) || ($toolset_all_disabled)) {
        	$current_tab='library';
    	} else {
    		if (($toolset_all_full) || ($toolset_all_full_types_views)) {
    			$current_tab='modules';
    		} else {
    			$current_tab='library';
    		}
    	}
    } else {
        $current_tab=$_GET['tab'];
    }

    //$murl=admin_url('options-general.php').'?page=ModuleManager_Modules';
    //$murl = add_query_arg( 'tab', $current_tab, remove_query_arg( array( 'tab' ), $murl ) );
    $_base_url=ModuleManager::$page;
    $murl = add_query_arg( 'tab', $current_tab, remove_query_arg( array( 'tab' ), $_base_url ) );

    echo '<h2 class="modman-tabs-wrapper nav-tab-wrapper">';
        foreach( $mtabs as $tab => $tabtitle)
        {
            $_tab_url = add_query_arg( 'tab', $tab, $_base_url);
            $class = ( $tab == $current_tab ) ? 'nav-tab-active' : '';
            echo "<a class='nav-tab $class' href='{$_tab_url}'>{$tabtitle}</a>";
        }
    echo '</h2>';

    if (defined('WPCF_VERSION') && version_compare(WPCF_VERSION, '1.2.1', '<'))
    {
        echo "<div class='error'><p>".__('Types 1.2.1 or greater is required for Module Manager', 'module-manager')."</p></div>";
    }
    if (defined('WPV_VERSION') && version_compare(WPV_VERSION, '1.2.1', '<'))
    {
        echo "<div class='error'><p>".__('Views 1.2.1 or greater is required for Module Manager', 'module-manager')."</p></div>";
    }
    if (defined('CRED_FE_VERSION') && version_compare(CRED_FE_VERSION, '1.1.3', '<'))
    {
        echo "<div class='error'><p>".__('CRED 1.1.4 or greater is required for Module Manager', 'module-manager')."</p></div>";
    }

    switch($current_tab)
    {
        case 'import':
            // import module
            $model=ModMan_Loader::get('MODEL/Modules');
            $sections=$model->getRegisteredSections();
            echo ModMan_Loader::tpl('import', array(
                'sections' => $sections,
                'url'=>$murl,
                'mm_url'=>ModuleManager::$page
            ));
            break;
        case 'library':
           	// display library
        	$library_class_path=MODMAN_LIBRARYCLASS_PATH.'/Class_Modules_Library.php';
        	require_once($library_class_path);
        	$Class_Modules_Library = new Class_Modules_Library;
            break;
        case 'modules':
        default:
        // define/export modules
            $model=ModMan_Loader::get('MODEL/Modules');
            $sections=$model->getRegisteredSections();
            echo ModMan_Loader::tpl('modules', array(
                'sections' => $sections,
                'items' => $model->getRegisteredItemsPerSection($sections),
                'modules' => apply_filters( 'wpmodules_saved_items', $model->getModules() ),
            ));
            break;
    }
    ?>

</div>
