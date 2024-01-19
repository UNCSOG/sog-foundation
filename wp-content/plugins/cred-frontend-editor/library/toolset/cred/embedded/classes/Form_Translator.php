<?php

/**
 * Toolset Forms Translator
 */
final class CRED_Form_Translator {

    private $_form_data;

    public function __construct() {
    }

	/**
	 * @param int $form_id
	 * @param string $form_name
	 */
    private function setFormData($form_id, $form_name) {
        $this->_form_data = array('ID' => $form_id, 'name' => $form_name);
    }

	/**
	 * @param string $name
	 * @param string $value
	 * @refactor The mirror method for this should separace legacy, hence do this, or packages, and register in a package.
	 *  Not sure if you can register single strings in package, I think ys.
	 */
    private function registerString($name, $value) {
        cred_translate_register_string('cred-form-' . $this->_form_data['name'] . '-' . $this->_form_data['ID'], $name, $value, false);
    }

	/**
	 * @param $content
	 * @refactor This one is good to mirror
	 */
    private function processFormForStrings($content) {
        $shorts = cred_disable_shortcodes();
        add_shortcode('cred-field', array(&$this, 'check_strings_in_shortcodes'));
        add_shortcode('cred_field', array(&$this, 'check_strings_in_shortcodes'));
        do_shortcode($content);
        remove_shortcode('cred-field', array(&$this, 'check_strings_in_shortcodes'));
        remove_shortcode('cred_field', array(&$this, 'check_strings_in_shortcodes'));
        cred_re_enable_shortcodes($shorts);
    }

	/**
	 * @param $atts
	 * @refactor Good to mirror
	 */
	public function check_strings_in_shortcodes( $atts ) {
		extract(
			shortcode_atts( array(
				'value' => null,
				'select_text' => null,
			), $atts )
		);

		if ( null !== $value
			&& ! empty( $value )
			&& is_string( $value )
		) {
			$_prefix = 'Value: ';
			$this->registerString( $_prefix . $value, $value );
		}

		if ( null !== $select_text
			&& ! empty( $select_text )
			&& is_string( $select_text )
		) {
			$_prefix = 'Default Label: ';
			$this->registerString( $_prefix . $select_text, $select_text );
		}
	}

	/**
	 * Process the WPML translation af all cred form elements
	 *
	 * @param $data
	 * @refactor The mirror of this method should get the $post as first paraeter,
	 *  and the relevant $data as optional second
	 *  to be calculated if missing.
	 */
    public function processForm($data) {
        if (!isset($data['post'])) {
	        return;
        }

        $form = $data['post'];
        $message = CRED_StaticClass::unesc_meta_data($data['message']);
        $notification = $data['notification'];
        $messages = CRED_StaticClass::unesc_meta_data($data['messages']);

        $this->setFormData($form->ID, $form->post_title);
        //  register field values
        $this->processFormForStrings($form->post_content);
        // register form title
        $this->registerString('Form Title: ' . $form->post_title, $form->post_title);
        $this->registerString('Display Message: ' . $form->post_title, $message);

        // register Notification Data also
        if ($notification && isset($notification->notifications) && is_array($notification->notifications)) {
            foreach ($notification->notifications as $ii => $nott) {
            	$mail_subject = CRED_StaticClass::unesc_meta_data($nott['mail']['subject']);
                $mail_body = CRED_StaticClass::unesc_meta_data($nott['mail']['body']);

                $hashSubject = CRED_Helper::strHash("notification-subject-" . $form->ID . "-" . $ii);
                $hashBody = CRED_Helper::strHash("notification-body-" . $form->ID . "-" . $ii);

                $this->registerString('CRED Notification Subject ' . $hashSubject, $mail_subject);
                $this->registerString('CRED Notification Body ' . $hashBody, $mail_body);
            }
        }
        // register messages also
        foreach ($messages as $msgid => $msg) {
            $this->registerString('Message_' . $msgid, $msg);
        }

        CRED_CRED::$_form_builder_instance = CRED_Form_Builder::initialize();
        CRED_CRED::$_form_builder_instance->get_form($form->ID);

        // allow 3rd-party to add extra localization
        do_action('cred_localize_form', $data);
    }

	/**
	 * @param array $arr_id
	 * @refactor Wow, getting all forms just to check whether one is supposed to be translated!!!!
	 * @refactor No association forms management!!!!!
	 */
    public function processAllForms($arr_id = array()) {
        //POST FORMS
        $fm = CRED_Loader::get('MODEL/Forms');
        $forms = $fm->getAllForms();
        foreach ($forms as $form) {
            if (!empty($arr_id) && !in_array($form->ID, $arr_id))
                continue;
            $data = array(
                'post' => $form,
                'message' => '',
                'messages' => array(),
                'notification' => (object) array(
                    'enable' => 0,
                    'notifications' => array()
                )
            );

            $fields = $fm->getFormCustomFields($form->ID, array('form_settings', 'notification', 'extra'));
            $settings = isset($fields['form_settings']) ? $fields['form_settings'] : false;
            $notification = isset($fields['notification']) ? $fields['notification'] : false;
            $extra = isset($fields['extra']) ? $fields['extra'] : false;

            // register settings
            if ($settings && isset($settings->form['action_message']))
                $data['message'] = $settings->form['action_message'];

            // register Notification Data also
            if ($notification) {
                $data['notification'] = $notification;
            }
            // register extra fields
            if ($extra && isset($extra->messages)) {
                // register messages also
                $data['messages'] = $extra->messages;
            }

            $this->processForm($data);
        }

        //USER FORMS
        $fm = CRED_Loader::get('MODEL/UserForms');
        $forms = $fm->getAllForms();
        foreach ($forms as $form) {
            if (!empty($arr_id) && !in_array($form->ID, $arr_id))
                continue;
            $data = array(
                'post' => $form,
                'message' => '',
                'messages' => array(),
                'notification' => (object) array(
                    'enable' => 0,
                    'notifications' => array()
                )
            );

            $fields = $fm->getFormCustomFields($form->ID, array('form_settings', 'notification', 'extra'));
            $settings = isset($fields['form_settings']) ? $fields['form_settings'] : false;
            $notification = isset($fields['notification']) ? $fields['notification'] : false;
            $extra = isset($fields['extra']) ? $fields['extra'] : false;

            // register settings
            if ($settings && isset($settings->form['action_message']))
                $data['message'] = $settings->form['action_message'];

            // register Notification Data also
            if ($notification) {
                $data['notification'] = $notification;
            }
            // register extra fields
            if ($extra && isset($extra->messages)) {
                // register messages also
                $data['messages'] = $extra->messages;
            }

            $this->processForm($data);
        }
    }

}
