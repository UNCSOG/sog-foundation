<?php

namespace OTGS\Toolset\CRED\Controller\Permissions;

/**
 * Toolset Forms permissions compatibility factory.
 * 
 * @since 2.1.1
 */
class Factory {

    /**
     * Instantiate the compatibility layer with Toolset Access.
     *
     * @param \OTGS\Toolset\CRED\Controller\Permissions $permission_manager
     * @return \OTGS\Toolset\CRED\Controller\Permissions\ToolsetAccess
     * @since 2.1.1
     */
    public function toolset_access( \OTGS\Toolset\CRED\Controller\Permissions $permission_manager ) {
        return new ToolsetAccess( $permission_manager );
    }

    /**
     * Instantiate the compatibility layer with third party plugins.
     *
     * @param \OTGS\Toolset\CRED\Controller\Permissions $permission_manager
     * @return \OTGS\Toolset\CRED\Controller\Permissions\ThirdParty
     * @since 2.1.1
     */
    public function third_party( \OTGS\Toolset\CRED\Controller\Permissions $permission_manager ) {
        return new ThirdParty( $permission_manager );
    }

}