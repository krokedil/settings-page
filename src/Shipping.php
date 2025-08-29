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
			add_filter( 'woocommerce_generate_krokedil_radio_html', array( __CLASS__, 'krokedil_radio' ), 10, 3 );

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
		<tr valign="top">
			<td class="<?php echo esc_attr( $section['class'] ); ?><?php echo $section['alignment'] ? ' align-prev' : ' no-align'; ?>">
				<?php
				if ( $section['description'] ) {
					?>
					<p><?php echo esc_html( $section['description'] ); ?></p>
				<?php } ?>
				<button class="krokedil_button button" type="button" id="<?php echo esc_attr( 'krokedil_button_' . $section['id'] ); ?>">
					<?php echo esc_html( $section['title'] ?? 'No title' ); ?>
				</button>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	public static function krokedil_divider( $html, $key, $section ) {
		ob_start();
		?>
		<tr valign="top" class="form-section form-section-<?php echo esc_attr( $section['id'] ); ?>-end">
			<td colspan="2"><hr></hr></td>
		</tr>
		<?php
		return ob_get_clean();
	}

	public static function krokedil_radio( $html, $key, $section ) {
		ob_start();
		$options = $section['options'] ?? array();
		$default = $section['default'] ?? '';
		?>
		<tr valign="top">
			<td class="<?php echo ! empty( $section['alignment'] ) ? 'align-prev' : 'no-align'; ?>">
				<?php foreach ( $options as $option_key => $option ) : ?>
					<label style="margin-right:20px;">
						<input
							type="radio"
							name="<?php echo esc_attr( $key ); ?>"
							value="<?php echo esc_attr( $option['value'] ); ?>"
							<?php checked( $option['value'], $default ); ?>
						>
						<?php echo esc_html( $option['title'] ?? '' ); ?>
					</label>
				<?php endforeach; ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}
}
