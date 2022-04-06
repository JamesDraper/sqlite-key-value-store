<?php
declare(strict_types=1);

namespace Test;

use SqliteKeyValueStore\Exception;
use SqliteKeyValueStore\Store;

use PHPUnit\Framework\TestCase;

use function str_repeat;
use function unlink;
use function touch;
use function copy;

class StoreTest extends TestCase
{
    private const ALREADY_EXISTS_PATH = __DIR__ . '/already.exists.txt';

    private const BACKUP_DB_PATH = __DIR__ . '/backup.sqlite';

    private const SEED_DB_PATH = __DIR__ . '/seed.sqlite';

    private const TEST_DB_PATH = __DIR__ . '/test.sqlite';

    private Store $store;

    /**
     * @test
     */
    public function backup_copies_sqlite_file_to_another_file(): void
    {
        $this->store->backup(static::BACKUP_DB_PATH);

        $this->assertFileEquals(static::BACKUP_DB_PATH, static::TEST_DB_PATH);
    }

    /**
     * @test
     */
    public function backup_fails_if_destination_and_src_are_same(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Backup file path and store file path cannot match.');

        $this->store->backup(static::TEST_DB_PATH);
    }

    /**
     * @test
     */
    public function backup_fails_if_destination_already_exists(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Backup file path must be empty.');

        touch(static::ALREADY_EXISTS_PATH);

        $this->store->backup(static::ALREADY_EXISTS_PATH);
    }

    /**
     * @test
     */
    public function get_returns_null_if_key_does_not_exist(): void
    {
        $result = $this->store->get('KEY 4');

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function get_returns_default_if_key_does_not_exist(): void
    {
        $result = $this->store->get('KEY 4', 'VALUE 4');

        $this->assertSame('VALUE 4', $result);
    }

    /**
     * @test
     */
    public function get_returns_value(): void
    {
        $result = $this->store->get('KEY 1');

        $this->assertSame('VALUE 1', $result);
    }

    public function set_returns_self_for_method_chaining(): void
    {
        $result = $this->store->set('KEY 4', 'VALUE 4');

        $this->assertSame($this->store, $result);
    }

    /**
     * @test
     * @depends get_returns_null_if_key_does_not_exist
     * @depends get_returns_default_if_key_does_not_exist
     * @depends get_returns_value
     */
    public function set_adds_values(): void
    {
        $this->store->set('KEY 4', 'VALUE 4');

        $result = $this->store->get('KEY 4');

        $this->assertSame('VALUE 4', $result);
    }

    /**
     * @test
     * @depends get_returns_null_if_key_does_not_exist
     * @depends get_returns_default_if_key_does_not_exist
     * @depends get_returns_value
     */
    public function set_adds_multiple_values(): void
    {
        $this->store->set('KEY 4', 'VALUE 4');
        $this->store->set('KEY 5', 'VALUE 5');
        $this->store->set('KEY 6', 'VALUE 6');

        $result1 = $this->store->get('KEY 4');
        $result2 = $this->store->get('KEY 5');
        $result3 = $this->store->get('KEY 6');

        $this->assertSame('VALUE 4', $result1);
        $this->assertSame('VALUE 5', $result2);
        $this->assertSame('VALUE 6', $result3);
    }

    /**
     * @test
     * @depends get_returns_null_if_key_does_not_exist
     * @depends get_returns_default_if_key_does_not_exist
     * @depends get_returns_value
     */
    public function set_updates_values(): void
    {
        $this->store->set('KEY 1', 'VALUE 4');

        $result1 = $this->store->get('KEY 1');
        $result2 = $this->store->get('KEY 2');
        $result3 = $this->store->get('KEY 3');

        $this->assertSame('VALUE 4', $result1);
        $this->assertSame('VALUE 2', $result2);
        $this->assertSame('VALUE 3', $result3);
    }

    /**
     * @test
     * @depends get_returns_null_if_key_does_not_exist
     * @depends get_returns_default_if_key_does_not_exist
     * @depends get_returns_value
     */
    public function set_updates_multiple_values(): void
    {
        $this->store->set('KEY 1', 'VALUE 4');
        $this->store->set('KEY 2', 'VALUE 5');
        $this->store->set('KEY 3', 'VALUE 6');

        $result1 = $this->store->get('KEY 1');
        $result2 = $this->store->get('KEY 2');
        $result3 = $this->store->get('KEY 3');

        $this->assertSame('VALUE 4', $result1);
        $this->assertSame('VALUE 5', $result2);
        $this->assertSame('VALUE 6', $result3);
    }

    /**
     * @test
     * @depends get_returns_null_if_key_does_not_exist
     * @depends get_returns_default_if_key_does_not_exist
     * @depends get_returns_value
     */
    public function set_stores_long_values(): void
    {
        $text = str_repeat('a', 50000000);

        $result1 = $this->store->set('KEY 4', $text);
        $result2 = $this->store->get('KEY 4');

        $this->assertSame($this->store, $result1);
        $this->assertSame($text, $result2);
    }

    /**
     * @test
     */
    public function remove_returns_self_for_method_chaining(): void
    {
        $result = $this->store->remove('KEY 1');

        $this->assertSame($this->store, $result);
    }

    /**
     * @test
     * @depends get_returns_null_if_key_does_not_exist
     * @depends get_returns_default_if_key_does_not_exist
     * @depends get_returns_value
     */
    public function remove_deletes_values(): void
    {
        $this->store->remove('KEY 1');

        $result1 = $this->store->get('KEY 1');
        $result2 = $this->store->get('KEY 2');
        $result3 = $this->store->get('KEY 3');

        $this->assertNull($result1);
        $this->assertSame('VALUE 2', $result2);
        $this->assertSame('VALUE 3', $result3);
    }

    /**
     * @test
     * @depends get_returns_null_if_key_does_not_exist
     * @depends get_returns_default_if_key_does_not_exist
     * @depends get_returns_value
     */
    public function remove_deletes_multiple_values(): void
    {
        $this->store->remove('KEY 1');
        $this->store->remove('KEY 2');
        $this->store->remove('KEY 3');

        $result1 = $this->store->get('KEY 1');
        $result2 = $this->store->get('KEY 2');
        $result3 = $this->store->get('KEY 3');

        $this->assertNull($result1);
        $this->assertNull($result2);
        $this->assertNull($result3);
    }

    /**
     * @test
     * @depends get_returns_null_if_key_does_not_exist
     * @depends get_returns_default_if_key_does_not_exist
     * @depends get_returns_value
     */
    public function remove_does_nothing_if_value_not_set(): void
    {
        $this->store->remove('KEY 4');

        $result1 = $this->store->get('KEY 1');
        $result2 = $this->store->get('KEY 2');
        $result3 = $this->store->get('KEY 3');

        $this->assertSame('VALUE 1', $result1);
        $this->assertSame('VALUE 2', $result2);
        $this->assertSame('VALUE 3', $result3);
    }

    protected function setUp(): void
    {
        parent::setUp();

        @unlink(static::ALREADY_EXISTS_PATH);
        @unlink(static::TEST_DB_PATH);

        copy(static::SEED_DB_PATH, static::TEST_DB_PATH);

        $this->store = new Store(__DIR__ . '/test.sqlite');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->store);

        @unlink(static::BACKUP_DB_PATH);
        @unlink(static::TEST_DB_PATH);
    }
}
