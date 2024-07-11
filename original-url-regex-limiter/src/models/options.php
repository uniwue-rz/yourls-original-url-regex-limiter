<?php

// no direct call
if (!defined('YOURLS_ABSPATH')) die();

// load dependencies
require_once(dirname(__DIR__) . '/models/form-list.php');
require_once(dirname(__DIR__) . '/models/regex-form-list.php');

/**
 * This class manages the plugin options.
 */
class UniwueUrlLimiterOptions
{
	// option key access identifiers used in the options array and in form names
	public const URL_ALLOW_LIST = UNIWUE_URL_LIMITER_PREFIX . 'url_allow_list';
	public const URL_BLOCK_LIST = UNIWUE_URL_LIMITER_PREFIX . 'url_block_list';
	public const USER_BYPASS_LIST = UNIWUE_URL_LIMITER_PREFIX . 'user_bypass_list';
	private const LISTS = [
		self::URL_ALLOW_LIST,
		self::URL_BLOCK_LIST,
		self::USER_BYPASS_LIST
	];

	// reload modes
	public const RELOAD_MODE_DB = 'db';
	public const RELOAD_MODE_REQUEST = 'request';

	// singleton for caching
	private static ?self $instance = null;

	// variable that holds the actual plugin options
	private array $options;


	// construction and persistence

	/**
	 * Constructs the options singleton.
	 *
	 * @param string $url_allow_list
	 * @param string $url_block_list
	 * @param string $user_bypass_list
	 */
	private function __construct(array $options = [])
	{
		$this->options = $options;
	}

	/**
	 * Returns the singleton instance. 
	 *
	 * @return self
	 */
	public static function get_instance(): self
	{
		if (self::$instance === null) {
			self::$instance = (new self())->reload(self::RELOAD_MODE_DB);
		}

		return self::$instance;
	}

	/**
	 * (Re)loads the options from the database or the request to have a fresh version cached.
	 *
	 * @return self
	 */
	public function reload(string $reload_mode = self::RELOAD_MODE_DB): self
	{
		$options = [];

		foreach (self::LISTS as $list) {
			$class = $list === self::USER_BYPASS_LIST ? UniwueUrlLimiterFormList::class : UniwueUrlLimiterRegexFormList::class;
			$options[$list] = call_user_func([$class, 'from_form_string'],
				$reload_mode === self::RELOAD_MODE_DB ?
					yourls_get_option($list, '') :
					$_REQUEST[$list] ?? '',
				$list
			);
		}

		self::$instance = new self($options);
		return self::$instance;
	}

	/**
	 * Saves the currently cached options to the database.
	 *
	 * @return void
	 */
	public function save(): void
	{
		foreach (self::LISTS as $list) {
			if (empty($this->options[$list]->get())) {
				yourls_delete_option($list);
			} else {
				yourls_update_option($list, $this->options[$list]->to_form_string());
			}
		}
	}

	/**
	 * Removes all options from the database.
	 *
	 * @return void
	 */
	public function destroy(): void
	{
		foreach (self::LISTS as $list) {
			yourls_delete_option($list);
		}
	}


	// getters

	/**
	 * Returns the URL allow list.
	 *
	 * @return UniwueUrlLimiterRegexFormList
	 */
	public function get_url_allow_list(): UniwueUrlLimiterRegexFormList
	{
		return $this->options[self::URL_ALLOW_LIST];
	}

	/**
	 * Returns the URL block list.
	 *
	 * @return UniwueUrlLimiterRegexFormList
	 */
	public function get_url_block_list(): UniwueUrlLimiterRegexFormList
	{
		return $this->options[self::URL_BLOCK_LIST];
	}

	/**
	 * Returns the user bypass list.
	 *
	 * @return UniwueUrlLimiterFormList
	 */
	public function get_user_bypass_list(): UniwueUrlLimiterFormList
	{
		return $this->options[self::USER_BYPASS_LIST];
	}

	/**
	 * Returns the plugins base directory.
	 *
	 * @return string
	 */
	public static function get_plugin_url($file = ''): string
	{
		$path =  yourls_plugin_url(basename(dirname(__DIR__, 2)));

		if (!empty($file)) {
			$path .= '/' . $file;
		}

		return $path;
	}
}
