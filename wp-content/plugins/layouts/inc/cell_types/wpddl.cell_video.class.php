<?php
/*
 * YouTube video cell type.
 * Displays YouTube video
 *
 */



if( ddl_has_feature('video-cell') === false ){
	return;
}

if (!class_exists('Layouts_cell_video', false )) {
    class Layouts_cell_video{

        // define cell name
        private $cell_type = 'video-cell';

        function __construct() {
            add_action( 'init', array(&$this,'register_video_cell_init' ), 12);
        }

        function register_video_cell_init() {
            if (function_exists('register_dd_layout_cell_type')) {
                register_dd_layout_cell_type($this->cell_type,
                    array(
                        'name' => __('YouTube', 'ddl-layouts'),
                        'description' => __('Display a video from YouTube.', 'ddl-layouts'),
                        'category' => __('Fields, text and media', 'ddl-layouts'),
                        'cell-image-url' => DDL_ICONS_SVG_REL_PATH . 'layouts-video-cell.svg',
                        'button-text' => __('Assign YouTube cell', 'ddl-layouts'),
                        'dialog-title-create' => __('Create new YouTube cell', 'ddl-layouts'),
                        'dialog-title-edit' => __('Edit YouTube cell', 'ddl-layouts'),
                        'dialog-template-callback' => array(&$this,'video_cell_dialog_template_callback'),
                        'cell-content-callback' => array(&$this,'video_cell_content_callback'),
                        'cell-template-callback' => array(&$this,'video_cell_template_callback'),
                        'has_settings' => true,
                        'cell-class' => '',
                        'preview-image-url' => DDL_ICONS_PNG_REL_PATH . 'youtube_expand-image.png',
                        'register-scripts' => array(
                            array('ddl-video-cell-script', WPDDL_GUI_RELPATH . 'editor/js/ddl-video-cell-script.js', array('jquery'), WPDDL_VERSION, true),
                        )
                    )
                );
            }
        }

        function video_cell_dialog_template_callback() {
            ob_start();
            ?>

            <div class="ddl-form ddl-youtube-video-cell">
                <p>
                    <label for="<?php the_ddl_name_attr('video_url'); ?>"><?php _e( 'Video URL', 'ddl-layouts' ) ?>:</label>
                    <input type="text" name="<?php the_ddl_name_attr('video_url'); ?>" class="ddl-layout-video-input">
                    <span class="desc"><?php _e( 'eg. https://www.youtube.com/watch?v=nm706dGXgjs', 'ddl-layouts' ) ?></span>
                    <div class="js-video-message" id="js-video-message"></div>
                </p>
                <p>
                    <label for="<?php the_ddl_name_attr('video_height'); ?>"><?php _e( 'Player height', 'ddl-layouts' ) ?>:</label>
                    <input type="number" name="<?php the_ddl_name_attr('video_height'); ?>" class="ddl-layout-video-input" value="300">
		    <span class="px_box">px</span>
                    <span class="desc"><?php _e( 'Fixed height of the video player. Width adjusts to cell\'s size', 'ddl-layouts' ) ?></span>
                </p>
            </div>

            <?php
            return ob_get_clean();
	}


	// Callback function for displaying the cell in the editor.
	function video_cell_template_callback() {
            ob_start();
            ?>
            <div class="cell-content">
                <p class="cell-name"><?php _e('YouTube', 'ddl-layouts'); ?></p>
                <div class="cell-preview">
                    <div class="ddl-video-preview">
                        <img src="<?php echo DDL_ICONS_SVG_REL_PATH . 'youtube-video.svg'; ?>" height="130px">
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
	}


	// Callback function for display the cell in the front end.
	function video_cell_content_callback() {

            global $wpddlayout;
            $wpddlayout->enqueue_scripts('ddl-video-cell-front-end');

            $video_url = get_ddl_field('video_url');
            $filter    = '#^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com(?:/embed/|/v/|/watch\?v=|/watch\?.+&v=))([\w-]{11})(?:.+)?$#x';

            preg_match( $filter, $video_url, $matches );

            if( isset($matches[1]) ) {
                $video_id  = $matches[1];
            } else {
                $video_id = null;
            }

            ob_start();
            ?>

            <style>
                .video-container iframe {
                        width: 100%;
                }
            </style>

            <?php if( $video_id === null ):?>

            <div class="video-container">
                <?php
                echo WPDDL_Messages::display_message(WPDDL_Messages::$message_warning, sprintf(__('The URL %s is not a valid YouTube URL.', 'ddl-layouts'), $video_url ) );
                ;?>
            </div>

			<?php else:
				$iframe_title = sprintf( __('Video cell: %s.', 'ddl-layouts'), $video_id );
				?>
                <div class="video-container">
                    <iframe title="<?php echo esc_attr( $iframe_title ); ?>" height="<?php echo esc_attr( get_ddl_field('video_height') ); ?>" src="//www.youtube.com/embed/<?php echo esc_attr( $video_id ); ?>" frameborder="0" allowfullscreen></iframe>
                </div>
            <?php endif; ?>

            <?php
            return ob_get_clean();
	}


    }

    new Layouts_cell_video();
}
