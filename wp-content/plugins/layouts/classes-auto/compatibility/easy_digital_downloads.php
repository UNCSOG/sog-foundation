<?php
/**
 * This class will handle conflicts between Layouts and Easy Digital Downloads
 */


class Layouts_Compatibility_Easy_Digital_Downloads implements Toolset_Compatibility_Handler_Interface {


    public function initialize(){
        add_action( 'ddl_before_frontend_render_cell', array( $this, 'disable_content_filter_if_necessary' ), 10, 2 );
    }


    /**
     * Use filter to make sure that the content filter in cells is not applied if cell is rendering
     * post content and Easy digital downloads plugin is active in the same time
     *
     * This will ensure compatibility and correct rendering for Easy Digital Downloads output
     * @param $cell
     * @param $renderer
     *
     * @return bool (is filter applied)
     */
    public function disable_content_filter_if_necessary( $cell, $renderer ){

        $should_apply_content_filter = true;

        // stop executing if edd function doesn't exist
        if( ! function_exists('edd_get_download')){
            return $should_apply_content_filter;
        }

        global $post;
        $download_item = edd_get_download($post->ID);

        if ( $download_item === null ) {
            return $should_apply_content_filter;
        }

        if (
            (
                $cell->get_cell_type() === 'cell-content-template'
                && $cell->check_if_cell_renders_post_content() === false
            ) || (
                $cell->get_cell_type() === 'cell-text'
                && $this->has_wpvbody_tag( array( $cell ) ) === false
            )
        ) {
            add_filter( 'ddl_apply_the_content_filter_in_cells', '__return_false' );
            $should_apply_content_filter = false;
        }

        return $should_apply_content_filter;
    }

    /**
     * Check do we have wpvbody tag inside any cell from the list
     * @param $cells
     *
     * @return bool
     */
    public function has_wpvbody_tag( $cells ) {
        return  WPDD_Utils::visual_editor_cell_has_wpvbody_tag( $cells ) !== '';
    }



}