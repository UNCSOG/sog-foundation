<?php

namespace OTGS\Toolset\CRED\Controller\PageExtension;

/**
 * Page extension factory.
 * 
 * @since 2.1
 */
class Factory {
    const CLASS_PATTERN = 'OTGS\Toolset\CRED\Controller\Forms\%s\PageExtension\%s';

    /**
     * Transform an under_score string into a CamelCase string.
     *
     * @param string $metabox
     * 
     * @return string
     * 
     * @since 2.1
     */
    private function build_metabox_name( $metabox ) {
        $metabox_pieces = explode( '_', $metabox );
        $metabox_pieces = array_map( 'ucfirst', $metabox_pieces );
        return implode( '', $metabox_pieces );
    }
    
	/**
     * Build classname that controls the extension for a given metabox.
     * 
	 * @param string $domain
	 * @param string $metabox
	 *
	 * @return string
     * 
     * @since 2.1
	 */
	private function build_class_name( $domain = '', $metabox = '' ) {
        $domain = ucfirst( $domain );
        $metabox = $this->build_metabox_name( $metabox );
		return sprintf( self::CLASS_PATTERN, $domain, $metabox );
    }
    
    /**
     * Get the callback that controls the output for a given metabox.
     * 
	 * @param string $domain
	 * @param string $metabox
	 *
	 * @return string
     * 
     * @since 2.1
	 */
    public function get_callback( $domain = '', $metabox = '' ) {
        $class_name = $this->build_class_name( $domain, $metabox );
        if ( class_exists( $class_name ) ) {
            $object = new $class_name();
            return array( $object, 'print_metabox_content' );
		} else {
            throw new \Exception( sprintf( 'Class with name %s does not exist!', $class_name ));
            return '__return_false';
		}
    }
}
