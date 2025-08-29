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
			add_filter( 'woocommerce_admin_field_krokedil_section_start', array( __CLASS__, 'krokedil_section_start' ), 10, 3 );
			add_filter( 'woocommerce_admin_field_krokedil_section_end', array( __CLASS__, 'krokedil_section_end' ), 10, 3 );
			add_filter( 'woocommerce_admin_field_krokedil_divider', array( __CLASS__, 'krokedil_divider' ), 10, 3 );

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
			<?php echo $this->generate_settings_html_shipping( $this->gateway->get_form_fields(), false ); ?>
		</table>
		<?php
	}

	/**
	 * Get the HTML as a string for a Klarna Payments section start.
	 *
	 * @param string $section The section data.
	 *
	 * @return string
	 */
	public static function krokedil_section_start( $section ) {
		$always_open_sections = array();
		$section_key          = $section['id'] ?? '';
		$html                 = '</table>';
		$html                .= '<div id="krokedil_section_' . esc_attr( $section_key ) . '" class="krokedil_settings__section">';
		$html                .= '<div class="krokedil_settings__section_header">';
		$html                .= '<span class="krokedil_settings__section_toggle dashicons' . esc_attr( \in_array( $section_key, $always_open_sections, \true ) ? ' dashicons-arrow-up-alt2' : ' dashicons-arrow-down-alt2' ) . '"></span>';
		$html                .= '<h3 class="krokedil_settings__section_title">' . esc_html( $section['name'] ) . '</h3>';
		$html                .= '<div class="krokedil_settings__section_description">';
		$html                .= '<p>' . esc_html( $section['description'] ?? '' ) . '</p>';
		$html                .= '</div>';
		$html                .= '</div>';
		$html                .= '<div class="krokedil_settings__section_content' . esc_attr( \in_array( $section_key, $always_open_sections, \true ) ? ' active' : '' ) . '">';
		$html                .= '<table class="form-table">';
		return $html;
	}

	/**
	 * Get the HTML as a string for a Klarna Payments section end.
	 *
	 * @param string $section The section data.
	 *
	 * @return string
	 */
	public static function krokedil_section_end( $section ) {
		$html  = '</table>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	/**
	 * Add a custom divider that works for
	 *
	 * @param array $section The section data.
	 * @return string
	 */
	public static function krokedil_divider( $section ) {
		$html  = '<tr valign="top" class="form-section form-section-' . esc_attr( $section['id'] ) . '-end">';
		$html .= '<td colspan="2"></td>';
		$html .= '</tr>';
		return $html;
	}

	/**
	 * Generate Settings HTML.
	 *
	 * Generate the HTML for the fields on the "settings" screen.
	 *
	 * @param array $form_fields (default: array()) Array of form fields.
	 * @param bool  $echo Echo or return.
	 * @return string the html for the settings
	 * @since  1.0.0
	 * @uses   method_exists()
	 */
	public function generate_settings_html_shipping( $form_fields = array(), $echo = true ) {
		if ( empty( $form_fields ) ) {
			$form_fields = $this->gateway->get_form_fields();
		}

		$html = '';
		foreach ( $form_fields as $k => $v ) {
			$type = $this->gateway->get_field_type( $v );

			if ( method_exists( $this->gateway, 'generate_' . $type . '_html' ) ) {
				$html .= $this->gateway->{'generate_' . $type . '_html'}( $k, $v );
			} elseif ( has_filter( 'woocommerce_admin_field_' . $type ) ) {
				$html .= apply_filters( 'woocommerce_admin_field_' . $type, $v );
			} else {
				$html .= $this->gateway->generate_text_html( $k, $v );
			}
		}

		return $html;
	}
}
