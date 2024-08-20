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
	 * @var array $support
	 */
	protected $support = array();

	/**
	 * Class constructor.
	 *
	 * @param array                    $support Support for the page.
	 * @param array                    $sidebar Sidebar content.
	 * @param \WC_Payment_Gateway|null $gateway The gateway object.
	 *
	 * @return void
	 */
	public function __construct( $support, $sidebar, $gateway = null ) {
		$this->title   = __( 'Support', 'krokedil-settings' );
		$this->gateway = $gateway;
		$this->support = $support;
		$this->sidebar = $sidebar;
	}

	/**
	 * Return the Helpscout beacon script.
	 *
	 * @return string;
	 */
	public static function hs_beacon_script() {
		return '!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});';
	}

	/**
	 * Enqueue the scripts for the support page.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		// Load CSS.
		wp_enqueue_style( 'krokedil-support-page' );

		$system_report = wc_get_container()->get( \Automattic\WooCommerce\Utilities\RestApiUtil::class )->get_endpoint_data( '/wc/v3/system_status' );
		$beacon_id     = '9c22f83e-3611-42aa-a148-1ca06de53566';

		// Localize the support scrip.
		wp_localize_script(
			'krokedil-support-page',
			'krokedil_support_params',
			array(
				'systemReport' => $system_report,
				'beaconId'     => $beacon_id,
			)
		);

		// Load JS.
		wp_add_inline_script(
			'krokedil-support-page',
			self::hs_beacon_script(),
			'before',
		);
		wp_enqueue_script( 'krokedil-support-page' );
	}

	/**
	 * Output the support HTML.
	 *
	 * @return void
	 */
	public function output_page_content() {
		global $hide_save_button;
		$hide_save_button = true;

		$this->enqueue_scripts();

		$links      = $this->support['links'];
		$link_texts = $this->support['link_texts'];

		?>
		<div class='krokedil_support'>
			<div class="krokedil_support__info">
				<p><?php esc_html_e( 'Before opening a support ticket, please make sure you have read the relevant plugin resources for a solution to your problem', 'krokedil-settings' ); ?>:</p>
				<ul>
					<?php foreach ( $links as $link ) : ?>
						<li><?php echo wp_kses_post( self::get_link( $link ) ); ?></li>
					<?php endforeach; ?>
				</ul>
				<div>
					<?php foreach ( $link_texts as $link_text ) : ?>
						<?php echo wp_kses_post( self::get_link_text( $link_text ) ); ?>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-primary support-button"><?php esc_html_e( 'Open support ticket', 'krokedil-settings' ); ?></button>
			</div>
		</div>
		<?php
	}
}
