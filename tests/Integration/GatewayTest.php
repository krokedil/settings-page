<?php

namespace Tests\Integration;

use Krokedil\SettingsPage\Gateway;
use Tests\Integration\Fixtures\TestGateway;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Integration tests for the Gateway settings page.
 */
class GatewayTest extends WPTestCase {

	public function test_id_comes_from_the_gateway(): void {
		$gateway = new TestGateway();
		$page    = new Gateway( $gateway );

		// The page id is protected, but it drives the output wrapper id.
		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'id="krokedil_settings_krokedil_test_gateway"', $html );
	}

	public function test_title_and_description_default_to_the_gateway_method(): void {
		$gateway = new TestGateway();
		$page    = new Gateway( $gateway );

		$this->assertSame( 'Krokedil Test Gateway', $page->get_page_title() );
		$this->assertSame( 'A test gateway for integration tests.', $page->get_page_description() );
	}

	public function test_title_and_description_can_be_overridden_by_args(): void {
		$gateway = new TestGateway();
		$page    = new Gateway(
			$gateway,
			array(
				'page_title'       => 'Custom Gateway Title',
				'page_description' => 'Custom gateway description.',
			)
		);

		$this->assertSame( 'Custom Gateway Title', $page->get_page_title() );
		$this->assertSame( 'Custom gateway description.', $page->get_page_description() );
	}

	public function test_output_page_content_renders_the_gateway_settings(): void {
		$gateway = new TestGateway();
		$page    = new Gateway( $gateway );

		ob_start();
		$page->output_page_content();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<table class="form-table">', $html );
		$this->assertStringContainsString( 'Gateway Title Field', $html );
		// WC_Settings_API prefixes generated field ids with "woocommerce_<id>_".
		$this->assertStringContainsString( 'woocommerce_krokedil_test_gateway_title', $html );
		$this->assertStringContainsString( 'Enable Test Gateway', $html );
	}
}
