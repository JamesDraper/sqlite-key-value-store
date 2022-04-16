<?php
declare(strict_types=1);

namespace Tests;

class SearchValueTests extends TestCase
{
    /**
     * @test
     */
    public function it_should_search_values(): void
    {
        $result = $this->store->searchValue('*1*');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_values_with_non_standard_wildcards(): void
    {
        $result = $this->store->searchValue('#1#', '#');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_values_with_underscore_wildcards(): void
    {
        $result = $this->store->searchValue('_1_', '_');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_values_with_percentage_wildcards(): void
    {
        $result = $this->store->searchValue('%2%', '%');

        $this->assertSame(['KEY%2' => 'VALUE%2'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_values_with_escape_symbol(): void
    {
        $result = $this->store->searchValue('^3^', '^');

        $this->assertSame(['KEY^3' => 'VALUE^3'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_underscore(): void
    {
        $result = $this->store->searchValue('VALUE_1');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_percentage(): void
    {
        $result = $this->store->searchValue('VALUE%2');

        $this->assertSame(['KEY%2' => 'VALUE%2'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_escape_symbol(): void
    {
        $result = $this->store->searchValue('VALUE^3');

        $this->assertSame(['KEY^3' => 'VALUE^3'], $result);
    }

    /**
     * @test
     */
    public function it_should_fail_if_wildcard_is_empty(): void
    {
        $this->assertExceptionThrown('Escape sequence must be exactly 1 character in length.');

        $this->store->searchValue('VALUE_1', '');
    }

    /**
     * @test
     */
    public function it_should_fail_if_wildcard_is_2_or_more_characters(): void
    {
        $this->assertExceptionThrown('Escape sequence must be exactly 1 character in length.');

        $this->store->searchValue('VALUE_1', 'ab');
    }
}
