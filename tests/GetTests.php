<?php
declare(strict_types=1);

namespace Tests;

class GetTests extends TestCase
{
    /**
     * @test
     */
    public function it_should__return_null_if_a_key_does_not_exist(): void
    {
        $result = $this->store->get('KEY 4');

        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function it_should_return_a_default_if_a_key_does_not_exist(): void
    {
        $result = $this->store->get('KEY 4', 'VALUE 4');

        $this->assertSame('VALUE 4', $result);
    }

    /**
     * @test
     */
    public function it_should_return_a_value(): void
    {
        $result = $this->store->get('KEY 1');

        $this->assertSame('VALUE 1', $result);
    }
}
