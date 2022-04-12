<?php
declare(strict_types=1);

namespace Tests;

use SqliteKeyValueStore\Exception;

use function touch;

class BackupTests extends TestCase
{
    private const ALREADY_EXISTS_PATH = self::TEMP_DIR . 'already.exists.txt';

    private const BACKUP_DB_PATH = self::TEMP_DIR . 'backup.sqlite';

    /**
     * @test
     */
    public function it_copies_sqlite_file_to_another_file(): void
    {
        $this->store->backup(static::BACKUP_DB_PATH);

        $this->assertFileEquals(static::BACKUP_DB_PATH, static::TEST_DB_PATH);
    }

    /**
     * @test
     */
    public function it_fails_if_destination_and_src_are_same(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Backup file path and store file path cannot match.');

        $this->store->backup(static::TEST_DB_PATH);
    }

    /**
     * @test
     */
    public function it_fails_if_destination_already_exists(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Backup file path must be empty.');

        touch(static::ALREADY_EXISTS_PATH);

        $this->store->backup(static::ALREADY_EXISTS_PATH);
    }
}
