<?php

namespace Tests\Integration;

use Krokedil\SettingsPage\Gateway;
use Tests\Integration\Fixtures\TestGateway;
use lucatume\WPBrowser\TestCase\WPTestCase;

/**
 * Integration tests for the settings page sidebar, primarily the sidebar boxes.
 */
class SidebarTest extends WPTestCase {

	/**
	 * Renders a Gateway settings page with the given sidebar args and returns the HTML.
	 *
	 * @param array<string, mixed> $sidebar The sidebar args.
	 *
	 * @return string
	 */
	private function render_page_with_sidebar( array $sidebar ): string {
		$gateway = new TestGateway();
		$page    = new Gateway( $gateway, array( 'sidebar' => $sidebar ) );

		ob_start();
		$page->output();
		return (string) ob_get_clean();
	}

	public function test_sidebar_without_boxes_renders_no_box_markup(): void {
		$html = $this->render_page_with_sidebar(
			array(
				'plugin_resources' => array(
					'links' => array(
						array(
							'href' => 'https://docs.krokedil.com/',
							'text' => 'Documentation',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( 'Plugin resources', $html );
		$this->assertStringNotContainsString( 'krokedil_settings__sidebar_box', $html );
	}

	public function test_sidebar_box_with_string_content_renders_above_plugin_resources(): void {
		$html = $this->render_page_with_sidebar(
			array(
				'boxes'            => array(
					array(
						'id'      => 'status_box',
						'title'   => 'Store setup check',
						'content' => '<p>All checks passed.</p>',
						'class'   => 'my-status-box',
					),
				),
				'plugin_resources' => array(
					'links' => array(
						array(
							'href' => 'https://docs.krokedil.com/',
							'text' => 'Documentation',
						),
					),
				),
			)
		);

		$this->assertStringContainsString( 'krokedil_settings__sidebar_box my-status-box', $html );
		$this->assertStringContainsString( 'Store setup check', $html );
		$this->assertStringContainsString( '<p>All checks passed.</p>', $html );

		// The box must render above the existing Plugin resources content.
		$box_position       = strpos( $html, 'Store setup check' );
		$resources_position = strpos( $html, 'Plugin resources' );
		$this->assertNotFalse( $box_position );
		$this->assertNotFalse( $resources_position );
		$this->assertLessThan( $resources_position, $box_position );
	}

	public function test_sidebar_box_with_callable_content_renders_its_output(): void {
		$html = $this->render_page_with_sidebar(
			array(
				'boxes' => array(
					array(
						'id'      => 'callable_box',
						'title'   => 'Callable box',
						'content' => function (): void {
							echo '<p class="callable-content">Rendered by a callable.</p>';
						},
					),
				),
			)
		);

		$this->assertStringContainsString( 'Callable box', $html );
		$this->assertStringContainsString( '<p class="callable-content">Rendered by a callable.</p>', $html );
	}

	public function test_multiple_boxes_render_in_order(): void {
		$html = $this->render_page_with_sidebar(
			array(
				'boxes' => array(
					array(
						'id'      => 'first_box',
						'title'   => 'First box',
						'content' => '<p>First content.</p>',
					),
					array(
						'id'      => 'second_box',
						'title'   => 'Second box',
						'content' => '<p>Second content.</p>',
					),
				),
			)
		);

		$first_position  = strpos( $html, 'First box' );
		$second_position = strpos( $html, 'Second box' );
		$this->assertNotFalse( $first_position );
		$this->assertNotFalse( $second_position );
		$this->assertLessThan( $second_position, $first_position );
	}

	public function test_throwing_box_is_skipped_and_fires_the_render_error_action(): void {
		$fired_id        = null;
		$fired_throwable = null;

		add_action(
			'krokedil_settings_page_render_error',
			function ( $id, $throwable ) use ( &$fired_id, &$fired_throwable ): void {
				$fired_id        = $id;
				$fired_throwable = $throwable;
			},
			10,
			2
		);

		$html = $this->render_page_with_sidebar(
			array(
				'boxes'            => array(
					array(
						'id'      => 'broken_box',
						'title'   => 'Broken box',
						'content' => function (): void {
							throw new \RuntimeException( 'Box render failure.' );
						},
					),
				),
				'plugin_resources' => array(
					'links' => array(
						array(
							'href' => 'https://docs.krokedil.com/',
							'text' => 'Documentation',
						),
					),
				),
			)
		);

		// The broken box is skipped entirely, but the rest of the page still renders.
		$this->assertStringNotContainsString( 'Broken box', $html );
		$this->assertStringContainsString( 'Plugin resources', $html );
		$this->assertStringContainsString( '<table class="form-table">', $html );

		$this->assertSame( 'broken_box', $fired_id );
		$this->assertInstanceOf( \RuntimeException::class, $fired_throwable );
	}
}
