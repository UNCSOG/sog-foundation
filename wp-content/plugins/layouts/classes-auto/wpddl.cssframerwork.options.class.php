<?php

use OTGS\Toolset\Common\Settings\BootstrapSetting;

class WPDD_Layouts_CSSFrameworkOptions{

	private $options_manager;
	private static $instance;
	const FRAMEWORK_OPTION = 'ddl_layouts_css_framework_options';
	const FRAMEWORK_SET = 'framework_setup';
	const DEFAULT_FRAMEWORK = 'bootstrap-3';
	const BOOTSTRAP_4_FRAMEWORK = 'bootstrap-4';
	private $supported_frameworks = null;

	private function __construct( )
	{
        
        
		$this->options_manager = new WPDDL_Options_Manager( self::FRAMEWORK_OPTION );

		$this->set_default_framework( );
		$this->set_up_frameworks();
		$this->set_up_features();

		//if( is_admin() && isset($_GET['page']) && $_GET['page'] == WPDDL_LAYOUTS_SETTINGS ){
		//
		//	add_action('admin_enqueue_scripts', array($this, 'settings_page_scripts'));
		//}
		
		add_action('switch_theme', array($this, 'reset_framework_set'));

		add_action( 'wp_ajax_save_layouts_css_framework_option',  array($this, 'save_layouts_css_framework_option_callback') );

		add_filter('ddl-get_current_framework_name', array(&$this, 'get_current_framework_name'), 10, 1 );
		add_filter('ddl-get_current_framework', array(&$this, 'get_current_framework'), 10, 1 );
	}

	
	public function settings_page_scripts()
	{
		global $wpddlayout;

		$wpddlayout->enqueue_scripts('ddl-cssframework-settings-script');
	}

	private function set_up_features()
	{
		$framework = $this->get_current_framework();

		switch( $framework )
		{
			case 'bootstrap-3':
				remove_ddl_support('fixed-layout');
				break;
			default:
				remove_ddl_support('fixed-layout');
				break;
		}
	}

	private function set_framework( $framework )
	{
		return $this->options_manager->update_options(  self::FRAMEWORK_OPTION, apply_filters('ddl-set_framework_option', $framework ), true );
	}

	private function set_default_framework(  )
	{
		if( !is_array( $this->options_manager->get_options() ) )
		{
			$this->set_framework( apply_filters('ddl-set_framework', self::DEFAULT_FRAMEWORK )  );
		}
	}

	public static function getInstance( )
	{
		if (!self::$instance)
		{
			self::$instance = new WPDD_Layouts_CSSFrameworkOptions();
		}

		return self::$instance;
	}

	private function set_up_frameworks()
	{
		$this->supported_frameworks = apply_filters('ddl-set_up_frameworks', array() );
		$this->supported_frameworks['bootstrap-2'] = (object) array('label' => 'Bootstrap 2');
		$this->supported_frameworks['bootstrap-3'] = (object) array('label' => 'Bootstrap 3');
		$this->supported_frameworks['bootstrap-4'] = (object) array('label' => 'Bootstrap 4');
	}

	public function get_supported_frameworks( )
	{
		return apply_filters( 'ddl-get_supported_frameworks', $this->supported_frameworks );
	}

	public function save_layouts_css_framework_option_callback()
	{
        if( user_can_edit_layouts() === false ){
            die( WPDD_Utils::ajax_caps_fail( __METHOD__ ) );
        }
		if( $_POST && wp_verify_nonce( $_POST['set-layout-css-framework-nonce'], 'set-layout-css-framework-nonce' ) )
		{
			$framework_saved = $this->set_framework( $_POST['css_framework'] );
			$current = $this->get_current_framework_name();
			$send = wp_json_encode( array( 'message' => array(
														   'text' => sprintf(__('The CSS framework has been set to %s. Please make sure that your theme supports %s.', 'ddl-layouts'), $current, $current),
														   'is_saved' => $framework_saved ) ) );
			$this->set_up_features();
			
			$this->options_manager->update_options( self::FRAMEWORK_SET, true, true );
			
		}
		else
		{
			$send = wp_json_encode( array( 'error' =>  __( sprintf('Nonce problem: apparently we do not know where the request comes from. %s', __METHOD__ ), 'ddl-layouts') ) );
		}

		die( $send );
	}

	public function print_frameworks_settings()
	{
			$data = array(
				'action' => 'save_layouts_css_framework_option',
				'set-layout-css-framework-nonce' => wp_create_nonce( 'set-layout-css-framework-nonce' )
			);
		?>

		<form id="layouts-css-framework-settings-form" class="js-layouts-css-framework-settings-form" data-object="<?php echo htmlspecialchars( wp_json_encode( $data ) ); ?>">
			<?php wp_nonce_field( 'ddl_layout_css_framework_settings_nonce', 'ddl_layout_css_framework_settings_nonce' );?>

			<?php foreach( $this->get_supported_frameworks( ) as $framework => $framework_data): ?>
				<p>
					<input type="radio" name="layouts-framework" id="layouts-framework-<?php echo $framework;?>" value="<?php echo $framework;?>" <?php if( ( $this->get_current_framework() == $framework ) ): ?>checked<?php endif;?> />
					<label for="layouts-framework-<?php echo $framework;?>"><?php echo $framework_data->label; ?></label>
				</p>
			<?php endforeach; ?>

			<div class="js-css-ajax-messages"></div>

			<p class="toolset-alert-error js-dir-messages css-dir-messages">

			</p>
			
			<?php 
				$options = $this->options_manager->get_options();
				if (!isset($options[self::FRAMEWORK_SET])) {
					?>
						<p id="ddl-framework-warning" class="toolset-alert-info toolset-alert">
							<strong><?php _e('Your theme has not specified a framework.', 'ddl-layouts'); ?></strong>
							<br />
							<?php _e( 'Please select and save the framework that your theme uses.', 'ddl-layouts'); ?>
						</p>
					<?php
				}
			?>

			<p class="buttons-wrap">
				<button class="button-primary js-save-layouts-css-framework-settings"><?php _e('Save CSS Framework', 'ddl-layouts');?></button>
			</p>
		</form>

		<?php

	}


	public function get_current_framework( /** @noinspection PhpUnusedParameterInspection */ $slug = null ) {
		$opts = $this->options_manager->get_options();
		$legacy_framework_setting = $opts[ self::FRAMEWORK_OPTION ];

		// If the framework setting has a default value (Bootstrap 3) but Toolset is actually using Bootstrap 4,
		// override it. Otherwise, keep the original value in order to not mess things up any further.
		$framework_setting = $legacy_framework_setting;
		if (
			self::DEFAULT_FRAMEWORK === $framework_setting
			&& Toolset_Settings::get_instance()->get_bootstrap_version_numeric() === BootstrapSetting::NUMERIC_BS4
		) {
			$framework_setting = self::BOOTSTRAP_4_FRAMEWORK;
		}

		return apply_filters( 'ddl-get_current_framework_option', $framework_setting );
	}
	
	public function get_current_framework_name( $name = null ) {
		$current = $this->get_current_framework();
		
		return apply_filters( 'ddl-get_current_framework_name', $this->supported_frameworks[$current]->label );
	}
	
	public function theme_set_framework ( $framework ) {
		if (array_key_exists($framework, $this->supported_frameworks)) {
			$this->set_framework ( $framework );
		}
	}
	
	function reset_framework_set () {
		$this->options_manager->delete_options( null, self::FRAMEWORK_SET);
		$this->set_default_framework();
		$this->options_manager->update_options( self::FRAMEWORK_SET, true, true );
	}
	
	public function get_options()
	{
		return $this->options_manager->get_options();
	}
}
