<?php

class WPDDL_views_support
{

	public function __construct() {
		add_action( 'init', array($this, 'init'));
		add_action('admin_notices', array($this, 'admin_notice'));
		add_action('wp_ajax_dll_dismiss_views_notice', array($this, 'dismiss_notice'));
	}

	public function init() {
	}

	public function admin_notice() {
		global $current_user, $pagenow ;

		if ( defined( 'WPV_VERSION' ) && !version_compare(WPV_VERSION, '1.6.1', '>')) {
			$page = isset($_GET['page']) ? $_GET['page'] : '';

			if ($pagenow == 'plugins.php' ||
				($pagenow == 'admin.php' && ($page == WPDDL_LAYOUTS_POST_TYPE || $page == 'dd_layouts_edit'))) {
				?>
				<div class="update-nag">
					<p>
						<i class="icon-views-logo ont-color-orange ont-icon-24"></i>&nbsp;<strong><span style="vertical-align: -6px"><?php _e('Layouts requires version 1.6.2 or higher of the Views plugin.'); ?></span></strong>
					</p>
					<p>
						&nbsp;&nbsp;
						<a class="fieldset-inputs" href="https://toolset.com/home/views-create-elegant-displays-for-your-content/?utm_source=plugin&utm_medium=gui&utm_campaign=layouts" target="_blank">
							<?php _e('About Views', 'ddl-layouts');?>
						</a>
					</p>
				</div>
				<?php
			}
		}
	}

	public function dismiss_notice () {
		global $current_user;

		$user_id = $current_user->ID;
		add_user_meta($user_id, 'views_required_ignore_notice', 'true', true);
	}

}

global $wpddl_views_support;
$wpddl_views_support = new WPDDL_views_support();
