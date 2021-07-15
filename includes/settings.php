<?php
/**
 * WSU Accessibility Status plugin Settings API.
 *
 * @package Setup
 * @since 1.0.0
 */

namespace WSUWP\A11yStatus\settings;

use WSUWP\A11yStatus\Init;

/**
 * Assigns the default plugin settings when they do not already exist.
 *
 * Only fires on plugin activation, and only for settings that can be
 * modified from the plugins settings page.
 *
 * @since 1.0.0
 */
function set_default_settings() {
	$default_settings = array(
		'api_url' => 'https://webserv.wsu.edu/accessibility/training/service',
	);
	$current_settings = get_option( Init\Setup::$slug . '_options' );

	$settings = array();
	if ( ! $current_settings ) {
		// Use the defaults if there are no existing settings.
		$settings = $default_settings;
	} else {
		// If settings already exist, don't overwrite them.
		foreach ( $default_settings as $key => $value ) {
			if ( ! isset( $current_settings[ $key ] ) ) {
				$settings[ $key ] = $value;
			} else {
				$settings[ $key ] = $current_settings[ $key ];
			}
		}
	}
	update_option( Init\Setup::$slug . '_options', $settings );
}


/**
 * Displays a field on the user profile screen to add a WSU NID.
 *
 * Callback method for the `edit_user_profile` and `show_user_profile` hooks
 * that allow adding fields and data to the user profile page for, respectively,
 * users viewing other user profiles and users viewing their own profile.
 *
 * @since 0.9.0
 *
 * @param WP_User $user The WP_User object of the user being edited.
 * @return void
 */
function usermeta_form_field_nid( $user ) {
	// Only allow administrators to modify the WSU NID usermeta.
	if ( ! current_user_can( 'edit_users') ) {
		return;
	}
	?>
	<h2>WSU Network ID</h2>
	<table class="form-table">
		<tbody>
			<tr class="user-wsu-nid-wrap">
				<th>
					<label for="wsu_nid"><?php esc_html_e( 'WSU NID', 'wsuwp-a11y-status' ); ?></label>
				</th>
				<td>
					<input type="text" name="wsu_nid" id="wsu_nid" aria-describedby="nid-description" value="<?php echo esc_attr( get_user_meta( $user->ID, '_wsu_nid', true ) ); ?>" class="regular-text">
					<p class="description" id="nid-description">Enter a WSU Network ID if not using a WSU email address.</p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Handles data submitted from the WSU NID field on the user profile screen.
 *
 * Callback method for the `edit_user_profile_update` hook, which triggers when
 * a user submits data to update another user's profile, and the
 * `personal_options_update`, which triggers when a user submits data to update
 * their own profile.
 *
 * @since 0.9.0
 *
 * @param int $user_id Optional. The user ID of the user being edited.
 * @return int|bool Meta ID if a new key was created, or true if value was updated and false on failure or no change
 */
function usermeta_form_field_nid_update( $user_id ) {
	check_admin_referer( 'update-user_' . $user_id );

	// Check permissions.
	if ( ! current_user_can( 'edit_users') || ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	// Sanitize input data.
	$wsu_nid = sanitize_text_field( wp_strip_all_tags( $_POST['wsu_nid'] ) );

	// Create/update user metadata for the given user ID.
	return update_user_meta( $user_id, '_wsu_nid', $wsu_nid );
}

/**
 * Registers plugin settings and settings form fields.
 *
 * @since 1.0.0
 */
function register_settings() {
	register_setting(
		Init\Setup::$slug,
		Init\Setup::$slug . '_options'
	);

	add_settings_section(
		Init\Setup::$slug . '_section_api',
		__( 'WSU Accessibility API URL', 'wsuwp-a11y-status' ),
		__NAMESPACE__ . '\settings_section_api',
		Init\Setup::$slug
	);

	add_settings_field(
		Init\Setup::$slug . '_options[api_url]',
		__( 'WSU API URL', 'wsuwp-a11y-status' ),
		__NAMESPACE__ . '\settings_field_api_url',
		Init\Setup::$slug,
		Init\Setup::$slug . '_section_api',
		array(
			'label_for' => Init\Setup::$slug . '_options[api_url]',
			'class'     => Init\Setup::$slug . '_row',
		)
	);
}

/**
 * Displays content on the plugin settings API section before the fields.
 *
 * @since 1.0.0
 *
 * @param array $args {
 *     Array of parameters defined in the `add_settings_section` function.
 *
 *     @type string   $id       The slug-name used to identify the section and in the 'id' attribute of tags.
 *     @type string   $title    Formatted title of the section. Shown as the heading for the section.
 *     @type callable $callback Function that echoes any content at the top of the section (between heading and fields).
 * }
 */
function settings_section_api( $args ) {
	printf(
		'<p id="%1$s">%2$s</p>',
		esc_attr( $args['id'] ),
		esc_html__(
			'The URL of the WSU accessibility training certification API should
			be entered without any query strings. The required NID parameter is
			added automatically by the plugin during the API request.',
			'wsuwp-a11y-status'
		)
	);
}

/**
 * Displays the field inputs for the plugin settings API URL field.
 *
 * @since 1.0.0
 *
 * @param array $args
 *     The optional extra arguments used when outputting the field, defined in
 *     `add_settings_field`.
 *
 *     @type string $label_for An optional setting title to wrap in a `<label>` element.
 *     @type string $class     A CSS class to be added to the field `<tr>` element.
 */
function settings_field_api_url( $args ) {
	// Get the value of the setting registered with `register_setting`.
	$options = get_option( Init\Setup::$slug . '_options' );

	printf(
		'<input type="text" name="%1$s" id="%1$s" aria-describedby="%2$s" value="%3$s" class="regular-text"><p class="description" id="%2$s">%4$s</p>',
		$args['label_for'],
		'wsu-api-url-description',
		$options['api_url'],
		__( 'Enter the full WSU API URL without the query string.', 'wsuwp-a11y-status' )
	);
}
