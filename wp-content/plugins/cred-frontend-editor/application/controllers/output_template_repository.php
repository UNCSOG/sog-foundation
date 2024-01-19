<?php

/**
 * Repository for templates in Types.
 *
 * See Toolset_Renderer for a detailed usage instructions.
 *
 * @since m2m
 */
class CRED_Output_Template_Repository extends Toolset_Output_Template_Repository_Abstract {

	const METABOX_POST_ACCESS = 'editor_metaboxes/post/access.phtml';
	const METABOX_USER_ACCESS = 'editor_metaboxes/user/access.phtml';

	const SETTINGS_ACTION_MESSAGE = 'editor_settings/shared/action_message.phtml';
	const SETTINGS_FORM_SUBMIT = 'editor_settings/shared/form_submit.phtml';
	const SETTINGS_POST_EXPIRATION = 'editor_settings/post/expiration.phtml';
	const SETTINGS_OTHER_SETTINGS = 'editor_settings/shared/other_settings.phtml';

	const CONTENT_EDITOR_TOOLBAR_SCAFFOLD_CONTENT = 'scaffold-content.phtml';
	const CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM = 'scaffold-item.phtml';
	const CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS = 'scaffold-item_options.phtml';
	const CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS_MEDIA = 'scaffold-item_options_media.phtml';
	const CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS_CONDITIONALS = 'scaffold-item_options_conditionals.phtml';
	const CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS_HTML_CONTENT = 'scaffold-item_options_html_content.phtml';
	const CONTENT_EDITOR_TOOLBAR_SCAFFOLD_NO_DATA = 'scaffold-need-more-data.phtml';
	const CONTENT_EDITOR_TOOLBAR_SCAFFOLD_CONTAINER = 'scaffold-container.phtml';
	const CONTENT_EDITOR_TOOLBAR_SCAFFOLD_DIALOG_SWITCH_TO_DD = 'scaffold-dialog-switch-to-dd.phtml';
	const CONTENT_EDITOR_TOOLBAR_FIELDS_DIALOG = 'fields-dialog.phtml';
	const CONTENT_EDITOR_TOOLBAR_GENERIC_FIELDS_DIALOG = 'generic-fields-dialog.phtml';
	const CONTENT_EDITOR_TOOLBAR_GENERIC_FIELDS_OPTIONS_MANUAL_TABLE = 'generic-fields-options-manual-table.phtml';
	const CONTENT_EDITOR_TOOLBAR_GENERIC_FIELDS_OPTIONS_MANUAL_ROW = 'generic-fields-options-manual-row.phtml';
	const CONTENT_EDITOR_TOOLBAR_FIELDS_ITEM = 'fields-item.phtml';
	const CONTENT_EDITOR_TOOLBAR_CONDITIONAL_GROUPS_DIALOG = 'conditional-groups-dialog.phtml';
	const CONTENT_EDITOR_TOOLBAR_CONDITIONAL_GROUPS_ROW = 'conditional-groups-row.phtml';
	const CONTENT_EDITOR_WIZARD_TITLE_WRAPPER = 'wizard-title-wrapper.phtml';

	const NOTIFICATION_EDITOR_ITEM = 'editor_notifications/notification.phtml';
	const NOTIFICATION_EDITOR_SECTION_SHARED_NAME = 'editor_notifications/shared/name.phtml';
	const NOTIFICATION_EDITOR_SECTION_POST_TRIGGER = 'editor_notifications/post/trigger.phtml';
	const NOTIFICATION_EDITOR_SECTION_POST_TRIGGER_EXPIRATION = 'editor_notifications/post/trigger/expiration.phtml';
	const NOTIFICATION_EDITOR_SECTION_USER_TRIGGER = 'editor_notifications/user/trigger.phtml';
	const NOTIFICATION_EDITOR_SECTION_SHARED_TRIGGER_META_CONDITION = 'editor_notifications/shared/trigger_meta_condition.phtml';
	const NOTIFICATION_EDITOR_SECTION_SHARED_RECIPIENT = 'editor_notifications/shared/recipient.phtml';
	const NOTIFICATION_EDITOR_SECTION_SHARED_FROM = 'editor_notifications/shared/from.phtml';
	const NOTIFICATION_EDITOR_SECTION_SHARED_SUBJECT = 'editor_notifications/shared/subject.phtml';
	const NOTIFICATION_EDITOR_SECTION_SHARED_BODY = 'editor_notifications/shared/body.phtml';

	const NOTIFICATION_EDITOR_TOOLBAR_PLACEHOLDERS_DIALOG = 'placeholders-dialog.phtml';
	const NOTIFICATION_EDITOR_TOOLBAR_PLACEHOLDERS_ITEM = 'placeholders-item.phtml';

	const FIELDS_CONTROL_POSTMETA_PAGE = 'fields_control/post/page_template.phtml';
	const FIELDS_CONTROL_USERMETA_PAGE = 'fields_control/user/page_template.phtml';
	const FIELDS_CONTROL_SHARED_PRIVATE_FIELDS_CONTROL = 'fields_control/shared/private_fields_control.phtml';
	const FIELDS_CONTROL_SHARED_TABLE_ROW = 'fields_control/shared/table_row.phtml';
	const FIELDS_CONTROL_ADD_OR_EDIT_DIALOG = 'fields_control/shared/add_or_edit_dialog.phtml';

	const SHORTCODE_CRED_FORM_DIALOG = 'cred-form.phtml';
	const SHORTCODE_CRED_USER_FORM_DIALOG = 'cred-user-form.phtml';
	const SHORTCODE_CRED_CHILD_DIALOG = 'cred-child.phtml';

	const SHORTCODE_CRED_RELATIONSHIP_FORM_WIZARD_DIALOG = 'cred-relationship-form.phtml';

	const MCE_VIEW_CRED_FORM = '/mce/cred_form.phtml';
	const MCE_VIEW_CRED_USER_FORM = '/mce/cred_user_form.phtml';
	const MCE_VIEW_CRED_RELATIONSHIP_FORM = '/mce/cred-relationship-form.phtml';

	const SINGULAR_METABOX_POST_EXPIRATION = 'singular_metaboxes/post_expiration.phtml';

	const TOOLSET_SETTINGS_POST_EXPIRATION = 'settings/other/post_expiration.phtml';
	const TOOLSET_SETTINGS_POST_DRAFTS     = 'settings/post_drafts.phtml';

	/**
	 * @var array Template definitions.
	 */
	private $templates = null;


	/** @var Toolset_Output_Template_Repository */
	private static $instance;

	/**
	 * CRED_Output_Template_Repository constructor.
	 *
	 * @param Toolset_Output_Template_Factory|null $template_factory_di
	 * @param Toolset_Constants|null $constants_di
	 *
	 * this can only be PUBLIC although singleton pattern is used since the parent class __construct is public
	 */
	public function __construct( Toolset_Output_Template_Factory $template_factory_di = null,
		Toolset_Constants $constants_di = null ) {
		parent::__construct( $template_factory_di, $constants_di );
		$this->set_templates();
	}


	/**
	 * @return Toolset_Output_Template_Repository
	 */
	public static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_default_base_path() {
		return $this->constants->constant( 'CRED_TEMPLATES' );
	}

	/**
	 * For the sake of php < 5.6 initialise $templates variable in constructor to avoid fatal errors for string concatenation !!!!
	 */
	protected function set_templates() {
		$this->templates = array(
			self::METABOX_POST_ACCESS => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::METABOX_USER_ACCESS => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::SETTINGS_ACTION_MESSAGE => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::SETTINGS_FORM_SUBMIT => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::SETTINGS_POST_EXPIRATION => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::SETTINGS_OTHER_SETTINGS => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_CONTENT => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS_MEDIA => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS_CONDITIONALS => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_ITEM_OPTIONS_HTML_CONTENT => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_NO_DATA => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_CONTAINER => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_SCAFFOLD_DIALOG_SWITCH_TO_DD => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_WIZARD_TITLE_WRAPPER => array(
				'base_path' => CRED_TEMPLATES . '/editor_gui',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_FIELDS_DIALOG => array(
				'base_path' => CRED_TEMPLATES . '/editor_toolbars',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_GENERIC_FIELDS_DIALOG => array(
				'base_path' => CRED_TEMPLATES . '/editor_toolbars',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_GENERIC_FIELDS_OPTIONS_MANUAL_TABLE => array(
				'base_path' => CRED_TEMPLATES . '/editor_toolbars',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_GENERIC_FIELDS_OPTIONS_MANUAL_ROW => array(
				'base_path' => CRED_TEMPLATES . '/editor_toolbars',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_FIELDS_ITEM => array(
				'base_path' => CRED_TEMPLATES . '/editor_toolbars',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_CONDITIONAL_GROUPS_DIALOG => array(
				'base_path' => CRED_TEMPLATES . '/editor_toolbars',
				'namespaces' => array()
			),
			self::CONTENT_EDITOR_TOOLBAR_CONDITIONAL_GROUPS_ROW => array(
				'base_path' => CRED_TEMPLATES . '/editor_toolbars',
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_ITEM => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_SECTION_SHARED_NAME => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_SECTION_POST_TRIGGER => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_SECTION_POST_TRIGGER_EXPIRATION => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_SECTION_USER_TRIGGER => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_SECTION_SHARED_TRIGGER_META_CONDITION => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_SECTION_SHARED_RECIPIENT => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_SECTION_SHARED_FROM => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_SECTION_SHARED_SUBJECT => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_SECTION_SHARED_BODY => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_TOOLBAR_PLACEHOLDERS_DIALOG => array(
				'base_path' => CRED_TEMPLATES . '/editor_toolbars',
				'namespaces' => array()
			),
			self::NOTIFICATION_EDITOR_TOOLBAR_PLACEHOLDERS_ITEM => array(
				'base_path' => CRED_TEMPLATES . '/editor_toolbars',
				'namespaces' => array()
			),
			self::FIELDS_CONTROL_POSTMETA_PAGE => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::FIELDS_CONTROL_USERMETA_PAGE => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::FIELDS_CONTROL_ADD_OR_EDIT_DIALOG => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::FIELDS_CONTROL_SHARED_PRIVATE_FIELDS_CONTROL => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::FIELDS_CONTROL_SHARED_TABLE_ROW => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::SHORTCODE_CRED_FORM_DIALOG => array(
				'base_path' => CRED_TEMPLATES . '/dialogs/shortcodes',
				'namespaces' => array()
			),
			self::SHORTCODE_CRED_USER_FORM_DIALOG => array(
				'base_path' => CRED_TEMPLATES . '/dialogs/shortcodes',
				'namespaces' => array()
			),
			self::SHORTCODE_CRED_CHILD_DIALOG => array(
				'base_path' => CRED_TEMPLATES . '/dialogs/shortcodes',
				'namespaces' => array()
			),
			self::SHORTCODE_CRED_RELATIONSHIP_FORM_WIZARD_DIALOG => array(
				'base_path' => CRED_TEMPLATES . '/dialogs/shortcodes',
				'namespaces' => array()
			),
			self::MCE_VIEW_CRED_FORM => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::MCE_VIEW_CRED_USER_FORM => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::MCE_VIEW_CRED_RELATIONSHIP_FORM => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::SINGULAR_METABOX_POST_EXPIRATION => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::TOOLSET_SETTINGS_POST_EXPIRATION => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
			self::TOOLSET_SETTINGS_POST_DRAFTS => array(
				'base_path' => CRED_TEMPLATES,
				'namespaces' => array()
			),
		);
	}


	/**
	 * Get the array with template definitions.
	 *
	 * @return array
	 */
	protected function get_templates() {
		return $this->templates;
	}
}
