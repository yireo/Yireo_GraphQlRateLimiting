<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Integration;

use Yireo\IntegrationTestHelper\Test\Integration\GraphQlTestCase;

class GraphQlOutputTest extends GraphQlTestCase
{
    /**
     * @return void
     * @magentoAppArea graphql
     * @magentoCache full_page disabled
     * @magentoConfigFixture default/graphql_rate_limiting/settings/enabled 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/limit_queries 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/max_queries 0
     * @magentoConfigFixture default/graphql_rate_limiting/settings/timeframe 1
     */
    public function testIfQueryIsDeniedWhenMaxQueriesIsZero()
    {
        $queryData = $this->getGraphQlQueryData('query { __schema { types { kind }} }');
        $this->assertGraphQlDataHasError('A maximum of 0 queries has been reached', $queryData);
    }
}
