<?php
namespace Krokedil\SettingsPage;

use Krokedil\SettingsPage\Traits\Layout;

defined( 'ABSPATH' ) || exit;

/**
 * Class for extending a Shipping settings page.
 */
class Shipping extends WcSettingsPage {
	/**
	 * Shipping method instance.
	 *
	 * @var \WC_Shipping_Method
	 */
	protected $shipping;

	/**
	 * Arguments for the page.
	 *
	 * @var array $args
	 */
	protected $args;

	/**
	 * Icon for the settings page.
	 *
	 * @var string $icon
	 */
	protected $icon;

	/**
	 * Whether to display page navigation as a sidebar for the settings sections of the current page.
	 *
	 * @var bool $settings_navigation
	 */
	protected $settings_navigation;

	/**
	 * Whether to style the output for the settings page.
	 *
	 * @var bool $styled_output
	 */
	protected $styled_output;

	/**
	 * Class Constructor.
	 *
	 * @param \WC_Shipping_Method $shipping The shipping method object.
	 * @param array               $args Arguments for the page.
	 *
	 * @return void
	 */
	public function __construct( $shipping, $args = array() ) {
		$this->shipping = $shipping;
		$this->id       = $shipping->id;
		parent::__construct( $this->id, $args, $shipping->get_form_fields() );

		$this->page_title       = $args['page_title'] ?? $shipping->get_method_title();
		$this->page_description = $args['page_description'] ?? $shipping->get_method_description();
	}
}
