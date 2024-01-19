<?php if (!defined('ABSPATH')) die('Security check'); ?>
<?php

// Define first $cred_help as this gets loaded via CRED_Loader::getVar() which uses get_defined_vars() and reset()
$cred_help = array();
$links_manager = new OTGS\Toolset\CRED\Controller\LinksManager();

// localize links also, to provide locale specific urls
$cred_help = array(
    'conditionals' => array(
        // Probably deprecated
        'link' => 'https://toolset.com/documentation/programmer-reference/forms/cred-conditional-display-engine/?utm_source=plugin&utm_medium=gui&utm_campaign=forms',
        'text' => __('Toolset Forms Conditional Expressions', 'wp-cred')
    ),
    'handle_caching' => array(
        // Probably deprecated
        'link' => '#',
        'text' => __('How to disable caching for content with forms', 'wp-cred')
    ),
    'add_forms_to_site' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_CONTENT,
            array(
                'utm_source' => 'plugin',
				'utm_campaign' => 'forms',
				'utm_medium' => 'gui',
                'utm_term' => 'forms-creating-doc'
            )
        ),
        'text' => __('How to add Forms to your site', 'wp-cred')
    ),
    'add_post_forms_to_site' => array(
        // Used in admin notice after saving a post form
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_CONTENT,
            array(
                'utm_source' => 'plugin',
				'utm_campaign' => 'forms',
				'utm_medium' => 'gui',
                'utm_term' => 'forms-creating-doc'
            )
        ),
        'text' => __('How to add Post Forms to your site &raquo;', 'wp-cred')
    ),
    'add_user_forms_to_site' => array(
        // Used in admin notice after saving an user form
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_USERS,
            array(
                'utm_source' => 'plugin',
                'utm_campaign' => 'forms',
                'utm_medium' => 'gui',
                'utm_term' => 'forms-users-doc'
            )
        ),
        'text' => __('How to add User Forms to your site &raquo;', 'wp-cred')
    ),
    'general_form_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_CONTENT,
            array(
                'utm_source' => 'plugin',
				'utm_campaign' => 'forms',
				'utm_medium' => 'gui',
                'utm_term' => 'forms-creating-doc'
            )
        ),
        'text' => __('Form Settings Help', 'wp-cred')
    ),
    'post_type_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_CONTENT,
            array(
                'utm_source' => 'plugin',
				'utm_campaign' => 'forms',
				'utm_medium' => 'gui',
                'utm_term' => 'forms-creating-doc'
            )
        ),
        'text' => __('Post Settings Help', 'wp-cred')
    ),
    'notification_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_CONTENT,
            array(
                'utm_source' => 'plugin',
				'utm_campaign' => 'forms',
				'utm_medium' => 'gui',
                'utm_term' => 'forms-creating-doc'
            ),
            'e-mail-notifications'
        ),
        'text' => __('Notification Settings Help', 'wp-cred')
    ),
    'css_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_CONTENT,
            array(
                'utm_source' => 'plugin',
				'utm_campaign' => 'forms',
				'utm_medium' => 'gui',
                'utm_term' => 'forms-creating-doc'
            ),
            'designing-the-form'
        ),
        'text' => __('Extra CSS Help', 'wp-cred')
    ),
    'scaffold_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_USERS,
            array(
                'utm_source' => 'plugin',
                'utm_campaign' => 'forms',
                'utm_medium' => 'gui',
                'utm_term' => 'forms-users-doc'
            ),
            'designing-the-user-form'
        ),
        'text' => __('Scaffold Help', 'wp-cred')
    ),
    'generic_fields_settings' => array(
        // Probably deprecated
        'link' => 'https://toolset.com/course-lesson/adding-generic-fields-to-forms/?utm_source=plugin&utm_medium=gui&utm_campaign=forms',
        'text' => __('Generic Fields Help', 'wp-cred')
    ),
    'fields_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_CONTENT,
            array(
                'utm_source' => 'plugin',
				'utm_campaign' => 'forms',
				'utm_medium' => 'gui',
                'utm_term' => 'forms-creating-doc'
            ),
            'designing-the-form'
        ),
        'text' => __('Post Fields Help', 'wp-cred')
    ),
    'fields_settings_users' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_USERS,
            array(
                'utm_source' => 'plugin',
                'utm_campaign' => 'forms',
                'utm_medium' => 'gui',
                'utm_term' => 'forms-users-doc'
            ),
            'designing-the-user-form'
        ),
        'text' => __('Post Fields Help', 'wp-cred')
    ),
    'content_creation_shortcode_post_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_CONTENT,
            array(
                'utm_source' => 'plugin',
				'utm_campaign' => 'forms',
				'utm_medium' => 'gui',
                'utm_term' => 'forms-creating-doc'
            ),
            'displaying-toolset-forms'
        ),
        'text' => __('Help', 'wp-cred')
    ),
    'content_creation_shortcode_user_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_CREATING_USERS,
            array(
                'utm_source' => 'plugin',
                'utm_campaign' => 'forms',
                'utm_medium' => 'gui',
                'utm_term' => 'forms-users-doc'
            ),
            'displaying-toolset-user-forms'
        ),
        'text' => __('Help', 'wp-cred')
    ),
    'content_delete_shortcode_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_EDITING,
            array(
                'utm_source' => 'plugin',
                'utm_campaign' => 'forms',
                'utm_medium' => 'gui',
                'utm_term' => 'forms-editing-doc'
            )
        ),
        'text' => __('Help', 'wp-cred')
    ),
    'content_edit_shortcode_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_EDITING,
            array(
                'utm_source' => 'plugin',
                'utm_campaign' => 'forms',
                'utm_medium' => 'gui',
                'utm_term' => 'forms-editing-doc'
            )
        ),
        'text' => __('Help', 'wp-cred')
    ),
    'content_edit_user_shortcode_settings' => array(
        // Probably deprecated
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_EDITING,
            array(
                'utm_source' => 'plugin',
                'utm_campaign' => 'forms',
                'utm_medium' => 'gui',
                'utm_term' => 'forms-editing-doc'
            )
        ),
        'text' => __('Help', 'wp-cred')
    ),
    'autogeneration_notification_missing_alert' => array(
        // Used in admin notices after saving an user form
        'link' => 'https://toolset.com/course-lesson/send-notifications-when-someone-submits-the-form/?utm_source=plugin&utm_medium=gui&utm_campaign=forms',
        'text' => __('How to create notifications for sending passwords', 'wp-cred')
    ),
    'cred_inserting_edit_links' => array(
        // Used in admin notice after saving a form
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_EDITING,
            array(
                'utm_source' => 'plugin',
                'utm_campaign' => 'forms',
                'utm_medium' => 'gui',
                'utm_term' => 'forms-editing-doc'
            )
        ),
		'text' => __('How to display editing forms &raquo;', 'wp-cred')
	),
	'displaying_cred_editing_forms' => array(
        // Used in wizards
        'link' => $links_manager->get_escaped_link(
            CRED_DOC_LINK_FRONTEND_EDITING,
            array(
                'utm_source' => 'plugin',
                'utm_campaign' => 'forms',
                'utm_medium' => 'gui',
                'utm_term' => 'forms-editing-doc'
            )
        ),
		'text' => __('displaying Toolset Editing Forms', 'wp-cred')
	),
	'cred_relationship_forms_instructions' => array(
        // Probably deprecated
		'link' => 'https://toolset.com/course-lesson/front-end-relationship-forms-for-connecting-posts/?utm_source=plugin&utm_medium=gui&utm_campaign=forms',
		'text' => __('Relationship Forms Instructions', 'wp-cred')
	)
);
?>
