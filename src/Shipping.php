<?php
namespace Krokedil\SettingsPage;

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

	/**
	 * Output the page HTML.
	 *
	 * @return void
	 */
	public function output_page_content() {
		?>
		<table class="form-table">
			<?php echo $this->shipping->generate_settings_html( $this->shipping->get_form_fields(), false ); //phpcs:ignore ?>
		</table>
		<?php
	}
}
