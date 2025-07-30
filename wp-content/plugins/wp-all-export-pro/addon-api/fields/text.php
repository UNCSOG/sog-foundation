<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Text_Field extends PMXE_Addon_Field {

    public function toString() {
        // There are some cases where the value is not a string and we don't know why.
        // The text field is the default field for unknown types, so we'll just cast it to a string.
        if ( ! is_string( $this->value ) ) {
            return json_encode( $this->value );
        }

        return trim( $this->value );
    }
}
