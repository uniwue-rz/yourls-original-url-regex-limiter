<?php

// no direct call
if( !defined( 'YOURLS_UNINSTALL_PLUGIN' ) ) die();

// load dependencies
require_once(__DIR__ . '/src/models/options.php');

// remove options
UniwueUrlLimiterOptions::get_instance()->destroy();
