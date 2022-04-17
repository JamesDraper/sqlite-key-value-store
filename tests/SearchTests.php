<?php
declare(strict_types=1);

namespace Tests;

class SearchTests extends TestCase
{
    /**
     * @test
     */
    public function it_should_search(): void
    {
        $result = $this->store->search('*1*', '*1*');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_with_non_standard_wildcards(): void
    {
        $result = $this->store->search('#1#', '#1#', '#');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_with_underscore_wildcards(): void
    {
        $result = $this->store->search('_1_', '_1_', '_');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_percentage_wildcards(): void
    {
        $result = $this->store->search('%2%', '%2%', '%');

        $this->assertSame(['KEY%2' => 'VALUE%2'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_escape_symbol(): void
    {
        $result = $this->store->search('^3^', '^3^', '^');

        $this->assertSame(['KEY^3' => 'VALUE^3'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_underscore(): void
    {
        $result = $this->store->search('KEY_1', 'VALUE_1');

        $this->assertSame(['KEY_1' => 'VALUE_1'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_percentage(): void
    {
        $result = $this->store->search('KEY%2', 'VALUE%2');

        $this->assertSame(['KEY%2' => 'VALUE%2'], $result);
    }

    /**
     * @test
     */
    public function it_should_escape_escape_symbol(): void
    {
        $result = $this->store->search('KEY^3', 'VALUE^3');

        $this->assertSame(['KEY^3' => 'VALUE^3'], $result);
    }

    /**
     * @test
     */
    public function it_should_fail_if_wildcard_is_empty(): void
    {
        $this->assertExceptionThrown('Escape sequence must be exactly 1 character in length.');

        $this->store->search('KEY_1', 'VALUE_1', '');
    }

    /**
     * @test
     */
    public function it_should_fail_if_wildcard_is_2_or_more_characters(): void
    {
        $this->assertExceptionThrown('Escape sequence must be exactly 1 character in length.');

        $this->store->search('KEY_1', 'VALUE_1', 'ab');
    }

    /**
     * @test
     */
    public function it_should_return_multiples(): void
    {
        $result = $this->store->search('KEY*', 'VALUE*');

        $expected = [
            'KEY%2' => 'VALUE%2',
            'KEY^3' => 'VALUE^3',
            'KEY_1' => 'VALUE_1',
        ];

        $this->assertSame($expected, $result);
    }
}
