<?php

abstract class CRED_Generic_Field_Abstract {

    protected $_atts;
    protected $_content;
    protected $cred_form_rendering;
    protected $_formHelper;
    protected $_formData;
    protected $_translate_field_factory;

	/**
	 * CRED_Generic_Field_Abstract constructor.
	 *
	 * @param array $atts
	 * @param string $content
	 * @param CRED_Form_Rendering $cred_form_rendering
	 * @param CRED_Helper $formHelper
	 * @param CRED_Form_Data $formData
	 * @param CRED_Translate_Field_Factory $translate_field_factory
	 */
    public function __construct($atts, $content, $cred_form_rendering, $formHelper, $formData, $translate_field_factory) {
        $this->_atts = $atts;
        $this->_content = $content;
        $this->cred_form_rendering = $cred_form_rendering;
        $this->_formHelper = $formHelper;
        $this->_formData = $formData;
        $this->_translate_field_factory = $translate_field_factory;
    }

}
