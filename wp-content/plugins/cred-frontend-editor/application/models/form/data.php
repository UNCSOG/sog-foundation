<?php

/**
 * Holds settings and other information about Toolset Forms.
 *
 * Legacy class. Used in CRED_Form_Base and CRED_Helper.
 *
 * @since unknown
 * @todo The properties form and fields needed to be made public on 2.6,
 *     because somehow some methods are expecting that an instance of this class holds both
 *     the object with its own methods and the _form_data obect with its own properties,
 *     because someone decided that having a constructore returning somethign was a good idea.
 *     Ideally, those properties would not need to be isolated, and they would be accessed using
 *     the right getForm/getFields methods.
 */
class CRED_Form_Data {

	private $_form_data = null;

	/**
	 * @var object
	 * @since 2.6
	 * @deprecated
	 */
	public $form;


	/**
	 * @var array
	 * @since 2.6
	 * @deprecated
	 */
	public $fields;

    public function __construct($form_id, $post_type, $preview) {
		$this->_form_data = $this->loadForm($form_id, $post_type, $preview);
		$this->form = $this->_form_data->form;
		$this->fields = $this->_form_data->fields;

		// This return statement is plan wrong, but I am keeping in in case removing it does more harm than good.
        return $this->_form_data;
    }

    public function getForm() {
        return $this->_form_data->form;
    }

    public function getFields() {
        return $this->_form_data->fields;
    }

    public function loadForm($formID, $post_type, $preview = false) {
        global $post, $current_user;

        // load form data
        $fm = ($post_type == CRED_USER_FORMS_CUSTOM_POST_NAME) ? CRED_Loader::get('MODEL/UserForms') : CRED_Loader::get('MODEL/Forms');
        $form = $fm->getForm($formID);

        if (/* false=== */!$form) {
            return $this->error(__('Form does not exist!', 'wp-cred'));
        }

        // preview when form is saved only partially
        if (!isset($form->fields) || !is_array($form->fields) || empty($form->fields)) {
            $form->fields = array();
            if ($preview) {
                unset($form);
                return $this->error(__('Form preview does not exist. Try saving your form first', 'wp-cred'));
            }
        }

        $form->fields = array_merge(
                array(
            'form_settings' => (object) array('form' => array(), 'post' => array()),
            'extra' => (object) array('css' => '', 'js' => ''),
            'notification' => (object) array('enable' => 0, 'notifications' => array())
                ), $form->fields
        );

	    if (empty($form->fields['extra'])) {
		    $form->fields['extra'] = new stdClass();
	    }
	    if ( ! isset( $form->fields['extra']->css ) ) {
		    $form->fields['extra']->css = '';
	    }
	    if ( ! isset( $form->fields['extra']->js ) ) {
		    $form->fields['extra']->js = '';
	    }

        $form->fields['form_settings']->form['redirect_delay'] =
            isset( $form->fields['form_settings']->form['redirect_delay'] )
            ? intval( $form->fields['form_settings']->form['redirect_delay'] )
            : 0;
        $form->fields['form_settings']->form['hide_comments'] =
            (
                isset( $form->fields['form_settings']->form['hide_comments'] )
                && $form->fields['form_settings']->form['hide_comments']
            )
            ? 1
            : 0;
        $form->fields['form_settings']->form['has_media_button'] =
            (
                isset( $form->fields['form_settings']->form['has_media_button'] )
                && $form->fields['form_settings']->form['has_media_button']
            )
            ? 1
            : 0;
        $form->fields['form_settings']->form['has_toolset_buttons'] =
            (
                isset( $form->fields['form_settings']->form['has_toolset_buttons'] )
                && $form->fields['form_settings']->form['has_toolset_buttons']
            )
            ? 1
            : 0;
        $form->fields['form_settings']->form['has_media_manager'] =
            (
                isset( $form->fields['form_settings']->form['has_media_manager'] )
                && $form->fields['form_settings']->form['has_media_manager']
            )
            ? 1
            : 0;
        $form->fields['form_settings']->form['has_media_manager'] =
			( isset( $form->fields['form_settings']->form['has_media_manager'] ) )
			? (
				( true === (bool) $form->fields['form_settings']->form['has_media_manager'] )
				? 1
				: 0
			) : 1;

        if ($preview) {
            if (array_key_exists(CRED_StaticClass::PREFIX . 'form_preview_post_type', $_POST))
                $form->fields['form_settings']->post['post_type'] = stripslashes($_POST[CRED_StaticClass::PREFIX . 'form_preview_post_type']);
            else {
                unset($form);
                return $this->error(__('Preview post type not provided', 'wp-cred'));
            }

            if (array_key_exists(CRED_StaticClass::PREFIX . 'form_preview_form_type', $_POST))
                $form->fields['form_settings']->form['type'] = stripslashes($_POST[CRED_StaticClass::PREFIX . 'form_preview_form_type']);
            else {
                unset($form);
                $this->error = __('Preview form type not provided', 'wp-cred');
            }
            if (array_key_exists(CRED_StaticClass::PREFIX . 'form_preview_content', $_POST)) {
                $form->form->post_content = stripslashes($_POST[CRED_StaticClass::PREFIX . 'form_preview_content']);
            } else {
                unset($form);
                return $this->error(__('No preview form content provided', 'wp-cred'));
            }

            if (array_key_exists(CRED_StaticClass::PREFIX . 'extra_css_to_use', $_POST)) {
                $form->fields['extra']->css = trim(stripslashes($_POST[CRED_StaticClass::PREFIX . 'extra_css_to_use']));
            }
            if (array_key_exists(CRED_StaticClass::PREFIX . 'extra_js_to_use', $_POST)) {
                $form->fields['extra']->js = trim(stripslashes($_POST[CRED_StaticClass::PREFIX . 'extra_js_to_use']));
            }
        } else {
            if ($post_type == CRED_USER_FORMS_CUSTOM_POST_NAME)
                $form->fields['form_settings']->post['post_type'] = "user";
        }

        if (!isset($form->fields['extra']->messages)) {
            $form->fields['extra']->messages = $fm->getDefaultMessages();
        }

        //return it
        return $form;
    }

    // whether this form attempts to hide comments
    public function hasHideComments() {
        $fields = $this->getFields();
        return $fields['form_settings']->form['hide_comments'];
    }

    // get extra javascript/css attached to this form
    public function getExtra() {
        $fields = $this->getFields();
        return $fields['extra'];
    }

    public function error($msg = '') {
        return new WP_Error($msg);
    }


	/**
	 * Are we adding or editing content here?
	 *
	 * @return string 'add'|'edit'
	 * @since 2.6
	 */
	public function get_form_type() {
		$fields = $this->getFields();
		return $fields['form_settings']->form['type'];
    }

}
