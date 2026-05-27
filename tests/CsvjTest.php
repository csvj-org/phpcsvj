<?php

declare(strict_types=1);

namespace Csvj\Tests;

use Csvj\Csvj;
use Csvj\ParseError;
use PHPUnit\Framework\TestCase;

final class CsvjTest extends TestCase
{
    public function testParseSimpleStrings(): void
    {
        $bytes = "\"Header1\", \"Header2\", \"Header3\"\n"
            . "\"Row1\", \"Row2\", \"Row3\"\n";

        $table = Csvj::parse($bytes);

        self::assertSame(['Header1', 'Header2', 'Header3'], $table['header']);
        self::assertSame([['Row1', 'Row2', 'Row3']], $table['rows']);
    }

    public function testParseMixedTypes(): void
    {
        $bytes = "\"h1\", \"h2\", \"h3\"\n"
            . "42, 3.14, false\n"
            . "null, true, \"trailing\"\n";

        $table = Csvj::parse($bytes);

        self::assertSame(['h1', 'h2', 'h3'], $table['header']);
        self::assertSame(
            [
                [42, 3.14, false],
                [null, true, 'trailing'],
            ],
            $table['rows'],
        );
    }

    public function testParseCRLFLineEndings(): void
    {
        $bytes = "\"h1\",\"h2\",\"h3\"\r\n"
            . "1,2,3\r\n";

        $table = Csvj::parse($bytes);

        self::assertSame(['h1', 'h2', 'h3'], $table['header']);
        self::assertSame([[1, 2, 3]], $table['rows']);
    }

    public function testParseUTF8Values(): void
    {
        $bytes = "\"h1\", \"h2\", \"h3\"\n"
            . "\"héllo\", \"日本語\", \"🚀\"\n";

        $table = Csvj::parse($bytes);

        self::assertSame([['héllo', '日本語', '🚀']], $table['rows']);
    }

    public function testParseJSONEscapes(): void
    {
        $bytes = "\"h1\", \"h2\", \"h3\", \"h4\"\n"
            . "\"line1\\nline2\", \"tab\\there\", \"quote\\\"end\", \"backslash\\\\\"\n";

        $table = Csvj::parse($bytes);

        self::assertSame(
            [["line1\nline2", "tab\there", "quote\"end", "backslash\\"]],
            $table['rows'],
        );
    }

    public function testParseUnicodeEscapeSurrogatePair(): void
    {
        $bytes = "\"h1\", \"h2\"\n"
            . "\"\\u00e9\", \"\\uD83D\\uDE00\"\n";

        $table = Csvj::parse($bytes);

        self::assertSame([['é', '😀']], $table['rows']);
    }

    public function testParseNumberForms(): void
    {
        $bytes = "\"a\", \"b\", \"c\", \"d\", \"e\"\n"
            . "-1, 0, 1.5, 1e10, -2.5e-3\n";

        $table = Csvj::parse($bytes);

        $row = $table['rows'][0];
        self::assertSame(-1, $row[0]);
        self::assertSame(0, $row[1]);
        self::assertSame(1.5, $row[2]);
        self::assertEqualsWithDelta(1e10, $row[3], 0.0);
        self::assertEqualsWithDelta(-2.5e-3, $row[4], 1e-12);
    }

    public function testParseBooleansAndNull(): void
    {
        $bytes = "\"h1\", \"h2\", \"h3\", \"h4\"\n"
            . "true, false, null, \"string\"\n";

        $table = Csvj::parse($bytes);

        self::assertSame([[true, false, null, 'string']], $table['rows']);
    }

    public function testParseMultipleRows(): void
    {
        $bytes = "\"h1\", \"h2\"\n"
            . "1, 2\n"
            . "3, 4\n"
            . "5, 6\n";

        $table = Csvj::parse($bytes);

        self::assertSame([[1, 2], [3, 4], [5, 6]], $table['rows']);
    }

    public function testParseLongValue(): void
    {
        $long = str_repeat('a', 4096);
        $bytes = "\"h1\"\n\"" . $long . "\"\n";

        $table = Csvj::parse($bytes);

        self::assertSame($long, $table['rows'][0][0]);
    }

    public function testParseTrailingNullAndEmptyString(): void
    {
        $bytes = "\"a\", \"b\", \"c\"\n"
            . "\"x\", \"y\", null\n"
            . "\"p\", \"q\", \"\"\n";

        $table = Csvj::parse($bytes);

        self::assertSame(
            [['x', 'y', null], ['p', 'q', '']],
            $table['rows'],
        );
    }

    public function testParseEmptyHeaderLine(): void
    {
        $table = Csvj::parse("\n");

        self::assertSame([], $table['header']);
        self::assertSame([], $table['rows']);
    }

    public function testParseEmptyHeaderLineCRLF(): void
    {
        $table = Csvj::parse("\r\n");

        self::assertSame([], $table['header']);
        self::assertSame([], $table['rows']);
    }

    public function testRejectEmptyFile(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse('');
    }

    public function testRejectMissingTrailingNewline(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse('"h1", "h2"');
    }

    public function testRejectMissingTrailingNewlineAfterDataRow(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"h1\"\n42");
    }

    public function testRejectRaggedShortRow(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n\"x\", \"y\"\n");
    }

    public function testRejectRaggedLongRow(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\"\n\"x\", \"y\", \"z\"\n");
    }

    public function testRejectEmptyLineInMiddle(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"h1\", \"h2\"\n\nnull, true\n");
    }

    public function testRejectDuplicateHeaderNames(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"a\"\n");
    }

    public function testRejectDuplicateEmptyHeaders(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"\", \"\"\n");
    }

    public function testRejectNonStringHeader(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", 42, \"b\"\n");
    }

    public function testRejectArrayValue(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n1, [], 3\n");
    }

    public function testRejectBareToken(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n1, \$, 3\n");
    }

    public function testRejectLeadingZeros(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\"\n0123\n");
    }

    public function testRejectNaN(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n42, NaN, false\n");
    }

    public function testRejectInfinity(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n42, Infinity, false\n");
    }

    public function testRejectUppercaseTrue(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n42, True, false\n");
    }

    public function testRejectUppercaseNull(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n42, Null, false\n");
    }

    public function testRejectSingleQuotedString(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n42, 'hi', false\n");
    }

    public function testRejectBareDotNumber(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n42, .5, false\n");
    }

    public function testRejectTrailingDotNumber(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n42, 1., false\n");
    }

    public function testRejectUnescapedControlChar(): void
    {
        $this->expectException(ParseError::class);
        Csvj::parse("\"a\", \"b\", \"c\"\n42, \"hello\x01world\", false\n");
    }

    public function testStringifySimple(): void
    {
        $bytes = Csvj::stringify([
            'header' => ['h1', 'h2'],
            'rows' => [[1, 2], [3, 4]],
        ]);

        self::assertSame("\"h1\",\"h2\"\n1,2\n3,4\n", $bytes);
    }

    public function testStringifyMixedTypes(): void
    {
        $bytes = Csvj::stringify([
            'header' => ['a', 'b', 'c', 'd'],
            'rows' => [['x', 1, true, null], ['y', 2.5, false, '']],
        ]);

        self::assertSame(
            "\"a\",\"b\",\"c\",\"d\"\n\"x\",1,true,null\n\"y\",2.5,false,\"\"\n",
            $bytes,
        );
    }

    public function testStringifyEmptyHeaderLine(): void
    {
        $bytes = Csvj::stringify(['header' => [], 'rows' => []]);

        self::assertSame("\n", $bytes);
    }

    public function testStringifyEscapesSpecialChars(): void
    {
        $bytes = Csvj::stringify([
            'header' => ['h1'],
            'rows' => [["line\nbreak"], ["quote\"end"]],
        ]);

        self::assertSame("\"h1\"\n\"line\\nbreak\"\n\"quote\\\"end\"\n", $bytes);
    }

    public function testStringifyPreservesUTF8(): void
    {
        $bytes = Csvj::stringify([
            'header' => ['h1'],
            'rows' => [['日本語']],
        ]);

        self::assertSame("\"h1\"\n\"日本語\"\n", $bytes);
    }

    public function testStringifyRejectsDuplicateHeader(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Csvj::stringify([
            'header' => ['a', 'a'],
            'rows' => [],
        ]);
    }

    public function testStringifyRejectsNonStringHeader(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Csvj::stringify([
            'header' => ['a', 42],
            'rows' => [],
        ]);
    }

    public function testStringifyRejectsRowLengthMismatch(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Csvj::stringify([
            'header' => ['a', 'b'],
            'rows' => [[1]],
        ]);
    }

    public function testStringifyRejectsNonFiniteFloat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Csvj::stringify([
            'header' => ['a'],
            'rows' => [[NAN]],
        ]);
    }

    public function testStringifyRejectsObjectValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Csvj::stringify([
            'header' => ['a'],
            'rows' => [[new \stdClass()]],
        ]);
    }

    public function testStringifyRejectsArrayValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Csvj::stringify([
            'header' => ['a'],
            'rows' => [[[1, 2]]],
        ]);
    }

    public function testRoundTrip(): void
    {
        $table = [
            'header' => ['name', 'age', 'active'],
            'rows' => [
                ['alice', 30, true],
                ['bob', 25, false],
                ['carol', null, true],
            ],
        ];

        $bytes = Csvj::stringify($table);
        $parsed = Csvj::parse($bytes);

        self::assertSame($table, $parsed);
    }
}
