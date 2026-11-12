<?php
namespace Krokedil\SettingsPage\SetupChecks;

defined( 'ABSPATH' ) || exit;

/**
 * A single store setup check shown in the setup checks sidebar box.
 *
 * Implement this for a custom check that the array form of a check cannot express. A check
 * only reports data, the package renders it.
 */
interface SetupCheck {
	/**
	 * The store cannot use the plugin until the check passes.
	 */
	const SEVERITY_REQUIRED = 'required';

	/**
	 * The plugin works, but we advise the merchant to fix the check.
	 */
	const SEVERITY_RECOMMENDED = 'recommended';

	/**
	 * Neutral information, nothing has to be changed.
	 */
	const SEVERITY_INFO = 'info';

	/**
	 * Get the unique id of the check.
	 *
	 * @return string
	 */
	public function get_id(): string;

	/**
	 * Get the label shown for the check.
	 *
	 * @return string
	 */
	public function get_label(): string;

	/**
	 * Get the severity of the check, one of the SEVERITY_* constants.
	 *
	 * @return string
	 */
	public function get_severity(): string;

	/**
	 * Run the check.
	 *
	 * @return CheckResult
	 */
	public function run(): CheckResult;
}
