<?php

    // rename this file to apikey.php to use it.

    // set your API key here to test the translation live.
    define('TESTS_DEEPL_APIKEY', '');

    
    // define a proxy URI to enable the proxy-related tests.
    // URI in the format: protocol://username:password@1.2.3.4:10
    $proxyURI = '';
    
    // proxy URI variant with username and password
    /*
    $proxyURI = sprintf(
        '%s://%s:%s@%s:%s',
        'http',                // protocol (http/tcp)
        urlencode('username'), // username
        urlencode('password'), // password
        'proxy.server',        // host
        1234                   // port
    );
    */
    
    define('TESTS_PROXY_SERVER', $proxyURI);
    
