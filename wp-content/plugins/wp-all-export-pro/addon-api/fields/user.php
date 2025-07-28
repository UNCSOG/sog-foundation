<?php

namespace Wpae\AddonAPI;

class PMXE_Addon_User_Field extends PMXE_Addon_Field {

    public function getEmail( $value ) {
        $user = get_user_by( 'id', $value );

        if ( $user ) {
            $email = $user->user_email;
        }

        return $email ?? '';
    }

    public function getLogin( $value ) {
        $user = get_user_by( 'id', $value );

        if ( $user ) {
            $login = $user->user_login;
        }

        return $login ?? '';
    }

    public function toString() {
        $format = $this->settings['user_value_format'] ?? 'id';

        $return_value = [];

        if ( ! is_array( $this->value ) ) {
            $value = [ $this->value ];
        } else {
            $value = $this->value;
        }

        foreach ( $value as $current_value ) {
            switch ( $format ) {
                case 'id':
                    $return_value[] = $current_value;
                    break;
                case 'email':
                    $return_value[] = $this->getEmail( $current_value );
                    break;
                default:
                    $return_value[] = $this->getLogin( $current_value );
            }
        }

        return implode( $this->getImplode(), $return_value );
    }
}
