<?php

namespace Tests\Integration;

use Krokedil\SettingsPage\WcSettingsPage;
use Tests\Integration\Fixtures\ThrowingSettingsPage;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Integration tests for the base WooCommerce settings page.
 */
class WcSettingsPageTest extends WPTestCase {

	/**
	 * A representative set of WooCommerce admin field definitions.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function form_fields(): array {
		return array(
			array(
				'type'  => 'title',
				'id'    => 'krokedil_test_section',
				'title' => 'Test Section',
			),
			array(
				'type'    => 'text',
				'id'      => 'krokedil_test_text',
				'title'   => 'Test Text Field',
				'default' => 'hello world',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'krokedil_test_section_end',
			),
		);
	}

	public function test_defaults_are_applied_when_no_args_are_given(): void {
		$page = new WcSettingsPage( 'krokedil_defaults' );

		$this->assertSame( 'Settings', $page->get_page_title() );
		$this->assertSame( '', $page->get_page_description() );
	}

	public function test_page_title_and_description_come_from_args(): void {
		$page = new WcSettingsPage(
			'krokedil_args',
			array(
				'page_title'       => 'My Title',
				'page_description' => 'My description.',
			)
		);

		$this->assertSame( 'My Title', $page->get_page_title() );
		$this->assertSame( 'My description.', $page->get_page_description() );
	}

	public function test_styled_output_registers_field_filters(): void {
		// Sanity check: the filters should not be present without styled output.
		$plain = new WcSettingsPage( 'krokedil_plain' );
		$this->assertFalse( has_filter( 'woocommerce_admin_field_krokedil_section_start' ) );

		new WcSettingsPage( 'krokedil_styled', array( 'styled_output' => true ) );

		$this->assertNotFalse(
			has_filter( 'woocommerce_generate_krokedil_section_start_html' ),
			'styled_output should register the section start HTML generator.'
		);
		$this->assertNotFalse(
			has_filter( 'woocommerce_admin_field_krokedil_section_start' ),
			'styled_output should register the section start admin field renderer.'
		);
		$this->assertNotFalse( has_filter( 'woocommerce_generate_krokedil_button_html' ) );
		$this->assertNotFalse( has_filter( 'woocommerce_admin_field_krokedil_radio' ) );
	}

	public function test_default_output_page_content_renders_the_form_fields(): void {
		$page = new WcSettingsPage( 'krokedil_render', array(), $this->form_fields() );

		ob_start();
		$page->output_page_content();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( '<table class="form-table">', $html );
		$this->assertStringContainsString( 'Test Text Field', $html );
		$this->assertStringContainsString( 'krokedil_test_text', $html );
	}

	public function test_output_renders_the_full_page_wrapper(): void {
		$page = new WcSettingsPage( 'krokedil_full', array(), $this->form_fields() );

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'krokedil_settings__header', $html );
		$this->assertStringContainsString( 'id="krokedil_settings_krokedil_full"', $html );
		$this->assertStringContainsString( 'krokedil_settings__wrapper', $html );
		// The default content (form fields) should be rendered inside the wrapper.
		$this->assertStringContainsString( 'Test Text Field', $html );
		// No error notice on the happy path.
		$this->assertStringNotContainsString( 'notice notice-error', $html );
	}

	public function test_render_error_falls_back_and_shows_a_notice(): void {
		$fired = array();
		add_action(
			'krokedil_settings_page_render_error',
			function ( $id ) use ( &$fired ) {
				$fired[] = $id;
			}
		);

		$page = new ThrowingSettingsPage( 'krokedil_throwing', array(), $this->form_fields() );

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		// The render error action fired with the page id.
		$this->assertContains( 'krokedil_throwing', $fired );
		// The page did not blank out: it shows the error notice...
		$this->assertStringContainsString( 'notice notice-error', $html );
		$this->assertStringContainsString( 'An error occurred while rendering this settings page.', $html );
		// ...and falls back to WooCommerce's default field rendering.
		$this->assertStringContainsString( 'Test Text Field', $html );
	}

	public function test_render_error_uses_custom_notice_and_string_fallback(): void {
		$page = new ThrowingSettingsPage(
			'krokedil_custom_fallback',
			array(
				'error_notice'     => 'Custom failure message.',
				'fallback_content' => '<p class="my-fallback">Fallback body.</p>',
			),
			$this->form_fields()
		);

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Custom failure message.', $html );
		$this->assertStringContainsString( 'my-fallback', $html );
		$this->assertStringContainsString( 'Fallback body.', $html );
	}

	public function test_render_error_supports_a_callable_fallback(): void {
		$page = new ThrowingSettingsPage(
			'krokedil_callable_fallback',
			array(
				'fallback_content' => function () {
					echo '<p class="callable-fallback">From callable.</p>';
				},
			),
			$this->form_fields()
		);

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'callable-fallback', $html );
		$this->assertStringContainsString( 'From callable.', $html );
		$this->assertStringContainsString( 'notice notice-error', $html );
	}

	public function test_section_start_and_end_html_helpers(): void {
		$start = WcSettingsPage::krokedil_section_start_html(
			'',
			'my_section',
			array(
				'class'       => 'my-class',
				'title'       => 'Section Title',
				'description' => 'Section description.',
			)
		);

		$this->assertStringContainsString( 'id="krokedil_section_my_section"', $start );
		$this->assertStringContainsString( 'my-class', $start );
		$this->assertStringContainsString( 'Section Title', $start );
		$this->assertStringContainsString( 'Section description.', $start );

		$end = WcSettingsPage::krokedil_section_end_html( '', 'my_section', array() );
		$this->assertStringContainsString( '</table>', $end );
	}

	public function test_button_html_helper(): void {
		$html = WcSettingsPage::krokedil_button_html(
			'',
			'my_button',
			array(
				'id'          => 'my_button',
				'title'       => 'Click me',
				'description' => 'Button description.',
				'alignment'   => 'right',
				'class'       => 'btn-class',
			)
		);

		$this->assertStringContainsString( 'id="krokedil_button_my_button"', $html );
		$this->assertStringContainsString( 'Click me', $html );
		$this->assertStringContainsString( 'Button description.', $html );
		$this->assertStringContainsString( 'align-right', $html );
	}

	public function test_radio_html_helper_marks_the_default_as_checked(): void {
		$html = WcSettingsPage::krokedil_radio_html(
			'',
			'my_radio',
			array(
				'id'      => 'my_radio',
				'default' => 'b',
				'options' => array(
					array(
						'value' => 'a',
						'title' => 'Option A',
					),
					array(
						'value' => 'b',
						'title' => 'Option B',
					),
				),
			)
		);

		$this->assertStringContainsString( 'name="my_radio"', $html );
		$this->assertStringContainsString( 'Option A', $html );
		$this->assertStringContainsString( 'Option B', $html );
		// The default ("b") should be the checked option.
		$this->assertMatchesRegularExpression( '/value="b"\s*checked/', $html );
		$this->assertDoesNotMatchRegularExpression( '/value="a"\s*checked/', $html );
	}

	public function test_divider_html_helper(): void {
		$html = WcSettingsPage::krokedil_divider_html( '', 'my_divider', array( 'id' => 'my_divider' ) );

		$this->assertStringContainsString( 'form-section-my_divider-end', $html );
		$this->assertStringContainsString( '<hr>', $html );
	}
}
