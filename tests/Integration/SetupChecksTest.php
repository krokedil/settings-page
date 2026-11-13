<?php

namespace Tests\Integration;

use Krokedil\SettingsPage\SettingsPage;
use Krokedil\SettingsPage\SetupChecks\CheckResult;
use Krokedil\SettingsPage\SetupChecks\Checks;
use Krokedil\SettingsPage\SetupChecks\Registry;
use Krokedil\SettingsPage\SetupChecks\SetupCheck;
use Tests\Integration\Fixtures\TestGateway;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Integration tests for the store setup checks and their sidebar box.
 */
class SetupChecksTest extends WPTestCase {

	/**
	 * Runs the given checks and returns the results keyed by check id.
	 *
	 * @param array<int, mixed> $checks The checks.
	 * @param object|null       $owner  The page owner.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function run_checks( array $checks, $owner = null ): array {
		$results = ( new Registry( 'test_page' ) )->add( $checks )->set_args( array( 'owner' => $owner ) )->get_results();

		return array_column( $results, null, 'id' );
	}

	public function test_option_check_passes_on_a_valid_value_and_fails_on_another(): void {
		$check = array(
			'id'      => 'decimals',
			'label'   => 'Decimals',
			'option'  => 'woocommerce_price_num_decimals',
			'valid'   => array( 2 ),
			'message' => 'Use two decimals.',
			'fix_url' => 'https://example.com/fix',
		);

		update_option( 'woocommerce_price_num_decimals', '2' );
		$this->assertSame( CheckResult::STATUS_PASSED, $this->run_checks( array( $check ) )['decimals']['status'] );

		update_option( 'woocommerce_price_num_decimals', '0' );
		$result = $this->run_checks( array( $check ) )['decimals'];

		$this->assertSame( CheckResult::STATUS_FAILED, $result['status'] );
		$this->assertSame( 'Use two decimals.', $result['message'] );
		$this->assertSame( 'https://example.com/fix', $result['fix_url'] );
		$this->assertSame( SetupCheck::SEVERITY_REQUIRED, $result['severity'] );
	}

	public function test_option_check_fails_on_an_invalid_value(): void {
		$check = array(
			'id'      => 'decimals',
			'option'  => 'woocommerce_price_num_decimals',
			'invalid' => array( '0' ),
		);

		update_option( 'woocommerce_price_num_decimals', '0' );
		$this->assertSame( CheckResult::STATUS_FAILED, $this->run_checks( array( $check ) )['decimals']['status'] );

		update_option( 'woocommerce_price_num_decimals', '3' );
		$this->assertSame( CheckResult::STATUS_PASSED, $this->run_checks( array( $check ) )['decimals']['status'] );
	}

	public function test_setting_check_reads_the_setting_from_the_page_owner(): void {
		$gateway = new TestGateway();
		$check   = array(
			'id'      => 'enabled',
			'setting' => 'enabled',
			'valid'   => array( 'yes' ),
		);

		$gateway->update_option( 'enabled', 'no' );
		$this->assertSame( CheckResult::STATUS_FAILED, $this->run_checks( array( $check ), $gateway )['enabled']['status'] );

		$gateway->update_option( 'enabled', 'yes' );
		$this->assertSame( CheckResult::STATUS_PASSED, $this->run_checks( array( $check ), $gateway )['enabled']['status'] );

		// Without an owner the setting cannot be read, so the check is left out.
		$this->assertArrayNotHasKey( 'enabled', $this->run_checks( array( $check ) ) );
	}

	public function test_callback_check_supports_bool_result_and_skip(): void {
		$results = $this->run_checks(
			array(
				array(
					'id'       => 'bool',
					'callback' => '__return_false',
					'message'  => 'Nope.',
				),
				array(
					'id'       => 'result',
					'callback' => function () {
						return CheckResult::failed( 'Custom message.', 'https://example.com/custom', 'Go' );
					},
				),
				array(
					'id'       => 'skipped',
					'callback' => '__return_null',
				),
			)
		);

		$this->assertSame( 'Nope.', $results['bool']['message'] );
		$this->assertSame( 'Custom message.', $results['result']['message'] );
		$this->assertSame( 'Go', $results['result']['fix_label'] );
		$this->assertArrayNotHasKey( 'skipped', $results );
	}

	public function test_custom_check_class_is_run(): void {
		$check = new class() implements SetupCheck {
			public function get_id(): string {
				return 'custom';
			}

			public function get_label(): string {
				return 'Custom check';
			}

			public function get_severity(): string {
				return self::SEVERITY_INFO;
			}

			public function run(): CheckResult {
				return CheckResult::failed( 'Heads up.' );
			}
		};

		$result = $this->run_checks( array( $check ) )['custom'];

		$this->assertSame( 'Custom check', $result['label'] );
		$this->assertSame( SetupCheck::SEVERITY_INFO, $result['severity'] );
		$this->assertSame( CheckResult::STATUS_FAILED, $result['status'] );
	}

	public function test_throwing_check_gets_the_error_status_and_fires_the_error_action(): void {
		$reported = array();
		$listener = function ( $id, $exception ) use ( &$reported ) {
			$reported[] = array( $id, $exception->getMessage() );
		};
		add_action( 'krokedil_settings_page_render_error', $listener, 10, 2 );

		$results = $this->run_checks(
			array(
				array(
					'id'       => 'broken',
					'callback' => function () {
						throw new \RuntimeException( 'Boom' );
					},
				),
				array(
					'id'       => 'fine',
					'callback' => '__return_true',
				),
				array( 'label' => 'No id' ),
			)
		);

		remove_action( 'krokedil_settings_page_render_error', $listener, 10 );

		$this->assertSame( CheckResult::STATUS_ERROR, $results['broken']['status'] );
		$this->assertSame( CheckResult::STATUS_PASSED, $results['fine']['status'] );
		$this->assertCount( 2, $results );
		$this->assertSame( array( 'broken', 'Boom' ), $reported[0] );
		$this->assertCount( 2, $reported );
	}

	public function test_results_keep_the_registered_order_and_failed_results_are_sorted_by_severity(): void {
		$failing = function ( string $id, string $severity ): array {
			return array(
				'id'       => $id,
				'severity' => $severity,
				'callback' => '__return_false',
			);
		};

		$registry = ( new Registry( 'test_page' ) )->add(
			array(
				array(
					'id'       => 'passed',
					'callback' => '__return_true',
				),
				$failing( 'info', SetupCheck::SEVERITY_INFO ),
				$failing( 'recommended', SetupCheck::SEVERITY_RECOMMENDED ),
				$failing( 'required_1', SetupCheck::SEVERITY_REQUIRED ),
				$failing( 'required_2', SetupCheck::SEVERITY_REQUIRED ),
			)
		);

		$this->assertSame( array( 'passed', 'info', 'recommended', 'required_1', 'required_2' ), array_column( $registry->get_results(), 'id' ) );
		$this->assertSame( array( 'required_1', 'required_2', 'recommended', 'info' ), array_column( $registry->get_failed(), 'id' ) );
		$this->assertSame( array( 'recommended' ), array_column( $registry->get_failed( SetupCheck::SEVERITY_RECOMMENDED ), 'id' ) );
	}

	public function test_owner_can_be_a_closure_that_is_resolved_when_the_checks_run(): void {
		$gateway = new TestGateway();
		$gateway->update_option( 'enabled', 'yes' );

		$results = $this->run_checks(
			array( Checks::enabled() ),
			function () use ( $gateway ) {
				return $gateway;
			}
		);

		$this->assertSame( CheckResult::STATUS_PASSED, $results['enabled']['status'] );
	}

	public function test_check_docs_url_falls_back_to_the_default_docs_url(): void {
		$results = ( new Registry( 'test_page' ) )
			->add(
				array(
					Checks::https( array( 'docs_url' => 'https://example.com/https' ) ),
					Checks::pretty_permalinks(),
				)
			)
			->set_args( array( 'docs_url' => 'https://example.com/docs' ) )
			->get_results();

		$this->assertSame( 'https://example.com/https', $results[0]['docs_url'] );
		$this->assertSame( 'https://example.com/docs', $results[1]['docs_url'] );
		$this->assertNotSame( '', $results[0]['description'] );
	}

	public function test_built_in_checks_follow_the_store_settings(): void {
		$gateway = new TestGateway();
		$terms   = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);

		update_option( 'woocommerce_terms_page_id', $terms );
		update_option( 'woocommerce_price_num_decimals', '0' );
		update_option( 'woocommerce_currency', 'SEK' );
		update_option( 'permalink_structure', '' );
		$gateway->update_option( 'title', '' );

		$results = $this->run_checks(
			array(
				Checks::terms_page(),
				Checks::price_decimals( 2 ),
				Checks::currency( array( 'SEK', 'EUR' ) ),
				Checks::currency( array( 'USD' ), array( 'id' => 'currency_usd' ) ),
				Checks::pretty_permalinks(),
				Checks::credentials( array( 'title' ) ),
				Checks::credentials( '__return_true', array( 'id' => 'credentials_callable' ) ),
			),
			$gateway
		);

		$this->assertSame( CheckResult::STATUS_PASSED, $results['terms_page']['status'] );
		$this->assertSame( CheckResult::STATUS_FAILED, $results['price_decimals']['status'] );
		$this->assertSame( SetupCheck::SEVERITY_RECOMMENDED, $results['price_decimals']['severity'] );
		$this->assertSame( CheckResult::STATUS_PASSED, $results['currency']['status'] );
		$this->assertSame( CheckResult::STATUS_FAILED, $results['currency_usd']['status'] );
		$this->assertSame( CheckResult::STATUS_FAILED, $results['pretty_permalinks']['status'] );
		$this->assertSame( CheckResult::STATUS_FAILED, $results['credentials']['status'] );
		$this->assertSame( CheckResult::STATUS_PASSED, $results['credentials_callable']['status'] );

		wp_update_post(
			array(
				'ID'          => $terms,
				'post_status' => 'draft',
			)
		);
		$draft = $this->run_checks( array( Checks::terms_page() ) )['terms_page'];
		$this->assertSame( CheckResult::STATUS_FAILED, $draft['status'] );
		$this->assertStringContainsString( 'is not published', $draft['message'] );
		$this->assertSame( 'Edit the page', $draft['fix_label'] );

		update_option( 'woocommerce_terms_page_id', '' );
		$missing = $this->run_checks( array( Checks::terms_page() ) )['terms_page'];
		$this->assertSame( CheckResult::STATUS_FAILED, $missing['status'] );
		$this->assertSame( 'Select a terms and conditions page', $missing['fix_label'] );
	}

	public function test_built_in_check_arguments_can_be_overridden(): void {
		update_option( 'woocommerce_price_num_decimals', '0' );

		$result = $this->run_checks(
			array(
				Checks::price_decimals(
					2,
					array(
						'severity' => SetupCheck::SEVERITY_REQUIRED,
						'label'    => 'My label',
					)
				),
			)
		)['price_decimals'];

		$this->assertSame( SetupCheck::SEVERITY_REQUIRED, $result['severity'] );
		$this->assertSame( 'My label', $result['label'] );
	}

	/**
	 * Registers a page with setup checks on the SettingsPage singleton and returns the rendered support sidebar.
	 *
	 * @param string            $page_id The page id.
	 * @param array<int, mixed> $checks  The checks.
	 *
	 * @return string
	 */
	private function render_sidebar( string $page_id, array $checks ): string {
		ob_start();
		SettingsPage::get_instance()
			->set_plugin_name( 'Test plugin' )
			->add_setup_checks( $page_id, $checks, array( 'docs_url' => 'https://example.com/docs' ) )
			->register_page(
				$page_id,
				array(
					'support' => array( 'links' => array() ),
					'sidebar' => array(
						'boxes' => array(
							array(
								'id'      => 'remote_box',
								'title'   => 'Remote box',
								'content' => '<p>From the remote config.</p>',
							),
						),
					),
				),
				new TestGateway()
			)
			->output( $page_id );

		// Navigation reads the subsection with filter_input(), which a test cannot set, so render the support sidebar directly.
		SettingsPage::get_instance()->support( $page_id )->output_sidebar();

		return (string) ob_get_clean();
	}

	public function test_setup_checks_box_is_appended_and_lists_only_the_failed_checks(): void {
		update_option( 'woocommerce_price_num_decimals', '0' );
		update_option( 'permalink_structure', '' );

		$html = $this->render_sidebar(
			'failing_page',
			array(
				Checks::price_decimals( 2 ),
				Checks::pretty_permalinks(),
				array(
					'id'       => 'always_fine',
					'label'    => 'Always fine',
					'callback' => '__return_true',
				),
				array(
					'id'       => 'markup',
					'label'    => 'Label <b>bold</b>',
					'message'  => 'See <a href="https://example.com/a">this</a><script>alert(1)</script>',
					'callback' => '__return_false',
				),
			)
		);

		$this->assertStringContainsString( 'From the remote config.', $html );
		$this->assertLessThan( strpos( $html, 'Store setup check' ), strpos( $html, 'Remote box' ) );
		$this->assertStringContainsString( 'Checks store settings that Test plugin requires or recommends.', $html );
		$this->assertStringContainsString( 'krokedil_settings__setup_checks_banner--required', $html );
		$this->assertStringContainsString( '2 required checks fail.', $html );
		$this->assertStringContainsString( 'Pretty permalinks configured (required)', $html );
		$this->assertStringContainsString( 'Number of decimals set to 2 (recommended)', $html );
		$this->assertStringContainsString( 'Currently set to 0.', $html );
		$this->assertStringContainsString( 'Read more about this recommendation in the <a href="https://example.com/docs"', $html );
		$this->assertStringContainsString( 'View full report', $html );
		$this->assertStringContainsString( 'page=wc-status#krokedil-setup-checks-failing_page', $html );

		// Passed checks are left out of the box, and markup is escaped or stripped.
		$this->assertStringNotContainsString( 'Always fine', $html );
		$this->assertStringContainsString( 'Label &lt;b&gt;bold&lt;/b&gt;', $html );
		$this->assertStringContainsString( '<a href="https://example.com/a">this</a>', $html );
		$this->assertStringNotContainsString( '<script>', $html );

		// The required check is listed before the recommended one.
		$this->assertLessThan( strpos( $html, 'Number of decimals set to 2' ), strpos( $html, 'Pretty permalinks configured' ) );

		$results = SettingsPage::get_instance()->get_setup_check_results( 'failing_page' );
		$this->assertSame( array( 'price_decimals', 'pretty_permalinks', 'always_fine', 'markup' ), array_column( $results, 'id' ) );
		$this->assertSame( array(), SettingsPage::get_instance()->get_setup_check_results( 'unknown_page' ) );
	}

	public function test_setup_checks_box_shows_a_passed_banner_when_all_checks_pass(): void {
		$html = $this->render_sidebar(
			'passing_page',
			array(
				array(
					'id'       => 'one',
					'callback' => '__return_true',
				),
				array(
					'id'       => 'two',
					'callback' => '__return_true',
				),
			)
		);

		$this->assertStringContainsString( 'krokedil_settings__setup_checks_banner--passed', $html );
		$this->assertStringContainsString( 'All 2 checks passed.', $html );
		$this->assertStringNotContainsString( 'krokedil_settings__setup_check"', $html );
	}

	public function test_setup_checks_are_added_to_the_system_report(): void {
		update_option( 'woocommerce_price_num_decimals', '0' );

		SettingsPage::get_instance()
			->set_plugin_name( 'Test plugin' )
			->add_setup_checks(
				'report_page',
				array(
					Checks::price_decimals( 2 ),
					array(
						'id'       => 'fine',
						'label'    => 'Fine check',
						'callback' => '__return_true',
					),
				)
			);

		ob_start();
		SettingsPage::get_instance()->output_setup_checks_report();
		$html = (string) ob_get_clean();

		$this->assertNotFalse( has_action( 'woocommerce_system_status_report', array( SettingsPage::get_instance(), 'output_setup_checks_report' ) ) );

		$this->assertStringContainsString( 'id="krokedil-setup-checks-report_page"', $html );
		$this->assertStringContainsString( 'data-export-label="Test plugin – Store setup check"', $html );
		$this->assertStringContainsString( 'data-export-label="Number of decimals set to 2 (recommended)"', $html );
		$this->assertStringContainsString( 'Currently set to 0.', $html );
		$this->assertStringContainsString( 'Change the number of decimals', $html );
		$this->assertStringContainsString( 'data-export-label="Fine check (required)"', $html );
		$this->assertStringContainsString( 'dashicons-yes', $html );
	}

	/**
	 * Registers a failing required check for a gateway and returns the output of the given notice callback.
	 *
	 * The callbacks are called directly, since firing the hooks also runs WooCommerce's own callbacks.
	 *
	 * @param string $page_id The page id, also used as the check label.
	 * @param string $enabled The enabled setting of the gateway, 'yes' or 'no'.
	 * @param string $method  The SettingsPage method to call.
	 *
	 * @return string
	 */
	private function render_notice( string $page_id, string $enabled, string $method ): string {
		$gateway          = new TestGateway();
		$gateway->enabled = $enabled;

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		SettingsPage::get_instance()
			->set_plugin_name( 'Test plugin' )
			->add_setup_checks(
				$page_id,
				array(
					array(
						'id'        => 'failing',
						'label'     => 'Label of ' . $page_id,
						'message'   => 'Something is wrong.',
						'fix_url'   => 'https://example.com/fix',
						'fix_label' => 'Fix it here',
						'callback'  => '__return_false',
					),
					array(
						'id'       => 'recommended',
						'label'    => 'Recommended of ' . $page_id,
						'severity' => SetupCheck::SEVERITY_RECOMMENDED,
						'callback' => '__return_false',
					),
				),
				array( 'owner' => $gateway )
			);

		ob_start();
		SettingsPage::get_instance()->$method();

		return (string) ob_get_clean();
	}

	public function test_admin_notice_lists_failed_required_checks_while_the_gateway_is_enabled(): void {
		$html = $this->render_notice( 'notice_enabled_page', 'yes', 'output_setup_checks_notices' );

		$this->assertStringContainsString( 'Test plugin store setup, 1 required check fails.', $html );
		$this->assertStringContainsString( 'Label of notice_enabled_page (required)', $html );
		$this->assertStringContainsString( 'Something is wrong.', $html );
		$this->assertStringContainsString( '<a href="https://example.com/fix">Fix it here</a>', $html );
		$this->assertStringContainsString( 'page=wc-status#krokedil-setup-checks-notice_enabled_page', $html );
		$this->assertStringNotContainsString( 'Recommended of notice_enabled_page', $html );
		$this->assertStringNotContainsString( 'ismiss', $html );
	}

	public function test_admin_notice_is_not_shown_while_the_gateway_is_disabled(): void {
		$html = $this->render_notice( 'notice_disabled_page', 'no', 'output_setup_checks_notices' );

		$this->assertStringNotContainsString( 'Label of notice_disabled_page', $html );
	}

	public function test_admin_notice_is_not_shown_to_users_who_cannot_manage_woocommerce(): void {
		$this->render_notice( 'notice_capability_page', 'yes', 'output_setup_checks_notices' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		ob_start();
		SettingsPage::get_instance()->output_setup_checks_notices();
		$html = (string) ob_get_clean();

		$this->assertStringNotContainsString( 'krokedil-setup-checks-notice', $html );
	}

	public function test_payments_list_notice_is_shown_for_a_disabled_gateway_but_not_on_a_gateway_section(): void {
		global $current_section;

		$current_section = '';
		$html            = $this->render_notice( 'payments_list_page', 'no', 'output_setup_checks_payments_notices' );

		$this->assertStringContainsString( 'Label of payments_list_page (required)', $html );
		$this->assertStringContainsString( 'krokedil-setup-checks-notice inline', $html );

		$current_section = 'krokedil_test_gateway';
		ob_start();
		SettingsPage::get_instance()->output_setup_checks_payments_notices();
		$section_html    = (string) ob_get_clean();
		$current_section = '';

		$this->assertSame( 10, has_action( 'admin_notices', array( SettingsPage::get_instance(), 'output_setup_checks_notices' ) ) );
		$this->assertSame( 5, has_action( 'woocommerce_settings_checkout', array( SettingsPage::get_instance(), 'output_setup_checks_payments_notices' ) ) );

		$this->assertStringNotContainsString( 'krokedil-setup-checks-notice', $section_html );
	}

	public function test_notice_can_be_turned_off(): void {
		$registry = ( new Registry( 'no_notice_page' ) )
			->add(
				array(
					array(
						'id'       => 'failing',
						'callback' => '__return_false',
					),
				)
			)
			->set_args( array( 'notice' => false ) );

		ob_start();
		$registry->output_notice();

		$this->assertSame( '', (string) ob_get_clean() );
	}

	public function test_admin_notice_is_left_out_where_the_sidebar_box_is_shown(): void {
		$_GET['page']       = 'wc-settings';
		$_GET['section']    = 'notice_box_page';
		$_GET['subsection'] = 'support';
		$on_support_tab     = $this->render_notice( 'notice_box_page', 'yes', 'output_setup_checks_notices' );

		// The main tab of the settings page has no sidebar, so the notice stays.
		unset( $_GET['subsection'] );
		ob_start();
		SettingsPage::get_instance()->output_setup_checks_notices();
		$on_main_tab = (string) ob_get_clean();

		// Another plugin's Support tab does not show this plugin's box.
		$_GET['section']    = 'another_gateway';
		$_GET['subsection'] = 'support';
		ob_start();
		SettingsPage::get_instance()->output_setup_checks_notices();
		$on_other_section = (string) ob_get_clean();

		unset( $_GET['page'], $_GET['section'], $_GET['subsection'] );

		$this->assertStringNotContainsString( 'Label of notice_box_page', $on_support_tab );
		$this->assertStringContainsString( 'Label of notice_box_page', $on_main_tab );
		$this->assertStringContainsString( 'Label of notice_box_page', $on_other_section );
	}

	public function test_section_argument_is_used_when_the_page_id_differs_from_the_settings_section(): void {
		$registry = ( new Registry( 'my_page_id' ) )->set_args( array( 'section' => 'my_gateway' ) );

		$_GET['page']       = 'wc-settings';
		$_GET['section']    = 'my_gateway';
		$_GET['subsection'] = 'addons';
		$shown              = $registry->is_box_on_current_page();
		unset( $_GET['page'], $_GET['section'], $_GET['subsection'] );

		$this->assertTrue( $shown );
		$this->assertFalse( $registry->is_box_on_current_page() );
	}
}
