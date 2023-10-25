# Changelog

## 1.2.0 - 2023-04-04

### Added

- Support for Nextcloud 26 and 27
- Support for PHP 8.2
- Translations for Czech, Dutch and Occitan

### Changed

- Dependencies upgrade

## 1.1.0 - 2022-08-14

### Added

- Support for Nextcloud 25.

### Removed

- Support for Nextcloud 22 and 23.
- Support for PHP < 7.4

## 1.0.4 - 2022-02-14

### Changed

- Updated German translation
- Update dependencies

### Fixed

- The `league/commonmark` dependency is now wrapped inside our own namespace with `coenjacobs/mozart` to avoid conflicts with other apps which use the same dependencies at a different version, such as Polls.

## 1.0.3 - 2022-01-24

### Changed

- Allowed PHP 8.1

## 1.0.2 - 2022-01-24

### Changed

- Upgraded dependencies
- Updated translations

## 1.0.1 - 2021-12-10

### Fixed

- Handle existing values with no page information properly

## 1.0.0 - 2021-12-07

### Added

- Support for Nextcloud 22, 23 and 24.
- Possibility to enable Github Markdown Styles on notes, for pretty display
- Allow to specify the pages where the note is displayed. In addition to the login page, you may now add notes on 2FA pages.

### Removed

- Support for Nextcloud 19, 20 and 21.

## 0.4.0 - 2021-03-01
### Added
* Support for Nextcloud 21 and 22
* Support for PHP 8.0

### Removed
* Drop support for Nextcloud 17 and Nextcloud 18

### Internal
* Improved CI, lint, tests and static analysis

### Translations
* German (new!)

## 0.3.0 - 2020-08-28
### Added
* Support for Nextcloud 20

### Fixed
* Release with new certificate (fixes #4)

### Changed
* Bump dependencies

### Removed
* Drop support for Nextcloud 16.

## 0.2.1 - 2020-04-19
### Fixed
* Fixed an issue between app's and Nextcloud's incompatible Symfony Event Dispatcher version

## 0.2.0 - 2020-04-19
### Added
* Option to pick between centered text and aligned notes
* CI for NodeJS & PHP lint checks

### Changed
* Handle correct text direction for RTL languages

## 0.1.0 - 2020-04-15
* Initial release.
