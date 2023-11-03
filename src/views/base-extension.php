<?php

// no direct call
if (!defined('YOURLS_ABSPATH')) die();

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
        $url = yourls_plugin_url(dirname(__DIR__, 2));

        echo <<<HEAD
            <link rel="stylesheet" href="$url/assets/css/style.css" type="text/css">
        HEAD;
    }

    /**
     * Extends the page footer.
     *
     * @return void
     */
    public static function action_html_footer(): void {
        $url = yourls_plugin_url(dirname(__DIR__, 2));
        
        echo <<<HEAD
            <script src="$url/assets/js/script.js" type="text/javascript" defer></script>
        HEAD;
    }
}


// register hooks
yourls_add_action('html_head', [UniwueUrlLimiterBaseExtensionView::class, 'action_html_head']);
yourls_add_action('html_footer', [UniwueUrlLimiterBaseExtensionView::class, 'action_html_footer']);
