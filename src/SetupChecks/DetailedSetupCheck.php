<?php
namespace Krokedil\SettingsPage\SetupChecks;

defined( 'ABSPATH' ) || exit;

/**
 * A setup check that also has a help text and a documentation link.
 */
interface DetailedSetupCheck extends SetupCheck {
	/**
	 * Get the help text shown in the help tip of the check, explaining why it matters.
	 *
	 * @return string
	 */
	public function get_description(): string;

	/**
	 * Get the URL of the documentation for the check.
	 *
	 * @return string
	 */
	public function get_docs_url(): string;
}
