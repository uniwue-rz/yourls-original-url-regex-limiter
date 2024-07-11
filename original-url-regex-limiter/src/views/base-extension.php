<?php

// no direct call
if (!defined('YOURLS_ABSPATH')) die();

// load dependencies
require_once(dirname(__DIR__) . '/models/options.php');

/**
 * This page extends all core pages with functionality provided by our plugin.
 */
class UniwueUrlLimiterBaseExtensionView
{
    // hooks

    /**
     * Extends the page header.
     *
     * @return void
     */
    public static function action_html_head(): void {
        $url = UniwueUrlLimiterOptions::get_plugin_url('assets/css/style.css');

        echo <<<HEAD
            <link rel="stylesheet" href="$url" type="text/css">
        HEAD;
    }

    /**
     * Extends the page footer.
     *
     * @return void
     */
    public static function action_html_footer(): void {
        $url = UniwueUrlLimiterOptions::get_plugin_url('assets/js/script.js');
        
        echo <<<HEAD
            <script src="$url" type="text/javascript" defer></script>
        HEAD;
    }
}


// register hooks
yourls_add_action('html_head', [UniwueUrlLimiterBaseExtensionView::class, 'action_html_head']);
yourls_add_action('html_footer', [UniwueUrlLimiterBaseExtensionView::class, 'action_html_footer']);
