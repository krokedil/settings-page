<?php
namespace Krokedil\SettingsPage\Traits;

trait Sidebar {
	/**
	 * The sidebar content.
	 *
	 * @var array $sidebar
	 */
	protected $sidebar = array();

	/**
	 * Locale of the site.
	 *
	 * @var string
	 */
	protected static $locale = '';

	/**
	 * Output the developed by text.
	 *
	 * @return void
	 */
	public function output_developed_by() {
		$default_text = 'Developed by:';
		$developed_by = $this->sidebar['developed_by'] ?? $default_text;
		$krokedil_url = get_locale() === 'sv_SE' ? 'https://krokedil.se/' : 'https://krokedil.com/';

		if ( is_string( $developed_by ) || ! is_array( $developed_by ) ) {
			?>
			<div class="krokedil_settings__sidebar_footer_krokedil">
				<p class="krokedil_settings__sidebar_subtext"><?php echo esc_html( $default_text ); ?></p>
				<a class="no-external-icon" href="<?php echo esc_url( $krokedil_url ); ?>" target="_blank">
					<img class="krokedil_settings__sidebar_logo" src="https://krokedil.se/wp-content/uploads/2020/05/webb_logo_400px.png" />
				</a>
			</div>
			<?php
			return;
		}

		$for  = isset( $developed_by['for'] ) ? self::get_text( $developed_by['for'] ) : 'Developed for';
		$by   = isset( $developed_by['by'] ) ? self::get_text( $developed_by['by'] ) : 'by';
		$logo = $developed_by['logo'] ?? null;

		?>
			<div class="krokedil_settings__sidebar_footer">
				<p class="krokedil_settings__sidebar_subtext"><?php echo esc_html( $for ); ?></p>
				<?php if ( $logo ) : ?>
					<img class="krokedil_settings__sidebar_logo" src="<?php echo esc_attr( $logo ); ?>" />
				<?php endif; ?>
				<p class="krokedil_settings__sidebar_subtext"><?php echo esc_html( $by ); ?></p>
				<a class="no-external-icon" href="<?php echo esc_url( $krokedil_url ); ?>" target="_blank">
					<img class="krokedil_settings__sidebar_logo" src="https://krokedil.se/wp-content/uploads/2020/05/webb_logo_400px.png" />
				</a>
			</div>
		<?php
	}

	/**
	 * Output the Sidebar.
	 *
	 * @return void
	 */
	public function output_sidebar() {
		$plugin_resources     = $this->sidebar['plugin_resources']['links'] ?? array();
		$additional_resources = $this->sidebar['additional_resources']['links'] ?? array();

		// Get the locale of the site but convert it to lowercase 2 letter language code.
		?>
			<div class="krokedil_settings__sidebar">
				<div class="krokedil_settings__sidebar_section">
					<div class="krokedil_settings__sidebar_content">
						<?php if ( ! empty( $plugin_resources ) ) : ?>
							<h1 class="krokedil_settings__sidebar_title"><?php echo esc_html( __( 'Plugin resources', 'krokedil-settings' ) ); ?></h1>

							<p class="krokedil_settings__sidebar_main_text">
								<?php foreach ( $plugin_resources as $link ) : ?>
									<span>
										&raquo;
										<?php echo wp_kses_post( self::get_link( $link ) ); ?>
									</span>
								<?php endforeach; ?>
							</p>
						<?php endif; ?>
						<?php if ( ! empty( $additional_resources ) ) : ?>
							<h1 class="krokedil_settings__sidebar_title"><?php echo esc_html( __( 'Additional resources', 'krokedil-settings' ) ); ?></h1>

							<p class="krokedil_settings__sidebar_main_text">
								<?php foreach ( $additional_resources as $link ) : ?>
									<span>
										&raquo;
										<?php echo wp_kses_post( self::get_link( $link ) ); ?>
									</span>
								<?php endforeach; ?>
							</p>
						<?php endif; ?>
					</div>
					<?php $this->output_developed_by(); ?>
				</div>
		<?php
	}

	/**
	 * Get a text based on locale.
	 *
	 * @param array $text Text to output.
	 *
	 * @return string
	 */
	protected static function get_text( $text ) {
		return $text[ self::get_locale() ] ?? $text['text']['en'] ?? '';
	}

	/**
	 * Get the locale of the site.
	 *
	 * @return string
	 */
	protected static function get_locale() {
		if ( ! empty( self::$locale ) ) {
			return self::$locale;
		}

		$locale = get_locale();
		$locale = strtolower( substr( $locale, 0, 2 ) );

		self::$locale = $locale;

		return $locale;
	}
}
