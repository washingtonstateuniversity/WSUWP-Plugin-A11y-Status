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
