<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Unit-level connector coverage using in-memory SQLite (no MySQL required).
 */
final class ConnectorSqliteQuoteTest extends TestCase
{
    /** @var Connector_relationaldb */
    private $connector;

    protected function setUp(): void
    {
        // Isolate shared connection cache between tests.
        Connector_relationaldb::$DB = array();
        $this->connector = new Connector_relationaldb('pdo_sqlite:///:memory:');
    }

    protected function tearDown(): void
    {
        Connector_relationaldb::$DB = array();
    }

    public function testQuoteWrapsStringsAndNull(): void
    {
        $db = $this->connector->DB();

        $this->assertSame('NULL', $db->quote(null));
        $this->assertSame('42', $db->quote(42, 'integer'));
        $quoted = $db->quote("O'Reilly", 'text');
        $this->assertStringContainsString("O''Reilly", $quoted);
        $this->assertStringStartsWith("'", $quoted);
        $this->assertStringEndsWith("'", $quoted);
    }

    public function testInsertGetRowAndListParity(): void
    {
            $conn = $this->connector->DB()->dbal;
            $conn->executeStatement(
                'CREATE TABLE demo (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(64) NOT NULL)'
            );

        $id = $this->connector->insert_record('demo', array('name' => 'alpha'), true);
        $this->assertNotEmpty($id);

        $row = $this->connector->getEntity('demo', array('id' => $id));
        $this->assertIsObject($row);
        $this->assertSame('alpha', $row->name);

        $list = $this->connector->getList('demo', array('name' => 'alpha'));
        $this->assertCount(1, $list);
        $this->assertSame('alpha', $list[0]->name);

        $count = $this->connector->countList('demo', array('name' => 'alpha'));
        $this->assertEquals(1, $count);

        $value = $this->connector->getValue('demo', 'name', array('id' => $id));
        $this->assertSame('alpha', $value);
    }

    public function testNamedParamsWithColonKeys(): void
    {
        $conn = $this->connector->DB()->getConnection();
        $conn->executeStatement(
            'CREATE TABLE counters (resource VARCHAR(64), type VARCHAR(32), value INTEGER)'
        );
        $conn->executeStatement(
            "INSERT INTO counters (resource, type, value) VALUES ('r1', 'views', 3)"
        );

        $rows = $this->connector->getListSQL(
            'SELECT resource, type, value FROM counters WHERE resource = :resource',
            null,
            array(':resource' => 'r1')
        );

        $this->assertCount(1, $rows);
        $this->assertSame('views', $rows[0]->type);
        $this->assertEquals(3, $rows[0]->value);
    }

    public function testTableInfoReturnsColumnMeta(): void
    {
        $conn = $this->connector->DB()->getConnection();
        $conn->executeStatement(
            'CREATE TABLE meta (id INTEGER PRIMARY KEY, title VARCHAR(32) NOT NULL)'
        );

        $info = $this->connector->table_info('meta');
        $this->assertIsArray($info);
        $names = array_column($info, 'name');
        $this->assertContains('id', $names);
        $this->assertContains('title', $names);
    }
}
