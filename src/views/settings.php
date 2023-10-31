<?php

// no direct call
if (!defined('YOURLS_ABSPATH')) die();

// load dependencies
require_once(dirname(__DIR__) . '/controllers/auth.php');
require_once(dirname(__DIR__) . '/models/dict.php');
require_once(dirname(__DIR__) . '/models/options.php');


/**
 * This class controls the plugin's setting page in the admin area.
 */
class UniwueUrlLimiterSettingsView
{
	// page name used in slug
	private const NAME = 'settings';

	// singleton for caching
	private static ?self $instance = null;


	// construction

	/**
	 * Constructs the settings page singleton and registers it.
	 */
	private function __construct()
	{
		yourls_register_plugin_page(
			UNIWUE_URL_LIMITER_PREFIX . self::NAME,
			UniwueUrlLimiterDict::translate('page_settings'),
			[$this, 'process']
		);
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


	// getter

	/**
	 * Returns the page's slug.
	 *
	 * @return string
	 */
	public function get_slug(): string
	{
		return UNIWUE_URL_LIMITER_PREFIX . self::NAME;
	}


	// page workflow

	/**
	 * Controls the workflow of the page and is called from yourls core.
	 *
	 * @return void
	 */
	public function process(): void
	{
		if (!UniwueUrlLimiterAuthController::has_capability(UniwueUrlLimiterAuthController::ACTION_EDIT_RAW)) {
			die();
		}

		$errors = $this->validate();
		$success = empty(array_filter($errors));

		if (isset($_REQUEST['submit']) && $success) {
			UniwueUrlLimiterOptions::get_instance()->save();
		}

		$this->render($success, $errors);
	}

	/**
	 * Renders the settings page that is displayed to the user.
	 *
	 * @param array $errors
	 * @return void
	 */
	private function render(bool $success, array $errors): void
	{
		// (re)load options to have the latest option values
		$options = UniwueUrlLimiterOptions::get_instance()->reload(
			$success ? UniwueUrlLimiterOptions::RELOAD_MODE_DB : UniwueUrlLimiterOptions::RELOAD_MODE_REQUEST
		);

		// output page
		echo("
			<h2>". UniwueUrlLimiterDict::translate('page_settings') . "</h2>
			<p>
				<strong>". sprintf(
					UniwueUrlLimiterDict::translate('settings_notice'),
					'<a href="https://regex101.com/" target="_blank">regex101.com</a>'
				) . "</strong>
			</p>
			<form method='post'>
				<input type='hidden' name='nonce' value='" . yourls_create_nonce($this->get_slug()) . "' />
				<div class='uniwue-url-limiter-form-item'>
					<h3>
						<label for='" . UniwueUrlLimiterOptions::URL_ALLOW_LIST . "'>
							<strong>" . UniwueUrlLimiterDict::translate('settings_field_url_allow_list') . "</strong>
						</label>
					</h3>
					<ul>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list') . "</li>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list_url') . "</li>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list_url_empty_allow') . "</li>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list_url_must_match') . "</li>
					</ul>
					<textarea class='uniwue-url-limiter-form-field-text' name='" . UniwueUrlLimiterOptions::URL_ALLOW_LIST . "'
						spellcheck='false' rows='20' cols='100'>" . $options->get_url_allow_list()->to_form_string() . "</textarea>
					<p class='uniwue-url-limiter-form-error'>$errors[0]</p>
				</div>
				<div class='uniwue-url-limiter-form-item'>
					<h3>
						<label for='" . UniwueUrlLimiterOptions::URL_BLOCK_LIST . "'>
							<strong>" . UniwueUrlLimiterDict::translate('settings_field_url_block_list') . "</strong>
						</label>
					</h3>
					<ul>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list') . "</li>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list_url') . "</li>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list_url_may_not_match') . "</li>
					</ul>
					<textarea class='uniwue-url-limiter-form-field-text' name='" . UniwueUrlLimiterOptions::URL_BLOCK_LIST . "'
						spellcheck='false' rows='20' cols='100'>" . $options->get_url_block_list()->to_form_string() . "</textarea>
					<p class='uniwue-url-limiter-form-error'>$errors[1]</p>
				</div>
				<div class='uniwue-url-limiter-form-item'>
					<h3>
						<label for='" . UniwueUrlLimiterOptions::USER_BYPASS_LIST . "'>
							<strong>" . UniwueUrlLimiterDict::translate('settings_field_user_bypass_list') . "</strong>
						</label>
					</h3>
					<ul>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list') . "</li>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list_user') . "</li>
						<li>" . UniwueUrlLimiterDict::translate('settings_notice_list_user_bypass') . "</li>
					</ul>
					<textarea class='uniwue-url-limiter-form-field-text' name='" . UniwueUrlLimiterOptions::USER_BYPASS_LIST . "'
						spellcheck='false' rows='5' cols='25'>" . $options->get_user_bypass_list()->to_form_string() . "</textarea>
				</div>

				<div class='uniwue-url-limiter-form-item'>
					<input type='submit' name='submit' value='" . UniwueUrlLimiterDict::translate('settings_submit') . "' />
				</div>
			</form>
		");
	}

	/**
	 * Returns the form field errors as a list in the fields' order.
	 *
	 * @return array [0 => allow list error, 1 => block list error]
	 */
	private function validate(): array
	{
		if (!isset($_REQUEST['submit'])) {
			return [null, null];
		}

		yourls_verify_nonce(UNIWUE_URL_LIMITER_PREFIX . self::NAME);

		$options = UniwueUrlLimiterOptions::get_instance()->reload(UniwueUrlLimiterOptions::RELOAD_MODE_REQUEST);

		return [
			$options->get_url_allow_list()->validate(),
			$options->get_url_block_list()->validate()
		];
	}
}
