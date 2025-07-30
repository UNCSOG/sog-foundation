<?php

/**
 * Helper class to render BS4 based layouts. Extends the BS3 class.
 *
 * @since 2.6.5
 */
class WPDD_BootstrapFour_render extends WPDD_BootstrapThree_render {

	/**
	 * Define the cell offset classname bsed on the effective offset.
	 *
	 * Note that BS4 follows the offset-{size}-{offset} schema.
	 *
	 * @return string
	 * @since 2.6.5
	 */
	public function set_cell_offset_class() {
        $offset_class = '';

        if ( $this->offset > 0 ) {
            if ( is_array( $this->column_prefix ) ) {
                foreach ( $this->column_prefix as $column_prefix ) {
                    $offset_class .= $this->get_cell_offset_class( $column_prefix, $this->offset );
                }
            } else if ( is_string( $this->column_prefix ) ) {
                $offset_class .= $this->get_cell_offset_class( $this->column_prefix, $this->offset );
            }
        }
        return $offset_class;
	}

	/**
	 * Calculate the cell offset class for a given size column prefix.
	 *
	 * @param string $column_prefix
	 * @param string $offset
	 * @return string
	 * @since 2.6.5
	 */
	private function get_cell_offset_class( $column_prefix, $offset ) {
		$column_prefix = str_replace( 'col', 'offset', $column_prefix );
		$o = apply_filters( 'ddl-get_column_offset', $offset, $column_prefix, $this );
		return sprintf( ' %s%s ',  $column_prefix, (string) $o );
	}

}
