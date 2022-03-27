<?php
declare(strict_types=1);

namespace Test;

use SqliteKeyValueStore\Store;

use PHPUnit\Framework\TestCase;

use function str_repeat;
use function unlink;

class StoreTest extends TestCase
{
    private const SQLITE_PATH = __DIR__ . '/../test.sqlite';

    private Store $store;

    /**
     * @test
     */
    public function it_should_return_null_if_a_key_does_not_exist(): void
    {
        $result = $this->store->get('KEY');

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_should_return_default_if_a_key_does_not_exist(): void
    {
        $result = $this->store->get('KEY', 'DEFAULT');

        $this->assertSame('DEFAULT', $result);
    }

    /**
     * @test
     */
    public function it_should_set_a_value(): void
    {
        $result1 = $this->store->set('KEY', 'VALUE');
        $result2 = $this->store->get('KEY');

        $this->assertSame($this->store, $result1);
        $this->assertSame('VALUE', $result2);
    }

    /**
     * @test
     */
    public function it_should_set_2_values(): void
    {
        $result1 = $this->store->set('KEY_1', 'VALUE_1');
        $result2 = $this->store->set('KEY_2', 'VALUE_2');
        $result3 = $this->store->get('KEY_1');
        $result4 = $this->store->get('KEY_2');

        $this->assertSame($this->store, $result1);
        $this->assertSame($this->store, $result2);
        $this->assertSame('VALUE_1', $result3);
        $this->assertSame('VALUE_2', $result4);
    }

    /**
     * @test
     */
    public function it_should_update_a_value(): void
    {
        $result1 = $this->store->set('KEY', 'VALUE_1');
        $result2 = $this->store->set('KEY', 'VALUE_2');
        $result3 = $this->store->get('KEY');

        $this->assertSame($this->store, $result1);
        $this->assertSame($this->store, $result2);
        $this->assertSame('VALUE_2', $result3);
    }

    /**
     * @test
     */
    public function it_should_update_2_values(): void
    {
        $result1 = $this->store->set('KEY_1', 'VALUE_1');
        $result2 = $this->store->set('KEY_1', 'VALUE_2');
        $result3 = $this->store->set('KEY_2', 'VALUE_3');
        $result4 = $this->store->set('KEY_2', 'VALUE_4');
        $result5 = $this->store->get('KEY_1');
        $result6 = $this->store->get('KEY_2');

        $this->assertSame($this->store, $result1);
        $this->assertSame($this->store, $result2);
        $this->assertSame($this->store, $result3);
        $this->assertSame($this->store, $result4);
        $this->assertSame('VALUE_2', $result5);
        $this->assertSame('VALUE_4', $result6);
    }

    /**
     * @test
     */
    public function it_should_remove_a_value(): void
    {
        $result1 = $this->store->set('KEY_1', 'VALUE_1');
        $result2 = $this->store->set('KEY_2', 'VALUE_2');
        $result3 = $this->store->remove('KEY_1');
        $result4 = $this->store->get('KEY_1');
        $result5 = $this->store->get('KEY_2');

        $this->assertSame($this->store, $result1);
        $this->assertSame($this->store, $result2);
        $this->assertSame($this->store, $result3);
        $this->assertNull($result4);
        $this->assertSame('VALUE_2', $result5);
    }

    /**
     * @test
     */
    public function it_should_remove_a_value_that_is_not_set(): void
    {
        $result = $this->store->remove('KEY');

        $this->assertSame($this->store, $result);
    }

    /**
     * @test
     */
    public function it_should_store_a_long_value(): void
    {
        $text = str_repeat('a', 50000);

        $result1 = $this->store->set('KEY', $text);
        $result2 = $this->store->get('KEY');

        $this->assertSame($this->store, $result1);
        $this->assertSame($text, $result2);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = new Store(static::SQLITE_PATH);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->store);

        @unlink(static::SQLITE_PATH);
    }
}
