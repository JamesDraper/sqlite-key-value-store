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
        $result = $this->store->searchKeys('*1*');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_non_standard_wildcards(): void
    {
        $result = $this->store->searchKeys('#1#', '#');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_underscore_wildcards(): void
    {
        $result = $this->store->searchKeys('_1_', '_');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_percentage_wildcards(): void
    {
        $result = $this->store->searchKeys('%2%', '%');

        $this->assertSame(['KEY%2' => 'VALUE%2'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_escape_symbol(): void
    {
        $result = $this->store->searchKeys('^3^', '^');

        $this->assertSame(['KEY^3' => 'VALUE^3'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_underscore(): void
    {
        $result = $this->store->searchKeys('KEY_1');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_percentage(): void
    {
        $result = $this->store->searchKeys('KEY%2');

        $this->assertSame(['KEY%2' => 'VALUE%2'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_escape_symbol(): void
    {
        $result = $this->store->searchKeys('KEY^3');

        $this->assertSame(['KEY^3' => 'VALUE^3'], $result);
    }

    /**
     * @test
     */
    public function it_should_fail_if_wildcard_is_empty(): void
    {
        $this->assertExceptionThrown('Escape sequence must be exactly 1 character in length.');

        $this->store->searchKeys('KEY_1', '');
    }

    /**
     * @test
     */
    public function it_should_fail_if_wildcard_is_2_or_more_characters(): void
    {
        $this->assertExceptionThrown('Escape sequence must be exactly 1 character in length.');

        $this->store->searchKeys('KEY_1', 'ab');
    }

    /**
     * @test
     */
    public function it_should_return_multiple_keys(): void
    {
        $result = $this->store->searchKeys('KEY*');

        $expected = [
            'KEY%2' => 'VALUE%2',
            'KEY^3' => 'VALUE^3',
            'KEY_1' => 'VALUE_1',
        ];

        $this->assertSame($expected, $result);
    }
}
