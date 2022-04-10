<?php
declare(strict_types=1);

namespace Test;

use PHPUnit\Framework\TestCase;

use function SqliteKeyValueStore\format_path;
use function str_replace;
use function in_array;
use function realpath;
use function is_file;
use function scandir;
use function is_dir;
use function unlink;
use function mkdir;
use function rmdir;

use const DIRECTORY_SEPARATOR;

class FormatPathTest extends TestCase
{
    private const TEMP_DIR = __DIR__ . '/temp/';

    /**
     * @test
     * @dataProvider provider
     */
    public function it_should__format_a_path(string $path, string $expected): void
    {
        $formatted = str_replace('/', DIRECTORY_SEPARATOR, static::TEMP_DIR . format_path($path));
        $expected = str_replace('/', DIRECTORY_SEPARATOR, static::TEMP_DIR . $expected);

        mkdir(static::TEMP_DIR . $path, 0755, true);
        $realPath = realpath(static::TEMP_DIR . $path);

        $this->assertSame($expected, $formatted, 'format_path did not return the expected result');
        $this->assertSame($realPath, $formatted, 'format_path and realpath do not match');
    }

    public function provider(): array
    {
        return [
            'simple' => [
                'path' => 'path/to/file',
                'expected' => 'path/to/file'
            ],
            'directory with trailing separator' => [
                'path' => 'path/to/directory/',
                'expected' => 'path/to/directory'
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearTemp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->clearTemp();
    }

    private function clearTemp(): void
    {
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
