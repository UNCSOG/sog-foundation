<?php
// deny access
if ( ! defined( 'ABSPATH' ) )
	die( 'Security check' );
?>
<?php
$is_part_init = false;
foreach ( $modules as $module_name => $data ) {
	if ( isset( $data[ $element->section ] ) ) {
		foreach ( $data[ $element->section ] as $el ) {
			if ( (isset( $el[ 'id' ] )) && (isset( $element->id )) ) {
				if ( $el[ 'id' ] == $element->id ) {
					$is_part_init = true;
					break;
				}
			}
		}
	}
	if ( $is_part_init )
		break;
}
?>
<style type="text/css">
	body .module-manager-box-head {
		padding-left:12px !important;
	}
    body .module-manager-box-head [class^="icon-"] {padding-right:4px;}
    body .modman-inline-module-manager .modman_learn_about_modules a[href^="//"]:after,
	body .modman-inline-module-manager .modman_learn_about_modules a[href^="http://"]:after,
	body .modman-inline-module-manager .modman_learn_about_modules a[href^="https://"]:after {
  		content: url("<?php echo plugin_dir_url( __FILE__ )?>../../assets/images/outer_link.png");
  		margin: 0 0 0 5px;
	}
	body .modman-inline-module-manager .modman_learn_about_modules {
		margin-top: 20px;
	}
</style>

<p>
	<?php if ( $is_part_init ) { ?>
		<?php printf( __( 'Belongs to these modules:', 'module-manager' ), $element->title ); ?>
	<?php } else { ?>
		<?php

//printf(__('Want to reuse this in other websites?','module-manager'), $element->title);
//Views 1.3 revision

		$outputted_section = $element->section;
		if ( isset( $outputted_section ) ) {

			$sections_array = array( 'views' => 'View', 'view-templates' => 'Content Template' );

			if ( array_key_exists( $outputted_section, $sections_array ) ) {

				$section_outputted = $sections_array[ $outputted_section ];

				printf( __( 'Want to reuse this %s in other websites?', 'module-manager' ), $section_outputted );
			} else {

				printf( __( 'Want to reuse this in other websites?', 'module-manager' ), $element->title );
			}
		}
		?>
	<?php } ?>
</p>

<div class="modman-inline-module-manager">
    <div class="modman-inline-module-manager-inner">
		<?php
		$is_part = false;
		$canonical_sections=array('cred','views','view-templates','groups');
		foreach ( $modules as $module_name => $data ) {
			$is_part = false;

			if ( isset( $data[ $element->section ] ) ) {
				foreach ( $data[ $element->section ] as $el ) {

					//Add special treatment for checking post types to properly checked.

					if ( (isset( $el[ 'title' ] )) && (isset( $element->title )) ) {

						if ( ($element->section == 'types') || (($element->section == 'taxonomies')) ) {

							//Retrieve post type from id
							$post_types_module_id = $element->id;

							//Get post_type slug from $element
							$post_types_module_id_element = extract_post_type_from_modelements( $post_types_module_id );

							//Get post_type slug from $el
							$post_types_module_id_el = extract_post_type_from_modelements( $el[ 'id' ] );

							//Compare
							if ( $post_types_module_id_element == $post_types_module_id_el ) {
								$is_part = true;
								break;
							}
						} elseif ( in_array($element->section,$canonical_sections)) {

								if ( $el[ 'id' ] == $element->id ) {
									$is_part = true;
									break;
                                }

                        } elseif ( $el[ 'title' ] == $element->title ) {
							$is_part = true;
							break;
						}
					}
				}
			}
			?>
			<p>
				<label><input type="checkbox" value="<?php echo esc_attr( $module_name ); ?>" <?php if ( $is_part ) echo 'checked="checked"'; ?> /><span style="margin-left:10px"><?php echo $module_name; ?></span></label>
			</p>
		<?php } ?>
    </div>
	<?php wp_nonce_field( 'modman-add-to-module-action', 'modman-add-to-module-field' ); ?>
    <!--<span style="margin-right:10px;"><img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" class="modman-ajax-feedback" title="" alt="" /></span>-->
	<button class="button button-large modman-addnew-module-inline"><?php _e( 'Create a New Module', 'module-manager' ); ?></button>
		<?php if ( ! $is_part_init ) { ?>
		<p class="modman_learn_about_modules">
			<a href="https://toolset.com/documentation/legacy-features/export-import/using-toolset-module-manager/?utm_source=plugin&utm_medium=gui&utm_campaign=modulmanager" target="_blank"><?php _e( 'Learn about modules', 'module-manager' ); ?></a>
		</p>
	<?php } ?>
</div>
<?php

function extract_post_type_from_modelements( $post_types_module_id ) {

	if ( (isset( $post_types_module_id )) && ( ! (empty( $post_types_module_id ))) ) {

		//Check if ID contains integer
		$post_types_module_id_integer = intval( $post_types_module_id );

		for ( $i = strlen( $post_types_module_id ) - 1; $i; $i --  ) {
			if ( is_numeric( $post_types_module_id[ $i ] ) )
				break;
		}

		if ( $post_types_module_id_integer ) {
			$id_result_extracted = substr( $post_types_module_id, $i + 1 );
		} else {
			$id_result_extracted = substr( $post_types_module_id, $i );
		}

		$id_from_element = strtolower( $id_result_extracted );

		return $id_from_element;
	}
}
?>
<script type='text/javascript'>
	/* <![CDATA[ */
	(function($) {
		var mmboxinner = $('.modman-inline-module-manager');

		// add icon
		mmboxinner
				.closest('.postbox')
				.find('.hndle')
				/*.css({
				 'padding-left':'30px',
				 'background-repeat':'no-repeat',
				 'background-image':'url(<?php echo MODMAN_ASSETS_URL . '/images/module-icon-color_16X16.png' ?>)',
				 'background-position':'5px 50%'
				 });*/
				.addClass('module-manager-box-head');

		mmboxinner
				.closest('table')
				.find('thead th').first()
				/*.css({
				 'padding-left':'30px',
				 'background-repeat':'no-repeat',
				 'background-image':'url(<?php echo MODMAN_ASSETS_URL . '/images/module-icon-color_16X16.png' ?>)',
				 'background-position':'5px 50%'
				 });*/
				.addClass('module-manager-box-head');
		/*
		mmboxinner
				.css({
				 'padding-bottom':'30px'
		    	 });
		*/
        //$('.module-manager-box-head > span').before('<i class="icon-module ont-icon-22 ont-color-orange"></i>')

		function addNewMod($el)
		{
			var ajax_url = "<?php echo ModuleManager::route( '/Modules/addToModule' ); ?>";
			var modname = prompt('<?php echo esc_js( __( 'Create a New Module', 'module-manager' ) ); ?>', 'New Module');
			if (!modname)
				return false;
			var $container = $el.closest('.modman-inline-module-manager').find('.modman-inline-module-manager-inner');
			var data = {};
			if ($('.modman-inline-module-manager-inner input[type="checkbox"]').filter(function() {
				if (modname == $(this).val())
					return true;
				return false;
			}).length)
			{
				alert('<?php echo esc_js( __( 'Module already exists!', 'module-manager' ) ); ?>');
				return false;
			}
			data.mod_name = modname;
			data.elem = {id: "<?php echo $element->id; ?>", title: "<?php echo $element->title; ?>", section: "<?php echo $element->section; ?>", details: "<?php echo $element->description; ?>"};
			data = $.param(data) + '&mod_creat=1&modman-add-to-module-field=' + $('#modman-add-to-module-field').val();
//			$('.modman-ajax-feedback').css('visibility', 'visible');

			var $spinner = $('<span class="spinner">');
			$spinner.appendTo('.modman-inline-module-manager').css('display','inline-block');

			$.post(ajax_url, data, function(req) {
//				$('.modman-ajax-feedback').css('visibility', 'hidden');
				$spinner.remove();
				var html = '<p><label><input type="checkbox" value="' + modname + '" checked="checked" /><span style="margin-left:10px">' + modname + '</span></label></p>';
				$container.append(html);
			});
		}
		function toggleItem($el)
		{
			var ajax_url = "<?php echo ModuleManager::route( '/Modules/toggleItem' ); ?>";
			var data = {}, set = 2;
			data.mod_name = $el.val();
			data.elem = {id: "<?php echo $element->id; ?>", title: "<?php echo $element->title; ?>", section: "<?php echo $element->section; ?>"};
			if ($el.is(':checked'))
				set = 1;
			data = $.param(data) + '&mod_set=' + set + '&modman-add-to-module-field=' + $('#modman-add-to-module-field').val();
//			$('.modman-ajax-feedback').css('visibility', 'visible');
			var $spinner = $('<span class="spinner">');
			$spinner.appendTo('.modman-inline-module-manager').css('display','inline-block');

			$.post(ajax_url, data, function(req) {
//				$('.modman-ajax-feedback').css('visibility', 'hidden');
				$spinner.remove();
			});
		}
		$(function() {
			$('.modman-ajax-feedback').css('visibility', 'hidden');
			/*
			$('.modman-inline-module-manager').on('click', '.modman-addnew-module-inline', function() {
				addNewMod($(this));
			});
			*/
			$('.modman-addnew-module-inline').click(function(e){
			   e.preventDefault();
			   addNewMod($(this));
			});
			$('.modman-inline-module-manager-inner').on('change', 'input[type="checkbox"]', function() {
				toggleItem($(this));
			});
		});
	})(jQuery);
	/* ]]> */
</script>
