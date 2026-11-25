<?php

namespace Tests\Integration;

use Krokedil\SettingsPage\Support;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Integration tests for the support tab, primarily the support sections.
 */
class SupportTest extends WPTestCase {

	/**
	 * Renders the support tab with the given support config and returns the HTML.
	 *
	 * @param array<string, mixed> $support       The support config.
	 * @param bool                 $styled_output Whether to style the output.
	 *
	 * @return string
	 */
	private function render_support( array $support, bool $styled_output ): string {
		$page = new Support( $support, array(), null, $styled_output );

		ob_start();
		$page->output();
		return (string) ob_get_clean();
	}

	/**
	 * A support config with two sections.
	 *
	 * @return array<string, mixed>
	 */
	private function sections_config(): array {
		return array(
			'use_helpscout' => 'no',
			'sections'      => array(
				array(
					'title'       => array( 'en' => 'Documentation' ),
					'description' => 'Read the docs.',
					'content'     => array(
						array(
							'type' => 'link',
							'text' => 'Get started',
							'href' => 'https://docs.krokedil.com/',
						),
					),
				),
				array(
					'title'   => 'Support',
					'content' => array(
						array(
							'type' => 'text',
							'en'   => 'Contact us.',
						),
					),
				),
			),
		);
	}

	public function test_content_only_config_renders_without_sections(): void {
		$html = $this->render_support(
			array(
				'use_helpscout' => 'no',
				'content'       => array(
					array(
						'type' => 'text',
						'en'   => 'Flat support text.',
					),
				),
			),
			true
		);

		$this->assertStringContainsString( '<p>Flat support text.</p>', $html );
		$this->assertStringNotContainsString( 'krokedil_settings__section', $html );
	}

	public function test_styled_sections_render_as_open_collapsible_boxes(): void {
		$html = $this->render_support( $this->sections_config(), true );

		$this->assertStringContainsString( 'class="krokedil_settings_page styled"', $html );
		$this->assertStringContainsString( 'id="krokedil_section_support_0" class="krokedil_settings__section"', $html );
		$this->assertStringContainsString( 'id="krokedil_section_support_1" class="krokedil_settings__section"', $html );
		$this->assertSame( 2, substr_count( $html, 'krokedil_settings__section_content active' ) );
		$this->assertSame( 2, substr_count( $html, 'dashicons-arrow-up-alt2' ) );
		$this->assertStringContainsString( 'Documentation', $html );
		$this->assertStringContainsString( 'Read the docs.', $html );
		$this->assertStringContainsString( 'Get started', $html );
		$this->assertStringContainsString( 'Contact us.', $html );
	}

	public function test_unstyled_sections_render_as_plain_headings(): void {
		$html = $this->render_support( $this->sections_config(), false );

		$this->assertStringContainsString( 'class="krokedil_settings_page"', $html );
		$this->assertStringContainsString( '<h3>Documentation</h3>', $html );
		$this->assertStringContainsString( '<h3>Support</h3>', $html );
		$this->assertStringNotContainsString( 'krokedil_settings__section_header', $html );
	}

	public function test_support_button_renders_in_last_section(): void {
		$config                  = $this->sections_config();
		$config['use_helpscout'] = 'yes';

		$html = $this->render_support( $config, true );

		$button_position       = strpos( $html, 'support-button' );
		$last_section_position = strpos( $html, 'krokedil_section_support_1' );
		$this->assertNotFalse( $button_position );
		$this->assertNotFalse( $last_section_position );
		$this->assertGreaterThan( $last_section_position, $button_position );
		$this->assertSame( 1, substr_count( $html, 'support-button' ) );
	}

	public function test_sections_replace_the_content(): void {
		$config            = $this->sections_config();
		$config['content'] = array(
			array(
				'type' => 'text',
				'en'   => 'Legacy content for older package versions.',
			),
		);

		$html = $this->render_support( $config, true );

		$this->assertStringNotContainsString( 'Legacy content for older package versions.', $html );
		$this->assertStringContainsString( 'krokedil_section_support_0', $html );
	}

	public function test_support_button_in_sections_has_no_intro_text(): void {
		$config                  = $this->sections_config();
		$config['use_helpscout'] = 'yes';

		$html = $this->render_support( $config, true );

		$this->assertStringNotContainsString( 'If you still need help', $html );
	}

	public function test_support_button_after_content_keeps_intro_text(): void {
		$html = $this->render_support(
			array(
				'content' => array(
					array(
						'type' => 'text',
						'en'   => 'Flat support text.',
					),
				),
			),
			false
		);

		$this->assertStringContainsString( 'If you still need help', $html );
		$this->assertStringContainsString( 'support-button', $html );
	}

	public function test_title_and_box_content_types(): void {
		$html = Support::print_content(
			array(
				'type' => 'title',
				'en'   => 'Kustom support',
			)
		);
		$this->assertSame( '<h4 class="krokedil_settings__content_title">Kustom support</h4>', $html );

		$html = Support::print_content(
			array(
				'type'  => 'box',
				'title' => array( 'en' => 'Before opening a support ticket:' ),
				'items' => array(
					array(
						'type' => 'text',
						'en'   => 'Attach the logs.',
					),
				),
			)
		);
		$this->assertSame(
			'<div class="krokedil_settings__content_box"><p class="krokedil_settings__content_box_title">Before opening a support ticket:</p><p>Attach the logs.</p></div>',
			$html
		);
	}

	public function test_styled_sidebar_renders_resources_with_description(): void {
		$page = new Support(
			array( 'sections' => $this->sections_config()['sections'] ),
			array(
				'plugin_resources' => array(
					'description' => array( 'en' => 'Helpful resources.' ),
					'links'       => array(
						array(
							'href' => 'https://docs.krokedil.com/',
							'text' => 'Plugin product page',
						),
					),
				),
			),
			null,
			true
		);

		ob_start();
		$page->output();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'krokedil_settings__sidebar_header', $html );
		$this->assertStringContainsString( '<p class="krokedil_settings__sidebar_description">Helpful resources.</p>', $html );
		$this->assertStringContainsString( 'Plugin product page', $html );
		$this->assertStringNotContainsString( '&raquo;', $html );
	}

	public function test_set_page_header_sets_title_and_description(): void {
		$page = new Support( $this->sections_config(), array() );
		$page->set_page_header( 'Kustom Checkout', 'Embedded in the checkout.' );

		$this->assertSame( 'Kustom Checkout', $page->get_page_title() );
		$this->assertSame( 'Embedded in the checkout.', $page->get_page_description() );
	}

	public function test_support_button_is_left_out_when_helpscout_is_off(): void {
		$html = $this->render_support( $this->sections_config(), true );

		$this->assertStringNotContainsString( 'support-button', $html );
	}
}
