<?php
namespace Krokedil\SettingsPage;

defined( 'ABSPATH' ) || exit;

use Krokedil\SettingsPage\Traits\Layout;

/**
 * Support class to handle the support section of the settings page.
 */
class Support {
	use Layout;

	/**
	 * The Support for the page.
	 *
	 * @var array<string, mixed> $support
	 */
	protected $support = array();

	/**
	 * The object the page belongs to.
	 *
	 * @var object|null
	 */
	protected $owner = null;

	/**
	 * Whether to style the output, the same as the styled_output arg of the settings pages.
	 *
	 * @var bool
	 */
	protected $styled_output = false;

	/**
	 * Class constructor.
	 *
	 * @param array<string, mixed> $support Support for the page.
	 * @param array<string, mixed> $sidebar Sidebar content.
	 * @param object|null          $owner   The object the page belongs to, e.g. a WC_Payment_Gateway, WC_Shipping_Method, or a plugin's own settings class.
	 * @param bool                 $styled_output Whether to output the sections as styled, collapsible boxes.
	 *
	 * @return void
	 */
	public function __construct( array $support, array $sidebar, $owner = null, bool $styled_output = false ) {
		$this->title         = __( 'Support', 'krokedil-settings' );
		$this->owner         = $owner;
		$this->support       = $support;
		$this->sidebar       = $sidebar;
		$this->styled_output = $styled_output;
	}

	/**
	 * Return the Helpscout beacon script.
	 *
	 * @param string $use_helpscout Whether to include the Helpscout beacon.
	 * @return string
	 */
	public static function hs_beacon_script( string $use_helpscout = 'no' ): string {
		if ( 'yes' !== $use_helpscout ) {
			return '';
		}

		return '!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});';
	}

	/**
	 * Enqueue the scripts for the support page.
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		// Load CSS.
		wp_enqueue_style( 'krokedil-support-page' );

		$beacon_id = '9c22f83e-3611-42aa-a148-1ca06de53566';

		// Localize the support scrip.
		wp_localize_script(
			'krokedil-support-page',
			'krokedil_support_params',
			array(
				'beaconId' => $beacon_id,
			)
		);

		// Load JS. Only add the inline script if there is one, since wp_add_inline_script does not accept empty data.
		$beacon_script = self::hs_beacon_script( $this->support['use_helpscout'] ?? 'yes' );
		if ( ! empty( $beacon_script ) ) {
			wp_add_inline_script(
				'krokedil-support-page',
				$beacon_script,
				'before',
			);
		}
		wp_enqueue_script( 'krokedil-support-page' );
	}

	/**
	 * Output the layout, wrapped like the settings pages so the styled output applies here too.
	 *
	 * @return void
	 */
	public function output(): void {
		wp_enqueue_style( 'krokedil-settings-page' );

		if ( $this->styled_output ) {
			wp_enqueue_script( 'krokedil-settings-page' );
		}

		printf(
			'<div id="krokedil_settings_support" class="krokedil_settings_page%s">',
			esc_attr( $this->styled_output ? ' styled' : '' )
		);
		?>
			<div class="krokedil_settings__wrapper">
				<?php $this->output_subsection(); ?>
				<?php $this->output_sidebar(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the support HTML.
	 *
	 * @return void
	 */
	public function output_page_content(): void {
		global $hide_save_button;
		$hide_save_button = true;

		$this->enqueue_scripts();

		$content  = $this->support['content'] ?? array();
		$sections = array_values( array_filter( $this->support['sections'] ?? array(), 'is_array' ) );

		?>
		<div class='krokedil_support'>
			<div class="krokedil_support__info">
				<?php
				// Sections replace the content, so a config can keep its content for package versions without sections.
				if ( ! empty( $sections ) ) {
					foreach ( $sections as $index => $section ) {
						$this->output_section( $section, $index, count( $sections ) - 1 === $index );
					}
				} else {
					foreach ( $content as $item ) {
						echo wp_kses_post( self::print_content( $item ) );
					}

					$this->output_support_button( true );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output a support section. Styled, it is a box that can be collapsed, like the sections of the settings.
	 *
	 * @param array<string, mixed> $section The section, with the keys title, description and content.
	 * @param int                  $index   The position of the section, used for its id.
	 * @param bool                 $is_last Whether it is the last section, which gets the support button.
	 *
	 * @return void
	 */
	protected function output_section( array $section, int $index, bool $is_last ): void {
		$title       = self::get_text( $section['title'] ?? '' );
		$description = self::get_text( $section['description'] ?? '' );
		$content     = $section['content'] ?? array();

		if ( ! $this->styled_output ) {
			?>
			<div class="krokedil_support__section_plain">
				<?php if ( '' !== $title ) : ?>
					<h3><?php echo esc_html( $title ); ?></h3>
				<?php endif; ?>
				<?php if ( '' !== $description ) : ?>
					<p><?php echo wp_kses_post( $description ); ?></p>
				<?php endif; ?>
				<?php
				foreach ( $content as $item ) {
					echo wp_kses_post( self::print_content( $item ) );
				}

				if ( $is_last ) {
					$this->output_support_button( false );
				}
				?>
			</div>
			<?php
			return;
		}

		// The same markup as WcSettingsPage::krokedil_section_start_html(), but open by default.
		?>
		<div id="krokedil_section_support_<?php echo esc_attr( (string) $index ); ?>" class="krokedil_settings__section">
			<div class="krokedil_settings__section_header">
				<span class="krokedil_settings__section_toggle dashicons dashicons-arrow-up-alt2"></span>
				<h3 class="krokedil_settings__section_title"><?php echo esc_html( $title ); ?></h3>
				<div class="krokedil_settings__section_description">
					<p><?php echo wp_kses_post( $description ); ?></p>
				</div>
			</div>
			<div class="krokedil_settings__section_content active">
				<?php
				foreach ( $content as $item ) {
					echo wp_kses_post( self::print_content( $item ) );
				}

				if ( $is_last ) {
					$this->output_support_button( false );
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Output the button that opens a support ticket with Krokedil, unless HelpScout is turned off.
	 *
	 * @param bool $with_intro Whether to print the text above the button. Sections have their own text instead.
	 *
	 * @return void
	 */
	protected function output_support_button( bool $with_intro ): void {
		if ( 'yes' !== ( $this->support['use_helpscout'] ?? 'yes' ) ) {
			return;
		}

		?>
		<?php if ( $with_intro ) : ?>
			<p><?php esc_html_e( 'If you still need help, please open a support ticket with Krokedil.', 'krokedil-settings' ); ?></p>
		<?php endif; ?>
		<button type="button" class="button button-primary support-button"><?php esc_html_e( 'Open support ticket with Krokedil', 'krokedil-settings' ); ?></button>
		<?php // Hidden WooCommerce status report, turned into the text report by support.js and attached to the Beacon. ?>
		<div id="krokedil-status-report" hidden>
			<?php
			if ( class_exists( \WC_Admin_Status::class ) ) {
				\WC_Admin_Status::status_report();
			}
			?>
		</div>
		<?php
	}
}
