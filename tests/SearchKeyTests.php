<?php
declare(strict_types=1);

namespace Tests;

class SearchKeyTests extends TestCase
{
    /**
     * @test
     */
    public function it_should_search_keys(): void
    {
        $result = $this->store->searchKey('*1*');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_non_standard_wildcards(): void
    {
        $result = $this->store->searchKey('#1#', '#');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_underscore_wildcards(): void
    {
        $result = $this->store->searchKey('_1_', '_');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_percentage_wildcards(): void
    {
        $result = $this->store->searchKey('%2%', '%');

        $this->assertSame(['KEY%2' => 'VALUE%2'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_escape_symbol(): void
    {
        $result = $this->store->searchKey('^3^', '^');

        $this->assertSame(['KEY^3' => 'VALUE^3'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_underscore(): void
    {
        $result = $this->store->searchKey('KEY_1');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_percentage(): void
    {
        $result = $this->store->searchKey('KEY%2');

        $this->assertSame(['KEY%2' => 'VALUE%2'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_escape_symbol(): void
    {
        $result = $this->store->searchKey('KEY^3');

        $this->assertSame(['KEY^3' => 'VALUE^3'], $result);
    }

    /**
     * @test
     */
    public function it_should_fail_if_wildcard_is_empty(): void
    {
        $this->assertExceptionThrown('Escape sequence must be exactly 1 character in length.');

        $this->store->searchKey('KEY_1', '');
    }

    /**
     * @test
     */
    public function it_should_fail_if_wildcard_is_2_or_more_characters(): void
    {
        $this->assertExceptionThrown('Escape sequence must be exactly 1 character in length.');

        $this->store->searchKey('KEY_1', 'ab');
    }
}
