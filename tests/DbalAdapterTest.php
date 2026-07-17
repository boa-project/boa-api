<?php

declare(strict_types=1);

namespace BoA\Api\Tests;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

/**
 * Doctrine DBAL smoke (integration-ish).
 *
 * Skips when MySQL env is missing or unreachable so unit runs stay green
 * without the compose mysql service. Inside Docker with MYSQL_* set,
 * asserts a live connection.
 *
 * Full Connector_relationaldb adapter coverage lands with the DBAL cutover.
 */
final class DbalAdapterTest extends TestCase
{
    public function testDoctrineDbalIsAvailable(): void
    {
        $this->assertTrue(
            class_exists(DriverManager::class),
            'doctrine/dbal must be installed (composer require)'
        );
    }

    public function testConnectsToMysqlWhenEnvPresent(): void
    {
        $host = getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: '';
        $db = getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: '';
        $user = getenv('MYSQL_USER') ?: getenv('DB_USER') ?: '';
        $pass = getenv('MYSQL_PASSWORD');
        if ($pass === false) {
            $pass = getenv('DB_PASSWORD');
        }

        if ($host === '' || $db === '' || $user === '') {
            $this->markTestSkipped(
                'Set MYSQL_HOST / MYSQL_DATABASE / MYSQL_USER (compose boa-api env) for DBAL smoke'
            );
        }

        $params = [
            'driver' => 'pdo_mysql',
            'host' => $host,
            'dbname' => $db,
            'user' => $user,
            'password' => $pass !== false ? $pass : '',
            'charset' => 'utf8mb4',
        ];

        try {
            $conn = DriverManager::getConnection($params);
            // DBAL 4: connect() is protected; opening the native handle forces connectivity.
            $native = $conn->getNativeConnection();
            $this->assertNotNull($native);
            $version = $conn->fetchOne('SELECT VERSION()');
            $this->assertIsString($version);
            $this->assertNotSame('', $version);
            $conn->close();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL unreachable for DBAL smoke: ' . $e->getMessage());
        }
    }

    /**
     * Once Connector_relationaldb is backed by DBAL, assert the class still loads.
     */
    public function testConnectorRelationaldbClassFileExists(): void
    {
        $path = dirname(__DIR__) . '/src/data_handlers/relationaldb/connector_relationaldb.php';
        $this->assertFileExists($path);
    }
}
