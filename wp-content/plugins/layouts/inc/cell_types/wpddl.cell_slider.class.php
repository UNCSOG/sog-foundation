<?php
/*
 * Slider cell type.
 * Displays set of images using Bootstrap's carousel component
 *
 */



use OTGS\Toolset\Common\Settings\BootstrapSetting;

if ( ddl_has_feature( 'slider-cell' ) === false ) {
	return;
}

if ( ! class_exists( 'Layouts_cell_slider', false ) ) {
	class Layouts_cell_slider {

		// define cell name
		private $cell_type = 'slider-cell';


		function __construct() {
			add_action( 'init', array( &$this, 'register_slider_cell_init' ), 12 );
		}


		function register_slider_cell_init() {
			if ( function_exists( 'register_dd_layout_cell_type' ) ) {
				register_dd_layout_cell_type( $this->cell_type,
					array(
						'name' => __( 'Slider', 'ddl-layouts' ),
						'cell-image-url' => DDL_ICONS_SVG_REL_PATH . 'layouts-slider-cell.svg',
						'description' => __( 'Display the image slider, built using the Bootstrap Carousel component.', 'ddl-layouts' ),
						'category' => __( 'Fields, text and media', 'ddl-layouts' ),
						'button-text' => __( 'Assign Slider cell', 'ddl-layouts' ),
						'dialog-title-create' => __( 'Create new Slider cell', 'ddl-layouts' ),
						'dialog-title-edit' => __( 'Edit Slider cell', 'ddl-layouts' ),
						'dialog-template-callback' => array( &$this, 'slider_cell_dialog_template_callback' ),
						'cell-content-callback' => array( &$this, 'slider_cell_content_callback' ),
						'cell-template-callback' => array( __CLASS__, 'slider_cell_template_callback' ),
						'cell-class' => '',
						'has_settings' => true,
						'preview-image-url' => DDL_ICONS_PNG_REL_PATH . 'slider_expand-image.png',
						'translatable_fields' => array(
							'slider[slide_url]' => array( 'title' => 'Slide URL', 'type' => 'LINE' ),
							'slider[slide_title]' => array( 'title' => 'Slide title', 'type' => 'LINE' ),
							'slider[slide_text]' => array( 'title' => 'Slide description', 'type' => 'AREA' ),
						),
						'register-scripts' => array(
							array(
								'ddl-slider-cell-script',
								WPDDL_GUI_RELPATH . 'editor/js/ddl-slider-cell-script.js',
								array( 'jquery' ),
								WPDDL_VERSION,
								true,
							),
						),
					)
				);
			}
		}


		private function render_image_size_option( $label, $value, $tooltip, $id_suffix ) {
			$id = get_ddl_name_attr( 'image_size' ) . $id_suffix;
			?>

			<label class="checkbox checkbox-smaller float-none"
				for="<?php echo esc_attr( $id ); ?>"
			>
				<input type="radio"
					name="<?php the_ddl_name_attr( 'image_size' ); ?>"
					id="<?php echo esc_attr( $id ); ?>"
					value="<?php echo esc_attr( $value ); ?>">
				<?php echo esc_html( $label ) ?>
			</label>

			<?php
			if( $tooltip ) {
				?>
				<span>
					<i class="fa fa-question-circle question-mark-and-the-mysterians js-otgs-popover-tooltip"
						title="<?php echo esc_attr( $tooltip ) ?>"
					></i>
				</span>
				<?php
				}
			?>

			<div class="clear from-bot-4"></div>

			<?php
		}


		function slider_cell_dialog_template_callback() {
			ob_start();
			?>

			<div class="ddl-form pad-bot-0">
				<p>
					<label for="<?php the_ddl_name_attr( 'slider_height' ); ?>"
						class="ddl-manual-width-201"><?php _e( 'Slider height', 'ddl-layouts' ) ?>:</label>
					<span class="ddl-input-wrap"><input type="number"
							name="<?php the_ddl_name_attr( 'slider_height' ); ?>" value="300"
							class="ddl-input-half-width"><span
							class="ddl-measure-unit"><?php _e( 'px', 'ddl-layouts' ) ?></span></span>
				</p>
				<p>
					<label for="<?php the_ddl_name_attr( 'interval' ); ?>"
						class="ddl-manual-width-201"><?php _e( 'Interval', 'ddl-layouts' ) ?>:</label>
					<span class="ddl-input-wrap"><input type="number" name="<?php the_ddl_name_attr( 'interval' ); ?>"
							value="5000" class="ddl-input-half-width"><span
							class="ddl-measure-unit"><?php _e( 'ms', 'ddl-layouts' ) ?></span><i
							class="fa fa-question-circle question-mark-and-the-mysterians js-ddl-question-mark"
							data-tooltip-text="<?php _e( 'The amount of time to delay between automatically cycling an item, ms.', 'ddl-layouts' ) ?>"></i></span>
				</p>
				<fieldset>
					<legend><?php _e( 'Options', 'ddl-layouts' ) ?></legend>
					<div class="fields-group">
						<label class="checkbox" for="<?php the_ddl_name_attr( 'autoplay' ); ?>">
							<input type="checkbox" name="<?php the_ddl_name_attr( 'autoplay' ); ?>"
								id="<?php the_ddl_name_attr( 'autoplay' ); ?>" value="true">
							<?php _e( 'Autoplay', 'ddl-layouts' ) ?>
						</label>
						<label class="checkbox" for="<?php the_ddl_name_attr( 'pause' ); ?>">
							<input type="checkbox" name="<?php the_ddl_name_attr( 'pause' ); ?>"
								id="<?php the_ddl_name_attr( 'pause' ); ?>" value="pause">
							<?php _e( 'Pause on hover', 'ddl-layouts' ) ?>
						</label>
						<?php apply_filters( 'ddl-slider_cell_additional_options', '' ); ?>
					</div>
				</fieldset>
				<fieldset class="from-top-6">
					<legend><?php _e( 'Image size', 'ddl-layouts' ) ?></legend>
					<div class="fields-group">
						<?php
						$this->render_image_size_option(
							__( 'Contain (crop)', 'ddl-layouts' ),
							'',
							__( 'The background image will be scaled so that each side is as large as possible while not exceeding the length of the corresponding side of the container.', 'ddl-layouts' ),
							''
						);

						$cover_tooltip = __( 'The background image will be sized so that it is as small as possible while ensuring that both dimensions are greater than or equal to the corresponding size of the container.', 'ddl-layouts' );
						$this->render_image_size_option(
							__( 'Cover (top alignment)', 'ddl-layouts' ),
							'cover',
							$cover_tooltip . ' ' . __( 'The image will be aligned to the top of the container.', 'ddl-layouts' ),
							'_cover'
						);
						$this->render_image_size_option(
							__( 'Cover (middle alignment)', 'ddl-layouts' ),
							'cover_middle_alignment',
							$cover_tooltip . ' ' . __( 'The image will be aligned to the middle of the container.', 'ddl-layouts' ),
							'_cover_middle_alignment'
						);
						$this->render_image_size_option(
							__( 'Cover (container height)', 'ddl-layouts' ),
							'cover_container_height',
							__( 'The background image will be sized so that its height matches the height of the container.', 'ddl-layouts' ),
							'_cover_container_height'
						);

						?>
					</div>
				</fieldset>

				<h3><?php _e( 'Slides', 'ddl-layouts' ); ?></h3>

				<?php ddl_repeat_start( 'slider', __( 'Add another slide', 'ddl-layouts' ), 10 ); ?>

				<p class="js-ddl-media-field">
					<label for="<?php the_ddl_name_attr( 'slide_url' ); ?>"
						class="ddl-manual-width-201"><?php _e( 'Image', 'ddl-layouts' ) ?>:</label>
					<span class="ddl-input-wrap"><input type="text" class="js-ddl-media-url ddl-input-two-thirds-width"
							name="<?php the_ddl_name_attr( 'slide_url' ); ?>"/>
                        <span class="ddl-form-button-wrap ddl-input-two-thirds-width-span">
                                <button class="button js-ddl-add-media"
									data-uploader-title="<?php _e( 'Choose an image', 'ddl-layouts' ) ?>"
									data-uploader-button-text="Insert image URL"><?php _e( 'Choose an image', 'ddl-layouts' ) ?>
                                </button>
                        </span>
                    </span>
				</p>
				<p>
					<label
						for="<?php the_ddl_name_attr( 'slide_title' ); ?>"><?php _e( 'Caption title', 'ddl-layouts' ) ?>
						:</label>
					<input type="text" name="<?php the_ddl_name_attr( 'slide_title' ); ?>">
				</p>
				<p>
					<label
						for="<?php the_ddl_name_attr( 'slide_text' ); ?>"><?php _e( 'Caption description', 'ddl-layouts' ) ?>
						:</label>
					<textarea name="<?php the_ddl_name_attr( 'slide_text' ); ?>" rows="3"></textarea>
					<span
						class="desc"><?php _e( 'You can add HTML to the slide description.', 'ddl-layouts' ); ?></span>
				</p>

				<?php ddl_repeat_end( array( 'additional_wrap_class' => ' from-top-0 pad-top-0' ) ); ?>

			</div>
			<?php
			return ob_get_clean();
		}


		// Callback function for displaying the cell in the editor.
		public static function slider_cell_template_callback() {
			ob_start();
			?>
			<div class="cell-content">
				<p class="cell-name"><?php _e( 'Slider', 'ddl-layouts' ); ?></p>
				<div class="cell-preview">
					<div class="ddl-slider-preview">
						<img src="<?php echo DDL_ICONS_SVG_REL_PATH . 'slider.svg'; ?>" height="130px">
					</div>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}


		private function get_slide_setup_from_image_size( $current_slide_index ) {
			// Cover image to slide
			switch( get_ddl_field( 'image_size' ) ) {
				case 'cover':
					return [
						'slide_style' => 'background: url(' . get_ddl_sub_field( 'slide_url' ) . ') no-repeat; background-size:cover;',
					];
				case 'cover_middle_alignment':
					return [
						'slide_style' => 'background: url(' . get_ddl_sub_field( 'slide_url' ) . ') no-repeat; background-size:cover; background-position: 50% 50%;',
					];
				case 'cover_container_height':
					return [
						'slide_style' => 'background: url(' . get_ddl_sub_field( 'slide_url' ) . ') no-repeat; background-size:cover; background-size: 100% 100%;',
					];
				case '':
				default:
					return [
						'html' => sprintf(
							'<img src="%s" alt="%s" />',
							esc_attr( get_ddl_sub_field( 'slide_url' ) ),
							esc_attr( 'slide-' . $current_slide_index )
						),
					];
			}
		}


		// Callback function for display the cell in the front end.
		public function slider_cell_content_callback() {

			$unique_id = uniqid( '', false );
			$pause = '';

			if ( get_ddl_field( 'pause' ) ) {
				$pause = 'data-pause="hover"';
			} else {
				$pause = 'data-pause="false"';
			}

			ob_start();

			if ( get_ddl_field( 'autoplay' ) ) {
				$this->get_autoplay_script( $unique_id );
			}

			$carousel_item_main_class = (
				Toolset_Settings::get_instance()->get_bootstrap_version_numeric() === BootstrapSetting::NUMERIC_BS4
					? 'carousel-item'
					: 'item'
			);

			$this->get_inline_style( $unique_id, $carousel_item_main_class );

			$current_slide_index = 1;
			$carousel_container_class = apply_filters( 'ddl-carousel_container_class', 'carousel slide ddl-slider' );
			$carousel_tag = apply_filters( 'ddl-carousel_element_tag', 'div' );
			$carousels_tags = apply_filters( 'ddl-carousel_elements_tag', 'div' );
			$carousel_data_attr = apply_filters( 'ddl-carousel_element_data_attribute', '' );
			$carousel_data_ride = ( get_ddl_field( 'autoplay' )
				? apply_filters( 'ddl-carousel_element_data_ride_attribute', 'data-ride="carousel"' )
				: apply_filters( 'ddl-carousel_element_data_ride_attribute', '' )
			);
			$carousel_class = apply_filters( 'ddl-carousel_element_class_attribute', 'carousel-inner' );
			$carousel_items_classes = apply_filters( 'ddl-carousel_items_classes', '' ) . ' ' . $carousel_item_main_class;

			$carousel_active_class = apply_filters( 'ddl-carousel_active_element_class', 'active' );
			$container_additional_attributes = apply_filters( 'ddl-carousel_container_additional_attributes', '' );

			?>
		<div id="slider-<?php echo $unique_id ?>" class="<?php echo $carousel_container_class; ?>"
			<?php echo $pause ?>
			data-interval="<?php the_ddl_field( 'interval' ) ?>" <?php echo $container_additional_attributes; ?> <?php echo $carousel_data_ride; ?> >

			<?php $this->get_carousel_indicators( $unique_id ); ?>

			<<?php echo $carousel_tag; ?> class="<?php echo $carousel_class ?>" <?php echo $carousel_data_attr; ?>>

			<?php
			apply_filters( 'ddl-get_additional_carousel_top_controls_if', '' );
			while ( has_ddl_repeater( 'slider' ) ) {
				the_ddl_repeater( 'slider' );

				$slide_setup = $this->get_slide_setup_from_image_size( $current_slide_index );

				printf(
					'<%s class="%s" style="%s">',
					$carousels_tags,
					esc_attr( $carousel_items_classes . ' ' . ( get_ddl_repeater_index() === 0 ? ' '
							. $carousel_active_class : '' ) ),
					toolset_getarr( $slide_setup, 'slide_style', '' )
				);

				echo toolset_getarr( $slide_setup, 'html', '' );

				if ( get_ddl_sub_field( 'slide_title' ) || get_ddl_sub_field( 'slide_text' ) ) {
					?>
					<div
						class="<?php echo apply_filters( 'ddl-carousel_caption_class_attribute', 'carousel-caption' ); ?>">
						<h4><?php the_ddl_sub_field( 'slide_title' ); ?></h4>
						<p><?php the_ddl_sub_field( 'slide_text' ); ?></p>
					</div>
					<?php
				}
				printf( '</%s>', $carousels_tags );
				$current_slide_index ++;
			}

			printf( '</%s>', $carousel_tag );

			$this->get_carousel_indicators_bottom( $unique_id, $current_slide_index );

			$this->render_carousel_control( 'left', $unique_id );
			$this->render_carousel_control( 'right', $unique_id );

			?>
			</div>
			<?php

			return apply_filters( 'ddl-minify_html', ob_get_clean() );
		}


		private function render_carousel_control( $direction, $unique_id ) {
			$direction_to_command = [
				'left' => 'prev',
				'right' => 'next',
			];
			$command = $direction_to_command[ $direction ];

			$link_class = apply_filters(
				"ddl-carousel_control_{$direction}_class_attribute",
				Toolset_Settings::get_instance()->get_bootstrap_version_numeric() === BootstrapSetting::NUMERIC_BS4
					? "carousel-control-{$command}"
					: 'left carousel-control'
			);

			$span_class = apply_filters(
				"ddl-slider-cell-icon-{$command}",
				Toolset_Settings::get_instance()->get_bootstrap_version_numeric() === BootstrapSetting::NUMERIC_BS4
					? "carousel-control-{$command}-icon"
					: 'icon-prev'
			);

			$control_markup = sprintf(
				'<a class="%s" href="%s" data-slide="%s"><span class="%s"></span></a>',
				esc_attr( $link_class ),
				esc_attr( '#slider-' . $unique_id ),
				esc_attr( $command ),
				esc_attr( $span_class )
			);

			echo apply_filters( 'ddl-carousel_control_' . $direction, $control_markup, $unique_id );

		}


		function get_carousel_indicators( $unique_id ) {
			ob_start(); ?>
			<ol class="carousel-indicators">
				<?php while ( has_ddl_repeater( 'slider' ) ) : the_ddl_repeater( 'slider' ); ?>
					<li data-target="#slider-<?php echo $unique_id ?>"
						data-slide-to="<?php the_ddl_repeater_index(); ?>" <?php if ( get_ddl_repeater_index() == 0 ) {
						echo ' class="active"';
					} ?>></li>
				<?php endwhile;
				ddl_rewind_repeater( 'slider' );
				?>
			</ol>
			<?php
			echo apply_filters( 'ddl-get_carousel_indicators', ob_get_clean(), $unique_id );
		}


		function get_carousel_indicators_bottom( $unique_id, $count_slides ) {
			echo apply_filters( 'ddl-get_carousel_indicators_bottom', '', $unique_id, $count_slides );
		}


		function get_autoplay_script( $unique_id ) {
			ob_start() ?>

			<script type="text/javascript">
				//<![CDATA[
				jQuery( function( $ ) {
					var ddl_slider_id_string = "#slider-<?php echo $unique_id ?>";
					$( ddl_slider_id_string ).carousel( {
						interval: <?php the_ddl_field( 'interval' ) ?>
						<?php if ( ! get_ddl_field( 'pause' ) ) {
							echo ', pause: "false"';
						} ?>
					} );
				} );
				//]]>
			</script>
			<?php
			echo apply_filters( 'ddl-get_autoplay_script', ob_get_clean(), $unique_id );
		}


		function get_inline_style( $unique_id, $item_class = 'item' ) {
			ob_start(); ?>
			<style>
				#slider-<?php echo $unique_id ?> .carousel-inner > .<?php echo $item_class ?> {
					height: <?php the_ddl_field('slider_height') ?>px;
				}
			</style>
			<?php
			echo apply_filters( 'ddl-get_inline_style', ob_get_clean(), $unique_id );
		}

	}

	new Layouts_cell_slider();
}
