<?php

namespace OTGS\Toolset\CRED\Controller\Condition;

/**
 * Conditional check against the existence of a given function.
 * 
 * @since 2.1.1
 */
class FunctionExists implements \Toolset_Condition_Interface {

    /**
     * Check if a given function exists.
     *
     * @param boolean|string $candidate
     * @return boolean
     * @since 2.1.1
     */
    public function is_met( $candidate = false ) {
        if (
            $candidate
            && function_exists( $candidate )
        ) {
            return true;
        }

        return false;
    }

}