<?php

// define ajax constants
define('YOURLS_ADMIN', true);
define('YOURLS_AJAX', true);

// load dependencies
require_once(
	is_file(dirname(__DIR__, 5) . '/includes/load-yourls.php') ?
	dirname(__DIR__, 5) . '/includes/load-yourls.php' : // regular setup
	dirname(__DIR__, 4) . '/yourls/includes/load-yourls.php' // local development
);

require_once(dirname(__DIR__) . '/controllers/auth.php');
require_once(dirname(__DIR__) . '/controllers/enforcer.php');
require_once(dirname(__DIR__) . '/models/dict.php');
require_once(dirname(__DIR__) . '/views/settings.php');

// load auth workflow as this ajax page is loaded asynchroneous (i.e. not as a standard plugin page)
yourls_maybe_require_auth();


/**
 * This class handles our AJAX requests.
 */
class UniwueUrlLimiterAjaxView
{
	/**
	 * Runs this AJAX page.
	 *
	 * @return void
	 */
	public static function process(): void
	{
		// signal the browser that the response is uncacheable json
		yourls_content_type_header('application/json');
		yourls_no_cache_headers();
		yourls_no_frame_header();

		$action = $_REQUEST['action'] ?? '';

		// run the specified action
		switch ($action) {
			case UniwueUrlLimiterAuthController::ACTION_IS_ALLOWED:
				self::is_allowed();
				break;
			case UniwueUrlLimiterAuthController::ACTION_TOGGLE_BY_DOMAIN:
				self::validateNonce($action);
				self::toggle_by_domain();
				break;
		}
	}

	/**
	 * Validates the provided nonce for the requested action.
	 *
	 * @param string $action
	 * @return void
	 */
	private static function validateNonce(string $action): void
	{
		yourls_verify_nonce(
			UNIWUE_URL_LIMITER_PREFIX . $action,
			$_REQUEST['nonce'],
			false,
			json_encode(['status' => 'error', 'msg' => UniwueUrlLimiterDict::translate('error_nonce_invalid')])
		);
	}

	/**
	 * Returns whether the url of the requested keyword is allowed.
	 *
	 * @return void
	 */
	private static function is_allowed()
	{
		$keyword_infos = yourls_get_keyword_infos($_REQUEST['keyword'] ?? null);
		if (!$keyword_infos) {
			die();
		}

		$url = $keyword_infos['url'];
		$is_allowed = UniwueUrlLimiterEnforcerController::is_url_allowed($url);

		echo (json_encode([
			'allowed' => $is_allowed
		]));
	}

	/**
	 * Toggles the url of the provided keyword between allowed and blocked
	 * if it is matched exactly by its generated domain regex.
	 * 
	 * If the url is matched by a broader regex, the operation will be denied
	 * and the user asked to modify the allow and block lists manually,
	 * because of possible impact on other urls.
	 *
	 * @return void
	 */
	private static function toggle_by_domain()
	{
		if (!UniwueUrlLimiterAuthController::has_capability(UniwueUrlLimiterAuthController::ACTION_TOGGLE_BY_DOMAIN))
			die();

		$keyword_infos = yourls_get_keyword_infos($_REQUEST['keyword'] ?? null);
		if (!$keyword_infos) {
			die();
		}

		$url = $keyword_infos['url'];
		$domain = parse_url($url, PHP_URL_HOST);
		$url_allow_list = UniwueUrlLimiterOptions::get_instance()->get_url_allow_list();
		$url_block_list = UniwueUrlLimiterOptions::get_instance()->get_url_block_list();

		$success = true;
		$is_allowed = UniwueUrlLimiterEnforcerController::is_url_allowed($url);
		if ($is_allowed) {
			$success &= $url_allow_list->remove_domain($domain) && $url_block_list->add_domain($domain);
		} else {
			$success &= $url_allow_list->add_domain($domain) && $url_block_list->remove_domain($domain);
		}

		if ($success) {
			UniwueUrlLimiterOptions::get_instance()->save();
		}

		echo (json_encode(
			$success ?
				[
					'status' => 'success',
					'msg' => sprintf(
						UniwueUrlLimiterDict::translate('success_template_toggle_domain'),
						htmlspecialchars($domain),
						UniwueUrlLimiterDict::translate($is_allowed ? 'status_blocked' : 'status_allowed')
					)
				] :
				[
					'status' => 'error',
					'msg' => sprintf(
						UniwueUrlLimiterDict::translate('error_template_toggle_domain'),
						yourls_admin_url('plugins.php?page=' . UniwueUrlLimiterSettingsView::get_instance()->get_slug())
					)
				]
		));
	}
}


// run the ajax page
UniwueUrlLimiterAjaxView::process();
