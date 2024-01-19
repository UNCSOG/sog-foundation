<?php

namespace OTGS\Toolset\CRED\Controller\Condition;

/**
 * Conditional check against the existence of a given class.
 * 
 * @since 2.1.1
 */
class ClassExists implements \Toolset_Condition_Interface {

    /**
     * Check if a given class exists.
     *
     * @param boolean|string $candidate
     * @return boolean
     * @since 2.1.1
     */
    public function is_met( $candidate = false ) {
        if (
            $candidate
            && class_exists( $candidate )
        ) {
            return true;
        }

        return false;
    }

}