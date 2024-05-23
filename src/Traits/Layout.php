<?php
namespace Krokedil\SettingsPage\Traits;

trait Layout {
	use Sidebar;
	use Subsection;

	/**
	 * Output the layout.
	 *
	 * @return void
	 */
	public function output() {
		wp_enqueue_style( 'krokedil-settings-page' );

		?>
		<div class="krokedil_settings__wrapper">
			<?php $this->output_subsection(); ?>
			<?php $this->output_sidebar(); ?>
		</div>
		<?php
	}
}
