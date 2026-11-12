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
		$results = ( new Registry( 'test_page' ) )->add( $checks )->set_owner( $owner )->get_results();

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

	public function test_results_are_sorted_by_what_needs_attention_first(): void {
		$failing = function ( string $id, string $severity ): array {
			return array(
				'id'       => $id,
				'severity' => $severity,
				'callback' => '__return_false',
			);
		};

		$results = $this->run_checks(
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

		$this->assertSame( array( 'required_1', 'required_2', 'recommended', 'info', 'passed' ), array_keys( $results ) );
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
		$this->assertSame( 'Edit page', $draft['fix_label'] );

		update_option( 'woocommerce_terms_page_id', '' );
		$missing = $this->run_checks( array( Checks::terms_page() ) )['terms_page'];
		$this->assertSame( CheckResult::STATUS_FAILED, $missing['status'] );
		$this->assertSame( 'Select page', $missing['fix_label'] );
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

	public function test_setup_checks_box_is_appended_to_the_existing_sidebar_boxes(): void {
		$gateway = new TestGateway();

		update_option( 'woocommerce_price_num_decimals', '0' );

		ob_start();
		SettingsPage::get_instance()
			->register_page(
				'setup_checks_page',
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
				$gateway
			)
			->add_setup_checks(
				'setup_checks_page',
				array(
					Checks::price_decimals( 2 ),
					array(
						'id'       => 'always_fine',
						'label'    => 'Always <b>fine</b>',
						'callback' => '__return_true',
					),
				)
			)
			->output( 'setup_checks_page' );

		// Navigation reads the subsection with filter_input(), which a test cannot set, so render the support sidebar directly.
		SettingsPage::get_instance()->support( 'setup_checks_page' )->output_sidebar();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'From the remote config.', $html );
		$this->assertStringContainsString( 'krokedil_settings__setup_checks_box', $html );
		$this->assertStringContainsString( '1 of 2 checks passed', $html );
		$this->assertStringContainsString( 'krokedil_settings__setup_check--recommended', $html );
		$this->assertStringContainsString( 'krokedil_settings__setup_check--passed', $html );
		$this->assertStringContainsString( 'Always &lt;b&gt;fine&lt;/b&gt;', $html );
		$this->assertLessThan( strpos( $html, 'Store setup' ), strpos( $html, 'Remote box' ) );

		$results = SettingsPage::get_instance()->get_setup_check_results( 'setup_checks_page' );
		$this->assertSame( array( 'price_decimals', 'always_fine' ), array_column( $results, 'id' ) );
		$this->assertSame( array(), SettingsPage::get_instance()->get_setup_check_results( 'unknown_page' ) );
	}
}
