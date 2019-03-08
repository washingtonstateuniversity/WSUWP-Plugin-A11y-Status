# WSUWP A11y Status Changelog

Authors: Adam Turner, Washington State University  
URI: https://github.com/washingtonstateuniversity/wsuwp-plugin-a11y-status/

<!--
Changelog formatting (http://semver.org/):

## Major.MinorAddorDeprec.Bugfix YYYY-MM-DD

### To Do (for upcoming changes)
### Security (in case of fixed vulnerabilities)
### Fixed (for any bug fixes)
### Changed (for changes in existing functionality)
### Added (for new features)
### Deprecated (for once-stable features removed in upcoming releases)
### Removed (for deprecated features removed in this release)
-->

## 0.6.0 (:construction: WIP 2019-03-08)

### Fixed

- Several phpcs standards issues.

### Added

- Methods to add a bulk action option on the Users list table bulk actions dropdown field to refresh accessibility status data from the API for all selected users and to execute that bulk refresh.
- A title attribute to the "A11y Status" column data in the Users list table to provide the date and time the data was last updated.
- Method to update an individual user's metadata with their WSU Accessibility Training status from the API as needed.
- An "immediate action" link in the list of action links displayed for each user row in the WP Users list table that triggers a manual refresh of that user's accessibility training status, along with a method to handle that refresh. The handler validates the request then calls the update individual user metadata method.
- An admin notice that displays after successfully refreshing a user's accessibility status metadata.

### Changed

- Consolidated all action-related admin notices into one callback method to display success and failure messages for individual and bulk accessibility status update actions.

## 0.5.0 (2019-03-06)

### Fixed

- Use the `wp_login` action's WP_User object parameter to check user capabilities instead of `current_user_can()`, which is maybe not available when the login action fires.

### Changed

- :card_file_box: Save accessibility status user data in the WordPress user metadata instead of in a transient. Replace all calls to `get_` and `set_transient` to `get_` and `update_user_meta`.
- :zap: Use the `wp_login` action hook instead of the `admin_init` hook to call the method that fetches data from the API and updates user data.
- :recycle: Separate the method that fetches API data and updates site data into separate methods: one to handle fetching and returning API data and the other to update user metadata.
- Set the API endpoint properties (the URL and users to check) from inside the fetch data update method instead of on login or admin init to allow for more easily performing updates on a per-user basis.
- Require the "users" list for the API endpoint to be in array format.
- :recycle: Update all `get_user_a11y_*` and `is_user_*` methods to use `get_user_meta()` and to check based on user ID instead of email.
- Update the admin notice to present different messages for users with no certification, expired certification, and soon-to-expire certification.
- Update the user list table A11y Status column to present different messages for users with no certification, expired certification, and soon-to-expire certification.

### Added

- Method to return the time remaining in a registered user's 30-day WSU Accessibility Training grace period.
- Method to return (true or false) whether a user was certified at any time in the past (to help distinguish expired certification from no certification).
- Save additional data to the "wsuwp_a11y_status" user metadata to record the date the API was last checked and whether or not the user has been certified in the past.
- :art: Prototype some styles on the user list table A11y Status column.

### Removed

- Custom `wsuwp_a11y_status_update` action hook.
- :fire: Admin screen filter methods and template that added a custom admin screen that is no longer needed. Using the default User list table instead.

## 0.4.1 (2019-02-22)

### Fixed

- Load admin styles on all admin pages. Admin enqueue style hook was set to fire only on the dedicated A11y Status admin screen, but the styles need to apply to the admin notice as well.

## 0.4.0 (2019-02-20)

### Changed

- Use an `admin_init` action hook to run the method that retrieves data from the API on every admin page load instead of once per hour. (The request method still only pings the API if the transient has expired.)

### Added

- Preliminary admin styles.
- Description and build instructions in the plugin Readme file.
- Error handling for get methods when no data is found. Instead of returning false the methods for getting user status info now return a WP_Error object if no data is found.

### Removed

- No longer using the WP cron scheduler to call the method that retrieves data from the API. It didn't work well with the method of storing the API results in a transient, leaving periods when the transient had expired but the cron job had yet to run.

## 0.3.0 (2019-02-19)

### Added

- Filter hook methods to add an "A11y Status" column to the Users table list and display the time remaining to certification expiration for each user.
- Display admin notices for logged-in users who are either not Accessibility certified or whose certification expires in less than one month.

### Changed

- Refactor the WSU Accessibility Training Status admin screen to use the new getter methods instead of querying the transient data directly.
- Make the WSU Accessibility Training Status admin screen display a full list of registered site users instead of listing only users in the transient data, and add an email column.

## 0.2.0 (2019-02-18)

### Added

- User accessibility certification conditional check methods to return whether a user is certified and whether their certification expires in less than one month.
- Methods to get user accessibility status data by email.
- Dedicated setup class method to set the plugin properties, including defining
the WSU Accessibility Training API endpoint properties.

### Changed

- Move endpoint property setup to the main plugin setup class.

## 0.1.0 (2019-02-15)

### Added

- Template to display the plugin admin screen.
- Plugin setup class with activation, deactivation, and API request methods, as well as an admin screen.
- Base plugin loader and index placeholder.
- Plugin documentation and licensing files.
- :wrench: Build tools and configuration.
- Initial configuration files.
