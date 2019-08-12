<?php
/**
 * WSU Accessibility Status plugin Settings API.
 *
 * @package Setup
 * @since 1.0.0
 */

namespace WSUWP\A11yStatus\settings;

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
	if ( ! current_user_can( 'list_users' ) ) {
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
	if ( ! current_user_can( 'list_users' ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	// Sanitize input data.
	$wsu_nid = sanitize_text_field( wp_strip_all_tags( $_POST['wsu_nid'] ) );

	// Create/update user metadata for the given user ID.
	return update_user_meta( $user_id, '_wsu_nid', $wsu_nid );
}
