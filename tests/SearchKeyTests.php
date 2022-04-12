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
        $result = $this->store->searchKey('*3*');

        $this->assertSame(['KEY%3' => 'VALUE%3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_non_standard_wildcards(): void
    {
        $result = $this->store->searchKey('_3_', '_');

        $this->assertSame(['KEY%3' => 'VALUE%3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_keys_with_percentage_sign_wildcards(): void
    {
        $result = $this->store->searchKey('%3%', '%');

        $this->assertSame(['KEY%3' => 'VALUE%3'], $result);
    }
}
