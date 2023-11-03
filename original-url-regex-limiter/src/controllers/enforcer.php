<?php

// no direct call
if (!defined('YOURLS_ABSPATH')) die();

// load dependencies
require_once(dirname(__DIR__) . '/controllers/auth.php');
require_once(dirname(__DIR__) . '/models/dict.php');
require_once(dirname(__DIR__) . '/models/options.php');


/**
 * This class is responsible for deciding whether a URL is allowed
 * as well as enforcing the resulting restrictions for the user.
 */
class UniwueUrlLimiterEnforcerController
{
	// hook

	/**
	 * Returns the original return if the original URL is allowed or the user is entitled to bypass this restriction.
	 * The latter case considers both explicit eligibility by username as well as role-based eligibility.
	 * Otherwise, returns an error object informing the user about the denied decision.
	 *
	 * @param mixed $original_return
	 * @param string $original_url
	 * @return mixed
	 */
	public static function filter_link_upsert(mixed $original_return, string $original_url): mixed
	{
		return (self::can_user_bypass_by_name() ||
			self::can_user_bypass_by_role() ||
			self::is_url_allowed($original_url)
		) ?
			$original_return :
			[
				'status' => 'fail',
				'code' => 'error:disallowedurl',
				'message' => UniwueUrlLimiterDict::translate('error_status_blocked'),
				'errorCode' => '403'
			];
	}

	/**
	 * Returns whether the user is entitled to bypass the restriction
	 * by having its name explicitly stored in the user bypass list.
	 *
	 * @return boolean
	 */
	private static function can_user_bypass_by_name(): bool
	{
		$user = defined('YOURLS_USER') ? YOURLS_USER : null;
		$user_bypass_list = UniwueUrlLimiterOptions::get_instance()->get_user_bypass_list()->get();

		return in_array($user, $user_bypass_list);
	}

	/**
	 * Returns whether the user is entitled to bypass the restriction
	 * by having a role that is eligible for bypassing.
	 *
	 * @return boolean
	 */
	private static function can_user_bypass_by_role(): bool
	{
		return UniwueUrlLimiterAuthController::has_capability(UniwueUrlLimiterAuthController::ACTION_BYPASS_ENFORCEMENT);
	}

	/**
	 * Returns whether the given URL is allowed by URL Regex Limiter policies.
	 *
	 * @param string $original_url
	 * @return boolean
	 */
	public static function is_url_allowed(string $original_url): bool
	{
		return filter_var($original_url, FILTER_VALIDATE_URL) !== false &&
			UniwueUrlLimiterOptions::get_instance()->get_url_allow_list()->matches($original_url, true) &&
			!UniwueUrlLimiterOptions::get_instance()->get_url_block_list()->matches($original_url, false);
	}
}


// register the hooks for link upserts
yourls_add_filter('shunt_add_new_link', [UniwueUrlLimiterEnforcerController::class, 'filter_link_upsert']);
yourls_add_filter('shunt_edit_link', function ($original_return, $keyword, $original_url) {
	return UniwueUrlLimiterEnforcerController::filter_link_upsert($original_return, $original_url);
});
