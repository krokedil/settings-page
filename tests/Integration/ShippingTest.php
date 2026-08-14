<?php

namespace Tests\Integration;

use Krokedil\SettingsPage\Shipping;
use Tests\Integration\Fixtures\TestShippingMethod;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Integration tests for the Shipping settings page.
 */
class ShippingTest extends WPTestCase {

	public function test_id_comes_from_the_shipping_method(): void {
		$shipping = new TestShippingMethod();
		$page     = new Shipping( $shipping );

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'id="krokedil_settings_krokedil_test_shipping"', $html );
	}

	public function test_title_and_description_default_to_the_shipping_method(): void {
		$shipping = new TestShippingMethod();
		$page     = new Shipping( $shipping );

		$this->assertSame( 'Krokedil Test Shipping', $page->get_page_title() );
		$this->assertSame( 'A test shipping method for integration tests.', $page->get_page_description() );
	}

	public function test_title_and_description_can_be_overridden_by_args(): void {
		$shipping = new TestShippingMethod();
		$page     = new Shipping(
			$shipping,
			array(
				'page_title'       => 'Custom Shipping Title',
				'page_description' => 'Custom shipping description.',
			)
		);

		$this->assertSame( 'Custom Shipping Title', $page->get_page_title() );
		$this->assertSame( 'Custom shipping description.', $page->get_page_description() );
	}

	public function test_output_page_content_renders_the_shipping_settings(): void {
		$shipping = new TestShippingMethod();
		$page     = new Shipping( $shipping );

		ob_start();
		$page->output_page_content();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<table class="form-table">', $html );
		$this->assertStringContainsString( 'Shipping Title Field', $html );
		$this->assertStringContainsString( 'woocommerce_krokedil_test_shipping_title', $html );
		// The select field options should be present.
		$this->assertStringContainsString( 'Taxable', $html );
	}
}
