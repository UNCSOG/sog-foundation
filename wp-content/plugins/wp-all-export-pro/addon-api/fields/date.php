<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Date_Field extends PMXE_Addon_Field {

    public function isTimestamp( $string ) {
        try {
            new \DateTime( '@' . $string );
        } catch ( \Exception $e ) {
            return false;
        }

        return true;
    }

    public function toString() {
        if ( ! $this->value ) {
            return '';
        }

        $timestamp = $this->isTimestamp( $this->value ) ?
            $this->value : strtotime( $this->value );

        // We need a default value for the settings if they're blank.
        $this->settings = $this->settings ?: 'Y-m-d';

        return prepare_date_field_value(
            $this->settings,
            $timestamp,
            "Y-m-d"
        );
    }
}
