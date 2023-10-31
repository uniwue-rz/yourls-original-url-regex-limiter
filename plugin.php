<?php
/*
Plugin Name: Original URL Regex Limiter
Plugin URI: https://github.com/uniwue-rz/yourls-original-url-regex-limiter
Description: This plugin extends the functionality of YOURLS by allowing entitled persons to restrict the original URLs with regular expressions. It simultaneously supports both allow- and block-listing as well as granting eligible users the capability to circumvent these limitations.
Version: 1.0
Author: University of Würzburg
Author URI: https://github.com/uniwue-rz
*/

// no direct call
if (!defined('YOURLS_ABSPATH')) die();

// global prefixes for actions, nonces, option keys, slugs and translation domain to make them unique
define('UNIWUE_URL_LIMITER_PREFIX', 'uniwue_url_limiter_');

// load dependencies
require_once(__DIR__ . '/src/controllers/auth.php');
require_once(__DIR__ . '/src/controllers/enforcer.php');
require_once(__DIR__ . '/src/views/index-extension.php');
require_once(__DIR__ . '/src/views/settings.php');

// load custom plugin pages
yourls_add_action('plugins_loaded', function () {
	UniwueUrlLimiterSettingsView::get_instance();
});

// inject custom scripts and styles
yourls_add_action("html_head", function () {
	$url = yourls_plugin_url(__DIR__);
	echo <<<HEAD
		<link rel="stylesheet" href="$url/assets/css/style.css" type="text/css">
	HEAD;
});
yourls_add_action("html_footer", function () {
	$url = yourls_plugin_url(__DIR__);
	echo <<<HEAD
		<script src="$url/assets/js/script.js" type="text/javascript" defer></script>
	HEAD;
});
