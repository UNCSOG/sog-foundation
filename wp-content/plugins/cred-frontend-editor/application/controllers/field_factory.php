<?php

class CRED_Field_Factory {

    public static function create_field($atts, $credRenderForm, $formHelper, $formData, $translateFieldFactory) {
        $cred_field = new CRED_Field($atts, $credRenderForm, $formHelper, $formData, $translateFieldFactory);
        return $cred_field->get_field();
    }

    public static function create_generic_field($atts, $content, $credRenderForm, $formHelper, $formData, $translateFieldFactory) {
        $cred_field = new CRED_Generic_Field($atts, $content, $credRenderForm, $formHelper, $formData, $translateFieldFactory);
        return $cred_field->get_field();
    }

}
