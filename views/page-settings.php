<?php
/**
 * WSU Accessibility Status plugin settings page output.
 *
 * @package Setup
 * @since 1.0.0
 */

use WSUWP\A11yStatus\Init;
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	<form action="options.php" method="post">
		<?php
		// Print security fields for registered setting `{{ plugin_slug }}_options`.
		settings_fields( Init\Setup::$slug );
		// Display the registered settings sections and fields.
		do_settings_sections( Init\Setup::$slug );
		// Display the save settings button.
		submit_button( 'Save Settings' );
		?>
	</form>
</div>
