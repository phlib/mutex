# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]
### Added
- Allow `phlib/config` v3 dependency.

## [3.0.1] - 2025-02-11
### Added
- Allow `phlib/db` v3 dependency.

## [3.0.0] - 2021-11-22
### Added
- Add support for PHP v8.0 .
- Type declarations have been added to all method parameters and return types
  where possible.
### Changed
- **BC break**: Constructor of `MySQL` requires instance of `\Phlib\Db\Adapter`
  instead of a raw DB config array. *Phlib/Db* is a new package dependency.
  Implementations can pass their existing DB config to `Adapter`. See *README*.
- **BC break**: Reduce visibility of internal methods and properties. These
  members are not part of the public API. No impact to standard use of this
  package. If an implementation has a use case which needs to override these
  members, please submit a pull request explaining the change.
### Removed
- **BC break**: Removed support for PHP versions <= v7.3 as they are no longer
  [actively supported](https://php.net/supported-versions.php) by the PHP project.

## [2.0.0] - 2018-02-28
### Added
- Apply GNU LGPLv3 software licence
### Changed
- Upgrade *phlib/config* to v2 to allow usages of this package to also update
  their dependency versions
### Removed
- Drop support for PHP 5

## [1.0.1] - 2015-06-25
### Changed
- Fix connection re-use in MySQL mutex 

## [1.0.0] - 2015-06-23
Initial Release

### Added
- Helper method `getOrCreate()`
