<?php
namespace Krokedil\SettingsPage;

defined( 'ABSPATH' ) || exit;

use Krokedil\SettingsPage\Traits\Singleton;

/**
 * Main class for the settings page package.
 */
class SettingsPage {
	use Singleton;

	/**
	 * Plugin name.
	 *
	 * @var string|null $plugin_name
	 */
	protected $plugin_name = null;

	/**
	 * Array of pages to extend.
	 *
	 * @var array $pages
	 */
	protected $pages = array();

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	protected function __construct() {
		$this->init();
	}

	/**
	 * Initialize the class.
	 *
	 * @return void
	 */
	public function init() {
		$this->load_textdomain();
		$this->register_scripts();

		add_filter( 'woocommerce_generate_section_start_html', array( __CLASS__, 'section_start' ), 10, 3 );
		add_filter( 'woocommerce_generate_section_end_html', array( __CLASS__, 'section_end' ), 10, 3 );
	}

	/**
	 * Load the text domain for the package.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		$filename = dirname( __DIR__ ) . '/languages/krokedil-settings-' . get_locale() . '.mo';

		if ( file_exists( $filename ) ) {
			load_textdomain( 'krokedil-settings', $filename );
		}
	}

	/**
	 * Enqueue the scripts for the settings page.
	 *
	 * @return void
	 */
	public function register_scripts() {
		wp_register_style(
			'krokedil-settings-page',
			plugin_dir_url( __FILE__ ) . '../assets/css/settings.css',
			array(),
			filemtime( __DIR__ . '/../assets/css/settings.css' ),
		);

		wp_register_style(
			'krokedil-support-page',
			plugin_dir_url( __FILE__ ) . '../assets/css/support.css',
			array( 'krokedil-settings-page' ),
			filemtime( __DIR__ . '/../assets/css/support.css' ),
		);

		wp_register_style(
			'krokedil-addons-page',
			plugin_dir_url( __FILE__ ) . '../assets/css/addons.css',
			array( 'krokedil-settings-page' ),
			filemtime( __DIR__ . '/../assets/css/addons.css' ),
		);

		wp_register_script(
			'krokedil-support-page',
			plugin_dir_url( __FILE__ ) . '../assets/js/support.js',
			array( 'jquery' ),
			filemtime( __DIR__ . '/../assets/js/support.js' ),
			false,
		);

		wp_register_script(
			'krokedil-settings-page',
			plugin_dir_url( __FILE__ ) . '../assets/js/settings.js',
			array( 'jquery' ),
			filemtime( __DIR__ . '/../assets/js/settings.js' ),
			false,
		);
	}

	/**
	 * Set the plugin name.
	 *
	 * @param string|null $plugin_name The plugin name.
	 *
	 * @return self
	 */
	public function set_plugin_name( $plugin_name ) {
		$this->plugin_name = $plugin_name;

		return $this;
	}

	/**
	 * Register a page for extension.
	 *
	 * @param string                   $id   ID of the page.
	 * @param array                    $args Arguments for the page.
	 * @param \WC_Payment_Gateway|null $gateway The gateway object.
	 *
	 * @return self
	 */
	public function register_page( $id, $args, $gateway = null ) {
		$default_args = array(
			'page'              => '',
			'tab'               => '',
			'section'           => '',
			'extra_subsections' => array(),
			'support'           => null,
			'addons'            => null,
			'general_content'   => null,
		);

		$args = wp_parse_args( $args, $default_args );

		$this->pages[ $id ] = array(
			'navigation' => new Navigation( $args ),
			'support'    => $args['support'] ? new Support( $args['support'], $args['sidebar'], $gateway ) : null,
			'addons'     => $args['addons'] ? new Addons( $args['addons'], $args['sidebar'], $gateway ) : null,
			'args'       => $args,
		);

		return $this;
	}

	/**
	 * Output content for the page.
	 *
	 * @param string $id ID of the page.
	 *
	 * @return self
	 */
	public function output( $id ) {
		// Get the registered page.
		if ( ! isset( $this->pages[ $id ] ) ) {
			return $this;
		}

		$page               = $this->pages[ $id ];
		$general_content    = $page['args']['general_content'] ?? '';
		$icon               = $page['args']['icon'] ?? '';
		$support            = $page['support'];
		$addons             = $page['addons'];
		$navigation         = $page['navigation'];
		$current_subsection = $navigation->get_current_subsection();

		switch ( $current_subsection ) {
			case 'support':
				// If we are on the support tab. Print the support content.
				$support->set_icon( $icon );
				$addons->set_plugin_name( $this->plugin_name );
				$support->output_header();
				$navigation->output();
				$support->output();
				break;
			case 'addons':
				// If we are on the addons tab. Print the addons content.
				$addons->set_icon( $icon );
				$addons->set_plugin_name( $this->plugin_name );
				$addons->output_header();
				$navigation->output();
				$addons->output();
				break;
			default:
				if ( is_string( $general_content ) ) {
					echo wp_kses_post( $general_content );
				} else {
					// If the general content is a callback. Call the callback.
					call_user_func( $general_content );
				}
				break;
		}

		return $this;
	}

	/**
	 * Get the navigation for a page.
	 *
	 * @param string $id ID of the page.
	 *
	 * @return Navigation|null
	 */
	public function navigation( $id ) {
		if ( ! isset( $this->pages[ $id ] ) ) {
			return null;
		}

		return $this->pages[ $id ]['navigation'];
	}

	/**
	 * Get the support for a page.
	 *
	 * @param string $id ID of the page.
	 *
	 * @return Support|null
	 */
	public function support( $id ) {
		if ( ! isset( $this->pages[ $id ] ) ) {
			return null;
		}

		return $this->pages[ $id ]['support'];
	}

	/**
	 * Get the addons for a page.
	 *
	 * @param string $id ID of the page.
	 *
	 * @return Addons|null
	 */
	public function addons( $id ) {
		if ( ! isset( $this->pages[ $id ] ) ) {
			return null;
		}

		return $this->pages[ $id ]['addons'];
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
	public static function section_start( $html, $key, $section ) {
		ob_start();
		?>
		</table>
		<div id="krokedil_section_<?php echo esc_attr( $key ); ?>" class="krokedil_settings__section">
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
	public static function section_end( $html, $key, $section ) {
		ob_start();
		?>
		</table>
			</div>
				</div>
		<?php
		return ob_get_clean();
	}
}
