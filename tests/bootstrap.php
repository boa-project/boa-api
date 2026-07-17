<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap for boa-api.
 *
 * Loads Composer autoload (Doctrine DBAL), the relational DB connector (for
 * DBAL adapter unit/integration tests), and Solr_querybuilder with a minimal
 * Restos stub so escapeQueryChars can be tested without full RestOS boot.
 *
 * Full RestOS boot (optional):
 *   define('RESTOS_INTERNAL', true);
 *   require_once dirname(__DIR__) . '/src/setup.php';
 *
 * Run via Docker only: `make test-api` or
 * `docker compose run --rm boa-api ./vendor/bin/phpunit`.
 */

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_readable($autoload)) {
    fwrite(
        STDERR,
        "Composer autoload missing at {$autoload}.\n"
        . "Install deps in Docker: make composer-api ARGS='install'\n"
    );
    exit(1);
}

require_once $autoload;

// Constants normally set by setup.php — needed when loading the connector alone.
if (!defined('RESTOS_INTERNAL')) {
    define('RESTOS_INTERNAL', true);
}
if (!defined('RESTOS_DEBUG_MODE')) {
    define('RESTOS_DEBUG_MODE', true);
}

if (!class_exists('Restos', false)) {
    // Minimal stub so connector / querybuilder unit tests skip full RestOS boot.
    class Restos
    {
        public static function using($path): void
        {
        }

        public static function throwException($e, $msg = null, $code = 9000, $http_status_code = null): void
        {
            if ($e instanceof \Throwable) {
                throw $e;
            }
            throw new \RuntimeException($msg !== null ? (string) $msg : 'Restos exception', (int) $code);
        }
    }
}

$connector = dirname(__DIR__) . '/src/data_handlers/relationaldb/connector_relationaldb.php';
if (is_readable($connector)) {
    require_once $connector;
}

$solrQueryBuilder = dirname(__DIR__)
    . '/src/resources/resources/engines/solr/solr_querybuilder.class.php';
if (is_readable($solrQueryBuilder) && !class_exists('Solr_querybuilder', false)) {
    require_once $solrQueryBuilder;
}

foreach ([
    dirname(__DIR__) . '/src/resources/resources/engines/solr/solr_escape.class.php',
    dirname(__DIR__) . '/src/resources/resources/engines/solr/SolrEscape.php',
] as $solrEscapeFile) {
    if (is_readable($solrEscapeFile)) {
        require_once $solrEscapeFile;
        break;
    }
}
