<?php

declare(strict_types=1);

namespace Csvj\Tests\Conformance;

use Csvj\Csvj;
use Csvj\ParseError;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Drives the language-agnostic conformance suite from
 * https://github.com/csvj-org/conformance against this checkout.
 *
 * Vector tree root is taken from the `CSVJ_CONFORMANCE_DIR` env var,
 * set by the `conformance` GHA job. This suite is opt-in via
 * `--testsuite=conformance` so default local `phpunit` runs do not
 * require a sibling conformance checkout.
 */
final class ConformanceTest extends TestCase
{
    private static function vectorRoot(): string
    {
        $dir = getenv('CSVJ_CONFORMANCE_DIR');
        if ($dir === false || $dir === '') {
            throw new \RuntimeException(
                'CSVJ_CONFORMANCE_DIR env var must point at a checkout of csvj-org/conformance'
            );
        }
        return $dir;
    }

    #[DataProvider('inputVectors')]
    public function testAcceptVector(string $inputPath, string $expectedPath): void
    {
        $bytes = file_get_contents($inputPath);
        self::assertNotFalse($bytes, "read $inputPath");

        $table = Csvj::parse($bytes);
        $got = array_merge([$table['header']], $table['rows']);

        $expRaw = file_get_contents($expectedPath);
        self::assertNotFalse($expRaw, "read $expectedPath");
        $want = json_decode($expRaw, true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals($want, $got, "vector $inputPath");
    }

    #[DataProvider('mustRejectVectors')]
    public function testRejectVector(string $inputPath): void
    {
        $bytes = file_get_contents($inputPath);
        self::assertNotFalse($bytes, "read $inputPath");

        $this->expectException(ParseError::class);
        Csvj::parse($bytes);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function inputVectors(): iterable
    {
        $root = self::vectorRoot();
        $inputs = glob($root . '/inputs/*.csvj');
        if ($inputs === false || $inputs === []) {
            throw new \RuntimeException("no inputs/*.csvj vectors found under $root");
        }
        sort($inputs);
        foreach ($inputs as $in) {
            $stem = basename($in, '.csvj');
            yield $stem => [$in, $root . '/expected/' . $stem . '.json'];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function mustRejectVectors(): iterable
    {
        $root = self::vectorRoot();
        $rejects = glob($root . '/must-reject/*.csvj');
        if ($rejects === false || $rejects === []) {
            throw new \RuntimeException("no must-reject/*.csvj vectors found under $root");
        }
        sort($rejects);
        foreach ($rejects as $in) {
            yield basename($in, '.csvj') => [$in];
        }
    }
}
