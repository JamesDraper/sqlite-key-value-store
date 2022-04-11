<?php
declare(strict_types=1);

namespace Test;

use function in_array;
use function is_file;
use function scandir;
use function is_dir;
use function unlink;
use function mkdir;
use function rmdir;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected const TEMP_DIR = __DIR__ . '/temp/';

    protected function setUp(): void
    {
        parent::setUp();

        @mkdir(static::TEMP_DIR);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

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
