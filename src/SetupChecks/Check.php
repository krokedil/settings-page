<?php
namespace Krokedil\SettingsPage\SetupChecks;

defined( 'ABSPATH' ) || exit;

/**
 * A setup check built from an array of arguments. The arguments are listed in docs/setup-checks.md.
 */
class Check implements SetupCheck {
	/**
	 * The check arguments.
	 *
	 * @var array<string, mixed>
	 */
	protected $args;

	/**
	 * The object the settings page belongs to, e.g. a WC_Payment_Gateway.
	 *
	 * @var object|null
	 */
	protected $owner = null;

	/**
	 * Class constructor.
	 *
	 * @param array<string, mixed> $args The check arguments, see docs/setup-checks.md.
	 *
	 * @throws \InvalidArgumentException If the check has no id.
	 */
	public function __construct( array $args ) {
		if ( empty( $args['id'] ) || ! is_string( $args['id'] ) ) {
			throw new \InvalidArgumentException( 'A setup check must have an id.' );
		}

		$this->args = $args;
	}

	/**
	 * Set the object the settings page belongs to.
	 *
	 * @param object|null $owner The page owner.
	 *
	 * @return void
	 */
	public function set_owner( $owner ): void {
		$this->owner = is_object( $owner ) ? $owner : null;
	}

	/**
	 * Get the unique id of the check.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->args['id'];
	}

	/**
	 * Get the label shown for the check.
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->get_string_arg( 'label', $this->get_id() );
	}

	/**
	 * Get the severity of the check.
	 *
	 * @return string
	 */
	public function get_severity(): string {
		$severity = $this->get_string_arg( 'severity', self::SEVERITY_REQUIRED );
		$allowed  = array( self::SEVERITY_REQUIRED, self::SEVERITY_RECOMMENDED, self::SEVERITY_INFO );

		return in_array( $severity, $allowed, true ) ? $severity : self::SEVERITY_REQUIRED;
	}

	/**
	 * Run the check.
	 *
	 * @return CheckResult
	 */
	public function run(): CheckResult {
		$outcome = $this->get_outcome();

		if ( $outcome instanceof CheckResult ) {
			return $outcome;
		}

		if ( null === $outcome ) {
			return CheckResult::skipped();
		}

		if ( $outcome ) {
			return CheckResult::passed( $this->get_string_arg( 'passed_message' ) );
		}

		return CheckResult::failed(
			$this->get_string_arg( 'message' ),
			$this->get_string_arg( 'fix_url' ),
			$this->get_string_arg( 'fix_label' )
		);
	}

	/**
	 * Work out whether the check passes.
	 *
	 * @return bool|CheckResult|null Null when the check does not apply.
	 */
	protected function get_outcome() {
		if ( isset( $this->args['callback'] ) && is_callable( $this->args['callback'] ) ) {
			$outcome = call_user_func( $this->args['callback'], $this->owner );

			return ( null === $outcome || $outcome instanceof CheckResult ) ? $outcome : (bool) $outcome;
		}

		if ( isset( $this->args['option'] ) && is_string( $this->args['option'] ) ) {
			return $this->is_value_valid( get_option( $this->args['option'], '' ) );
		}

		if ( isset( $this->args['setting'] ) && is_string( $this->args['setting'] ) ) {
			// Without an owner there is nowhere to read the setting from.
			if ( null === $this->owner || ! is_callable( array( $this->owner, 'get_option' ) ) ) {
				return null;
			}

			return $this->is_value_valid( $this->owner->get_option( $this->args['setting'] ) );
		}

		return null;
	}

	/**
	 * Judge a value read from an option or a setting.
	 *
	 * @param mixed $value The value.
	 *
	 * @return bool
	 */
	protected function is_value_valid( $value ): bool {
		if ( isset( $this->args['compare'] ) && is_callable( $this->args['compare'] ) ) {
			return (bool) call_user_func( $this->args['compare'], $value, $this->owner );
		}

		// Options are stored as strings, so compare as strings to let 2 match '2'.
		$string_value = is_scalar( $value ) ? (string) $value : '';

		if ( isset( $this->args['valid'] ) ) {
			return in_array( $string_value, array_map( 'strval', (array) $this->args['valid'] ), true );
		}

		if ( isset( $this->args['invalid'] ) ) {
			return ! in_array( $string_value, array_map( 'strval', (array) $this->args['invalid'] ), true );
		}

		return ! empty( $value );
	}

	/**
	 * Get a string argument.
	 *
	 * @param string $key           The argument key.
	 * @param string $default_value Value to use when the argument is missing or not a string.
	 *
	 * @return string
	 */
	protected function get_string_arg( string $key, string $default_value = '' ): string {
		$value = $this->args[ $key ] ?? $default_value;

		return is_string( $value ) ? $value : $default_value;
	}
}
