<?php

class WPDDL_WPML_TM_Dashboard {

	public function add_hooks() {
		add_filter( 'wpml_tm_dashboard_date', array( $this, 'tm_dashboard_date' ), 10, 3 );
	}

	public function tm_dashboard_date( $current_time, $id, $type ) {
		$date = $current_time;
		if ( 'package_layout' === $type ) {
			$post = get_post( $id );
			if(null === $post){
				$date = $current_time;
			} else {
				$date = strtotime( $post->post_date );
			}

		}
		return $date;
	}

}