<?php
namespace Krokedil\SettingsPage;

defined( 'ABSPATH' ) || exit;

use Krokedil\SettingsPage\SetupChecks\Registry;
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
	 * @var array<string, array<string, mixed>> $pages
	 */
	protected $pages = array();

	/**
	 * The setup checks of each page, keyed by page id.
	 *
	 * @var array<string, Registry> $setup_checks
	 */
	protected $setup_checks = array();

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
	public function init(): void {
		$this->load_textdomain();
		$this->register_scripts();
	}

	/**
	 * Load the text domain for the package.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
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
	public function register_scripts(): void {
		wp_register_style(
			'krokedil-settings-page',
			plugin_dir_url( __FILE__ ) . '../assets/css/settings.css',
			array(),
			(string) filemtime( __DIR__ . '/../assets/css/settings.css' ),
		);

		wp_register_style(
			'krokedil-support-page',
			plugin_dir_url( __FILE__ ) . '../assets/css/support.css',
			array( 'krokedil-settings-page' ),
			(string) filemtime( __DIR__ . '/../assets/css/support.css' ),
		);

		wp_register_style(
			'krokedil-addons-page',
			plugin_dir_url( __FILE__ ) . '../assets/css/addons.css',
			array( 'krokedil-settings-page' ),
			(string) filemtime( __DIR__ . '/../assets/css/addons.css' ),
		);

		wp_register_script(
			'krokedil-support-page',
			plugin_dir_url( __FILE__ ) . '../assets/js/support.js',
			array( 'jquery' ),
			(string) filemtime( __DIR__ . '/../assets/js/support.js' ),
			false,
		);

		wp_register_script(
			'krokedil-settings-page',
			plugin_dir_url( __FILE__ ) . '../assets/js/styled-settings.js',
			array( 'jquery' ),
			(string) filemtime( __DIR__ . '/../assets/js/styled-settings.js' ),
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
	public function set_plugin_name( ?string $plugin_name ): self {
		$this->plugin_name = $plugin_name;

		return $this;
	}

	/**
	 * Register a page for extension.
	 *
	 * @param string               $id    ID of the page.
	 * @param array<string, mixed> $args  Arguments for the page.
	 * @param object|null          $owner The object the page belongs to, e.g. a WC_Payment_Gateway, WC_Shipping_Method, or a plugin's own settings class.
	 *
	 * @return self
	 */
	public function register_page( string $id, array $args, $owner = null ): self {
		$default_args = array(
			'page'              => '',
			'tab'               => '',
			'section'           => '',
			'extra_subsections' => array(),
			'support'           => null,
			'addons'            => null,
			'general_content'   => null,
			'fallback_content'  => null,
			'error_notice'      => '',
			'sidebar'           => array(),
		);

		$args = wp_parse_args( $args, $default_args );

		$this->pages[ $id ] = array(
			'navigation' => new Navigation( $args ),
			'support'    => $args['support'] ? new Support( $args['support'], $args['sidebar'], $owner ) : null,
			'addons'     => $args['addons'] ? new Addons( $args['addons'], $args['sidebar'], $owner ) : null,
			'args'       => $args,
			'owner'      => $owner,
		);

		return $this;
	}

	/**
	 * Add store setup checks to a page. Call it early, e.g. on admin_init, so the checks are also in the system report.
	 *
	 * @param string                   $id     ID of the page.
	 * @param array<int|string, mixed> $checks List of SetupCheck instances and check argument arrays, see docs/setup-checks.md.
	 * @param array<string, mixed>     $args   Optional arguments, see docs/setup-checks.md.
	 *
	 * @return self
	 */
	public function add_setup_checks( string $id, array $checks, array $args = array() ): self {
		if ( ! isset( $this->setup_checks[ $id ] ) ) {
			$this->setup_checks[ $id ] = new Registry( $id );
		}

		$this->setup_checks[ $id ]->add( $checks )->set_args( $args );

		if ( ! has_action( 'woocommerce_system_status_report', array( $this, 'output_setup_checks_report' ) ) ) {
			add_action( 'woocommerce_system_status_report', array( $this, 'output_setup_checks_report' ) );
			add_action( 'admin_notices', array( $this, 'output_setup_checks_notices' ) );

			// Priority 5 prints before the list of payment methods, which WooCommerce prints at priority 10.
			add_action( 'woocommerce_settings_checkout', array( $this, 'output_setup_checks_payments_notices' ), 5 );
		}

		return $this;
	}

	/**
	 * Get the results of the setup checks of a page, e.g. to decide whether to show a notice.
	 *
	 * @param string $id ID of the page.
	 *
	 * @return array<int, array<string, string>> One array per check, the keys are listed in docs/setup-checks.md.
	 */
	public function get_setup_check_results( string $id ): array {
		$registry = $this->setup_checks( $id );

		return $registry ? $registry->get_results() : array();
	}

	/**
	 * Get the setup checks for a page.
	 *
	 * @param string $id ID of the page.
	 *
	 * @return Registry|null
	 */
	public function setup_checks( string $id ): ?Registry {
		if ( ! isset( $this->setup_checks[ $id ] ) ) {
			return null;
		}

		return $this->setup_checks[ $id ]->set_defaults(
			array(
				'plugin_name' => $this->plugin_name,
				'owner'       => $this->pages[ $id ]['owner'] ?? null,
			)
		);
	}

	/**
	 * Output the setup checks of every page as sections of the WooCommerce system report.
	 *
	 * @return void
	 */
	public function output_setup_checks_report(): void {
		$this->render_setup_checks(
			function ( Registry $registry ) {
				$registry->output_system_report();
			}
		);
	}

	/**
	 * Output a notice for every page with failed required checks, while its payment or shipping method is enabled.
	 * It cannot be dismissed, and is left out where the sidebar box already shows the same checks.
	 *
	 * @return void
	 */
	public function output_setup_checks_notices(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
			return;
		}

		$this->render_setup_checks(
			function ( Registry $registry ) {
				if ( $registry->is_enabled() && ! $registry->is_box_on_current_page() ) {
					$registry->output_notice();
				}
			}
		);
	}

	/**
	 * Output the failed required checks of payment gateways for their rows in the list of payment methods.
	 *
	 * WooCommerce removes the admin notices of other plugins on that page, and its list has no hook for the rows.
	 * So the notices are printed hidden above the list, with a script that moves each one into its row.
	 *
	 * @return void
	 */
	public function output_setup_checks_payments_notices(): void {
		global $current_section;

		// The hook also runs on the settings page of each payment method.
		if ( ! empty( $current_section ) || ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce capability.
			return;
		}

		ob_start();
		$this->render_setup_checks(
			function ( Registry $registry ) {
				$registry->output_row_notice();
			}
		);
		$notices = trim( (string) ob_get_clean() );

		if ( '' === $notices ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by the renderer above.
		echo '<div class="krokedil-setup-checks-row-notices">' . $notices . '</div>';

		// Printed inline and not enqueued: the script works on the element before it, so the copies of this
		// package in other plugins cannot clash over a script handle or act on each other's notices.
		wp_print_inline_script_tag( (string) file_get_contents( __DIR__ . '/../assets/js/setup-checks-payments.js' ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file.
	}

	/**
	 * Render something for the setup checks of every page. A page that throws is skipped, so it cannot break the admin.
	 *
	 * @param callable $render Receives the Registry of a page and prints its output.
	 *
	 * @return void
	 */
	protected function render_setup_checks( callable $render ): void {
		foreach ( array_keys( $this->setup_checks ) as $id ) {
			$registry     = $this->setup_checks( $id );
			$buffer_level = ob_get_level();

			ob_start();
			try {
				if ( $registry ) {
					call_user_func( $render, $registry );
				}

				echo (string) ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered output is escaped by the renderer above.
			} catch ( \Throwable $exception ) {
				while ( ob_get_level() > $buffer_level ) {
					ob_end_clean();
				}

				do_action( 'krokedil_settings_page_render_error', $id, $exception, array( 'page_id' => $id ) );
			}
		}
	}

	/**
	 * Add the setup checks box to the sidebars of a page.
	 *
	 * @param string $id ID of the page.
	 *
	 * @return void
	 */
	protected function add_setup_checks_box( string $id ): void {
		$registry = $this->setup_checks( $id );

		if ( ! $registry || ! $registry->has_checks() ) {
			return;
		}

		$box = array(
			'id'      => 'krokedil_setup_checks',
			'title'   => $registry->get_title(),
			'content' => array( $registry, 'output' ),
			'class'   => 'krokedil_settings__setup_checks_box',
		);

		foreach ( array( 'support', 'addons' ) as $subsection ) {
			if ( $this->pages[ $id ][ $subsection ] ) {
				$this->pages[ $id ][ $subsection ]->add_sidebar_box( $box );
			}
		}
	}

	/**
	 * Output content for the page.
	 *
	 * @param string $id ID of the page.
	 *
	 * @throws \InvalidArgumentException If the content for the default subsection is not a string or callable.
	 * @return self
	 */
	public function output( string $id ): self {
		// Get the registered page.
		if ( ! isset( $this->pages[ $id ] ) ) {
			return $this;
		}

		$page               = $this->pages[ $id ];
		$general_content    = $page['args']['general_content'] ?? '';
		$fallback_content   = $page['args']['fallback_content'] ?? 'Something went wrong while loading this page. Please try again later.';
		$error_notice       = $page['args']['error_notice'] ?? '';
		$icon               = $page['args']['icon'] ?? '';
		$support            = $page['support'];
		$addons             = $page['addons'];
		$navigation         = $page['navigation'];
		$current_subsection = $navigation->get_current_subsection();
		$buffer_level       = ob_get_level();

		ob_start();
		try {
			$this->add_setup_checks_box( $id );

			switch ( $current_subsection ) {
				case 'support':
					// If we are on the support tab. Print the support content.
					$support->set_icon( $icon );
					$support->set_plugin_name( $this->plugin_name );
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
					} elseif ( is_callable( $general_content ) ) {
						// If the general content is a callback. Call the callback.
						call_user_func( $general_content );
					} else {
						throw new \InvalidArgumentException( sprintf( 'General content for subsection "%s" must be a string or a callable.', $current_subsection ) );
					}
					break;
			}

			echo (string) ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered output is generated by trusted renderers above.
		} catch ( \Throwable $exception ) {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}

			do_action( 'krokedil_settings_page_render_error', $id, $exception, $page );
			$notice_message = ! empty( $error_notice ) ? $error_notice : __( 'An error occurred while rendering this settings page.', 'krokedil-settings' );

			if ( is_callable( $fallback_content ) ) {
				$fallback_buffer_level = ob_get_level();
				ob_start();

				try {
					call_user_func( $fallback_content );
					echo (string) ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered output is generated by trusted renderers above.
				} catch ( \Throwable $fallback_exception ) {
					while ( ob_get_level() > $fallback_buffer_level ) {
						ob_end_clean();
					}
					$notice_message .= ' ' . __( 'Additionally, an error occurred while rendering the fallback content.', 'krokedil-settings' );
					do_action( 'krokedil_settings_page_fallback_render_error', $id, $fallback_exception, $page );
				}
			} elseif ( is_string( $fallback_content ) ) {
				echo wp_kses_post( $fallback_content );
			}

			echo '<div class="notice notice-error"><p>' . esc_html( $notice_message ) . '</p></div>';
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
	public function navigation( string $id ): ?Navigation {
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
	public function support( string $id ): ?Support {
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
	public function addons( string $id ): ?Addons {
		if ( ! isset( $this->pages[ $id ] ) ) {
			return null;
		}

		return $this->pages[ $id ]['addons'];
	}
}
