<?php

/**
 * This class handles the authorization of this plugin's features.
 */
class UniwueUrlLimiterAuthController
{
    // capability identifiers
    private const PREFIX = 'uniwue-url-limiter-';
    public const ACTION_BYPASS_ENFORCEMENT = self::PREFIX . 'bypass-enforcement';
    public const ACTION_IS_ALLOWED = self::PREFIX . 'is-allowed';
    public const ACTION_TOGGLE_BY_DOMAIN = self::PREFIX . 'toggle-by-domain';
    public const ACTION_EDIT_RAW = self::PREFIX . 'edit-raw';


    /**
     * Returns whether the current user has the given capability.
     * This uses AuthMgrPlus if installed, otherwise grants the capabilities only to administrators.
     *
     * @param string $capability
     * @return boolean
     */
    public static function has_capability(string $capability): bool
    {
        return function_exists('amp_have_capability') ? amp_have_capability($capability) : yourls_is_admin();
    }
}


// registers a hook for adding this plugin's capabilities to the AuthMgrPlus plugin
yourls_add_filter('amp_action_capability_map', function (array $action_capability_map): array {
    $action_capability_map[UniwueUrlLimiterAuthController::ACTION_BYPASS_ENFORCEMENT] = UniwueUrlLimiterAuthController::ACTION_BYPASS_ENFORCEMENT;
    $action_capability_map[UniwueUrlLimiterAuthController::ACTION_IS_ALLOWED] = UniwueUrlLimiterAuthController::ACTION_IS_ALLOWED;
    $action_capability_map[UniwueUrlLimiterAuthController::ACTION_TOGGLE_BY_DOMAIN] = UniwueUrlLimiterAuthController::ACTION_TOGGLE_BY_DOMAIN;
    $action_capability_map[UniwueUrlLimiterAuthController::ACTION_EDIT_RAW] = UniwueUrlLimiterAuthController::ACTION_EDIT_RAW;

    return $action_capability_map;
});
yourls_add_filter('amp_button_capability_map', function (array $button_capability_map): array {
    $button_capability_map[UniwueUrlLimiterAuthController::ACTION_TOGGLE_BY_DOMAIN] = UniwueUrlLimiterAuthController::ACTION_TOGGLE_BY_DOMAIN;

    return $button_capability_map;
});
