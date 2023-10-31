<?php

// no direct call
if (!defined('YOURLS_ABSPATH')) die();


/**
 * This class manages our translations.
 * 
 * TODO: convert this to an enum once the minimum php version is changed to 8.1
 */
class UniwueUrlLimiterDict
{
    // singleton for caching
    private static ?self $instance = null;

    // plugin translation domain
    private string $domain;
    private array $translations;


    // construction and persistence

    /**
     * Constructs the translations singleton.
     */
    private function __construct()
    {
        $this->domain = substr(UNIWUE_URL_LIMITER_PREFIX, 0, -1);
        yourls_load_custom_textdomain($this->domain, dirname(__DIR__, 2) . '/translations' );
        $this->translations = [
            /**
             * If you make changes here:
             * - for key changes: update the keys used in this plugin
             * - for value changes: update the translation files
             */
            'action_toggle_domain' => yourls__('Toggle URL Limiter status by corresponding domain regex', $this->domain),
            'error' => yourls__(
                'Something went wrong! Please try again later or contact the administrator if the problem persists.',
                $this->domain
            ),
            'error_nonce_invalid' => yourls__('Nonce expired or invalid. Please refresh the page.', $this->domain),
            'error_regex_internal' => yourls__('There is an internal PCRE error. Please check the regex syntax.', $this->domain),
            'error_regex_limit_backtrack' => yourls__('The backtrack limit is exhausted.', $this->domain),
            'error_regex_limit_jit' => yourls__('The JIT stack limit is exhausted.', $this->domain),
            'error_regex_limit_recursion' => yourls__('The recursion limit is exhausted.', $this->domain),
            'error_regex_malformed' => yourls__('The UTF-8 data is malformed.', $this->domain),
            'error_regex_offset' => yourls__('The offset doesn\'t correspond to the begin of a valid UTF-8 code point.', $this->domain),
            'error_status_blocked' => yourls__(
                'This URL is blocked by URL Limiter policies. This means that you cannot create and edit similar URLs. ' .
                    'Please contact the administrator if you think this is a mistake and want this URL to be allowed.',
                $this->domain
            ),
            'error_template_regex' => yourls__('Error on line #%d: %s', $this->domain),
            'error_template_toggle_domain' => yourls__(
                'URL Limiter policies could not be updated due to the domain being involved in a more complex regex. ' .
                    '<strong>Please update it manually at the <a href="%s">settings page</a></strong>.',
                $this->domain
            ),
            'error_translation_missing' => yourls__('- translation not found -', $this->domain),
            'page_settings' => yourls__('URL Limiter Settings', $this->domain),
            'settings_field_url_allow_list' => yourls__('URL Allow List', $this->domain),
            'settings_field_url_block_list' => yourls__('URL Block List', $this->domain),
            'settings_field_user_bypass_list' => yourls__('User Bypass List', $this->domain),
            'settings_notice' => yourls__('Use websites like %s to verify and validate your regexes.', $this->domain),
            'settings_notice_list' => yourls__('One item per line.', $this->domain),
            'settings_notice_list_url' => yourls__(
                'Items are interpreted as regular expressions and matched against the original URL on creation and updates.',
                $this->domain
            ),
            'settings_notice_list_url_empty_allow' => yourls__('Leaving this field empty means that all original URLs are allowed.', $this->domain),
            'settings_notice_list_url_may_not_match' => yourls__(
                'The original URL <strong>may not match any item</strong> of this list.',
                $this->domain
            ),
            'settings_notice_list_url_must_match' => yourls__('The original URL <strong>must match any item</strong> of this list.', $this->domain),
            'settings_notice_list_user' => yourls__('Items are interpreted as normal strings and matched against the user\'s name.', $this->domain),
            'settings_notice_list_user_bypass' => yourls__('Users in this list bypass all restrictions enforced by this plugin.', $this->domain),
            'settings_submit' => yourls__('Submit', $this->domain),
            'success_status_allowed' => yourls__('This URL is allowed by URL Limiter policies.', $this->domain),
            'success_template_toggle_domain' => yourls__('The domain <strong>%s</strong> is now <strong>%s</strong>.', $this->domain),
            'status' => yourls__('URL Status', $this->domain),
            'status_allowed' => yourls__('allowed', $this->domain),
            'status_blocked' => yourls__('blocked', $this->domain),
            'status_all' => yourls__('processed', $this->domain),
            'status_undecided' => yourls__('undecided', $this->domain),
            'status_filter_template' => yourls__('Show links that are %s by URL Limiter policies.', $this->domain)
        ];
    }

    /**
     * Returns the singleton instance. 
     *
     * @return self
     */
    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    // getters

    /**
     * Returns the translation for the given key,
     * otherwise a notice that the translation is not found.
     *
     * @param string $key
     * @return string
     */
    private function get_translation(string $key): string
    {
        return $this->translations[$key] ?? $this->translations['error_translation_missing'];
    }


    // public translation method

    /**
     * Returns the translation for the given key.
     *
     * @param string $key
     * @return string
     */
    public static function translate(string $key): string
    {
        return self::get_instance()->get_translation($key);
    }
}
