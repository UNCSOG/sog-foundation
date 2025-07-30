<?php
namespace Toolset\DynamicSources\Integrations\ThirdParty;

use Toolset\DynamicSources\Integrations\ThirdParty\XML\XMLConfigErrorListTableFactory;
use Toolset\DynamicSources\Utils\TemplateRenderer;
use Toolset\DynamicSources\Utils\TemplateRepository;

/**
 * Handles the overall orchestration of the updating of the configuration for automatic Dynamic Sources integration in
 * third-party plugins.
 */
class ConfigurationUpdater {
	/** @var ConfigurationCDNUpdater */
	private $cdn_config_updater;

	/** @var TemplateRepository */
	private $template_repository;

	/** @var TemplateRenderer */
	private $template_renderer;

	/** @var XMLConfigErrorListTableFactory */
	private $xml_config_error_list_table_factory;

	/**
	 * ConfigurationUpdater constructor.
	 *
	 * @param ConfigurationCDNUpdater $cdn_config_updater
	 * @param TemplateRenderer        $template_renderer
	 * @param TemplateRepository      $template_repository
	 * @param XMLConfigErrorListTableFactory $xml_config_error_list_table_factory
	 */
	public function __construct( ConfigurationCDNUpdater $cdn_config_updater, TemplateRenderer $template_renderer, TemplateRepository $template_repository, XMLConfigErrorListTableFactory $xml_config_error_list_table_factory ) {
		$this->cdn_config_updater = $cdn_config_updater;
		$this->template_renderer = $template_renderer;
		$this->template_repository = $template_repository;
		$this->xml_config_error_list_table_factory = $xml_config_error_list_table_factory;
	}

	/**
	 * Initializes the class by adding some hooks.
	 */
	public function initialize() {
		// Hook to update configuration using a CRON job.
		add_action( 'update_toolset_dynamic_sources_config_index', array( $this, 'update_toolset_dynamic_sources_configuration_action' ) );

		// Hook to update configuration manually from within the WordPress update screen.
		add_action( 'wp_ajax_update_toolset_dynamic_sources_config_index', array( $this, 'update_toolset_dynamic_sources_configuration_ajax' ) );

		// Hook to update configuration after the current theme has changed.
		add_action( 'after_switch_theme', array( $this, 'update_toolset_dynamic_sources_configuration_action' ) );

		// Hook to update configuration after the new plugin has been activated current theme has changed.
		add_action( 'activated_plugin', array( $this, 'update_toolset_dynamic_sources_configuration_action' ) );

		// Hook to add the update UI on the WordPress update screen.
		add_action( 'core_upgrade_preamble', array( $this, 'update_index_screen' ) );

		add_action( 'toolset/dynamic_sources/actions/third_party_integration/print_configuration_update_error_list_table', array( $this, 'print_config_error_list_table' ) );
	}

	/**
	 * Triggers the configuration update process from the remote location.
	 *
	 * @return bool
	 */
	public function update_toolset_dynamic_sources_configuration() {
		return $this->cdn_config_updater->run();
	}

	/**
	 * Triggers the configuration update process from the remote location.
	 */
	public function update_toolset_dynamic_sources_configuration_action() {
		$this->update_toolset_dynamic_sources_configuration();
	}

	/**
	 * Callback that prints the Toolset Dynamic Source Configuration updater errors.
	 */
	public function print_config_error_list_table() {
		$xml_config_error_list_table = $this->xml_config_error_list_table_factory->get();
		$xml_config_error_list_table->prepare_items();

		ob_start();
		$xml_config_error_list_table->display();
		$errors_list_table = ob_get_clean();

		return $this->template_renderer->render(
			$this->template_repository->get( TemplateRepository::CONFIGURATION_UPDATE_ERRORS_LIST_TABLE ),
			array( 'ds_errors_list_table' => $errors_list_table )
		);
	}

	/**
	 * Callback that updates the Toolset Dynamic Sources configuration for the manual AJAX call.
	 */
	public function update_toolset_dynamic_sources_configuration_ajax() {
		check_ajax_referer( 'toolset_dynamic_sources_theme_plugins_integration_nonce', 'security' );

		if ( $this->update_toolset_dynamic_sources_configuration() ) {
			echo esc_html( date_i18n( 'F j, Y H:i a' ) );
		}

		wp_die();
	}

	/**
	 * Prints the UI for the manual Toolset Dynamic Sources config update on the WordPress update screen.
	 */
	public function update_index_screen() {
		$context = array(
			'last_updated' => date_i18n( 'F j, Y H:i a', get_option( 'toolset_dynamic_sources_config_index_updated' ) ),
			'nonce' => wp_create_nonce( 'toolset_dynamic_sources_theme_plugins_integration_nonce' ),
		);

		$this->template_renderer->render(
			$this->template_repository->get( TemplateRepository::WORDPRESS_UPDATE_SCREEN_MANUAL_CONFIGURATION_UPDATE ),
			$context
		);
	}
}
