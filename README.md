# WSUWP A11y Status [:construction: Work in Progress]

## Overview

This WordPress plugin is designed to help site administrators monitor the WSU Web Accessibility Training status of all users on the site and to notify individual users when their training status needs attention.

## Description

WSUWP A11y (Accessibility) Status retrieves WSU Web Accessibility Training status data from the WSU Accessibility Training API for all users registered on a WordPress site using a WSU email address. **(Users must be registered with their WSU email in order to retrieve data.)**

### All users will see:

- A warning notice in the WordPress admin area with a link to take the training if they are not WSU Accessibility certified or if their certification has expired.
- A caution notice in the WP admin area if their certification is due to expire in less than one (1) month, also with a link to take the training.

### Admin users will also see:

- An additional column in the users table list on the Users screen showing the WSU Accessibility Training status for each user: "None" for users that are not certified and the time remaining until required recertification for users that are.

## For Developers

The WSU HRS Theme development environment relies primarily on the NPM and Composer package managers. The `package.json` and `composer.json` configuration files will install the necessary dependencies for testing and building the production version of the theme. The NPM scripts in `package.json` do most of the heavy lifting.

### Initial Setup

1. Clone the WSUWP A11y Status plugin to a directory on your computer.
2. Change into that directory.
3. Install the Composer dependencies.
4. Install the NPM dependencies.
5. Ensure PHP and CSS linting coding standards checks are working -- this should exit with zero (0) errors.
6. Create a new branch for local development.

In a terminal:

~~~bash
git clone https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status.git wsuwp-a11y-status
cd wsuwp-a11y-status
composer install
npm install
npm test
git checkout -b new-branch-name
~~~

### Build Commands

The following commands will handle basic build functions. (Remove the `-s` flag to show additional debug info.)

- `npm run build`: Remove old compiled files such as minified CSS, lint PHP and CSS, and then compile new versions.
- `npm test`: Check all PHP and CSS files for coding standards compliance.
- `npm run clean`: Remove old compiled files such as minified CSS.
- `npm run style`: Compile CSS.
