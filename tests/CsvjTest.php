<?php

declare(strict_types=1);

namespace Csvj\Tests;

use Csvj\Csvj;
use PHPUnit\Framework\TestCase;

final class CsvjTest extends TestCase
{
    public function testParsePlaceholderThrows(): void
    {
        $this->expectException(\LogicException::class);
        Csvj::parse("\n");
    }

    public function testStringifyPlaceholderThrows(): void
    {
        $this->expectException(\LogicException::class);
        Csvj::stringify(['header' => [], 'rows' => []]);
    }
}
