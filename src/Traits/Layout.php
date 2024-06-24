<?php
namespace Krokedil\SettingsPage\Traits;

trait Layout {
	use Sidebar;
	use Subsection;

	/**
	 * The gateway object.
	 *
	 * @var \WC_Payment_Gateway|null $gateway
	 */
	protected $gateway;

	/**
	 * Print the header for the page.
	 *
	 * @return void
	 */
	public function output_header() {
		if ( empty( $this->gateway ) ) {
			return;
		}

		?>
		<div class="krokedil_settings__header">
			<?php if ( ! empty( $this->icon ) ) : ?>
				<img height="64px" class="kp_settings__header_logo" src="<?php echo esc_attr( $this->icon ); ?>" alt="<?php echo esc_html( $this->gateway->get_method_title() ); ?>" />
			<?php endif; ?>
			<div class="krokedil_settings__header_text">
				<h2 class="krokedil_settings__header_title">
					<?php echo esc_html( $this->gateway->get_method_title() ); ?>
					<?php wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); //phpcs:ignore ?>
				</h2>
				<p class="krokedil_settings__header_description"><?php echo esc_html( $this->gateway->get_method_description() ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the layout.
	 *
	 * @return void
	 */
	public function output() {
		wp_enqueue_style( 'krokedil-settings-page' );

		?>
		<div class="krokedil_settings__wrapper">
			<?php $this->output_subsection(); ?>
			<?php $this->output_sidebar(); ?>
		</div>
		<?php
	}
}
