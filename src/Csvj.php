<?php

declare(strict_types=1);

namespace Csvj;

/**
 * CSVJ reader and writer. The public surface is two static methods:
 *   - Csvj::parse(string $bytes): array{header: list<string>, rows: list<list<mixed>>}
 *   - Csvj::stringify(array $table): string
 *
 * Both are placeholders until reader/writer implementation lands.
 */
final class Csvj
{
    /**
     * @return array{header: list<string>, rows: list<list<mixed>>}
     */
    public static function parse(string $bytes): array
    {
        throw new \LogicException('Csvj::parse is not yet implemented');
    }

    /**
     * @param array{header: list<string>, rows: list<list<mixed>>} $table
     */
    public static function stringify(array $table): string
    {
        throw new \LogicException('Csvj::stringify is not yet implemented');
    }
}
