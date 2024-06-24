<?php
namespace Krokedil\SettingsPage;

use Krokedil\SettingsPage\Traits\Layout;

defined( 'ABSPATH' ) || exit;

/**
 * Main class for the settings page package.
 */
class Page {
	use Layout;

	/**
	 * The content for the page.
	 *
	 * @var string $content
	 */
	protected $content = '';

	/**
	 * Class constructor.
	 *
	 * @param string $content The content for the page.
	 * @param array  $sidebar Sidebar content.
	 *
	 * @return void
	 */
	public function __construct( $content, $sidebar ) {
		$this->content = $content;
		$this->sidebar = $sidebar;
	}

	/**
	 * Output the page HTML.
	 *
	 * @return void
	 */
	public function output_page_content() {
		echo wp_kses_post( $this->content );
	}
}
