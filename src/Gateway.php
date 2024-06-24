<?php
namespace Krokedil\SettingsPage;

use Krokedil\SettingsPage\Traits\Layout;

defined( 'ABSPATH' ) || exit;

/**
 * Class for extending a Gateways settings page.
 */
class Gateway {
	use Layout;

	/**
	 * The gateway object.
	 *
	 * @var \WC_Payment_Gateway $gateway
	 */
	protected $gateway;

	/**
	 * Arguments for the page.
	 *
	 * @var array $args
	 */
	protected $args;

	/**
	 * Class Constructor.
	 *
	 * @param \WC_Payment_Gateway $gateway The gateway object.
	 * @param array               $args Arguments for the page.
	 *
	 * @return void
	 */
	public function __construct( $gateway, $args = array() ) {
		$this->gateway = $gateway;
		$this->args    = $args;

		$this->sidebar = $args['sidebar'] ?? array();
	}

	/**
	 * Output the layout.
	 *
	 * @return void
	 */
	public function output() {
		wp_enqueue_style( 'krokedil-settings-page' );

		/*
			Output the settings page headers before the navigation.
			No actions or filters exists to hook into for this.
			Copied from https://github.com/woocommerce/woocommerce/blob/841d00e1506f43dd31c1f66078b93f1edea5aaa0/plugins/woocommerce/includes/abstracts/abstract-wc-payment-gateway.php#L207-L210
		*/
		echo '<h2>' . esc_html( $this->gateway->get_method_title() );
		wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); // phpcs:ignore
		echo '</h2>';
		echo wp_kses_post( wpautop( $this->gateway->get_method_description() ) );
		SettingsPage::get_instance()->navigation( $this->gateway->id )->output();
		?>
		<div class="krokedil_settings__gateway_page">
			<div class="krokedil_settings__wrapper">
				<?php $this->output_subsection(); ?>
				<?php $this->output_sidebar(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the page HTML.
	 *
	 * @return void
	 */
	public function output_page_content() {
		?>
		<table class="form-table">
			<?php echo $this->gateway->generate_settings_html( $this->gateway->get_form_fields(), false ); //phpcs:ignore ?>
		</table>
		<?php
	}
}
