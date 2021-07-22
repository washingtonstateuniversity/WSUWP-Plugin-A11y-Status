# WSUWP A11y Status

[![Support Level](https://img.shields.io/badge/support-active-green.svg)](#support-level) [![Build Status](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/actions/workflows/coding-standards.yml/badge.svg)](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/actions) [![Release Version](https://img.shields.io/github/v/release/washingtonstateuniversity/WSUWP-Plugin-A11y-Status)](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/releases/latest) ![WordPress tested up to version 5.8.0](https://img.shields.io/badge/WordPress-v5.8.0%20tested-success.svg) [![GPLv3 License](https://img.shields.io/github/license/washingtonstateuniversity/WSUWP-Plugin-A11y-Status)](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/blob/develop/LICENSE.md)

## Overview

This WordPress plugin helps site administrators monitor the WSU Web Accessibility Training status of all users on the site and to notify individual users when their training status needs attention.

## Description

WSUWP A11y (Accessibility) Status retrieves WSU Web Accessibility Training status data from the WSU Accessibility Training API using a WSU network ID. The plugin adds a "WSU NID" field on the user profile page where site admins can enter a WSU NID for each user. When doing an API call, it'll check for this value first, and then fall back to the user portion of the user email address. This means valid WSU email addresses will work without the need to set the WSU NID value manually.

### All users will see:

- An error notice in the WordPress admin area with a link to take the training if they are not WSU Accessibility certified or if their certification has expired.
- A warning notice in the WordPress admin area if their certification is due to expire in less than one (1) month, also with a link to take the training.

### Admin users will also see:

- An additional column in the users table on the Users screen showing the WSU Accessibility Training status for each user: "None" for users that are not certified and the time remaining until required recertification for users that are. (Admins can also hover over this value to display a tooltip with the date of the last successful API call.)

## For Developers

The WSUWP A11y Status plugin development environment relies primarily on NPM and Composer. The `package.json` and `composer.json` configuration files manage necessary dependencies for testing and building the production version of the theme. The NPM scripts in `package.json` do most of the heavy lifting.

### Initial Setup

1. Clone the WSUWP A11y Status plugin to a directory on your computer.
2. Change into that directory.
3. Install the NPM and Composer dependencies.
4. Ensure linting and coding standards checks are working -- this should exit with zero (0) errors.
5. Create a new branch for local development.

In a terminal:

~~~bash
git clone https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status.git wsuwp-a11y-status
cd wsuwp-a11y-status
npm install; composer install
npm test -s
git checkout -b new-branch-name
~~~

### Build Commands

The following commands will handle basic build functions. (Remove the `-s` flag to show additional debug info.)

- `npm run build -s`: Remove old compiled files such as minified CSS, lint PHP and CSS, and then compile new versions.
- `npm test -s`: Check all PHP and CSS files for coding standards compliance.
- `npm run clean -s`: Remove old compiled files such as minified CSS.
- `npm run build:styles -s`: Compile CSS.

See the scripts section of `package.json` for additional available commands.

## Support Level

**Active:** WSU HRS actively works on this plugin. We plant to continue work for the foreseeable future, adding new features, enhancing existing ones, and maintaining compatability with the latest version of WordPress. Bug reports, feature requests, questions, and pull requests are welcome.

## Changelog

All notable changes are documented in the [CHANGELOG.md](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/blob/develop/CHANGELOG.md), with dates and version numbers.

## Contributing

Please submit bugs and feature requests through [GitHub Issues](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/issues). Refer to [CONTRIBUTING.md](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/blob/develop/CONTRIBUTING.md) for the development workflow and details for submitting pull requests.
