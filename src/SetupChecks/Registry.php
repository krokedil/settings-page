<?php
namespace Krokedil\SettingsPage\SetupChecks;

defined( 'ABSPATH' ) || exit;

/**
 * Holds the setup checks of one settings page, runs them and renders the sidebar box and the system report section.
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
	 * The arguments passed by the plugin, see docs/setup-checks.md.
	 *
	 * @var array<string, mixed>
	 */
	protected $args = array();

	/**
	 * Values used for the arguments that the plugin did not pass.
	 *
	 * @var array<string, mixed>
	 */
	protected $defaults = array();

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
	 * @param array<int|string, mixed> $checks List of SetupCheck instances and check argument arrays, see docs/setup-checks.md.
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
	 * Set arguments, on top of the ones that are already set.
	 *
	 * @param array<string, mixed> $args The arguments, see docs/setup-checks.md.
	 *
	 * @return self
	 */
	public function set_args( array $args ): self {
		if ( ! empty( $args ) ) {
			$this->args    = array_merge( $this->args, $args );
			$this->results = null;
		}

		return $this;
	}

	/**
	 * Set the values to use for the arguments that the plugin did not pass.
	 *
	 * @param array<string, mixed> $defaults The default arguments.
	 *
	 * @return self
	 */
	public function set_defaults( array $defaults ): self {
		if ( $defaults !== $this->defaults ) {
			$this->defaults = $defaults;
			$this->results  = null;
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
	 * Run the checks and get their results, in the order the checks were added. The checks only run once per request.
	 *
	 * @return array<int, array<string, string>> One array per check, the keys are listed in docs/setup-checks.md. Checks that do not apply are left out.
	 */
	public function get_results(): array {
		if ( null !== $this->results ) {
			return $this->results;
		}

		$owner = $this->get_owner();

		/**
		 * Filters the setup checks of a settings page before they run.
		 *
		 * @param array<int, SetupCheck|array<string, mixed>> $checks  List of SetupCheck instances and check argument arrays.
		 * @param string                                      $page_id The id of the settings page.
		 * @param object|null                                 $owner   The object the settings page belongs to.
		 */
		$checks = apply_filters( 'krokedil_settings_page_setup_checks', $this->checks, $this->page_id, $owner );

		$this->results = array();
		foreach ( is_array( $checks ) ? $checks : array() as $check ) {
			$result = $this->run_check( $check, $owner );

			if ( null !== $result && CheckResult::STATUS_SKIPPED !== $result['status'] ) {
				$this->results[] = $result;
			}
		}

		return $this->results;
	}

	/**
	 * Get the results of the checks that did not pass, the ones that need attention the most first.
	 *
	 * @param string $state Optional. Only get the results in this state: a SetupCheck::SEVERITY_* constant, or 'error'.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function get_failed( string $state = '' ): array {
		$failed = array();

		foreach ( array( SetupCheck::SEVERITY_REQUIRED, SetupCheck::SEVERITY_RECOMMENDED, 'error', SetupCheck::SEVERITY_INFO ) as $failed_state ) {
			if ( '' !== $state && $state !== $failed_state ) {
				continue;
			}

			foreach ( $this->get_results() as $result ) {
				if ( self::get_state( $result ) === $failed_state ) {
					$failed[] = $result;
				}
			}
		}

		return $failed;
	}

	/**
	 * Get the title of the sidebar box and the system report section.
	 *
	 * @return string
	 */
	public function get_title(): string {
		$title = $this->get_arg( 'title' );

		return is_string( $title ) && '' !== $title ? $title : __( 'Store setup check', 'krokedil-settings' );
	}

	/**
	 * Get the URL of the section with the full results in the WooCommerce system report.
	 *
	 * @return string
	 */
	public function get_report_url(): string {
		return admin_url( 'admin.php?page=wc-status' ) . '#' . $this->get_report_id();
	}

	/**
	 * Output the content of the setup checks sidebar box.
	 *
	 * @return void
	 */
	public function output(): void {
		if ( empty( $this->get_results() ) ) {
			return;
		}

		$plugin_name = $this->get_plugin_name();
		$banner      = $this->get_banner();
		?>
		<div class="krokedil_settings__setup_checks">
			<p class="krokedil_settings__setup_checks_intro">
				<?php
				/* translators: %s: plugin name. */
				echo esc_html( sprintf( __( 'Checks store settings that %s requires or recommends.', 'krokedil-settings' ), '' !== $plugin_name ? $plugin_name : __( 'the plugin', 'krokedil-settings' ) ) );
				?>
			</p>
			<p class="<?php echo esc_attr( 'krokedil_settings__setup_checks_banner krokedil_settings__setup_checks_banner--' . $banner['state'] ); ?>">
				<span class="<?php echo esc_attr( 'dashicons ' . ( 'passed' === $banner['state'] ? 'dashicons-yes-alt' : 'dashicons-warning' ) ); ?>" aria-hidden="true"></span>
				<?php echo esc_html( $banner['text'] ); ?>
			</p>
			<ul class="krokedil_settings__setup_checks_list">
				<?php foreach ( $this->get_failed() as $result ) : ?>
					<li class="<?php echo esc_attr( 'krokedil_settings__setup_check krokedil_settings__setup_check--' . self::get_state( $result ) ); ?>" data-check-id="<?php echo esc_attr( $result['id'] ); ?>">
						<span class="krokedil_settings__setup_check_label">&raquo; <?php echo esc_html( self::get_label( $result ) ); ?></span>
						<?php echo self::get_help_tip( $result ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_help_tip() escapes its output. ?>
						<p class="krokedil_settings__setup_check_message"><?php echo wp_kses( self::get_message_html( $result ), self::get_allowed_html() ); ?></p>
						<?php if ( '' !== $result['fix_url'] ) : ?>
							<a class="krokedil_settings__setup_check_fix no-external-icon" href="<?php echo esc_url( $result['fix_url'] ); ?>"><?php echo esc_html( self::get_fix_label( $result ) ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<a class="krokedil_settings__setup_checks_report no-external-icon" href="<?php echo esc_url( $this->get_report_url() ); ?>"><?php esc_html_e( 'View full report', 'krokedil-settings' ); ?></a>
		</div>
		<?php
	}

	/**
	 * Output the results as a section of the WooCommerce system report.
	 *
	 * @return void
	 */
	public function output_system_report(): void {
		$results = $this->get_results();

		if ( empty( $results ) ) {
			return;
		}

		$plugin_name = $this->get_plugin_name();
		$title       = '' !== $plugin_name ? $plugin_name . ' – ' . $this->get_title() : $this->get_title();
		$colors      = array(
			SetupCheck::SEVERITY_RECOMMENDED => '#996800',
			SetupCheck::SEVERITY_INFO        => '#2271b1',
		);
		?>
		<table class="wc_status_table widefat" cellspacing="0" id="<?php echo esc_attr( $this->get_report_id() ); ?>">
			<thead>
				<tr>
					<th colspan="3" data-export-label="<?php echo esc_attr( $title ); ?>">
						<h2><?php echo esc_html( $title ); ?></h2>
					</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $results as $result ) : ?>
					<?php $state = self::get_state( $result ); ?>
					<tr>
						<td data-export-label="<?php echo esc_attr( self::get_label( $result ) ); ?>"><?php echo esc_html( self::get_label( $result ) ); ?>:</td>
						<td class="help"><?php echo self::get_help_tip( $result ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_help_tip() escapes its output. ?></td>
						<td>
							<?php if ( 'passed' === $state ) : ?>
								<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
								<?php echo wp_kses( $result['message'], self::get_allowed_html() ); ?>
							<?php else : ?>
								<?php // WooCommerce only styles the error mark red, so the other states get their color inline. ?>
								<mark class="error" style="<?php echo esc_attr( isset( $colors[ $state ] ) ? 'color:' . $colors[ $state ] : '' ); ?>">
									<span class="dashicons dashicons-warning"></span>
									<?php echo wp_kses( self::get_message_html( $result ), self::get_allowed_html() ); ?>
									<?php if ( '' !== $result['fix_url'] ) : ?>
										<a href="<?php echo esc_url( $result['fix_url'] ); ?>"><?php echo esc_html( self::get_fix_label( $result ) ); ?></a>
									<?php endif; ?>
								</mark>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Whether the payment or shipping method the checks belong to is enabled in the store.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		$enabled = $this->get_arg( 'enabled' );

		if ( is_callable( $enabled ) ) {
			return (bool) call_user_func( $enabled );
		}

		if ( null !== $enabled ) {
			return (bool) $enabled;
		}

		$owner = $this->get_owner();

		// Gateways and shipping methods keep it in their enabled property. Anything else is in use as long as it is active.
		return null === $owner || ! isset( $owner->enabled ) || 'yes' === $owner->enabled;
	}

	/**
	 * Whether the current admin page is the plugin's own WooCommerce settings page.
	 *
	 * @return bool
	 */
	public function is_own_settings_page(): bool {
		$own_section = $this->get_arg( 'section' );
		$own_section = is_string( $own_section ) && '' !== $own_section ? $own_section : $this->page_id;

		return 'wc-settings' === self::get_query_arg( 'page' ) && sanitize_key( $own_section ) === self::get_query_arg( 'section' );
	}

	/**
	 * Whether the current admin page shows the sidebar box, which makes a notice on the same page redundant.
	 *
	 * The notice is printed before the settings page renders, so this can only be read from the URL.
	 *
	 * @return bool
	 */
	public function is_box_on_current_page(): bool {
		// The sidebar is only part of the Support and Addons tabs.
		return $this->is_own_settings_page() && in_array( self::get_query_arg( 'subsection' ), array( 'support', 'addons' ), true );
	}

	/**
	 * Output an admin notice listing the failed required checks. Prints nothing when they all pass.
	 *
	 * @return void
	 */
	public function output_notice(): void {
		$failed = $this->get_notice_checks();

		if ( empty( $failed ) ) {
			return;
		}
		?>
		<div class="notice notice-error krokedil-setup-checks-notice" data-page-id="<?php echo esc_attr( $this->page_id ); ?>">
			<?php $this->output_notice_content( $failed ); ?>
		</div>
		<?php
	}

	/**
	 * Output the failed required checks as a hidden notice for the row of the payment gateway in the list of payment methods.
	 *
	 * WooCommerce renders that list with React, so the notice is moved into the row by assets/js/setup-checks-payments.js.
	 *
	 * @return void
	 */
	public function output_row_notice(): void {
		$owner  = $this->get_owner();
		$failed = $owner instanceof \WC_Payment_Gateway ? $this->get_notice_checks() : array();

		if ( empty( $failed ) || ! $owner instanceof \WC_Payment_Gateway ) {
			return;
		}

		// Styled inline, since the stylesheet of the package is not loaded on this page.
		?>
		<div class="krokedil-setup-checks-row-notice" data-gateway-id="<?php echo esc_attr( $owner->id ); ?>" data-page-id="<?php echo esc_attr( $this->page_id ); ?>" style="background: #fcf0f1; border-left: 4px solid #d63638; padding: 4px 12px; margin: 8px 0;" hidden>
			<?php $this->output_notice_content( $failed ); ?>
		</div>
		<?php
	}

	/**
	 * Get the failed checks that a notice lists.
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function get_notice_checks(): array {
		return false === $this->get_arg( 'notice' ) ? array() : $this->get_failed( SetupCheck::SEVERITY_REQUIRED );
	}

	/**
	 * Output the content of a notice.
	 *
	 * @param array<int, array<string, string>> $failed The failed checks to list.
	 *
	 * @return void
	 */
	protected function output_notice_content( array $failed ): void {
		$plugin_name = $this->get_plugin_name();
		$count       = count( $failed );
		/* translators: %d: number of checks. */
		$summary = sprintf( _n( '%d required check fails.', '%d required checks fail.', $count, 'krokedil-settings' ), $count );

		if ( '' !== $plugin_name ) {
			/* translators: 1: plugin name, 2: summary of the failed checks, e.g. "3 required checks fail." */
			$summary = sprintf( __( '%1$s store setup, %2$s', 'krokedil-settings' ), $plugin_name, $summary );
		}
		?>
		<p><strong><?php echo esc_html( $summary ); ?></strong></p>
		<?php foreach ( $failed as $result ) : ?>
			<p>
				&raquo; <?php echo esc_html( self::get_label( $result ) ); ?>
				<?php if ( '' !== $result['message'] ) : ?>
					&ndash; <?php echo wp_kses( $result['message'], self::get_allowed_html() ); ?>
				<?php endif; ?>
				<?php if ( '' !== $result['fix_url'] ) : ?>
					<a href="<?php echo esc_url( $result['fix_url'] ); ?>"><?php echo esc_html( self::get_fix_label( $result ) ); ?></a>
				<?php endif; ?>
			</p>
		<?php endforeach; ?>
		<p><a href="<?php echo esc_url( $this->get_report_url() ); ?>"><?php esc_html_e( 'View full report', 'krokedil-settings' ); ?></a></p>
		<?php
	}

	/**
	 * Get a query argument of the current request as a key.
	 *
	 * @param string $key The query argument.
	 *
	 * @return string
	 */
	protected static function get_query_arg( string $key ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only reads which admin page is shown.
		return isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ? sanitize_key( wp_unslash( $_GET[ $key ] ) ) : '';
	}

	/**
	 * Get an argument, falling back to its default.
	 *
	 * @param string $key The argument key.
	 *
	 * @return mixed Null if the argument is not set.
	 */
	protected function get_arg( string $key ) {
		return $this->args[ $key ] ?? $this->defaults[ $key ] ?? null;
	}

	/**
	 * Get the object the settings page belongs to, e.g. a WC_Payment_Gateway.
	 *
	 * @return object|null
	 */
	protected function get_owner() {
		$owner = $this->get_arg( 'owner' );

		// A closure lets the plugin register its checks early, without loading the gateway on every request.
		if ( $owner instanceof \Closure ) {
			$owner = $owner();
		}

		return is_object( $owner ) ? $owner : null;
	}

	/**
	 * Get the name of the plugin the checks belong to.
	 *
	 * @return string
	 */
	protected function get_plugin_name(): string {
		$plugin_name = $this->get_arg( 'plugin_name' );

		return is_string( $plugin_name ) ? $plugin_name : '';
	}

	/**
	 * Get the id of the section in the WooCommerce system report.
	 *
	 * @return string
	 */
	protected function get_report_id(): string {
		return 'krokedil-setup-checks-' . sanitize_html_class( $this->page_id );
	}

	/**
	 * Get the state and text of the summary banner. The worst state among the results decides it.
	 *
	 * @return array{state: string, text: string}
	 */
	protected function get_banner(): array {
		$required = count( $this->get_failed( SetupCheck::SEVERITY_REQUIRED ) );
		if ( $required > 0 ) {
			/* translators: %d: number of checks. */
			return self::make_banner( SetupCheck::SEVERITY_REQUIRED, _n( '%d required check fails.', '%d required checks fail.', $required, 'krokedil-settings' ), $required );
		}

		$recommended = count( $this->get_failed( SetupCheck::SEVERITY_RECOMMENDED ) );
		if ( $recommended > 0 ) {
			/* translators: %d: number of checks. */
			return self::make_banner( SetupCheck::SEVERITY_RECOMMENDED, _n( '%d recommended check fails.', '%d recommended checks fail.', $recommended, 'krokedil-settings' ), $recommended );
		}

		$errors = count( $this->get_failed( 'error' ) );
		if ( $errors > 0 ) {
			/* translators: %d: number of checks. */
			return self::make_banner( 'error', _n( '%d check could not be run.', '%d checks could not be run.', $errors, 'krokedil-settings' ), $errors );
		}

		$passed = count( wp_list_filter( $this->get_results(), array( 'status' => CheckResult::STATUS_PASSED ) ) );

		// Info checks do not fail the setup, but with them in the list "all checks passed" would be wrong.
		if ( count( $this->get_results() ) > $passed ) {
			/* translators: %d: number of checks. */
			return self::make_banner( 'passed', _n( '%d check passed.', '%d checks passed.', $passed, 'krokedil-settings' ), $passed );
		}

		// Not _n(), since its singular needs the number and "All 1 checks passed" reads badly.
		if ( 1 === $passed ) {
			return self::make_banner( 'passed', __( 'The check passed.', 'krokedil-settings' ), $passed );
		}

		/* translators: %d: number of checks. */
		return self::make_banner( 'passed', __( 'All %d checks passed.', 'krokedil-settings' ), $passed );
	}

	/**
	 * Build the summary banner.
	 *
	 * @param string $state The banner state.
	 * @param string $text  The banner text, with a placeholder for the number of checks.
	 * @param int    $count The number of checks.
	 *
	 * @return array{state: string, text: string}
	 */
	protected static function make_banner( string $state, string $text, int $count ): array {
		return array(
			'state' => $state,
			'text'  => sprintf( $text, $count ),
		);
	}

	/**
	 * Run a single check. A check that throws gets the error status, so it cannot break the page.
	 *
	 * @param mixed       $check A SetupCheck instance or a check argument array.
	 * @param object|null $owner The object the settings page belongs to.
	 *
	 * @return array<string, string>|null Null if the check is not a valid check.
	 */
	protected function run_check( $check, $owner ) {
		$id      = is_array( $check ) && isset( $check['id'] ) && is_string( $check['id'] ) ? $check['id'] : '';
		$details = array(
			'label'       => $id,
			'severity'    => SetupCheck::SEVERITY_REQUIRED,
			'description' => '',
			'docs_url'    => '',
		);

		try {
			if ( is_array( $check ) ) {
				$check = new Check( $check );
			}

			if ( ! $check instanceof SetupCheck ) {
				return null;
			}

			if ( $check instanceof Check ) {
				$check->set_owner( $owner );
			}

			$id                  = $check->get_id();
			$details['label']    = $check->get_label();
			$details['severity'] = $check->get_severity();

			if ( $check instanceof DetailedSetupCheck ) {
				$details['description'] = $check->get_description();
				$details['docs_url']    = $check->get_docs_url();
			}

			$result = $check->run();
		} catch ( \Throwable $exception ) {
			do_action( 'krokedil_settings_page_render_error', $id, $exception, array( 'page_id' => $this->page_id ) );

			if ( '' === $id ) {
				return null;
			}

			$result = CheckResult::error( __( 'This check could not be run.', 'krokedil-settings' ) );
		}

		$default_docs_url = $this->get_arg( 'docs_url' );
		if ( '' === $details['docs_url'] && is_string( $default_docs_url ) ) {
			$details['docs_url'] = $default_docs_url;
		}

		return array(
			'id'          => $id,
			'label'       => $details['label'],
			'severity'    => $details['severity'],
			'status'      => $result->get_status(),
			'message'     => $result->get_message(),
			'fix_url'     => $result->get_fix_url(),
			'fix_label'   => $result->get_fix_label(),
			'description' => $details['description'],
			'docs_url'    => $details['docs_url'],
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
	 * Get the label of a result, with its severity.
	 *
	 * @param array<string, string> $result The result, see get_results().
	 *
	 * @return string
	 */
	protected static function get_label( array $result ): string {
		if ( SetupCheck::SEVERITY_REQUIRED === $result['severity'] ) {
			/* translators: %s: the label of a check. */
			return sprintf( __( '%s (required)', 'krokedil-settings' ), $result['label'] );
		}

		if ( SetupCheck::SEVERITY_RECOMMENDED === $result['severity'] ) {
			/* translators: %s: the label of a check. */
			return sprintf( __( '%s (recommended)', 'krokedil-settings' ), $result['label'] );
		}

		return $result['label'];
	}

	/**
	 * Get the link text for the fix URL of a result.
	 *
	 * @param array<string, string> $result The result, see get_results().
	 *
	 * @return string
	 */
	protected static function get_fix_label( array $result ): string {
		return '' !== $result['fix_label'] ? $result['fix_label'] : __( 'Fix this', 'krokedil-settings' );
	}

	/**
	 * Get the help tip of a result.
	 *
	 * @param array<string, string> $result The result, see get_results().
	 *
	 * @return string
	 */
	protected static function get_help_tip( array $result ): string {
		if ( '' === $result['description'] || ! function_exists( 'wc_help_tip' ) ) {
			return '';
		}

		return wc_help_tip( $result['description'] );
	}

	/**
	 * Get the message of a result, followed by the link to its documentation.
	 *
	 * @param array<string, string> $result The result, see get_results().
	 *
	 * @return string HTML, not escaped.
	 */
	protected static function get_message_html( array $result ): string {
		if ( '' === $result['docs_url'] ) {
			return $result['message'];
		}

		$link = '<a href="' . esc_url( $result['docs_url'] ) . '" target="_blank" rel="noopener">' . esc_html__( 'plugin docs', 'krokedil-settings' ) . '</a>';

		if ( SetupCheck::SEVERITY_REQUIRED === $result['severity'] ) {
			/* translators: %s: link to the plugin documentation. */
			$read_more = __( 'Read more about this requirement in the %s.', 'krokedil-settings' );
		} elseif ( SetupCheck::SEVERITY_RECOMMENDED === $result['severity'] ) {
			/* translators: %s: link to the plugin documentation. */
			$read_more = __( 'Read more about this recommendation in the %s.', 'krokedil-settings' );
		} else {
			/* translators: %s: link to the plugin documentation. */
			$read_more = __( 'Read more in the %s.', 'krokedil-settings' );
		}

		return trim( $result['message'] . ' ' . sprintf( esc_html( $read_more ), $link ) );
	}

	/**
	 * Get the HTML that is allowed in a message.
	 *
	 * @return array<string, array<string, bool>>
	 */
	protected static function get_allowed_html(): array {
		return array(
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
			),
			'strong' => array(),
			'em'     => array(),
			'code'   => array(),
		);
	}
}
