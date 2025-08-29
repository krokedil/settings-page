<?php
namespace Krokedil\SettingsPage;

use Krokedil\SettingsPage\Traits\Layout;

defined( 'ABSPATH' ) || exit;

/**
 * Class for extending a Shipping settings page.
 */
class Shipping {
	use Layout;

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
		$this->gateway             = $shipping;
		$this->args                = $args;
		$this->icon                = $args['icon'] ?? 'img.png';
		$this->sidebar             = $args['sidebar'] ?? array();
		$this->settings_navigation = $args['settings_navigation'] ?? false;
		$this->styled_output       = $args['styled_output'] ?? false;

		if ( $this->styled_output ) {
			add_filter( 'woocommerce_generate_krokedil_section_start_html', array( Gateway::class, 'krokedil_section_start' ), 10, 3 );
			add_filter( 'woocommerce_generate_krokedil_section_end_html', array( Gateway::class, 'krokedil_section_end' ), 10, 3 );
			add_filter( 'woocommerce_generate_krokedil_button_html', array( __CLASS__, 'krokedil_button' ), 10, 3 );
			add_filter( 'woocommerce_generate_krokedil_divider_html', array( __CLASS__, 'krokedil_divider' ), 10, 3 );

		}
	}

	/**
	 * Output the layout.
	 *
	 * @return void
	 */
	public function output() {
		wp_enqueue_style( 'krokedil-settings-page' );
		wp_enqueue_script( 'krokedil-settings-page' );
		?>
		<?php $this->output_header(); ?>
		<?php SettingsPage::get_instance()->navigation( $this->gateway->id )->output(); ?>
		<div class="krokedil_settings_page<?php echo esc_attr( $this->styled_output ? ' styled' : '' ); ?>">
			<div class="krokedil_settings__wrapper">
				<?php
				$this->output_subsection( ( $this->styled_output && $this->settings_navigation ) ? true : false );
				?>
				<?php
				$this->output_sidebar();
				?>
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

	public static function krokedil_button( $html, $key, $section ) {
		ob_start();
		?>
		<tr valign="top" class="page-title-action <?php echo esc_attr( $section['alignment'] ? 'align-prev' : '' ); ?>">
			<td class="forminp">
				<button type="button" id="krokedil_button_<?php echo esc_attr( $section['id'] ); ?>">
					<?php echo esc_html( $section['title'] ?? 'No title' ); ?>
				</button>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	public static function krokedil_divider( $section ) {
		ob_start();
		?>
		<tr valign="top" class="form-section form-section-<?php echo esc_attr( $section['id'] ); ?>-end">
			<td colspan="2"></td>
		</tr>
		<?php
		return ob_get_clean();
	}
}
