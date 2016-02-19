Cloudfront Signed
=================

Protecting downloadable content can be challenging and time-consuming. &nbsp;If you use AWS Cloudfront to deliver downloadable content then signed URLs add a great layer of security. &nbsp;Signed URLs allow you to enforce policies on who can access the content. &nbsp;The plugin will build the appropriate policy statement based on the parameters passed in.

Installation
------------

Install like any other plugin by placing the cloudfront_signed folder in your /system/expressionengine/third_party/ folder (or wherever you have your add-ons installed).

Setup
-----

Before using the plugin you&#39;ll need to create a Cloudfront key/pair for the trusted signer. Upload the public key to a secure location (above webroot) on your server.

Update the config.php file in /cloudfront_signed/ to include the location of the newly uploaded public key file and a default key-pair-id.

Tag Usage
---------

`{exp:cloudfront_protect:url resource="{your_resource_url}" key_pair="your key-pair-id" expiration="" activation="" ip="yes"}`

Parameters
----------

+ **resource** (required) : the url to resource object
+ **key_pair** (optional) : specify a key-pair-id to override the default set in config
+ **expiration** (optional) : set an expiration in seconds from the current time. Default is 3600 (1 hour).
+ **activation** (optional) : set an activation time (not relative to the current time).
+ **ip** : if set to &quot;yes&quot; the Cloudfront policy includes the users IP
