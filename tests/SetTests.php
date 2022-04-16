<?php
declare(strict_types=1);

namespace Tests;

use function str_repeat;

class SetTests extends TestCase
{
    /**
     * @test
     */
    public function it_returns_self_for_method_chaining(): void
    {
        $result = $this->store->set('KEY#4', 'VALUE#4');

        $this->assertSame($this->store, $result);
    }

    /**
     * @test
     * @depends Tests\GetTests::class
     */
    public function it_adds_values(): void
    {
        $this->store->set('KEY#4', 'VALUE#4');

        $result = $this->store->get('KEY#4');

        $this->assertSame('VALUE#4', $result);
    }

    /**
     * @test
     * @depends Tests\GetTests::class
     */
    public function it_adds_multiple_values(): void
    {
        $this->store->set('KEY#4', 'VALUE#4');
        $this->store->set('KEY#5', 'VALUE#5');
        $this->store->set('KEY#6', 'VALUE#6');

        $result1 = $this->store->get('KEY#4');
        $result2 = $this->store->get('KEY#5');
        $result3 = $this->store->get('KEY#6');

        $this->assertSame('VALUE#4', $result1);
        $this->assertSame('VALUE#5', $result2);
        $this->assertSame('VALUE#6', $result3);
    }

    /**
     * @test
     * @depends Tests\GetTests::class
     */
    public function it_updates_values(): void
    {
        $this->store->set('KEY_1', 'VALUE#4');

        $result1 = $this->store->get('KEY_1');
        $result2 = $this->store->get('KEY%2');
        $result3 = $this->store->get('KEY^3');

        $this->assertSame('VALUE#4', $result1);
        $this->assertSame('VALUE%2', $result2);
        $this->assertSame('VALUE^3', $result3);
    }

    /**
     * @test
     * @depends Tests\GetTests::class
     */
    public function it_updates_multiple_values(): void
    {
        $this->store->set('KEY_1', 'VALUE#4');
        $this->store->set('KEY%2', 'VALUE#5');
        $this->store->set('KEY^3', 'VALUE#6');

        $result1 = $this->store->get('KEY_1');
        $result2 = $this->store->get('KEY%2');
        $result3 = $this->store->get('KEY^3');

        $this->assertSame('VALUE#4', $result1);
        $this->assertSame('VALUE#5', $result2);
        $this->assertSame('VALUE#6', $result3);
    }

    /**
     * @test
     * @depends Tests\GetTests::class
     */
    public function it_stores_long_values(): void
    {
        $text = str_repeat('a', 50000000);

        $result1 = $this->store->set('KEY#4', $text);
        $result2 = $this->store->get('KEY#4');

        $this->assertSame($this->store, $result1);
        $this->assertSame($text, $result2);
    }
}
