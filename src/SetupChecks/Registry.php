<?php
namespace Krokedil\SettingsPage\SetupChecks;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the setup checks of one settings page, runs them and renders the sidebar box content.
 */
class Registry {
	/**
	 * The id of the settings page the checks belong to.
	 *
	 * @var string
	 */
	protected $page_id;

	/**
	 * The registered checks, as passed by the plugin.
	 *
	 * @var array<int, SetupCheck|array<string, mixed>>
	 */
	protected $checks = array();

	/**
	 * The object the settings page belongs to, e.g. a WC_Payment_Gateway.
	 *
	 * @var object|null
	 */
	protected $owner = null;

	/**
	 * The results of the last run. Null until the checks have been run.
	 *
	 * @var array<int, array<string, string>>|null
	 */
	protected $results = null;

	/**
	 * Class constructor.
	 *
	 * @param string $page_id The id of the settings page the checks belong to.
	 */
	public function __construct( string $page_id ) {
		$this->page_id = $page_id;
	}

	/**
	 * Add checks.
	 *
	 * @param array<int|string, mixed> $checks List of SetupCheck instances and check argument arrays, see Check.
	 *
	 * @return self
	 */
	public function add( array $checks ): self {
		foreach ( $checks as $check ) {
			if ( $check instanceof SetupCheck || is_array( $check ) ) {
				$this->checks[] = $check;
			}
		}

		$this->results = null;

		return $this;
	}

	/**
	 * Set the object the settings page belongs to.
	 *
	 * @param object|null $owner The page owner.
	 *
	 * @return self
	 */
	public function set_owner( $owner ): self {
		$owner = is_object( $owner ) ? $owner : null;

		if ( $owner !== $this->owner ) {
			$this->owner   = $owner;
			$this->results = null;
		}

		return $this;
	}

	/**
	 * Whether any checks are registered.
	 *
	 * @return bool
	 */
	public function has_checks(): bool {
		return ! empty( $this->checks );
	}

	/**
	 * Run the checks and get their results. The checks only run once per request.
	 *
	 * Each result is an array with the keys 'id', 'label', 'severity', 'status', 'message',
	 * 'fix_url' and 'fix_label'. Checks that do not apply to the store are left out.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_results(): array {
		if ( null !== $this->results ) {
			return $this->results;
		}

		/**
		 * Filters the setup checks of a settings page before they run.
		 *
		 * @param array<int, SetupCheck|array<string, mixed>> $checks  List of SetupCheck instances and check argument arrays.
		 * @param string                                      $page_id The id of the settings page.
		 * @param object|null                                 $owner   The object the settings page belongs to.
		 */
		$checks = apply_filters( 'krokedil_settings_page_setup_checks', $this->checks, $this->page_id, $this->owner );

		$results = array();
		foreach ( is_array( $checks ) ? $checks : array() as $check ) {
			$result = $this->run_check( $check );

			if ( null !== $result && CheckResult::STATUS_SKIPPED !== $result['status'] ) {
				$results[] = $result;
			}
		}

		// usort is not stable before PHP 8, so keep the registered order as the tie breaker.
		$order = array_flip( array_keys( $results ) );
		uksort(
			$results,
			function ( $a, $b ) use ( $results, $order ) {
				$diff = self::get_sort_weight( $results[ $a ] ) - self::get_sort_weight( $results[ $b ] );

				return 0 !== $diff ? $diff : $order[ $a ] - $order[ $b ];
			}
		);

		$this->results = array_values( $results );

		return $this->results;
	}

	/**
	 * Output the content of the setup checks sidebar box.
	 *
	 * @return void
	 */
	public function output(): void {
		$results = $this->get_results();

		if ( empty( $results ) ) {
			return;
		}

		$passed = count( wp_list_filter( $results, array( 'status' => CheckResult::STATUS_PASSED ) ) );
		?>
		<div class="krokedil_settings__setup_checks">
			<p class="krokedil_settings__setup_checks_summary">
				<?php
				/* translators: 1: number of passed checks, 2: total number of checks. */
				echo esc_html( sprintf( __( '%1$d of %2$d checks passed', 'krokedil-settings' ), $passed, count( $results ) ) );
				?>
			</p>
			<ul class="krokedil_settings__setup_checks_list">
				<?php foreach ( $results as $result ) : ?>
					<?php $this->output_result( $result ); ?>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Output a single check result.
	 *
	 * @param array<string, string> $result The result, see get_results().
	 *
	 * @return void
	 */
	protected function output_result( array $result ): void {
		$is_passed = CheckResult::STATUS_PASSED === $result['status'];
		$state     = self::get_state( $result );
		$fix_label = '' !== $result['fix_label'] ? $result['fix_label'] : __( 'Fix this', 'krokedil-settings' );
		?>
		<li class="<?php echo esc_attr( 'krokedil_settings__setup_check krokedil_settings__setup_check--' . $state ); ?>" data-check-id="<?php echo esc_attr( $result['id'] ); ?>">
			<span class="<?php echo esc_attr( 'krokedil_settings__setup_check_icon dashicons ' . self::get_icon( $state ) ); ?>" aria-hidden="true"></span>
			<div class="krokedil_settings__setup_check_body">
				<span class="krokedil_settings__setup_check_label"><?php echo esc_html( $result['label'] ); ?></span>
				<span class="krokedil_settings__setup_check_badge"><?php echo esc_html( self::get_state_label( $state ) ); ?></span>
				<?php if ( '' !== $result['message'] ) : ?>
					<p class="krokedil_settings__setup_check_message"><?php echo esc_html( $result['message'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! $is_passed && '' !== $result['fix_url'] ) : ?>
					<a class="krokedil_settings__setup_check_fix no-external-icon" href="<?php echo esc_url( $result['fix_url'] ); ?>"><?php echo esc_html( $fix_label ); ?></a>
				<?php endif; ?>
			</div>
		</li>
		<?php
	}

	/**
	 * Run a single check. A check that throws gets the error status, so it cannot break the settings page.
	 *
	 * @param mixed $check A SetupCheck instance or a check argument array.
	 *
	 * @return array<string, string>|null Null if the check is not a valid check.
	 */
	protected function run_check( $check ) {
		$id       = is_array( $check ) && isset( $check['id'] ) && is_string( $check['id'] ) ? $check['id'] : '';
		$label    = $id;
		$severity = SetupCheck::SEVERITY_REQUIRED;

		try {
			if ( is_array( $check ) ) {
				$check = new Check( $check );
			}

			if ( ! $check instanceof SetupCheck ) {
				return null;
			}

			if ( $check instanceof Check ) {
				$check->set_owner( $this->owner );
			}

			$id       = $check->get_id();
			$label    = $check->get_label();
			$severity = $check->get_severity();
			$result   = $check->run();
		} catch ( \Throwable $exception ) {
			do_action( 'krokedil_settings_page_render_error', $id, $exception, array( 'page_id' => $this->page_id ) );

			if ( '' === $id ) {
				return null;
			}

			$result = CheckResult::error( __( 'This check could not be run.', 'krokedil-settings' ) );
		}

		return array(
			'id'        => $id,
			'label'     => $label,
			'severity'  => $severity,
			'status'    => $result->get_status(),
			'message'   => $result->get_message(),
			'fix_url'   => $result->get_fix_url(),
			'fix_label' => $result->get_fix_label(),
		);
	}

	/**
	 * Get the display state of a result: 'passed', 'error', or the severity of a failed check.
	 *
	 * @param array<string, string> $result The result, see get_results().
	 *
	 * @return string
	 */
	protected static function get_state( array $result ): string {
		if ( CheckResult::STATUS_FAILED === $result['status'] ) {
			return $result['severity'];
		}

		return CheckResult::STATUS_PASSED === $result['status'] ? 'passed' : 'error';
	}

	/**
	 * Get the position of a result in the list. What needs attention the most comes first.
	 *
	 * @param array<string, string> $result The result, see get_results().
	 *
	 * @return int
	 */
	protected static function get_sort_weight( array $result ): int {
		$weights = array(
			SetupCheck::SEVERITY_REQUIRED    => 0,
			SetupCheck::SEVERITY_RECOMMENDED => 1,
			'error'                          => 2,
			SetupCheck::SEVERITY_INFO        => 3,
			'passed'                         => 4,
		);

		return $weights[ self::get_state( $result ) ] ?? 2;
	}

	/**
	 * Get the dashicon class for a display state.
	 *
	 * @param string $state The display state, see get_state().
	 *
	 * @return string
	 */
	protected static function get_icon( string $state ): string {
		$icons = array(
			'passed'                         => 'dashicons-yes-alt',
			SetupCheck::SEVERITY_REQUIRED    => 'dashicons-dismiss',
			SetupCheck::SEVERITY_RECOMMENDED => 'dashicons-warning',
			SetupCheck::SEVERITY_INFO        => 'dashicons-info',
		);

		return $icons[ $state ] ?? 'dashicons-editor-help';
	}

	/**
	 * Get the badge text for a display state.
	 *
	 * @param string $state The display state, see get_state().
	 *
	 * @return string
	 */
	protected static function get_state_label( string $state ): string {
		switch ( $state ) {
			case 'passed':
				return __( 'Done', 'krokedil-settings' );
			case SetupCheck::SEVERITY_REQUIRED:
				return __( 'Required', 'krokedil-settings' );
			case SetupCheck::SEVERITY_RECOMMENDED:
				return __( 'Recommended', 'krokedil-settings' );
			case SetupCheck::SEVERITY_INFO:
				return __( 'Info', 'krokedil-settings' );
			default:
				return __( 'Unknown', 'krokedil-settings' );
		}
	}
}
