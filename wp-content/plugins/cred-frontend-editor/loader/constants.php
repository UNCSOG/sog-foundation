<?php
/**
 * Plugin constants.
 *
 * @package Toolset Forms
 */

define( 'CRED_ABSURL', plugins_url() . '/' . basename( CRED_ABSPATH ) );

/**
 * Templates path.
 */
define( 'CRED_TEMPLATES', CRED_ABSPATH . '/application/views' );

/**
 * General plugin capability.
 */
define( 'CRED_CAPABILITY', 'manage_options' );

/**
 * Custom objects post types.
 *
 * @deprecated Use \OTGS\Toolset\CRED\Controller\Forms\Post\Main::POST_TYPE instead.
 * @deprecated Use \OTGS\Toolset\CRED\Controller\Forms\User\Main::POST_TYPE instead.
 */
define( 'CRED_FORMS_CUSTOM_POST_NAME', 'cred-form' );
define( 'CRED_USER_FORMS_CUSTOM_POST_NAME', 'cred-user-form' );
define( 'CRED_RELATIONSHIP_FORMS_CUSTOM_POST_NAME', 'cred-relationship-form' );

/**
 * Module Manager
 */
define( '_CRED_MODULE_MANAGER_KEY_', 'cred' );
define( '_CRED_MODULE_MANAGER_USER_KEY_', 'cred-user' );

/**
 * Constants for Toolset Forms
 *
 * Section: formatting
 */
define( 'CRED_STRING_SPACE', '&nbsp;' );

/**
 * Constants for Toolset Forms
 *
 * Section: help links
 */
define( 'CRED_DOC_LINK_CONDITIONAL_GROUP', 'https://toolset.com/course-lesson/conditional-display-for-form-inputs/' );
define( 'CRED_DOC_LINK_NON_TOOLSET_FIELDS_CONTROL', 'https://toolset.com/course-lesson/' );
define( 'CRED_DOC_LINK_AUTOMATIC_POST_EXPIRATION', 'https://toolset.com/course-lesson/setting-up-automatic-post-expiration/' );
define( 'CRED_DOC_LINK_NOTIFICATIONS', 'https://toolset.com/course-lesson/send-notifications-when-someone-submits-the-form/' );

define( 'CRED_DOC_LINK_GETTING_STARTED', 'https://toolset.com/course-lesson/front-end-forms-for-adding-content/?utm_source=plugin&utm_medium=gui&utm_campaign=forms' );
define( 'CRED_DOC_LINK_FRONTEND_CREATING_CONTENT', 'https://toolset.com/course-lesson/front-end-forms-for-adding-content/' );
define( 'CRED_DOC_LINK_FRONTEND_CREATING_USERS', 'https://toolset.com/course-lesson/creating-forms-for-registering-users/' );
define( 'CRED_DOC_LINK_FRONTEND_EDITING', 'https://toolset.com/course-lesson/front-end-forms-for-editing-content/' );
