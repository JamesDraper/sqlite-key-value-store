<?php
declare(strict_types=1);

namespace Tests;

class GetSizeTests extends TestCase
{
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
     * @depends Tests\RemoveTests::class
     */
    public function it_should_get_size_if_size_is_0(): void
    {
        $this->store->remove('KEY%1');
        $this->store->remove('KEY%2');
        $this->store->remove('KEY%3');

        $result = $this->store->getSize();

        $this->assertSame(0, $result);
    }
}
