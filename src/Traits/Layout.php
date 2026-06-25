<?php
namespace Krokedil\SettingsPage\Traits;

trait Layout {
	use Sidebar;
	use Subsection;

	/**
	 * The icon for the page.
	 *
	 * @var string $icon
	 */
	protected $icon;

	/**
	 * Plugin name.
	 *
	 * @var string|null $plugin_name
	 */
	protected $plugin_name = null;

	/**
	 * Page title.
	 *
	 * @var string $page_title
	 */
	protected $page_title = 'Settings';

	/**
	 * Page description.
	 *
	 * @var string $page_description
	 */
	protected $page_description = '';

	/**
	 * Form fields for the settings page.
	 *
	 * @var array<string, mixed> $form_fields
	 */
	protected $form_fields = array();

	/**
	 * Set the icon url.
	 *
	 * @param string $icon The icon url.
	 *
	 * @return void
	 */
	public function set_icon( string $icon ): void {
		$this->icon = $icon;
	}

	/**
	 * Set the plugin name.
	 *
	 * @param string|null $plugin_name The plugin name.
	 *
	 * @return void
	 */
	public function set_plugin_name( ?string $plugin_name ): void {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * Get the page title.
	 *
	 * @return string
	 */
	public function get_page_title(): string {
		return $this->page_title;
	}

	/**
	 * Get the page description.
	 *
	 * @return string
	 */
	public function get_page_description(): string {
		return $this->page_description;
	}

	/**
	 * Print the header for the page.
	 *
	 * @return void
	 */
	public function output_header(): void {
		?>
		<div class="krokedil_settings__header">
			<?php if ( ! empty( $this->icon ) ) : ?>
				<img height="64px" class="kp_settings__header_logo" src="<?php echo esc_attr( $this->icon ); ?>" alt="<?php echo esc_html( $this->get_page_title() ); ?>" />
			<?php endif; ?>
			<div class="krokedil_settings__header_text">
				<h2 class="krokedil_settings__header_title">
					<?php echo esc_html( $this->get_page_title() ); ?>
					<?php wc_back_link( __( 'Return to payments', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ); //phpcs:ignore ?>
				</h2>
				<p class="krokedil_settings__header_description"><?php echo esc_html( $this->get_page_description() ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the layout.
	 *
	 * @return void
	 */
	public function output(): void {
		wp_enqueue_style( 'krokedil-settings-page' );

		?>
		<?php if ( method_exists( $this, 'output_description' ) ) : ?>
			<?php $this->output_description(); ?>
		<?php endif; ?>
		<div class="krokedil_settings__wrapper">
			<?php $this->output_subsection(); ?>
			<?php $this->output_sidebar(); ?>
		</div>
		<?php
	}
}
