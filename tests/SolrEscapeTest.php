<?php

declare(strict_types=1);

namespace BoA\Api\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Local Lucene/Solr query escape replacing PECL SolrUtils::escapeQueryChars.
 *
 * Implemented as Solr_querybuilder::escapeQueryChars (static). Also accepts
 * a future SolrEscape::escapeQueryChars if that helper is introduced.
 *
 * Special chars: + - && || ! ( ) { } [ ] ^ " ~ * ? : \ /
 */
final class SolrEscapeTest extends TestCase
{
    private function escape(string $input): string
    {
        if (class_exists(\Solr_querybuilder::class, false)
            || class_exists(\Solr_querybuilder::class)
        ) {
            return \Solr_querybuilder::escapeQueryChars($input);
        }
        if (class_exists(\SolrEscape::class, false) || class_exists(\SolrEscape::class)) {
            return \SolrEscape::escapeQueryChars($input);
        }
        if (function_exists('solr_escape_query_chars')) {
            return solr_escape_query_chars($input);
        }
        $this->markTestSkipped(
            'Waiting for Solr_querybuilder::escapeQueryChars under engines/solr/'
        );
    }

    public function testEscapesLuceneSpecialCharacters(): void
    {
        $raw = '+ - && || ! ( ) { } [ ] ^ " ~ * ? : \\ /';
        $escaped = $this->escape($raw);

        foreach (['+', '-', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '/', '\\'] as $ch) {
            $this->assertStringContainsString(
                '\\' . $ch,
                $escaped,
                "Expected escape of special character: {$ch}"
            );
        }
    }

    public function testLeavesAlphanumericUnchanged(): void
    {
        $this->assertSame('helloWorld123', $this->escape('helloWorld123'));
    }

    public function testEscapesUserQueryFragment(): void
    {
        $escaped = $this->escape('title:(draft)');
        $this->assertStringContainsString('\\:', $escaped);
        $this->assertStringContainsString('\\(', $escaped);
        $this->assertStringContainsString('\\)', $escaped);
    }
}
