<?php
namespace Krokedil\SettingsPage\Traits;

trait Sidebar {
	/**
	 * The sidebar content.
	 *
	 * @var array<string, mixed> $sidebar
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
	public function output_developed_by(): void {
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
	 * Output any registered sidebar boxes.
	 *
	 * Boxes are registered through the `boxes` key of the sidebar args. Each box renders
	 * as its own sidebar card, above the resources section, and supports the keys:
	 *
	 *  - 'id':      string Unique box id.
	 *  - 'title':   string|array<string, string> The box heading. Locale arrays are supported, see get_text().
	 *  - 'content': string|callable The box body. A callable prints its own output, a string is run through wp_kses_post().
	 *  - 'class':   string Optional extra CSS class for the box element.
	 *
	 * A box whose rendering throws is skipped entirely, and the
	 * `krokedil_settings_page_render_error` action is fired with the box id, the
	 * throwable, and the box configuration.
	 *
	 * @return void
	 */
	public function output_sidebar_boxes(): void {
		$boxes = $this->sidebar['boxes'] ?? array();

		if ( ! is_array( $boxes ) ) {
			return;
		}

		foreach ( $boxes as $box ) {
			if ( ! is_array( $box ) ) {
				continue;
			}

			$buffer_level = ob_get_level();

			ob_start();
			try {
				$this->output_sidebar_box( $box );
				echo (string) ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Buffered output is escaped by the renderer above.
			} catch ( \Throwable $exception ) {
				while ( ob_get_level() > $buffer_level ) {
					ob_end_clean();
				}

				do_action( 'krokedil_settings_page_render_error', $box['id'] ?? '', $exception, $box );
			}
		}
	}

	/**
	 * Output a single sidebar box.
	 *
	 * @param array<string, mixed> $box The box configuration, see output_sidebar_boxes().
	 *
	 * @return void
	 */
	protected function output_sidebar_box( array $box ): void {
		$title   = $box['title'] ?? '';
		$content = $box['content'] ?? '';
		$class   = $box['class'] ?? '';
		$class   = is_string( $class ) ? $class : '';

		?>
		<div class="<?php echo esc_attr( trim( 'krokedil_settings__sidebar_box ' . $class ) ); ?>">
			<?php if ( ! empty( $title ) ) : ?>
				<h1 class="krokedil_settings__sidebar_title"><?php echo esc_html( self::get_text( $title ) ); ?></h1>
			<?php endif; ?>
			<?php
			if ( is_callable( $content ) ) {
				call_user_func( $content );
			} elseif ( is_string( $content ) && '' !== $content ) {
				echo wp_kses_post( $content );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Output the Sidebar.
	 *
	 * @return void
	 */
	public function output_sidebar(): void {
		$plugin_resources     = $this->sidebar['plugin_resources']['links'] ?? array();
		$additional_resources = $this->sidebar['additional_resources']['links'] ?? array();

		// Get the locale of the site but convert it to lowercase 2 letter language code.
		?>
			<div class="krokedil_settings__sidebar">
				<?php $this->output_sidebar_boxes(); ?>
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
			</div>
		<?php
	}
}
