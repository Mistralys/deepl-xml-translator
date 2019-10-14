<?php
/**
 * Main bootstrapper used to set up the testsuites environment.
 * 
 * @package DeeplXML
 * @subpackage Tests
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */

    /**
     * The tests root folder (this file's location)
     * @var string
     */
    define('TESTS_ROOT', __DIR__ );

    $apikeyConf = realpath(TESTS_ROOT.'/apikey.php');
    
    if($apikeyConf !== false) 
    {
        require_once $apikeyConf;
    } 
    else 
    {
        define('TESTS_DEEPL_APIKEY', '');
    }
