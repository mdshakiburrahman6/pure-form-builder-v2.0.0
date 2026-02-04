<?php
if (!defined('ABSPATH')) exit;

class LA_LicenseAuth_API {

    private $name = 'PFB';
    private $ownerid = '8aRHggxpEz';
    private $sessionid;

    public function init() {

        $response = wp_remote_post(
            'https://licenseauth.cloud/api/1.2/',
            [
                'body' => [
                    'type'    => 'init',
                    'name'    => $this->name,
                    'ownerid' => $this->ownerid,
                ],
                'timeout' => 15,
                'sslverify' => false,
            ]
        );

        if (is_wp_error($response)) return $response;

        $json = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($json['success'])) {
            return new WP_Error('init_failed', $json['message'] ?? 'Init failed');
        }

        $this->sessionid = $json['sessionid'];
        return true;
    }

    private function hwid() {
        return hash('sha256', strtolower(parse_url(home_url(), PHP_URL_HOST)));
    }

    public function verify_license($key) {

        $response = wp_remote_post(
            'https://licenseauth.cloud/api/1.2/',
            [
                'body' => [
                    'type'      => 'license',
                    'key'       => $key,
                    'hwid'      => $this->hwid(),
                    'sessionid' => $this->sessionid,
                    'name'      => $this->name,
                    'ownerid'   => $this->ownerid,
                ],
                'timeout' => 15,
                'sslverify' => false,
            ]
        );

        if (is_wp_error($response)) return $response;

        $json = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($json['success'])) {
            return new WP_Error('license_invalid', $json['message'] ?? 'Invalid license');
        }

        return true;
    }
}
