<?php


/**
 * Records the WP filter state.
 *
 * @since 1.9.1
 * @since 2.3.0	No longer needed as WordPress 4.7 manages properly nested instances of the same hooks.
 *				Check whether $wp_filter[ $key ] is an array or not to decide whether this is still needed or not.
 */

class WPV_WP_filter_state {

    private $current_index;
    private $tag;
    
    public function __construct( $tag ) {
        global $wp_filter;

        $this->tag = $tag;
        
        if ( 
			isset( $wp_filter[ $tag ] ) 
			&& is_array( $wp_filter[ $tag ] )
		) {
            $this->current_index = current( $wp_filter[ $tag ] );
        }
    }
    
    public function restore( ) {
        global $wp_filter;

        if ( 
			isset( $wp_filter[ $this->tag ] ) 
			&& is_array( $wp_filter[ $this->tag ] ) 
			&& $this->current_index 
		) {
            reset( $wp_filter[ $this->tag ] );
            while ( 
				$this->current_index 
				&& current( $wp_filter[ $this->tag ] ) 
				&& $this->current_index != current( $wp_filter[ $this->tag ] ) 
			) {
                next( $wp_filter[ $this->tag ] );
            }
        }
        
    }

}