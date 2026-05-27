# phpcsvj

[![CI](https://github.com/csvj-org/phpcsvj/actions/workflows/ci.yml/badge.svg)](https://github.com/csvj-org/phpcsvj/actions/workflows/ci.yml)

PHP reader and writer for [CSVJ](https://csvj.org) files. PHP 8.2+.

> **Status (2026-05-27):** repo scaffolding only. `Csvj::parse` and
> `Csvj::stringify` are placeholder methods that throw `LogicException` so
> consumers can pin against the public surface before reader/writer
> implementation lands.

## Overview

CSVJ is a tabular data format where each value is a JSON literal. The
spec is at <https://csvj.org>; the Go reference implementation lives at
[csvj-org/gocsvj](https://github.com/csvj-org/gocsvj), the JavaScript
reference at [csvj-org/jscsvj](https://github.com/csvj-org/jscsvj), and
the language-agnostic conformance suite at
[csvj-org/conformance](https://github.com/csvj-org/conformance).

This package targets parity with the conformance suite once
implementation lands.

## Parse

```php
use Csvj\Csvj;

$table = Csvj::parse("\"name\",\"age\"\n\"alice\",30\n\"bob\",null\n");
// [
//   'header' => ['name', 'age'],
//   'rows'   => [
//     ['alice', 30],
//     ['bob', null],
//   ],
// ]
```

The returned array has a `header` key (`list<string>`, zero or more
column names) and a `rows` key (`list<list<mixed>>`) where every row has
exactly `count($header)` values. Each value is `string`, `int`, `float`,
`bool`, or `null`.

Parsing rejects every input the spec says must be rejected — see the
[conformance suite](https://github.com/csvj-org/conformance) for the
full list. Invalid input raises `Csvj\ParseError`.

## Serialize

```php
use Csvj\Csvj;

$bytes = Csvj::stringify([
    'header' => ['name', 'age'],
    'rows' => [
        ['alice', 30],
        ['bob', null],
    ],
]);
// "\"name\",\"age\"\n\"alice\",30\n\"bob\",null\n"
```

The output is always spec-compliant CSVJ: terminated by `\n`, every row
has exactly `count($header)` values, and every value is encoded as a
JSON literal.

## Install

```sh
composer require csvj-org/phpcsvj
```

(Not yet published to Packagist — see
[PLAN.md §6d](https://github.com/csvj-org/website) for the publication
checklist.)

## License

MIT — see [LICENSE](LICENSE).
