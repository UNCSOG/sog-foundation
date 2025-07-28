<?php

namespace Wpae\Scheduling;


class LicensingManager
{
    private $options = false;

    public function checkLicense($licenseKey, $productName)
    {
		// Short-circuit check if recently validated.
		if ( get_transient( 'wpai_wpae_scheduling_license_verified' ) ) {
			return ['success' => true];
		}
        if ($productName !== false) {
            // data to send in our API request
            $api_params = array(
                'edd_action' => 'activate_license',
                'license' => \PMXE_Plugin::decode($licenseKey),
                'item_name' => urlencode($productName) // the name of our product in EDD
            );

            // Call the custom API.
            $response = wp_remote_get(
                esc_url_raw(
                    add_query_arg(
                        $api_params,
                        $this->getInfoApiUrl()
                )),
                array(
                    'timeout' => 15,
                    'sslverify' => true
                )
            );

            // make sure the response came back okay
            if (is_wp_error($response)){
                return ['success' => false];
            }

            $responseData = \json_decode($response['body'], true);

            if(is_null($responseData) || empty($responseData['success'])) {
                return $responseData ?? ['success' => false];
            } else {
				// Set transient so we only recheck successful licenses once every ten minutes.
	            set_transient('wpai_wpae_scheduling_license_verified', true, 600);
                return $responseData;
            }
        } else {
            return ['success' => false];
        }
    }

    public function getLicense()
    {
        $options = $this->getOptions();
        return $options['license'];
    }

    public function getInfoApiUrl()
    {
        $options = $this->getOptions();
        return $options['info_api_url_new'];
    }

    private function getOptions()
    {
        // Cache the options
        if(!$this->options) {
            $this->options = \PMXE_Plugin::getInstance()->getOption();
        }

        return $this->options;
    }
}