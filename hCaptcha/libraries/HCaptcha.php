<?php

use function PHPUnit\Framework\isEmpty;

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter Recaptcha library
 *
 * @package CodeIgniter
 * @author  Artur
 * @link    https://github.com/ArdaiArtur
 */

class HCaptcha {

    /**
     * ci instance object
     *
     */
    private $CI;

    /**
     * secret key 
     *
     */
    private $secretKey;

    /**
     * url for hCaptcha
     *
     */
    private $url;

    /**
     * ssl default true so people wont forget 
     * turn it off for local whit setSSLEnabled(false);
     *
     */
    private $sslEnabled = true;

    /**
     * Constructor sets the secret key and verification URL from the config file. If these values are not
     * provided default values are used. User can  override them later.
     *
     * @param string $config
     */

    public function __construct() {
        $this->CI =& get_instance();
        // Load configuration
        $this->CI->load->config('hcaptcha',false,true);
        // Get the secret key and URL (using your env or config method)
        $this->secretKey = $this->CI->config->item('hcaptcha_secret') ?: 'your_default_secret';
        $this->url = $this->CI->config->item('hcaptcha_verify_url') ?: 'https://api.hcaptcha.com/siteverify';
        
    }


    // Setter for secret key
    public function setSecretKey($key) {
        $this->secretKey = $key;
    }

    // Getter for secret key
    public function getSecretKey() {
        return $this->secretKey;
    }

    // Setter for URL
    public function setUrl($url) {
        $this->url = $url;
    }

    // Getter for URL
    public function getUrl() {
        return $this->url;
    }

    //for local development turn it off
    public function setSSLEnabled($enabled) {
        $this->sslEnabled = (bool) $enabled;
    }

    /**
     * Send a verification request to the hCaptcha API.
     * If the request fails, it logs the cURL error and a message about the URL configuration.
     *
     * @param array $data An array containing the hCaptcha token,key and the user's IP address.
     * @return array The decoded JSON response from the hCaptcha API.
     */
    private function submitRequest($data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->sslEnabled);


        $result = curl_exec($ch);
        if ($result === false) {
            $error = curl_error($ch);
            log_message('error', 'cURL error: ' . $error . "\n".'url:'.$this->url);
            if(empty($error))
            log_message('error', 'No url set: use $this->hcaptcha->setUrl("url"); function');
        }
        curl_close($ch);

        return json_decode($result, true);
    }


    /**
     * Verify hCaptcha response.
     * Accepts the token and IP address, returns a boolean.
     */
    public function verify($response, $remoteIp = null): bool {
        if (!$response) {
            return false;
        }

        try {
        $data = [
            'secret' => $this->secretKey,
            'response' => $response
        ];
        
        if ($remoteIp !== null) {
            $data['remoteip'] = $remoteIp;
        }
        $resultData = $this->submitRequest($data);

        if (isset($resultData['success']) && $resultData['success']) {
            return true;
        }

        log_message('error', 'hCaptcha verification failed: ' . json_encode($resultData));
        return false;
        } catch (Exception $ex) {
            log_message('error', 'Exception during hCaptcha verification: ' . $ex->getMessage());
            return false;
        }
    }
}
