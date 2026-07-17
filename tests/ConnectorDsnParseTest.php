<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ConnectorDsnParseTest extends TestCase
{
    public function testParsesMysqliDsnLikeDatabaseUrl(): void
    {
        $params = Connector_relationaldb::parseDsnToDbalParams(
            'mysqli://boa:boa_secret@mysql:3306/boaapi?charset=utf8'
        );

        $this->assertSame('pdo_mysql', $params['driver']);
        $this->assertSame('mysql', $params['host']);
        $this->assertSame(3306, $params['port']);
        $this->assertSame('boa', $params['user']);
        $this->assertSame('boa_secret', $params['password']);
        $this->assertSame('boaapi', $params['dbname']);
        $this->assertSame('utf8', $params['charset']);
    }

    public function testParsesUrlEncodedPassword(): void
    {
        $params = Connector_relationaldb::parseDsnToDbalParams(
            'mysqli://user:p%40ss%3Aword@dbhost:3307/app'
        );

        $this->assertSame('user', $params['user']);
        $this->assertSame('p@ss:word', $params['password']);
        $this->assertSame(3307, $params['port']);
        $this->assertSame('app', $params['dbname']);
    }

    public function testParsesSqliteMemoryDsn(): void
    {
        $params = Connector_relationaldb::parseDsnToDbalParams('pdo_sqlite:///:memory:');

        $this->assertSame('pdo_sqlite', $params['driver']);
        $this->assertTrue($params['memory']);
    }

    public function testNormalizeQueryParamsStripsLeadingColons(): void
    {
        $normalized = Connector_relationaldb::normalizeQueryParams(array(
            ':resource' => 'abc',
            'type' => 'views',
        ));

        $this->assertSame(array('resource' => 'abc', 'type' => 'views'), $normalized);
    }
}
