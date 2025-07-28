<?php

namespace Wpae\App\Controller;

use PMXE_Plugin;
use Wpae\App\Service\License\LicenseActivator;
use Wpae\Http\JsonResponse;
use Wpae\Scheduling\LicensingManager;

class SchedulingLicenseController
{
    /** @var  LicensingManager */
    private $licensingManager;

    /** @var LicenseActivator  */
    private $licensingActivator;

    private $slug = 'wp-all-export-pro';

    public function __construct()
    {
        $this->licensingManager = new LicensingManager();
        $this->licensingActivator = new LicenseActivator();
    }

    public function getSchedulingLicense()
    {
        $options = \PMXE_Plugin::getInstance()->getOption();

        if (empty($options['scheduling_license'])) {
            return false;
        }

        return new JsonResponse(
            array(
                'license' => $this->licensingManager->checkLicense($options['scheduling_license'], \PMXE_Plugin::getSchedulingName())
            )
        );
    }

    public function saveSchedulingLicenseAction()
    {
        $license = $_POST['license'];

		$licenseCheckResponse = $this->licensingManager->checkLicense($license, \PMXE_Plugin::getSchedulingName());
        if(!empty($licenseCheckResponse['success'])) {
            PMXE_Plugin::getInstance()->updateOption(array('scheduling_license' => $license));
            $post['license_status'] = $this->check_scheduling_license();
            $response = $this->activate_scheduling_licenses();

	        if (class_exists('PMXI_Plugin')) {
		        if (method_exists('PMXI_Plugin', 'getSchedulingName')) {

			        if (!empty($license)) {
				        $schedulingLicenseData = array();
				        $schedulingLicenseData['scheduling_license_status'] = $post['license_status'];
				        $schedulingLicenseData['scheduling_license'] = $license;

				        \PMXI_Plugin::getInstance()->updateOption($schedulingLicenseData);
			        }
		        }
	        }

	        return new JsonResponse($licenseCheckResponse);
        } else {
            return new JsonResponse($licenseCheckResponse);
        }
    }

    /*
    *
    * Activate licenses for main plugin and all premium addons
    *
    */
    protected function activate_scheduling_licenses()
    {
        global $wpdb;

        delete_transient(PMXE_Plugin::$cache_key);

        $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name = %s", $this->slug . '_' . PMXE_Plugin::$cache_key) );
        $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name = %s", $this->slug . '_timeout_' . PMXE_Plugin::$cache_key) );

        delete_site_transient('update_plugins');

        // retrieve the license from the database
        return $this->licensingActivator->activateLicense(PMXE_Plugin::getSchedulingName(),\Wpae\App\Service\License\LicenseActivator::CONTEXT_SCHEDULING);
    }

    public function check_scheduling_license()
    {
        $options = PMXE_Plugin::getInstance()->getOption();

        global $wpdb;

        delete_transient(PMXE_Plugin::$cache_key);

        $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name = %s", $this->slug . '_' . PMXE_Plugin::$cache_key) );
        $wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->options WHERE option_name = %s", $this->slug . '_timeout_' . PMXE_Plugin::$cache_key) );

        return $this->licensingActivator->checkLicense(PMXE_Plugin::getSchedulingName(), $options, LicenseActivator::CONTEXT_SCHEDULING);
    }
}