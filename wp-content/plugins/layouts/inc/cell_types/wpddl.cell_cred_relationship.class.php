<?php
/*
 * CRED cell type.
 * Displays CRED Post Form
 *
 */



if( ddl_has_feature('cred-relationship-cell') === false ){
	return;
}

if( !class_exists('CRED_Relationship_Cell') )
{
	class CRED_Relationship_Cell extends Layouts_toolset_based_cell {
	    const FORM_POST_TYPE = 'cred_rel_form';
	    const FORM_DOMAIN = 'relationships';
		protected $cell_type = 'cred-relationship-cell';

		public function __construct() {
			add_action( 'init', array( $this,'register_cred_cell_init' ), 12 );
			add_action('wp_ajax_ddl_get_option_for_cred_relationship_form', array( $this, 'get_option_for_cred_relationship_callback') );
			add_action('wp_ajax_ddl_delete_cred_relationship_forms', array( $this,'delete_cred_relationship_forms') );
			add_action('wp_ajax_ddl_create_cred_relationship_form', array( $this,'create_cred_relationship_form') );
		}

		public function get_option_for_cred_relationship_callback(){
			if( WPDD_Utils::user_not_admin() ){
				die( __("You don't have permission to perform this action!", 'ddl-layouts') );
			}
			if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
					'ddl_layout_cred_nonce')) {
				die('verification failed');
			}

			$result = array();

			// FIXME: add a method to get the relationship form from the $ID
			$form = $_POST['cred_id'];
			$post_title = '';

			$result['option'] = $this->ddl_cred_get_option_element($_POST['cred_id'],
				$post_title,
				$form->fields['form_settings']->form['type'],
				$form->fields['form_settings']->post['post_type']);

			print wp_json_encode($result);

			die();
        }

		public function get_options_for_cred_relationship(){

			$forms = apply_filters( 'cred_get_available_forms', null, self::FORM_DOMAIN );

			if( null === $forms ){
				return null;
            }

            return $forms;
        }

        public function print_options_for_cred_relationship(){

		    $forms = $this->get_options_for_cred_relationship();

		    if( !$forms ) return;

	        foreach( $forms as $form ){
		        print $this->ddl_cred_get_option_element(
			        $form->ID,
			        $form->post_title,
			        $form->post_name
		        );
	        }
        }

        public function delete_cred_relationship_forms(){
	        if( WPDD_Utils::user_not_admin() ){
		        die( __("You don't have permission to perform this action!", 'ddl-layouts') );
	        }
	        if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
			        'ddl_layout_cred_relationship_nonce')) {
		        die('verification failed');
	        }

	        $cred_forms = $_POST['forms'];

            $result = apply_filters( 'cred_delete_form', $cred_forms, 'relationships' );

	        wp_send_json_success( array( 'result' => $result ) );

	        die();
        }

        public function create_cred_relationship_form(){
	        if( WPDD_Utils::user_not_admin() ){
		        die( __("You don't have permission to perform this action!", 'ddl-layouts') );
	        }
	        if (!isset($_POST['wpnonce']) || !wp_verify_nonce($_POST['wpnonce'],
			        'ddl_layout_cred_relationship_nonce')) {
		        die('verification failed');
	        }

	            $form = apply_filters( 'cred_create_new_form', array(), $_POST['name'], self::FORM_DOMAIN, null );
		        if ( count( $form ) > 0 && isset( $form['status'] ) && $form['status'] === true ) {
			        $result['form_id'] = $form['result']->ID;
			        $result['option'] = $this->ddl_cred_get_option_element( $result['form_id'],
				        $form['result']->name,
				        $form['result']->slug
			        );

		        } elseif( count( $form ) > 0 && isset( $form['status'] ) && $form['status'] === false ) {
			        $result['error'] = $form['message'];
		        } else {
			        $result['error'] = __('Could not create the Post Form', 'ddl-layouts');
                }


	        print wp_json_encode($result);

	        die();
        }

		function ddl_cred_get_option_element( $id, $title, $name, $type = self::FORM_POST_TYPE ) {

			ob_start();
			?>
            <option value="<?php echo $id; ?>"
                    data-type="<?php echo 'create'; ?>"
                    data-post-type="<?php echo $type; ?>"
                    data-form-slug="<?php echo $name; ?>" ><?php echo $title; ?></option>
			<?php
			$ret = ob_get_clean();
			return $ret;
		}

		public function register_cred_cell_init(){
			if ( function_exists('register_dd_layout_cell_type') ) {
				register_dd_layout_cell_type ( $this->cell_type,
					array (
						'name'						=> __('Relationship Form', 'ddl-layouts'),
						'description'				=> __('Display the Relationship Form which allows users to create and edit Toolset Types Relationships.', 'ddl-layouts'),
						'category'					=> __('Forms', 'ddl-layouts'),
						'cell-image-url'					=> DDL_ICONS_SVG_REL_PATH.'cred-form-relationship.svg',
						'button-text'				=> __('Assign Relationship Form cell', 'ddl-layouts'),
						'dialog-title-create'		=> __('Create new Relationship Form cell', 'ddl-layouts'),
						'dialog-title-edit'			=> __('Edit CRED Post Relationship cell', 'ddl-layouts'),
						'dialog-template-callback'	=> array(&$this, 'cell_dialogs_callback'),
						'cell-content-callback'		=> array(&$this,'cell_content_callback'),
						'cell-template-callback'	=> array(&$this,'cell_template_callback'),
						'cell-class'				=> '',
						'has_settings' => false,
						'preview-image-url'			=>  DDL_ICONS_PNG_REL_PATH . 'CRED-relationship-form_expand-image.png',
						'register-scripts'		   => $this->cell_edit_script()
					)
				);
			}
		}

		function back_end_dialog()
		{
			ob_start();

			?>

			<div class="ddl-form cred-edit-cells-form">
				<?php if ( class_exists('CRED_Association_Form_Main') ): ?>

						<fieldset>
							<div class="fields-group">
								<label class="radio">
									<input type="radio" name="cred-relationship-action" class="js-ddl-cred-relationship-form-create"
									       value="new_form">
									<?php _e('Create a new Relationship Form', 'ddl-layouts'); ?>
								</label>
								<br class="js-ddl-newcred"/>
							</div>
						</fieldset>


						<fieldset class="js-ddl-newcred">
							<div class="fields-group ddl-form-indent">
								<button class="button button-primary js-ddl-create-cred-relationship-form">
									<?php _e('Create Cell', 'ddl-layouts'); ?>
								</button>
								<p class="js-cred-relationship-form-create-error toolset toolset-alert-error alert ddl-form-input-alert"
								   style="display:none">
								</p>
							</div>
						</fieldset>


					<fieldset class="ddl-dialog-fieldset">
						<div class="fields-group">
							<label class="radio">
								<input type="radio" name="cred-relationship-action" class="js-ddl-cred-relationship-form-existing"
								       value="existing">
								<?php _e('Use an existing Form', 'ddl-layouts'); ?>
							</label>
						</div>
					</fieldset>

					<span class="js-ddl-select-existing-cred ddl-select-existing-cred">

						<select name="<?php the_ddl_name_attr('ddl_layout_cred_relationship_id'); ?>"
						        class="ddl-cred-relationship-select js-ddl-cred-relationship-select
						        data-new="<?php _e('create', 'ddl-layout'); ?>"
						        data-edit="<?php _e('edit', 'ddl-layouts'); ?>">

							<option value=""><?php _e('--- Select form ---', 'ddl-layouts'); ?></option>

							<?php
							$this->print_options_for_cred_relationship();
							?>
						</select>
						<input type="hidden" name="<?php the_ddl_name_attr('cred-relationship-post-type'); ?>"
						       class="js-cred-relationship-post-type" value="<?php echo CRED_Association_Form_Main::ASSOCIATION_FORMS_POST_TYPE; ?>"/>
                         <input type="hidden" name="<?php the_ddl_name_attr('ddl_layout_cred_relationship_shortcode'); ?>" class="js-cred-relationship-form-shortcode" />
                        <div class="fields-group ddl-form-indent">
                            <button class="button button-primary js-ddl-edit-cred-relationship-link"
                                    data-close-cred-relationship-text="<?php _e('Continue', 'ddl-layouts'); ?>"
                                    data-save-cred-relationship-text="<?php _e('Save and Close this form and return to the layout', 'ddl-layouts'); ?>"
                                    data-discard-cred-relationship-text="<?php _e('Close this form and discard any changes', 'ddl-layouts'); ?>">
								<?php _e('Create Cell', 'ddl-layouts'); ?>
                            </button>
                        </div>
                       </span>
				<?php else: ?>
					<div class="toolset-alert toolset-alert-info js-ddl-cred-relationship-not-activated">
						<p>
							<i class="icon-cred-logo ont-color-orange ont-icon-24"></i>
							<?php _e('This cell requires the Toolset Forms plugin. Install and activate the Toolset Forms plugin and you will be able to create custom forms for creating and editing content.', 'ddl-layouts'); ?>
							<br>
							<br>

							&nbsp;&nbsp;
							<a class="fieldset-inputs"
							   href="https://toolset.com/home/cred/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts"
							   target="_blank">
								<?php _e('About Toolset Forms', 'ddl-layouts'); ?>
							</a>

						</p>
					</div>

				<?php endif; // end of full cred. ?>

				<div class="ddl-learn-more alignleft from-top-20">
					<?php ddl_add_help_link_to_dialog(WPDLL_CRED_CELL, __('Learn about the Relationship Form cell', 'ddl-layouts')); ?>
				</div>


			</div>

			<div id="ddl-cred-relationship-preview" style="display:none">
				- <p><strong><?php _e('This cell displays %FORM%', 'ddl-layouts'); ?></strong></p>

				<div class="ddl-cred-relationship-preview">
					<img src="<?php echo DDL_ICONS_SVG_REL_PATH.'cred-form-relationship.svg'; ?>" height="130px">
				</div>
			</div>

			<div id="ddl-cred-relationship-preview-cred-relationship-not-found" style="display:none">
				<div class="ddl-center-align"><?php _e('The Relationship Form was not found. It may have been deleted or Toolset Forms is not active.', 'ddl-layouts'); ?></div>
			</div>

			<?php

			echo wp_nonce_field('ddl_layout_cred_relationship_nonce', 'ddl_layout_cred_relationship_nonce', true, false);

			return ob_get_clean();
		}

		protected function cell_edit_script(){
			if( is_admin() ){
				return array(
					array( 'ddl-cred-relationship-cell-script', WPDDL_RELPATH . '/inc/gui/dialogs/js/cred-relationship-cell.js', array( 'jquery' ), WPDDL_VERSION, true ),
				);
			} else{
				return null;
			}
		}

		function cell_content_callback(){

			$is_private_layout = get_ddl_field('is_private_layout' );
			if( $is_private_layout ===true ){
				return  html_entity_decode( urldecode( get_ddl_field('ddl_layout_cred_relationship_shortcode' ) ), ENT_QUOTES );
			}

            return do_shortcode( html_entity_decode( urldecode( get_ddl_field('ddl_layout_cred_relationship_shortcode' ) ), ENT_QUOTES )  );
		}

		function cell_template_callback(){
			ob_start();
			?>
            <div class="cell-content">

                <p class="cell-name"><?php _e('Relationship Form', 'ddl-layouts'); ?></p>
                <div class="cell-preview">
                    <#
                    var preview = DDLayout.cred_relationship_cell.preview(content);
                    print( preview );
                    #>
                </div>
            </div>
			<?php
			return ob_get_clean();
		}
	}

	new CRED_Relationship_Cell();
}
