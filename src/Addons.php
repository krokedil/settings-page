<?php
namespace Krokedil\SettingsPage;

defined( 'ABSPATH' ) || exit;

use Krokedil\SettingsPage\Traits\Layout;

/**
 * Addons class to handle the addons section of the settings page.
 */
class Addons {
	use Layout;

	/**
	 * The addons for the page.
	 *
	 * @var array $addons
	 */
	protected $addons = array();

	/**
	 * Installed plugins on the site to check if the addons are installed.
	 *
	 * @var array $installed_plugins
	 */
	protected $installed_plugins = array();

	/**
	 * List of active plugins on the site to check if the addons are active.
	 *
	 * @var array $active_plugins
	 */
	protected $active_plugins = array();

	/**
	 * Class constructor.
	 *
	 * @param array                    $addons Addons for the page.
	 * @param array                    $sidebar Sidebar content.
	 * @param \WC_Payment_Gateway|null $gateway The gateway object.

	 * @return void
	 */
	public function __construct( $addons, $sidebar, $gateway = null ) {
		$this->gateway = $gateway;
		$this->title   = __( 'Addons', 'krokedil-settings' );
		$this->addons  = $addons;
		$this->sidebar = $sidebar;

		$this->installed_plugins = self::get_installed_plugins();
		$this->active_plugins    = self::get_active_plugins();
	}

	/**
	 * Parse the list of installed plugins and store the slug only.
	 *
	 * @return array
	 */
	private static function get_installed_plugins() {
		$plugins = get_plugins();

		foreach ( $plugins as $plugin => $data ) {
			$slug                = explode( '/', $plugin );
			$slug                = $slug[0];
			$installed_plugins[] = $slug;
		}

		return $installed_plugins;
	}

	/**
	 * Parse the list of active plugins and get the slug only.
	 *
	 * @return array
	 */
	private static function get_active_plugins() {
		$active_plugins = get_option( 'active_plugins' );

		foreach ( $active_plugins as $plugin ) {
			$slug             = explode( '/', $plugin );
			$slug             = $slug[0];
			$active_plugins[] = $slug;
		}

		return $active_plugins;
	}

	/**
	 * Check if the plugin is installed.
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return bool
	 */
	private function is_plugin_installed( $slug ) {
		return in_array( $slug, $this->installed_plugins, true );
	}

	/**
	 * Check if the plugin is active.
	 *
	 * @param string $slug The plugin slug.
	 *
	 * @return bool
	 */
	private function is_plugin_active( $slug ) {
		return in_array( $slug, $this->active_plugins, true );
	}

	/**
	 * Output the addons HTML.
	 *
	 * @return void
	 */
	public function output_page_content() {
		global $hide_save_button;
		$hide_save_button = true;

		wp_enqueue_style( 'krokedil-addons-page' );

		// Include the plugin-installer script from WordPress.
		wp_enqueue_script( 'plugin-install' );
		add_thickbox(); // Required for the plugin installer to work.

		$addons = $this->addons['items'];

		?>
		<div class="krokedil_addons">
			<p>These are other plugins from Krokedil that work well together with the plugin.</p>
			<div class='krokedil_addons__cards'>
				<?php foreach ( $addons as $addon ) : ?>
					<?php $this->print_addon_card( $addon ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Print the output for a single addon card.
	 *
	 * @param array $addon Addon data.
	 *
	 * @return void
	 */
	public function print_addon_card( $addon ) {
		$title     = $addon['title'];
		$read_more = $addon['links']['read_more'] ?? array();

		$description = self::get_description( $addon['description'] );
		$image       = self::get_image( $addon['image'] ?? array() );

		?>
		<div class="krokedil_addons__card">
			<div class="krokedil_addons__card_image">
				<?php echo $image; // phpcs:ignore ?>
			</div>
			<div class="krokedil_addons__card_content">
				<h3 class="krokedil_addons__card_title"><?php echo esc_html( $title ); ?></h3>
				<p class="krokedil_addons__card_description"><?php echo wp_kses_post( $description ); ?></p>
				<span class='krokedil_addons__read_more'>
					<?php echo wp_kses_post( self::get_link( $read_more ) ); ?>
				</span>
			</div>
			<?php $this->print_addon_actions( $addon ); ?>
		</div>
		<?php
	}

	/**
	 * Print the action buttons/links for the addon card.
	 *
	 * @param array $addon Addon data.
	 *
	 * @return void
	 */
	public function print_addon_actions( $addon ) {
		$buy_now = $addon['links']['buy_now'] ?? array();
		$price   = $addon['price'] ?? array();

		// If the locale is swedish, then use the sek price. Else use the eur price.
		if ( ! empty( $price ) ) {
			$price = 'sv' === self::get_locale() ? $price['sek'] : $price['eur'];
		} else {
			$price = __( 'Free', 'krokedil-settings' );
		}

		$active           = self::is_plugin_active( $addon['slug'] );
		$installed        = self::is_plugin_installed( $addon['slug'] );
		$status           = $active ? __( 'Active', 'krokedil-settings' ) : ( $installed ? __( 'Installed', 'krokedil-settings' ) : 'not-installed' );
		$plugins_page_url = admin_url( 'plugins.php' );

		?>
		<div class="krokedil_addons__card_action">
			<span class='krokedil_addons__price'><b><?php echo esc_html( $price ); ?></b></span>
			<span class='krokedil_addons__buy_now'>
				<?php if ( 'not-installed' === $status ) : ?>
					<?php echo wp_kses_post( self::get_link( $buy_now ) ); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( $plugins_page_url ); ?>" class="button button-secondary"><?php echo esc_html( $status ); ?></a>
				<?php endif; ?>
			</span>
		</div>
		<?php
	}
}
