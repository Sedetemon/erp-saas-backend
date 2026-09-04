<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected static bool $landlordDbEnsured = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$landlordDbEnsured) {

            $dbName = config('database.connections.landlord.database');

            $pdo = new \PDO(
                sprintf(
                    'mysql:host=%s;port=%s',
                    config('database.connections.landlord.host'),
                    config('database.connections.landlord.port')
                ),
                config('database.connections.landlord.username'),
                config('database.connections.landlord.password')
            );

            $pdo->exec(
                "CREATE DATABASE IF NOT EXISTS `{$dbName}`
                 CHARACTER SET utf8mb4
                 COLLATE utf8mb4_unicode_ci"
            );

            static::$landlordDbEnsured = true;
        }
    }
}
