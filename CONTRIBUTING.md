# Contributing and Maintaining

Thanks for contributing!

The following guidelines outline how to contribute and provide instructions about our workflow and release processes.

## Ways to contribute

Contributing includes reporting problems with the plugin, suggesting improvements or new features, or submitting pull requests with new code. All contributions are managed here on GitHub. Here are some ways you can help:

### Reporting bugs

If you run into an issue with the plugin, please take a look through [existing issues](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/issues) and [open a new one](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/issues/new/choose) if needed. If you're able, include steps to reproduce, environment information, and screenshots/screencasts as relevant.

### Suggesting enhancements

New features and enhancements are also managed via [issues](https://github.com/washingtonstateuniversity/WSUWP-Plugin-A11y-Status/issues/new/choose). Select "Feature request" and complete the form as applicable.

### Pull requests

Pull requests represent a proposed solution to a specified problem. They should always reference an issue that describes the problem and contains discussion about the problem itself. Discussion on pull requests should be limited to the pull request itself, i.e. code review.

## Documenting changes

### Commits

Commits should be small, individual items of work. They should focus distinctly on one type of change and be separate from other features. Each commit must include a short (no more than 70 characters) commit message summarizing the change. A longer description is optional.

### Semantic Versioning & Human-friendly Changelogs

We follow the [Semantic Versioning pattern](https://semver.org/) when assigning version numbers. Each version number must have three digits (including zero) in the <major>.<minor>.<patch> format, where:

We follow the [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) standard when maintaining changelogs.

## Workflow

The `develop` branch is the development branch, which means it contains the next version to be released. The `stable` branch contains the current latest release. Always work on the `develop` branch and open pull requests against `develop`.

### `Stable` and `Develop` branches

The stable branch stores the official release history, while the develop branch serves as an intermediary between feature branches and stable. All branches – except hotfixes – are branched off of develop instead of stable.

Changes should never be pushed directly to the stable branch. New features should be branched off of develop and, when complete, merged back into develop using a non-fast-forward merge.

### Develop on `feature` branches

Create a new branch from develop for development work. Feature branches should be given short names prefixed with the appropriate label for the type of change they introduce, such as `fix/name-of-issue` or `enhancement/update-to-feature`. We have a minimal set of labels, which we’ll add to and clean up as needed:

- fix: Correct something that isn’t worked.
- feature: A new feature.
- enhancement: Modification of existing functionality or features.
- documentation: Improvement or addition to documentation.
- dependencies: Changes to dependency files.
- management: Updates to project-level settings.

Make frequent, small commits to the feature branch. Frequently check develop against the feature branch to keep up with changes other developers are working on. When finished with the feature, merge the feature branch back into develop using a non-fast-forward merge (--no-ff): `git checkout develop && git merge --no-ff <label>/<feature-name>`.

### Deploying with `release` branches

When the develop branch is ready to be merged into stable as a new release, create a new `release/<version>` branch off of develop. In the `release/<version>` branch you will:

1. First bump version numbers to `<version>-rc.1`.
1. Update the changelog in CHANGELOG.md.
1. Update documentation.
1. Push the release branch to GitHub and create a pre-release targeting the release branch. Tag the pre-release `v<version>-rc.1`, title it `<version> RC 1` and copy the Changelog contents for the release into the description.
1. Use the release branch and the build RC 1 version to run tests and fix bugs. No new features will be added on this branch.
1. Once testing is finished, increment the version numbers again, to `<version>`, and update the changelog and any other docs if changes were made during testing.
1. Open a new pull request to merge `release/<version>` into stable. Title it `<version>` and complete the PR template as needed. When the pull request is ready, non-fast forward merge it into stable (or select “Merge pull request” in GitHub): `git checkout stable && git merge --no-ff release/<version> && git push origin stable`
1. Create a new release in GitHub targeting the stable branch. Tag the release `v<version>`, title it `<version>`, and copy the Changelog contents for the release into the description.
1. Last, merge stable back into develop with a --no-ff merge so that it contains any changes made on the release branch.

### Emergency patching with `hotfix` branches

Critical maintenance and fixes are implemented thorugh hotfix branches. These are the only branches made directly off the stable branch. They operate much like release branches, but their parent is the stable branch instead of develop. As soon as the fix is complete it is merged into stable and a new release is made. Then stable is merged into develop to keep both up to date.
