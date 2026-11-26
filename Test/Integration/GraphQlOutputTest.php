<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Integration;

use Magento\Framework\App\CacheInterface;
use Yireo\GraphQlRateLimiting\Cache\Type\CacheType;
use Yireo\GraphQlRateLimiting\Rate\Limiter;
use Yireo\IntegrationTestHelper\Test\Integration\GraphQlTestCase;

/**
 * @magentoAppArea graphql
 * @magentoCache full_page disabled
 */
class GraphQlOutputTest extends GraphQlTestCase
{
    private const QUERY = 'query { __typename }';

    protected function setUp(): void
    {
        parent::setUp();
        $this->objectManager->get(CacheInterface::class)->clean([CacheType::CACHE_TAG]);
    }

    /**
     * @magentoConfigFixture default/graphql_rate_limiting/settings/enabled 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/limit_queries 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/max_queries 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/timeframe 60
     */
    public function testIfQueryIsDeniedAfterMaxQueries(): void
    {
        $this->assertGraphQlDataHasData('__typename', $this->getGraphQlQueryData(self::QUERY));
        $this->assertGraphQlDataHasError(Limiter::ERROR_MSG, $this->getGraphQlQueryData(self::QUERY));
    }

    /**
     * @magentoCache graphql_rate_limiting disabled
     * @magentoConfigFixture default/graphql_rate_limiting/settings/enabled 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/limit_queries 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/max_queries 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/timeframe 60
     */
    public function testIfQueryIsDeniedWhenCacheTypeIsDisabled(): void
    {
        $this->getGraphQlQueryData(self::QUERY);
        $this->assertGraphQlDataHasError(Limiter::ERROR_MSG, $this->getGraphQlQueryData(self::QUERY));
    }

    /**
     * @magentoConfigFixture default/graphql_rate_limiting/settings/enabled 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/limit_queries 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/max_queries 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/timeframe 60
     */
    public function testIfAliasesAreCountedSeparately(): void
    {
        $queryData = $this->getGraphQlQueryData('query { a: __typename b: __typename }');
        $this->assertGraphQlDataHasError(Limiter::ERROR_MSG, $queryData);
    }

    /**
     * @magentoConfigFixture default/graphql_rate_limiting/settings/enabled 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/limit_queries 0
     * @magentoConfigFixture default/graphql_rate_limiting/settings/max_queries 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/timeframe 60
     */
    public function testIfQueriesAreNotLimitedWhenDisabled(): void
    {
        $this->getGraphQlQueryData(self::QUERY);
        $this->assertGraphQlDataHasData('__typename', $this->getGraphQlQueryData(self::QUERY));
    }
}
