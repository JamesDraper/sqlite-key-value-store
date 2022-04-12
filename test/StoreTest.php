<?php
declare(strict_types=1);

namespace Test;

use SqliteKeyValueStore\Exception;

use function str_repeat;
use function touch;
use function copy;

class StoreTest extends TestCase
{
    private const ALREADY_EXISTS_PATH = self::TEMP_DIR . 'already.exists.txt';

    private const BACKUP_DB_PATH = self::TEMP_DIR . 'backup.sqlite';

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
    public function it_should_get_size(): void
    {
        $result = $this->store->getSize();

        $this->assertSame(3, $result);
    }

    /**
     * @test
     * @depends remove_deletes_values
     * @depends remove_deletes_multiple_values
     * @depends remove_does_nothing_if_value_not_set
     */
    public function it_should_get_size_if_size_is_0(): void
    {
        $this->store->remove('KEY 1');
        $this->store->remove('KEY 2');
        $this->store->remove('KEY 3');

        $result = $this->store->getSize();

        $this->assertSame(0, $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys(): void
    {
        $result = $this->store->searchKey('*3*');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_non_standard_wildcards(): void
    {
        $result = $this->store->searchKey('_3_', '_');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_percentage_sign_wildcards(): void
    {
        $result = $this->store->searchKey('%3%', '%');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_values(): void
    {
        $result = $this->store->searchValue('*3*');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_values_with_non_standard_wildcards(): void
    {
        $result = $this->store->searchValue('_3_', '_');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_values_with_percentage_sign_wildcards(): void
    {
        $result = $this->store->searchValue('%3%', '%');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search(): void
    {
        $result = $this->store->search('*3*', '*3*');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_with_non_standard_wildcards(): void
    {
        $result = $this->store->search('_3_', '_3_', '_');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_with_percentage_sign_wildcards(): void
    {
        $result = $this->store->search('%3%', '%3%', '%');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }
}
