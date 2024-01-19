<?php

class CRED_Translate_Wysiwyg_Command extends CRED_Translate_Field_Command_Base {

    public function execute() {
        $this->field_attributes = array_merge( $this->field_attributes, array( 'disable_xss_filters' => true ) );
        // Make sure we do include settings for editor toolbar buttons.
        $this->field_attributes['has_media_button'] = ( toolset_getarr( $this->form->fields['form_settings']->form, 'has_media_button' ) ? 1 : 0 );
        $this->field_attributes['has_toolset_buttons'] = ( toolset_getarr( $this->form->fields['form_settings']->form, 'has_toolset_buttons' ) ? 1 : 0 );

        return new CRED_Field_Translation_Result( $this->field_configuration, $this->field_type, $this->field_name, $this->field_value, $this->field_attributes, $this->field );
    }
}