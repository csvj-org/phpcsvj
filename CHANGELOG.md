# Changelog

All notable changes to this project will be documented in this file. The
format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
This project does not follow Semantic Versioning until 1.0.0; before
then breaking changes may occur in any release.

## [Unreleased]

### Added

- Initial repository scaffolding: Composer package `csvj-org/phpcsvj`,
  PSR-4 autoload, PHPUnit 11, GHA CI matrix over PHP 8.2 / 8.3 / 8.4
  with SHA-pinned third-party actions, Dependabot config for composer
  and github-actions.
- Public surface stub: `Csvj\Csvj::parse()` and `Csvj\Csvj::stringify()`
  static methods (both throw `LogicException` pending implementation),
  plus a `Csvj\ParseError` exception class.
