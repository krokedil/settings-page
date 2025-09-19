<?php
namespace Krokedil\SettingsPage;

use Krokedil\SettingsPage\Traits\Layout;

/**
 * Class for adding the settings page styling and functionality to a WooCommerce settings page.
 * Can be extended to create custom settings pages for specific things, like gateways or shipping.
 */
class WcSettingsPage {
	use Layout;

	/**
	 * The ID for the page.
	 *
	 * @var string $id
	 */
	protected $id;

	/**
	 * Arguments for the page.
	 *
	 * @var array $args
	 */
	protected $args;

	/**
	 * Icon for the gateway.
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
	 * Class constructor.
	 *
	 * @param string $id The ID for the page.
	 * @param array  $args Arguments for the page.
	 * @param array  $form_fields The form fields for the settings page.
	 *
	 * @return void
	 */
	public function __construct( $id, $args = array(), $form_fields = array() ) {
		$this->id                  = $id;
		$this->args                = $args;
		$this->icon                = $args['icon'] ?? 'img.png';
		$this->sidebar             = $args['sidebar'] ?? array();
		$this->settings_navigation = $args['settings_navigation'] ?? false;
		$this->styled_output       = $args['styled_output'] ?? false;
		$this->page_title          = $args['page_title'] ?? 'Settings';
		$this->page_description    = $args['page_description'] ?? '';
		$this->form_fields         = $form_fields;

		if ( $this->styled_output ) {
			add_filter( 'woocommerce_generate_krokedil_section_start_html', array( __CLASS__, 'krokedil_section_start_html' ), 10, 3 );
			add_filter( 'woocommerce_generate_krokedil_section_end_html', array( __CLASS__, 'krokedil_section_end_html' ), 10, 3 );
			add_filter( 'woocommerce_generate_krokedil_button_html', array( __CLASS__, 'krokedil_button_html' ), 10, 3 );
			add_filter( 'woocommerce_generate_krokedil_divider_html', array( __CLASS__, 'krokedil_divider_html' ), 10, 3 );
			add_filter( 'woocommerce_generate_krokedil_radio_html', array( __CLASS__, 'krokedil_radio_html' ), 10, 3 );

			add_filter( 'woocommerce_admin_field_krokedil_section_start', array( __CLASS__, 'krokedil_section_start' ), 10, 1 );
			add_filter( 'woocommerce_admin_field_krokedil_section_end', array( __CLASS__, 'krokedil_section_end' ), 10, 1 );
			add_filter( 'woocommerce_admin_field_krokedil_button', array( __CLASS__, 'krokedil_button' ), 10, 1 );
			add_filter( 'woocommerce_admin_field_krokedil_divider', array( __CLASS__, 'krokedil_divider' ), 10, 1 );
			add_filter( 'woocommerce_admin_field_krokedil_radio', array( __CLASS__, 'krokedil_radio' ), 10, 1 );
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
		<?php SettingsPage::get_instance()->navigation( $this->id )->output(); ?>
		<div id="krokedil_settings_<?php echo esc_attr( $this->id ); ?>" class="krokedil_settings_page<?php echo esc_attr( $this->styled_output ? ' styled' : '' ); ?>">
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
			<?php \WC_Admin_Settings::output_fields( $this->form_fields ); ?>
		</table>
		<?php
	}

	/**
	 * Get the HTML as a string for a Klarna Payments section start.
	 *
	 * @param string $html The HTML to append the section start to.
	 * @param string $key The key for the section.
	 * @param array  $section The arguments for the section.
	 *
	 * @return string
	 */
	public static function krokedil_section_start_html( $html, $key, $section ) {
		ob_start();
		?>
		</table>
		<div id="krokedil_section_<?php echo esc_attr( $key ); ?>" class="krokedil_settings__section<?php echo esc_attr( ' ' . $section['class'] ?? '' ); ?>">
			<div class="krokedil_settings__section_header">
				<span class="krokedil_settings__section_toggle dashicons dashicons-arrow-down-alt2"></span>
				<h3 class="krokedil_settings__section_title">
					<?php echo esc_html( $section['title'] ); ?>
				</h3>
				<div class="krokedil_settings__section_description">
					<p><?php echo esc_html( $section['description'] ?? '' ); ?></p>
				</div>
			</div>

			<div class="krokedil_settings__section_content">
				<table class="form-table">
		<?php
		return ob_get_clean();
	}

	/**
	 * Get the HTML as a string for a Klarna Payments section end.
	 *
	 * @param string $html The HTML to append the section end to.
	 * @param string $key The key for the section end.
	 * @param array  $section The arguments for the section.
	 *
	 * @return string
	 */
	public static function krokedil_section_end_html( $html, $key, $section ) {
		ob_start();
		?>
		</table>
			</div>
				</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Custom rendering for a button field type.
	 *
	 * @param string $html The HTML for the field.
	 * @param string $key The key for the field.
	 * @param array  $section The section for the field.
	 *
	 * @return string
	 */
	public static function krokedil_button_html( $html, $key, $section ) {
		ob_start();
		?>
		<tr valign="top">
			<td class="<?php echo esc_attr( $section['class'] ); ?>">
				<?php
				if ( $section['description'] ) {
					?>
					<p><?php echo esc_html( $section['description'] ); ?></p>
				<?php } ?>
				<button class="krokedil_button button<?php echo $section['alignment'] ? ' align-' . esc_attr( $section['alignment'] ) : ' no-align'; ?>" type="button" id="<?php echo esc_attr( 'krokedil_button_' . $section['id'] ); ?>">
					<?php echo esc_html( $section['title'] ?? 'No title' ); ?>
				</button>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Custom rendering for a divider field type.
	 *
	 * @param string $html The HTML for the field.
	 * @param string $key The key for the field.
	 * @param array  $section The section for the field.
	 *
	 * @return string
	 */
	public static function krokedil_divider_html( $html, $key, $section ) {
		ob_start();
		?>
		<tr valign="top" class="form-section form-section-<?php echo esc_attr( $section['id'] ); ?>-end">
			<td colspan="2"><hr></hr></td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Custom rendering for a radio field type.
	 *
	 * @param string $html The HTML for the field.
	 * @param string $key The key for the field.
	 * @param array  $section The section for the field.
	 *
	 * @return string
	 */
	public static function krokedil_radio_html( $html, $key, $section ) {
		ob_start();
		$options = $section['options'] ?? array();
		$default = $section['default'] ?? '';
		?>
		<tr valign="top">
			<td class="<?php echo esc_attr( $section['class'] ); ?> <?php echo ! empty( $section['alignment'] ) ? 'align-prev' : 'no-align'; ?>">
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

	/**
	 * Print the krokedil_section_start field html.
	 *
	 * @param array $section The section arguments.
	 *
	 * @return void
	 */
	public static function krokedil_section_start( $section ) {
		echo self::krokedil_section_start_html( '', $section['id'], $section ); // phpcs:ignore
	}

	/**
	 * Print the krokedil_section_end field html.
	 *
	 * @param array $section The section arguments.
	 *
	 * @return void
	 */
	public static function krokedil_section_end( $section ) {
		echo self::krokedil_section_end_html( '', $section['id'], $section ); // phpcs:ignore_user_abort
	}

	/**
	 * Print the krokedil_button field html.
	 *
	 * @param array $section The section arguments.
	 *
	 * @return void
	 */
	public static function krokedil_button( $section ) {
		echo self::krokedil_button_html( '', $section['id'], $section ); // phpcs:ignore_user_abort
	}

	/**
	 * Print the krokedil_divider field html.
	 *
	 * @param array $section The section arguments.
	 *
	 * @return void
	 */
	public static function krokedil_divider( $section ) {
		echo self::krokedil_divider_html( '', $section['id'], $section ); // phpcs:ignore_user_abort
	}

	/**
	 * Print the krokedil_radio field html.
	 *
	 * @param array $section The section arguments.
	 *
	 * @return void
	 */
	public static function krokedil_radio( $section ) {
		echo self::krokedil_radio_html( '', $section['id'], $section ); // phpcs:ignore_user_abort
	}
}
