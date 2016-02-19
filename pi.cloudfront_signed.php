<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include( PATH_THIRD . 'cloudfront_signed/config.php' );

$plugin_info = array(
    'pi_name'			=> 'Cloudfront Protect',
    'pi_version'		=> CLOUDFRONT_SIGNED_VERSION,
    'pi_author'			=> 'Ron Hickson (EE-Forge)',
    'pi_author_url'		=> 'http://ee-forge.com',
    'pi_description'	=> 'Creating signed urls for Cloudfront - easy.',
    'pi_usage'			=> Cloudfront_signed::usage()
);

class Cloudfront_signed {

    public function __construct()
    {
        include( PATH_THIRD . 'cloudfront_signed/config.php' );
        $this->config = $config;
    }

    /**
     * Creates a signed-URL per Cloudfront requirements
     *
     * @return string  URL
     */
    public function url() {

        //Fetch params
        $expiration = time() + ee()->TMPL->fetch_param('expires', '3600');
        $activation = ee()->TMPL->fetch_param('activation', null);
        $ip = ee()->TMPL->fetch_param('ip', null);
        $key_pair = ee()->TMPL->fetch_param('key_pair_id', $this->config['key_pair_id']);
        $resource = ee()->TMPL->fetch_param('resource', '');

        // Create policy
        $policy = $this->create_policy_json($resource, $expiration, $activation, $ip);

        $signature = $this->sha1_sign($policy);
        $encoded_signature = $this->url_safe_base64_encode($signature);

        // Parse the resource should it already have a query string
        $parsed_resc = parse_url($resource);

        if (!isset($activation) && !isset($ip)) {
            $aws_query = array('Expires'=>$expiration, 'Signature'=>$encoded_signature, 'Key-Pair-Id'=>$key_pair);
        } else {
            $encoded_policy = $this->url_safe_base64_encode($policy);
            $aws_query = array('Policy'=>$encoded_policy, 'Signature'=>$encoded_signature, 'Key-Pair-Id'=>$key_pair);
        }

        if (isset($parsed_resc['query'])) {
            $http_query = array_merge($parsed_resc['query'], $aws_query);
        } else {
            $http_query = $aws_query;
        }
        $query_str = http_build_query($http_query);

        return $resource . '?' . $query_str;
    }


    /**
     * Create the json policy based on params
     *
     * @param $resc
     * @param $expiration
     * @param null $activation
     * @param null $ip
     * @return array
     */
    private function create_policy_json($resc, $expiration, $activation = null, $ip = null) {
        $policy = array(
            'Resource'  => $resc,
            'Condition' => array(
                'DateLessThan'  => array(
                    'AWS:EpochTime' => $expiration
                )
            )
        );
        if ($activation != null) {
            $policy['Condition']['DateGreaterThan']['AWS:EpochTime'] = $activation;
        }
        if ($ip == 'yes') {
            $policy['Condition']['IpAddress']['AWS:SourceIp'] = SOURCE_IP;
            //$policy['Condition']['IpAddress']['AWS:SourceIp'] = ee()->session->userdata('ip_address');
        }
        $stmnt = array(
            'Statement' => array($policy)
        );
        $json = json_encode($stmnt);
        return str_ireplace('\/','/',$json);
    }

    /**
     * Signs a policy with the private key set in the config file
     *
     * @param $policy
     * @return string
     */
    private function sha1_sign($policy) {
        $signature = '';

        // load the private key
        $fp = fopen($this->config['private_key_filename'], "r");
        $priv_key = fread($fp, 8192);
        fclose($fp);
        $pkeyid = openssl_get_privatekey($priv_key);

        // make signature
        openssl_sign($policy, $signature, $pkeyid);

        // free the key from memory
        openssl_free_key($pkeyid);

        return $signature;
    }

    /**
     * Safety first!  Clean and encode the signature and policy values
     *
     * @param $value
     * @return mixed
     */
    private function url_safe_base64_encode($value) {
        $encoded = base64_encode($value);
        // replace unsafe characters +, =  / with
        // the safe characters -, _ and ~
        return str_replace(
            array('+', '=', '/'),
            array('-', '_', '~'),
            $encoded);
    }

    /**
     * This function describes how the plugin is used.
     *
     * @return string
     */
    public static function usage()
    {
        ob_start();
        ?>
        Setup

        Before using the plugin you'll need to create a Cloudfront key/pair for the trusted signer. Upload the public key to a secure location (above webroot) on your server. Update the config.php file in /cloudfront_signed/ to include the location of the newly uploaded public key file and a default key-pair-id.

        Tag Usage

        {exp:cloudfront_protect:url resource="{your_resource_url}" key_pair="your key-pair-id" expiration="" activation="" ip="yes"}

        Parameters

        - resource (required) : the url to resource object
        - key_pair (optional) : specify a key-pair-id to override the default set in config
        - expiration (optional) : set an expiration in seconds from the current time.  Default is 3600 (1 hour).
        - activation (optional) : set an expiration time (not relative to the current time).
        - ip : if set to "yes" the Cloudfront policy includes the users IP

        <?php
        $buffer = ob_get_contents();
        ob_end_clean();
        return $buffer;
    }

}