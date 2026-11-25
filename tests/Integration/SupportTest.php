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

	public function test_support_button_is_left_out_when_helpscout_is_off(): void {
		$html = $this->render_support( $this->sections_config(), true );

		$this->assertStringNotContainsString( 'support-button', $html );
	}
}
