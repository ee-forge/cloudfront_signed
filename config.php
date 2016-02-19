<?php

if ( ! defined('CLOUDFRONT_SIGNED_VERSION') )
{
    define('CLOUDFRONT_SIGNED_VERSION', '1.0.0');
    define('SOURCE_IP', '127.0.0.1');
}

$config['name'] = 'Cloudfront Signed';
$config['version'] = CLOUDFRONT_SIGNED_VERSION;

$config['private_key_filename'] = PATH_THIRD . 'cloudfront_signed/YOUR_PEM_FILE_LOCATION;
$config['key_pair_id'] = YOUR_KEY_PAIR_ID';