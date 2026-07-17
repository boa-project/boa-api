<?php

declare(strict_types=1);

namespace BoA\Api\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Smoke test: PHPUnit + Composer autoload are wired correctly.
 */
final class BootstrapTest extends TestCase
{
    public function testPhpUnitEnvironmentIsReady(): void
    {
        $this->assertGreaterThanOrEqual(80300, PHP_VERSION_ID);
        $this->assertTrue(class_exists(TestCase::class));
    }

    public function testDoctrineDbalIsAutoloadable(): void
    {
        $this->assertTrue(
            class_exists(\Doctrine\DBAL\Connection::class),
            'doctrine/dbal should be present after composer install'
        );
    }
}
