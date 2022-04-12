<?php
declare(strict_types=1);

namespace Test;

class RemoveTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_self_for_method_chaining(): void
    {
        $result = $this->store->remove('KEY 1');

        $this->assertSame($this->store, $result);
    }

    /**
     * @test
     * @depends Test\GetTest::class
     */
    public function it_removes_values(): void
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
     * @depends Test\GetTest::class
     */
    public function it_removes_multiple_values(): void
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
     * @depends Test\GetTest::class
     */
    public function it_does_nothing_if_value_not_set(): void
    {
        $this->store->remove('KEY 4');

        $result1 = $this->store->get('KEY 1');
        $result2 = $this->store->get('KEY 2');
        $result3 = $this->store->get('KEY 3');

        $this->assertSame('VALUE 1', $result1);
        $this->assertSame('VALUE 2', $result2);
        $this->assertSame('VALUE 3', $result3);
    }
}
