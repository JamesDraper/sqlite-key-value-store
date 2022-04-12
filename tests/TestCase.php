<?php
declare(strict_types=1);

namespace Tests;

use SqliteKeyValueStore\Exception;
use SqliteKeyValueStore\Store;

use function in_array;
use function is_file;
use function scandir;
use function is_dir;
use function unlink;
use function mkdir;
use function rmdir;
use function copy;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEMP_DIR = __DIR__ . '/temp/';

    protected const TEST_DB_PATH = self::TEMP_DIR . 'test.sqlite';

    protected const SEED_DB_PATH = __DIR__ . '/seed.sqlite';

    protected Store $store;

    protected function assertExceptionThrown(string $message): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage($message);
    }

    protected function setUp(): void
    {
        parent::setUp();

        @mkdir(static::TEMP_DIR);

        copy(static::SEED_DB_PATH, static::TEST_DB_PATH);

        $this->store = new Store(static::TEST_DB_PATH);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->store);

        $this->delete(static::TEMP_DIR);
    }

    private function delete(string $path): void
    {
        switch (true) {
            case is_file($path):
                unlink($path);
                break;

            case is_dir($path):
                $children = scandir($path);

                foreach ($children as $child) {
                    if (in_array($child, ['.', '..'])) {
                        continue;
                    }

                    $this->delete($path . '/' . $child);
                }

                rmdir($path);
                break;

            default:
                break;
        }
    }
}
