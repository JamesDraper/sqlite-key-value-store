<?php
declare(strict_types=1);

namespace Test;

use SqliteKeyValueStore\Store;

use PHPUnit\Framework\TestCase;

class StoreTest extends TestCase
{
    /**
     * @test
     */
    public function get_returns_null_if_key_does_not_exist(): void
    {
        $store = new Store(__DIR__ . '/test_get.sqlite');

        $result = $store->get('KEY 4');

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function get_returns_default_if_key_does_not_exist(): void
    {
        $store = new Store(__DIR__ . '/test_get.sqlite');

        $result = $store->get('KEY 4', 'VALUE 4');

        $this->assertSame('VALUE 4', $result);
    }

    /**
     * @test
     */
    public function get_returns_value(): void
    {
        $store = new Store(__DIR__ . '/test_get.sqlite');

        $result = $store->get('KEY 1');

        $this->assertSame('VALUE 1', $result);
    }
}
