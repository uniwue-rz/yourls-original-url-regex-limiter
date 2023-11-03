<?php

// no direct call
if (!defined('YOURLS_ABSPATH')) die();


/**
 * This class manages a form list.
 */
class UniwueUrlLimiterFormList
{
	// variable that holds the list
	protected array $list;


	// construction

	/**
	 * Constructs the form list and sanitizes it.
	 *
	 * @param array $list
	 */
	protected function __construct(array $list = [])
	{
		$this->list = $list;
		$this->sanitize();
	}


	// getters and setters

	/**
	 * Returns the list.
	 *
	 * @return array
	 */
	public function get(): array
	{
		return $this->list;
	}

	/**
	 * Sets the list and sanitizes it.
	 *
	 * @param array $list
	 * @return void
	 */
	public function set(array $list): void
	{
		$this->list = $list;
		$this->sanitize();
	}


	// transformation

	/**
	 * Returns a new form list from the given form list string.
	 *
	 * @param string $list
	 * @param string $name
	 * @return self
	 */
	public static function from_form_string(string $list, string $name): self
	{
		return new self(
			yourls_apply_filter(
				$name,
				empty($list) ? [] : explode("\n", $list)
			)
		);
	}

	/**
	 * Returns the form list string representation of this list.
	 *
	 * @return string
	 */
	public function to_form_string(): string
	{
		return implode("\n", $this->list);
	}


	// sanitization

	/**
	 * Sanitizes the list by removing empty items.
	 *
	 * @return void
	 */
	protected function sanitize(): void
	{
		$list = array_map('trim', $this->list); // trim leading and trailing whitespaces
		$list = array_filter($list); // remove empty items
		sort($list); // sort items
		$this->list = array_unique($list); // remove duplicates
	}
}
