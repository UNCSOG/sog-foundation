<?php

namespace OTGS\Toolset\CRED\Controller;

use OTGS\Toolset\CRED\Controller\Links\Link;

/**
 * Links manager, mainly used for documentation links.
 * 
 * @since 2.1
 */
class LinksManager {

    /**
     * Get the escaped URL.
     * 
     * @param string $url
     * @param array $query_args
     * @param string anchor
     *
     * @return string
     * @since 2.1
     */
    public function get_escaped_link( $url, $query_args = array(), $anchor = '' ) {
        $link = new Link( $url, $query_args, $anchor );
        return $link->get_escaped_link();
    }

    /**
     * Get the generated URL.
     * 
     * @param string $url
     * @param array $query_args
     * @param string anchor
     *
     * @return string
     * @since 2.1
     */
    public function get_link( $url, $query_args = array(), $anchor = '' ) {
        $link = new Link( $url, $query_args, $anchor );
        return $link->get_link();
    }

}