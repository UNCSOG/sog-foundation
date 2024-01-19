<?php

namespace OTGS\Toolset\CRED\Controller\Links;

/**
 * Link object, mainly used for documentation links.
 * 
 * @since 2.1
 */
class Link {

    /**
     * @var string
     */
    private $url;

    /**
     * @var array
     */
    private $query_args = array();

    /**
     * @var string
     */
    private $anchor = '';

    function __construct( $url, $query_args = array(), $anchor = '' ) {
        $this->url = $url;
        $this->query_args = $query_args;
        $this->anchor = $anchor;
    }

    /**
     * Get the escaped URL.
     *
     * @return string
     * @since 2.1
     */
    public function get_escaped_link() {
        return esc_url( $this->get_link() );
    }

    /**
     * Get the generated URL.
     *
     * @return string
     * @since 2.1
     */
    public function get_link() {
        if ( ! empty( $this->query_args ) ) {
			$this->url = add_query_arg( $this->query_args, $this->url );
		}
		if ( ! empty( $this->anchor ) ) {
			$this->url .= '#' . esc_attr( $this->anchor );
		}
		return $this->url;
    }
}