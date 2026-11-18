<?php
namespace Krokedil\SettingsPage\SetupChecks;

defined( 'ABSPATH' ) || exit;

/**
 * The outcome of a setup check.
 */
class CheckResult {
	const STATUS_PASSED = 'passed';
	const STATUS_FAILED = 'failed';

	/**
	 * The check does not apply to this store, and is left out of the list.
	 */
	const STATUS_SKIPPED = 'skipped';

	/**
	 * The check could not be run, e.g. because it threw.
	 */
	const STATUS_ERROR = 'error';

	/**
	 * The status, one of the STATUS_* constants.
	 *
	 * @var string
	 */
	protected $status;

	/**
	 * Message explaining the status.
	 *
	 * @var string
	 */
	protected $message;

	/**
	 * URL where the merchant can fix a failed check.
	 *
	 * @var string
	 */
	protected $fix_url;

	/**
	 * Link text for the fix URL.
	 *
	 * @var string
	 */
	protected $fix_label;

	/**
	 * Class constructor.
	 *
	 * @param string $status    The status, one of the STATUS_* constants.
	 * @param string $message   Message explaining the status.
	 * @param string $fix_url   URL where the merchant can fix a failed check.
	 * @param string $fix_label Link text for the fix URL.
	 */
	public function __construct( string $status, string $message = '', string $fix_url = '', string $fix_label = '' ) {
		$this->status    = $status;
		$this->message   = $message;
		$this->fix_url   = $fix_url;
		$this->fix_label = $fix_label;
	}

	/**
	 * Create a passed result.
	 *
	 * @param string $message Optional message.
	 *
	 * @return self
	 */
	public static function passed( string $message = '' ): self {
		return new self( self::STATUS_PASSED, $message );
	}

	/**
	 * Create a failed result.
	 *
	 * @param string $message   What is wrong and what the merchant should do.
	 * @param string $fix_url   URL where the merchant can fix it.
	 * @param string $fix_label Link text for the fix URL.
	 *
	 * @return self
	 */
	public static function failed( string $message = '', string $fix_url = '', string $fix_label = '' ): self {
		return new self( self::STATUS_FAILED, $message, $fix_url, $fix_label );
	}

	/**
	 * Create a skipped result, for a check that does not apply to this store.
	 *
	 * @param string $message Optional message.
	 *
	 * @return self
	 */
	public static function skipped( string $message = '' ): self {
		return new self( self::STATUS_SKIPPED, $message );
	}

	/**
	 * Create an error result, for a check that could not be run.
	 *
	 * @param string $message Optional message.
	 *
	 * @return self
	 */
	public static function error( string $message = '' ): self {
		return new self( self::STATUS_ERROR, $message );
	}

	/**
	 * Get the status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}

	/**
	 * Get the message.
	 *
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Get the fix URL.
	 *
	 * @return string
	 */
	public function get_fix_url(): string {
		return $this->fix_url;
	}

	/**
	 * Get the link text for the fix URL.
	 *
	 * @return string
	 */
	public function get_fix_label(): string {
		return $this->fix_label;
	}
}
