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
- `Csvj\Csvj::parse()` reader and `Csvj\Csvj::stringify()` writer with
  strict §1 enforcement from day one: empty input, missing trailing
  newline, ragged rows, duplicate header names, non-string header
  values, and any value outside `string | int | float | bool | null`
  are rejected. JSON lexical rules (RFC 8259) flow through PHP's
  `json_decode`, so leading zeros, `NaN`, `Infinity`, `True`/`Null`,
  single-quoted strings, bare `.5` / trailing `1.`, and unescaped
  control characters in strings are all rejected.
- PHPUnit suite with 45 cases covering reader, writer, round-trip, and
  every must-reject vector from the conformance suite. All 25 vectors
  in `csvj-org/conformance@master` pass (CI wiring tracked separately
  per PLAN §7a.3).
