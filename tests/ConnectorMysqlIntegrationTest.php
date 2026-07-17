<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Integration tests against MySQL (DATABASE_URL / compose mysql service).
 *
 * @group integration
 */
final class ConnectorMysqlIntegrationTest extends TestCase
{
    /** @var Connector_relationaldb|null */
    private $connector;

    protected function setUp(): void
    {
        $dsn = getenv('DATABASE_URL') ?: getenv('BOA_DATABASE_URL') ?: '';
        if ($dsn === '') {
            $this->markTestSkipped('DATABASE_URL not set');
        }

        Connector_relationaldb::$DB = array();

        try {
            $this->connector = new Connector_relationaldb($dsn);
            $this->connector->DB()->getConnection()->executeQuery('SELECT 1');
        } catch (Throwable $e) {
            $this->markTestSkipped('MySQL unavailable: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        Connector_relationaldb::$DB = array();
        $this->connector = null;
    }

    public function testConnectAndSelectOne(): void
    {
        $row = $this->connector->getRow('SELECT 1 AS one');
        $this->assertIsObject($row);
        $this->assertEquals(1, $row->one);
    }

    public function testQuoteAgainstMysql(): void
    {
        $quoted = $this->connector->DB()->quote("a'b", 'text');
        $this->assertStringStartsWith("'", $quoted);
        $this->assertTrue(
            str_contains($quoted, "\\'") || str_contains($quoted, "''"),
            'Expected escaped quote in: ' . $quoted
        );
    }
}
