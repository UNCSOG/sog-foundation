<?php

namespace ToolsetCommonEs\Library\ImageUpscale;

/*
Author: Bjørn Johansen
Author URI: https://bjornjohansen.no
License: GPL2

Just slightly changed it to not skip functionality if any other filter already ran on that hook.
If someone wants to overwrite this, it requires a higher priority on the filter callback apply.
*/

class Upscale {
	/*
	Based on: wp-includes/media.php:image_resize_dimensions()
	https://github.com/WordPress/WordPress/blob/3.6.1/wp-includes/media.php#L319
	*/
	public function image_resize_dimensions( $value, $orig_w, $orig_h, $dest_w, $dest_h, $crop ) {
		if ( $crop ) {
			// crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
			$aspect_ratio = $orig_w / $orig_h;
			$new_w = $dest_w;
			$new_h = $dest_h;

			if ( !$new_w ) {
				$new_w = intval( $new_h * $aspect_ratio );
			}

			if ( !$new_h ) {
				$new_h = intval( $new_w / $aspect_ratio );
			}

			$size_ratio = max( $new_w / $orig_w, $new_h / $orig_h );

			$crop_w = round( $new_w / $size_ratio );
			$crop_h = round( $new_h / $size_ratio );

			$s_x = floor( ( $orig_w - $crop_w ) / 2 );
			$s_y = floor( ( $orig_h - $crop_h ) / 2 );
		} else {
			// don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
			$crop_w = $orig_w;
			$crop_h = $orig_h;

			$s_x = 0;
			$s_y = 0;

			list( $new_w, $new_h ) = $this->constrain_dimensions( $orig_w, $orig_h, $dest_w, $dest_h );
		}

		// the return array matches the parameters to imagecopyresampled()
		// int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
		$return = array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );
		return $return;
	}

	/*
	Based on: wp-includes/media.php:wp_constrain_dimensions()
	https://github.com/WordPress/WordPress/blob/3.6.1/wp-includes/media.php#L259
	*/
	public function constrain_dimensions( $current_width, $current_height, $max_width=0, $max_height=0 ) {
		if ( !$max_width and !$max_height )
				return array( $current_width, $current_height );

			$width_ratio = $height_ratio = 1.0;
			$did_width = $did_height = false;

			if ( $max_width > 0 && $current_width > 0 ) {
				$width_ratio = $max_width / $current_width;
				$did_width = true;
			}

			if ( $max_height > 0 && $current_height > 0 ) {
				$height_ratio = $max_height / $current_height;
				$did_height = true;
			}

			// Calculate the larger/smaller ratios
			$smaller_ratio = min( $width_ratio, $height_ratio );
			$larger_ratio  = max( $width_ratio, $height_ratio );

			if ( intval( $current_width * $larger_ratio ) > $max_width || intval( $current_height * $larger_ratio ) > $max_height )
		 		// The larger ratio is too big. It would result in an overflow.
				$ratio = $smaller_ratio;
			else
				// The larger ratio fits, and is likely to be a more "snug" fit.
				$ratio = $larger_ratio;

			$w = intval( $current_width  * $ratio );
			$h = intval( $current_height * $ratio );

			// Sometimes, due to rounding, we'll end up with a result like this: 465x700 in a 177x177 box is 117x176... a pixel short
			// We also have issues with recursive calls resulting in an ever-changing result. Constraining to the result of a constraint should yield the original result.
			// Thus we look for dimensions that are one pixel shy of the max value and bump them up
			if ( $did_width && $w == $max_width - 1 )
				$w = $max_width; // Round it up
			if ( $did_height && $h == $max_height - 1 )
				$h = $max_height; // Round it up

			return array( $w, $h );
	}

}
