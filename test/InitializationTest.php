<?php
declare(strict_types=1);

namespace Test;

use SqliteKeyValueStore\Store;

use function array_fill;
use function str_repeat;
use function implode;

use const PHP_MAXPATHLEN;

class InitializationTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_fail_if_the_file_path_is_memory(): void
    {
        $this->assertExceptionThrown('Sqlite store cannot be in memory.');

        new Store(':memory:');
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_file_path_is_invalid(): void
    {
        $this->assertExceptionThrown('Invalid file path');

        $path = implode('/', array_fill(0, 1000, '..'));

        new Store($path);
    }

    /**
     * @test
     */
    public function it_should_fail_if_the_path_length_exceeds_the_maximum(): void
    {
        $this->assertExceptionThrown('Path exceeds maximum path length');

        $path = str_repeat('a', PHP_MAXPATHLEN + 1);

        new Store($path);
    }
}
