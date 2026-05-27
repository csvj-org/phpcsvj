<?php

declare(strict_types=1);

namespace Csvj;

/**
 * CSVJ reader and writer. The public surface is two static methods:
 *   - Csvj::parse(string $bytes): array{header: list<string>, rows: list<list<mixed>>}
 *   - Csvj::stringify(array $table): string
 *
 * Spec: https://csvj.org. Strict §1 enforcement from day one:
 * empty input, missing trailing newline, ragged rows, and duplicate
 * header names are rejected. Values are restricted to string, int,
 * float, bool, and null.
 */
final class Csvj
{
    /**
     * Parse a CSVJ document. Returns ['header' => list<string>, 'rows' => list<list<mixed>>].
     *
     * @return array{header: list<string>, rows: list<list<mixed>>}
     * @throws ParseError on any input the spec says must be rejected.
     */
    public static function parse(string $bytes): array
    {
        if ($bytes === '') {
            throw new ParseError('empty input');
        }
        if (!str_ends_with($bytes, "\n")) {
            throw new ParseError('file does not end with newline');
        }

        $body = substr($bytes, 0, -1);
        $rawLines = explode("\n", $body);

        $lines = [];
        foreach ($rawLines as $line) {
            if ($line !== '' && substr($line, -1) === "\r") {
                $line = substr($line, 0, -1);
            }
            $lines[] = $line;
        }

        $headerValues = self::parseLine(array_shift($lines), 'header');

        $header = [];
        foreach ($headerValues as $i => $v) {
            if (!is_string($v)) {
                throw new ParseError('non-string item at csvj header');
            }
            $header[] = $v;
        }

        $seen = [];
        foreach ($header as $name) {
            if (isset($seen[$name])) {
                throw new ParseError(sprintf('duplicate header name %s', json_encode($name)));
            }
            $seen[$name] = true;
        }

        $rows = [];
        $headerLen = count($header);
        foreach ($lines as $idx => $line) {
            $rowNum = $idx + 1;
            $row = self::parseLine($line, sprintf('row %d', $rowNum));
            if (count($row) !== $headerLen) {
                throw new ParseError(sprintf(
                    'row %d has %d values, header has %d',
                    $rowNum,
                    count($row),
                    $headerLen,
                ));
            }
            $rows[] = $row;
        }

        return ['header' => $header, 'rows' => $rows];
    }

    /**
     * Serialize a table to CSVJ bytes. Always returns content terminated
     * with a single `\n`. Every row must have exactly count($header) values.
     *
     * @param array{header: list<string>, rows: list<list<mixed>>} $table
     * @throws \InvalidArgumentException on malformed input.
     */
    public static function stringify(array $table): string
    {
        if (!array_key_exists('header', $table) || !is_array($table['header'])) {
            throw new \InvalidArgumentException('table must have a header array');
        }
        if (!array_key_exists('rows', $table) || !is_array($table['rows'])) {
            throw new \InvalidArgumentException('table must have a rows array');
        }

        $header = $table['header'];
        foreach ($header as $i => $v) {
            if (!is_string($v)) {
                throw new \InvalidArgumentException(sprintf('header item %d is not a string', $i));
            }
        }

        $seen = [];
        foreach ($header as $name) {
            if (isset($seen[$name])) {
                throw new \InvalidArgumentException(sprintf('duplicate header name %s', json_encode($name)));
            }
            $seen[$name] = true;
        }

        $headerLen = count($header);
        $out = self::serializeRow($header);

        foreach ($table['rows'] as $i => $row) {
            if (!is_array($row)) {
                throw new \InvalidArgumentException(sprintf('row %d is not an array', $i));
            }
            if (count($row) !== $headerLen) {
                throw new \InvalidArgumentException(sprintf(
                    'row %d has %d values, expected %d',
                    $i,
                    count($row),
                    $headerLen,
                ));
            }
            $out .= "\n" . self::serializeRow($row);
        }

        return $out . "\n";
    }

    /**
     * @return list<mixed>
     */
    private static function parseLine(string $body, string $label): array
    {
        try {
            /** @var mixed $decoded */
            $decoded = json_decode('[' . $body . ']', true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ParseError(sprintf('%s parse error: %s', $label, $e->getMessage()));
        }

        if (!is_array($decoded) || !array_is_list($decoded)) {
            throw new ParseError(sprintf('%s parse error: not a JSON array', $label));
        }

        foreach ($decoded as $i => $v) {
            if ($v === null || is_string($v) || is_int($v) || is_float($v) || is_bool($v)) {
                continue;
            }
            throw new ParseError(sprintf('%s parse error at item %d', $label, $i));
        }

        return $decoded;
    }

    /**
     * @param list<mixed> $row
     */
    private static function serializeRow(array $row): string
    {
        if (!array_is_list($row)) {
            throw new \InvalidArgumentException('row must be a list (sequential integer keys)');
        }

        foreach ($row as $i => $v) {
            if ($v === null || is_string($v) || is_bool($v) || is_int($v)) {
                continue;
            }
            if (is_float($v)) {
                if (!is_finite($v)) {
                    throw new \InvalidArgumentException(sprintf('item %d is not CSVJ type-safe: non-finite number', $i));
                }
                continue;
            }
            throw new \InvalidArgumentException(sprintf('item %d is not CSVJ type-safe: %s', $i, gettype($v)));
        }

        try {
            $json = json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException $e) {
            throw new \InvalidArgumentException('json_encode failed: ' . $e->getMessage());
        }

        return substr($json, 1, -1);
    }
}
