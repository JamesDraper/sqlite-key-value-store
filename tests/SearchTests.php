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
        $result = $this->store->search('*3*', '*3*');

        $this->assertSame(['KEY%3' => 'VALUE%3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_with_non_standard_wildcards(): void
    {
        $result = $this->store->search('_3_', '_3_', '_');

        $this->assertSame(['KEY%3' => 'VALUE%3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_with_percentage_sign_wildcards(): void
    {
        $result = $this->store->search('%3%', '%3%', '%');

        $this->assertSame(['KEY%3' => 'VALUE%3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_while_escaping_standard_sqlite_wildcard(): void
    {
        $result = $this->store->search('*%3*', '*%3*');

        $this->assertSame(['KEY%3' => 'VALUE%3'], $result);
    }
}
