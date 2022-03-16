# WSUWP A11y Status Changelog

Authors: Adam Turner, Washington State University  
URI: https://github.com/washingtonstateuniversity/wsuwp-plugin-a11y-status/

<!--
Changelog formatting (https://keepachangelog.com/en/1.0.0/):

## Major.MinorAddorDeprec.Bugfix YYYY-MM-DD

### Added (for new features.)
### Changed (for changes in existing functionality.)
### Deprecated (for soon-to-be removed features.)
### Removed (for now removed features.)
### Fixed (for any bug fixes.)
### Security (in case of vulnerabilities.)
-->

## 1.2.0-rc.1 (:construction: TBD)

### Changed

- Add Update URI plugin header, close #48. (1909d29)
- Upgrade stylelint from 13.13.1 to 14.6.0. (6253e15)
- Upgrade @wordpress/stylelint-config from 19.1.0 to 20.0.2. (67b6eee)
- Upgrade browserlist database. (1ac4851)
- Upgrade npm-package-json-lint from 5.1.0 to 6.0.3. (4779ff1)
- Upgrade postcss-preset-env from 6.7.0 to 7.4.2. (e439fc1)
- Update postcss from 8.3.6 to 8.4.12. (77cc5d9)
- Upgrade postcss-cli from 8.3.1 to 9.1.0. (f8f3dfe)
- Update cssnano from 5.0.7 to 5.1.4. (565b2ee)
- Update roave/security-advisories dev-master from fc5e5d7 to 86b842d. (e2c7be2)

### Security

- Bump squizlabs/php_codesniffer from 3.6.0 to 3.6.2. (9d1870e)
- Bump dealerdirect/phpcodesniffer-composer-installer from 0.7.1 to 0.7.2. (8fb4ae1)
- Bump sirbrillig/phpcs-variable-analysis from 2.11.2 to 2.11.3. (e99ff1a)
- Bump @wordpress/npm-package-json-lint-config from 4.1.0 to 4.1.2. (4681512)
- Bump ansi-regex from 5.0.0 to 5.0.1. (ea71801)
- Bump nanoid from 3.1.23 to 3.2.0. (1f2ecf6)
- Bump nth-check from 2.0.0 to 2.0.1. (5fe1952)
- Bump path-parse from 1.0.6 to 1.0.7. (1d9fc95)

## 1.1.0 (2021-07-22)

### Added

- Close #24 add feature to send a11y reminder to users via email. (6ecc917)
- Create pull request and issue templates, close #39. (9ffffe1)
- Guide for contributors in CONTRIBUTING.md. (9ffffe1)
- Add postcss dependency for postcss-cli. (f0fd2ef)

### Changed

- Bump WP tested-to to 5.8.
- Close #28 make a11y status user table column sortable. (855dd27)
- Close #29 set default permission level to network/site admin. (c1d98b7)
- Update README document with new badges and add contributing info. (9ffffe1)
- Replace stylelint-config-wordpress with @wordpress/stylelint-config. (136b69e)
- Replace Travis CI in favor of GitHub Actions for coding standards CI. (c5200f6)
- Replace GPL license 2.0 with GPL license 3.0. (5dcb580, fe4b583)
- Update Composer dependencies for v2 and simplify scripts. (d4ce1e8)
- Upgrade postcss-cli from 6.1.3 to 8.3.1. (f0fd2ef)
- Upgrade cssnano from 4.1.x to 5.0.7. (f0fd2ef, b5c0935)
- Upgrade NPM Package JSON Lint and config. (7758fa6, b5c0935)
- Upgrade stylelint from 11.0.0 to 13.13.1. (136b69e)
- Bump hosted-git-info from 2.7.1 to 2.8.9. (ff9d4b2)
- Update @wordpress/stylelint-config from 19.0.5 to 19.1.0. (b5c0935)

### Removed

- Remove unneeded script linting in build tools. (b351611)

### Fixed

- Fix no html content in translated string linting issue. (9bad7c4)

### Security

- Bump postcss from 8.3.5 to 8.3.6. (b5c0935)
- Bump rimraf from 3.0.0 to 3.0.2. (92fbc64)
- Bump browserslist from 4.4.1 to 4.16.6. (5f3d794)
- Bump lodash from 4.17.19 to 4.17.21. (4cfcc76)
- Bump y18n from 4.0.0 to 4.0.1. (642215a)
- Bump dot-prop from 4.2.0 to 4.2.1. (dfdc424)
- Bump ini from 1.3.5 to 1.3.7. (d8ad793)
- Bump lodash from 4.17.15 to 4.17.19. (dc88f93)

## 1.0.1 (2020-06-11)

### Changed

- Rename "master" branch to "stable." See the Internet Engineering Task Force (IEFT), [Terminology, Power and Oppressive Language](https://tools.ietf.org/id/draft-knodel-terminology-00.html#rfc.section.1.1.1).

## 1.0.0 (2019-09-16)

### Fixed

- :bug: User a11y status functions should exit silently when no data exists.
- :bug: Using a nonexistent variable in `get_user_a11y_training_url`.
- Fix #18 Don't modify a11y expiration date when certification expires. Keep the old expiration date until certification is renewed.
- :bug: Fix #17 Don't overwrite `was_certified` value on expiration. Merge existing data with new data instead of replacing it entirely.
- :warning: PHP and CSS lint warnings from updated rules.

### Changed

- Set the Setup class 'basename' property to static to allow accessing it from within the static activate/deactivate/uninstall methods.
- Save plugin version in an option instead of always retrieving from `get_plugin_data`.
- Retrieve the WSU API URL from a plugin setting.
- :lock: Close #25 Allow only site admins to modify WSU NID usermeta value.
- :wrench: Replace manual stylelint config file with modified WP default rules.
- :wrench: Update npm package metadata and scripts.
- :wrench: Close #20 Use up-to-date WP linting configuration.
- :truck: Move plugin settings API methods to a dedicated `settings.php` file.
- :truck: Move all user messaging functions to a dedicated `notices.php` file.
- :truck: Move all admin page functions to a dedicated `admin.php` file.
- :truck: Move all user-related functions, like getting/setting user meta, to a dedicated User API file.
- Simplify setting the plugin basename value in the Setup class.
- :recycle: Standardize user a11y meta methods to use similar syntax.
- :truck: Move sanitizing and formatting methods to a dedicated formatting API file.
- :truck: Move API handler from the setup class to a dedicated API class.
- Use namespaces in all PHP files.
- :arrow_up: Composer wp-coding-standards/wpcs to 2.1.1.
- :arrow_up: npm stylelint 10.1.0 to 11.0.0.
- :arrow_up: npm rimraf to 3.0.0.

### Added

- Close #26 Plugin update methods to watch for out-of-date database keys, prompt the user to update the database, and process the update action.
- Uninstall methods to handle deleting usermeta, settings, and options saved to the WP database.
- Logic to update plugin status to "deactivated" on plugin deactivation and to better handle re-activation.
- Create a plugin status option in the `*_options` table to monitor activation/upgrade status.
- Set up a method on `admin_init` to watch for version changes to fire upgrade actions if the plugin is sideloaded (skips the activation hook).
- :sparkles: Close #23 Register a settings page and nav menu item for the plugin as a subpage in the main Settings nav menu.
- Create the plugin settings page output in a new `views` directory.
- Set default plugin settings on plugin activation or manual upgrade when they don't already exist.
- :heavy_plus_sign: WP stylelint configuration npm dev dependency.
- :wrench: Configuration file for the `npm-package-json-lint` dependency.
- :heavy_plus_sign: NPM dev dependencies for linting `package.json` files.

### Removed

- :fire: Not using a grace period, so remove all of those functions and logic.
- :fire: Not fetching data for all users so remove the `get_usernames_list` method.
- Fix #22 Don't automatically fetch accessibility status data for all users on plugin activation or login.

## 0.10.0 (2019-08-07)

### Fixed

- :green_heart: Fix PHP linting errors found in initial CI test and following PHP rules update.
- :bug: Fix #16 Let empty bulk user list actions fail silently.
- :bug: Fix #10 Direct refresh a11y status from admin notice message button to admin home to prevent errors on select pages.
- :bug: Fix #12 Delete usermeta on uninstall.

### Changed

:arrow_up: Upgrade Composer dev dependencies (including PHP linting and CS rules).
- Clean up status actions handler to prevent returning excess unneeded data.

### Added

- :wrench: Configure Travis CI testing on master branch, close #9.

## 0.9.2 (2019-04-25)

### Fixed

- :bug: Fix #14 Move `get_plugin_data` into admin-only method to prevent function not defined error on non-admin pages.

## 0.9.1 (2019-03-14)

### Fixed

- :bug: Fix #11 Load plugin and set properties in activation method to prevent empty API URL error.

## 0.9.0 (2019-03-13)

### Fixed

- :bug: A mistyped comparison operator in the `handle_a11y_status_actions` method blocked non-admins from refreshing their own accessibility data.

### Changed

- Replace the email-to-username method of generating NIDs with the more specific `get_user_wsu_nid` method, which checks the user's usermeta for a saved WSU NID value and falls back to the previous email-to-username conversion method.
- :zap: During login action, only fetch new API data for certified users when nearing expiration to save on requests. (Per-user and batch update methods will still always fetch new data.)

### Added

- Add a refresh button to the "remind" admin notice to allow users to refresh their accessibility status data manually.
- :lock: Add admin nonce verification on WSU NID profile form handler.
- :sparkles: Add a new form field on the user profile screen -- along with an update handler -- to allow users to manually save a WSU network ID to the user metadata to use with the accessibility checker (to override the email address in case the email address isn't a WSU email).

## 0.8.0 (2019-03-12)

### Fixed

- :bug: Fix #6 Correct date diff calculation for grace period to count down instead of up.
- :bug: Fix #5 Return expiration dates whether user certified or not.

### Changed

- :card_file_box: Consolidate accessibility data sanitizing and formatting into the fetch method.
- Clean up getting and printing methods using newly sanitized and normalized accessibility data.
- :memo: Clean up and expand some documentation.
- Get plugin version from plugin data (set plugin data in newly added `set_properties` method).

### Added

- Method to get the WSU Accessibility Training course URL from the user metadata.
- Method `set_properties` to set plugin properties when instantiated.
- Formatting method to convert email addresses into usernames.

### Removed

- Remove `set_endpoint_props` method that set the `$url` and `$users` property in favor of setting the URL once and setting the users only as needed.
- No longer needed `$users` property.

## 0.7.0 (2019-03-11)

### Changed

- Load the plugin on the `plugins_loaded` hook instead of `after_setup_theme`.
- Refactored singleton instance setup method to use `isset()`.

### Added

- An uninstall method (which fires only when the plugin is deleted from the Plugins admin screen) that removes all plugin data from the from the database.
- Activation method to fetch API data and populate the user metadata for all users when the plugin is initially activated.
- An action on the `user_register` hook, which fires immediately after a user is added to the database, to fetch and save accessibility training data for that user.

## 0.6.0 (2019-03-08)

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
