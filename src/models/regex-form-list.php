<?php

// no direct call
if (!defined('YOURLS_ABSPATH')) die();

// load dependencies
require_once(dirname(__DIR__) . '/models/dict.php');
require_once(dirname(__DIR__) . '/models/form-list.php');


/**
 * This class manages a regex list.
 */
class UniwueUrlLimiterRegexFormList extends UniwueUrlLimiterFormList
{
	// regex constants
	public const REGEX_MATCH_ALL = '.*';
	public const REGEX_MATCH_NONE = 'a^';
	private const REGEX_DELIMITER = '/';
	private const REGEX_DOMAIN_PREFIX = '(^|:\/\/|\.)';
	private const REGEX_DOMAIN_SUFFIX = '(:|\/|\?|#|$)';


	// transformation

	/**
	 * Returns a new regex form list from the given form list string.
	 *
	 * @param string $list
	 * @return self
	 */
	public static function from_form_string(string $list): self
	{
		return new self(empty($list) ? [] : explode("\n", $list));
	}

	/**
	 * Adds the regex of the given domain to the list.
	 *
	 * @param string $domain
	 * @return boolean
	 */
	public function add_domain(string $domain): bool
	{
		$domain_regex = self::get_regex($domain);

		$present = false;
		$success = true;

		foreach ($this->list as $regex) {
			if (preg_match(sprintf('/%s/', $regex), $domain)) {
				if ($regex === $domain_regex) {
					$present = true;
				} else {
					$success = false;
				}
			}
		}

		if ($success && !$present) {
			array_push($this->list, $domain_regex);
			$this->sanitize();
		}

		return $success;
	}

	/**
	 * Removes the regex of the given domain from the list.
	 *
	 * @param string $domain
	 * @return boolean
	 */
	public function remove_domain(string $domain): bool
	{
		$domain_regex = self::get_regex($domain);

		$present = false;
		$success = true;

		foreach ($this->list as $regex) {
			if (preg_match(sprintf('/%s/', $regex), $domain)) {
				if ($regex === $domain_regex) {
					$present = true;
				} else {
					$success = false;
				}
			}
		}

		if ($success && $present) {
			array_splice($this->list, array_search($domain_regex, $this->list), 1);
			$this->sanitize(); // not required yet, but place this here to make it future-proof
		}

		return $success;
	}

	/**
	 * Returns the sql string representation of this list
	 * that can be used in a "REGEXP '<expr>'" clause.
	 *
	 * @param string $return_on_empty
	 * @return string
	 */
	public function to_sql_string(string $return_on_empty): string
	{
		return empty($this->list) ?
			$return_on_empty :
			implode(
				"|",
				array_map(
					fn ($v) => str_replace("\\", "\\\\", $v),
					$this->list
				)
			);
	}


	// validation

	/**
	 * Returns the validation error message for this list.
	 *
	 * @return string|null
	 */
	public function validate(): ?string
	{
		$error = '';

		foreach ($this->list as $idx => $regex) {
			@preg_match(sprintf('/%s/', $regex), '');
			$last_error = preg_last_error();

			if ($last_error !== PREG_NO_ERROR) {
				$error .= sprintf(
					UniwueUrlLimiterDict::translate('error_template_regex'),
					$idx + 1,
					$this->get_regex_error($last_error)
				) . '<br/>';
			}
		}

		return empty($error) ? null : $error;
	}

	/**
	 * Returns the error message for the given regex error code.
	 *
	 * @param integer $error_code
	 * @return string
	 */
	private function get_regex_error(int $error_code): string
	{
		return [
			1 => UniwueUrlLimiterDict::translate('error_regex_internal'),
			2 => UniwueUrlLimiterDict::translate('error_regex_limit_backtrack'),
			3 => UniwueUrlLimiterDict::translate('error_regex_limit_recursion'),
			4 => UniwueUrlLimiterDict::translate('error_regex_offset'),
			5 => UniwueUrlLimiterDict::translate('error_regex_malformed'),
			6 => UniwueUrlLimiterDict::translate('error_regex_limit_jit')
		][$error_code] ?? UniwueUrlLimiterDict::translate('error');
	}

	/**
	 * Returns whether the given URL is matched by any of the regexes in this list.
	 * The last parameter is used to determine whether an empty list of regexes should count as matched.
	 *
	 * @param string $original_url
	 * @param boolean $return_on_empty
	 * @return boolean
	 */
	public function matches(string $original_url, bool $return_on_empty): bool
	{
		if (empty($this->list)) {
			return $return_on_empty;
		}

		foreach ($this->list as $regex) {
			if (preg_match(sprintf('/%s/', $regex), $original_url)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns the regex of the given domain.
	 *
	 * @param string $domain
	 * @return string
	 */
	private static function get_regex(string $domain): string {
		return self::REGEX_DOMAIN_PREFIX . preg_quote($domain, self::REGEX_DELIMITER) . self::REGEX_DOMAIN_SUFFIX;
	}
}
