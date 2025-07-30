<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_Time_Field extends PMXE_Addon_Field {

    public function toString() {
        if ( ! $this->value ) {
            return '';
        }
        $format = $this->settings['time_format'] ?? 'H:i:s';

        return date( $format, strtotime( $this->value ) );
    }
}
