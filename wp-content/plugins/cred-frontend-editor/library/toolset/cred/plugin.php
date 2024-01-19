<?php



/** @deprecated Since 1.9. Use only CRED_ABSPATH. */
/* Used by Forms while defining CRED_ROOT_CLASSES_PATH. */
/* Used by the TCL Status plugin ONLY for legacy support, should be updatable. */
define('CRED_ROOT_PLUGIN_PATH', CRED_ABSPATH . '/library/toolset/cred');

/** @deprecated Since 1.9. Use only CRED_ABSPATH. */
/* Used by Forms in its CRED_Loader loader. */
define('CRED_ROOT_CLASSES_PATH', CRED_ABSPATH . '/library/toolset/cred/classes');

/** @deprecated Since 1.9. Use only CRED_ABSPATH. */
/* Used by Forms in its CRED_Loader loader. */
define('CRED_FILE_PATH', realpath(__FILE__));

/** @deprecated Since 1.9. Use only CRED_ABSPATH. */
/* Used by Forms while defining some paths. */
/* @deprecate after the assets review. */
define('CRED_FILE_NAME', basename(CRED_FILE_PATH));

/** @deprecated Since 1.9. Use only CRED_ABSPATH. */
/* Used wildly and BY LAYOUTS and BY TYPES. */
define('CRED_CLASSES_PATH', CRED_ABSPATH . '/library/toolset/cred/embedded/classes');

/** @deprecated Since 1.9. Use only CRED_ABSURL. */
/* Used widely. */
define('CRED_ASSETS_URL', CRED_ABSURL . '/library/toolset/cred/embedded/assets');

// include loader
require_once( CRED_ABSPATH . '/library/toolset/cred/embedded/loader.php' );


if ( ! function_exists( 'cred_loaded_common_dependencies' ) ) {
    add_action( 'after_setup_theme', 'cred_loaded_common_dependencies', 11 );

    function cred_loaded_common_dependencies() {
        require_once dirname(__FILE__) . '/embedded/classes/CRED_help_videos.php';
        require_once dirname(__FILE__) . '/embedded/classes/CRED_scripts_manager.php';
    }

}

add_filter( 'plugin_row_meta', 'toolset_cred_plugin_plugin_row_meta', 10, 4 );

function toolset_cred_plugin_plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status) {
    $this_plugin = basename( CRED_ABSPATH ) . '/plugin.php';
    if ($plugin_file == $this_plugin) {
        $ver2url = strtolower( str_replace( ".", "-", CRED_FE_VERSION ) );
        $plugin_meta[] = sprintf(
            '<a href="%s" target="_blank">%s</a>',
            'https://toolset.com/version/cred-' . $ver2url . '/?utm_source=credplugin&utm_campaign=cred&utm_medium=release-notes-plugin-row&utm_term=Forms ' . CRED_FE_VERSION . ' release notes', __('Toolset Forms ' . CRED_FE_VERSION . ' release notes', 'wp-cred')
        );
    }
    return $plugin_meta;
}

// enable CRED_DEBUG, on top of this file
// register assets
CRED_Loader::add('assets', array(
    'STYLE' => array(
        'cred_template_style' => array(
            // @deprecated
            'loader_url' => CRED_ABSURL . '/library/toolset/cred/embedded/' . CRED_FILE_NAME,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('wp-admin', 'colors-fresh', 'font-awesome', 'cred_cred_style_nocodemirror_dev'),
            'path' => CRED_ASSETS_URL . '/css/gfields.css',
            'src' => CRED_ABSPATH . '/library/toolset/cred/embedded/assets/css/gfields.css'
        ),
        'cred_cred_style_dev' => array(
            // @deprecated
            'loader_url' => CRED_ABSURL . '/library/toolset/cred/embedded/' . CRED_FILE_NAME,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('font-awesome', 'toolset-meta-html-codemirror-css-hint-css', 'toolset-meta-html-codemirror-css', 'wp-jquery-ui-dialog', 'wp-pointer'),
            'path' => CRED_ASSETS_URL . '/css/cred.css',
            'src' => CRED_ABSPATH . '/library/toolset/cred/embedded/assets/css/cred.css'
        ),
        'cred_cred_style_nocodemirror_dev' => array(
            // @deprecated
            'loader_url' => CRED_ABSURL . '/library/toolset/cred/embedded/' . CRED_FILE_NAME,
            'loader_path' => CRED_FILE_PATH,
            'version' => CRED_FE_VERSION,
            'dependencies' => array('font-awesome', 'wp-jquery-ui-dialog', 'wp-pointer'),
            'path' => CRED_ASSETS_URL . '/css/cred.css',
            'src' => CRED_ABSPATH . '/library/toolset/cred/embedded/assets/css/cred.css'
        )
    )
));

// init loader for this specific plugin and load assets if needed
CRED_Loader::init(CRED_FILE_PATH);

// if called when loading assets, ;)
if (!function_exists('add_action'))
    return;

if (defined('ABSPATH')) {
// register dependencies
    CRED_Loader::add('dependencies', array(
        'CONTROLLER' => array(
            '%%PARENT%%' => array(
                array(
                    'class' => 'CRED_Abstract_Controller',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/controllers/Abstract.php'
                )
            ),
            'Forms' => array(
                array(
                    'class' => 'CRED_Forms_Controller',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/controllers/Forms.php'
                )
            ),
            'Posts' => array(
                array(
                    'class' => 'CRED_Posts_Controller',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/controllers/Posts.php'
                )
            ),
            'Settings' => array(
                array(
                    'class' => 'CRED_Settings_Controller',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/controllers/Settings.php'
                )
            ),
            'Import' => array(
                array(
                    'class' => 'CRED_Import_Controller',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/controllers/Import.php'
                )
            )
        ),
        'MODEL' => array(
            '%%PARENT%%' => array(
                array(
                    'class' => 'CRED_Abstract_Model',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/models/Abstract.php'
                ),
	            array(
		            'class' => 'CRED_Fields_Abstract_Model',
		            'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/models/FieldsAbstract.php'
	            ),
	            array(
		            'class' => 'CRED_Fields_Types_Utils',
		            'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/models/CRED_Fields_Types_Utils.php'
	            )
            ),
            'Forms' => array(
                // dependencies
                array(
                    'path' => ABSPATH . '/wp-admin/includes/post.php'
                ),
                array(
                    'class' => 'CRED_Forms_Model',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/models/Forms.php'
                )
            ),
            'UserForms' => array(
                // dependencies
                array(
                    'path' => ABSPATH . '/wp-admin/includes/post.php'
                ),
                array(
                    'class' => 'CRED_User_Forms_Model',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/models/UserForms.php'
                )
            ),
            // @deprecated Use OTGS\Toolset\CRED\Model\Settings instead
            'Settings' => array(
                array(
                    'class' => 'CRED_Settings_Model',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/models/Settings.php'
                )
            ),
            'Import' => array(
                array(
                    'class' => 'CRED_Import_Model',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/models/Import.php'
                )
            ),
            'Fields' => array(
                array(
                    'class' => 'CRED_Fields_Model',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/models/Fields.php'
                )
            ),
            'UserFields' => array(
                array(
                    'class' => 'CRED_User_Fields_Model',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/models/UserFields.php'
                )
            )
        ),
        'TABLE' => array(
            '%%PARENT%%' => array(
                array(
                    'class' => 'WP_List_Table',
                    'path' => ABSPATH . '/wp-admin/includes/class-wp-list-table.php'
                )
            ),
            'Forms' => array(
                array(
                    'class' => 'CRED_Forms_List_Table',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/tables/Forms.php'
                )
            ),
            'UserForms' => array(
                array(
                    'class' => 'CRED_User_Forms_List_Table',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/tables/UserForms.php'
                )
            )
        ),
        'CLASS' => array(
            'CRED_Helper' => array(
                array(
                    'class' => 'CRED_Helper',
                    'path' => CRED_CLASSES_PATH . '/CRED_Helper.php'
                )
            ),
            'CRED' => array(
                array(
                    'class' => 'CRED_Admin',
                    'path' => CRED_ROOT_CLASSES_PATH . '/CRED_Admin.php'
                ),
                // make CRED Helper a depenency of CRED
                array(
                    'class' => 'CRED_Helper',
                    'path' => CRED_CLASSES_PATH . '/CRED_Helper.php'
                ),
                // make CRED Router a depenency of CRED
                array(
                    'class' => 'CRED_Router',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/classes/common/Router.php'
                ),
                array(
                    'class' => 'CRED_CRED',
                    'path' => CRED_CLASSES_PATH . '/CRED.php'
                )
            ),
            'Form_Helper' => array(
                array(
                    'class' => 'CRED_Form_Builder_Helper',
                    'path' => CRED_CLASSES_PATH . '/Form_Builder_Helper.php'
                )
            ),
            'Form_Builder' => array(
                // make Form Helper a depenency of Form Builder
                array(
                    'class' => 'CRED_Form_Builder_Helper',
                    'path' => CRED_CLASSES_PATH . '/Form_Builder_Helper.php'
                ),
                array(
                    'class' => 'CRED_Form_Builder',
                    'path' => CRED_CLASSES_PATH . '/Form_Builder.php'
                )
            ),
            'Form_Translator' => array(
                array(
                    'class' => 'CRED_Form_Translator',
                    'path' => CRED_CLASSES_PATH . '/Form_Translator.php'
                )
            ),
            'XML_Processor' => array(
                array(
                    'class' => 'CRED_XML_Processor',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/classes/common/XML_Processor.php'
                )
            ),
            'Mail_Handler' => array(
                array(
                    'class' => 'CRED_Mail_Handler',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/classes/common/Mail_Handler.php'
                )
            ),
            'Shortcode_Parser' => array(
                array(
                    'class' => 'CRED_Shortcode_Parser',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/classes/common/Shortcode_Parser.php'
                )
            ),
            'Router' => array(
                array(
                    'class' => 'CRED_Router',
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/classes/common/Router.php'
                )
            )
        ),
        'VIEW' => array(
            'forms' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/forms.php'
                )
            ),
            'user_forms' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/user_forms.php'
                )
            ),
            'settings-wizard' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/settings_wizard.php'
                )
            ),
            'settings-export' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/settings_export.php'
                )
            ),
            'settings-styling' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/settings_styling.php'
                )
            ),
            'settings-other' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/settings_other.php'
                )
            ),
            'settings-recaptcha' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/settings_recaptcha.php'
                )
            ),
            'settings-filter' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/settings_filter.php'
                )
            ),
            'settings-user-forms' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/settings_user_forms.php'
                )
            ),
            'export' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/export.php'
                )
            ),
            'import-post-forms' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/import_post_forms.php'
                )
            ),
            'import-user-forms' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/import_user_forms.php'
                )
            ),
            'help' => array(
                array(
                    'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/help.php'
                )
            )
        ),
        'TEMPLATE' => array(
            'form-settings-meta-box' => array(
                'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/templates/form-settings-meta-box.tpl.php'
            ),
            'user-form-settings-meta-box' => array(
                'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/templates/user-form-settings-meta-box.tpl.php'
            ),
            'text-settings-meta-box' => array(
                'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/templates/text-settings-meta-box.tpl.php'
            ),
	        'how-to-display-meta-box' => array(
		        'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/templates/how-to-display-meta-box.tpl.php'
	        ),
            'delete-post-link' => array(
                'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/templates/delete-post-link.tpl.php'
            ),
            'pe_form_notification_option' => array(
                'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/templates/pe_form_notification_option.tpl.php'
            ),
            'pe_settings_meta_box' => array(
                'path' => CRED_ABSPATH . '/library/toolset/cred/embedded/views/templates/pe_settings_meta_box.tpl.php'
            )
        )
    ));
}

require_once "embedded/common/functions.php";

cred_start();
