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
        $result = $this->store->searchValue('*3*');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_values_with_non_standard_wildcards(): void
    {
        $result = $this->store->searchValue('_3_', '_');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }

    /**
     * @test
     */
    public function it_should_search_values_with_percentage_sign_wildcards(): void
    {
        $result = $this->store->searchValue('%3%', '%');

        $this->assertSame(['KEY 3' => 'VALUE 3'], $result);
    }
}
