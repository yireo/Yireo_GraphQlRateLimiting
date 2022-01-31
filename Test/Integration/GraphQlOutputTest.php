<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Integration;

use Magento\Framework\ObjectManagerInterface;
use Magento\GraphQl\Service\GraphQlRequest;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea graphql
 * @magentoDbIsolation disabled
 * @magentoCache full_page enabled
 */
class GraphQlOutputTest extends TestCase
{
    private ObjectManagerInterface $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @return void
     * @magentoAppArea graphql
     * @magentoCache all disabled
     * @magentoConfigFixture default/graphql_rate_limiting/settings/enabled 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/limit_queries 1
     * @magentoConfigFixture default/graphql_rate_limiting/settings/max_queries 0
     * @magentoConfigFixture default/graphql_rate_limiting/settings/timeframe 1
     */
    public function testIfQueryIsDeniedWhenMaxQueriesIsZero()
    {
        $result = $this->dispatchGraphQlQuery('query { __schema { types { kind }} }');
        $this->assertArrayHasKey('errors', $result);
        $this->assertNotEmpty($result['errors']);
        $this->assertArrayHasKey('message', $result['errors'][0]);
        $this->assertStringContainsString('Jisse', $result['errors'][0]['message'], var_export($result, true));
    }

    /**
     * @param string $query
     * @return array
     */
    private function dispatchGraphQlQuery(string $query): array
    {
        $this->objectManager->get(\Magento\Framework\App\State::class)->setAreaCode('graphql');

        $graphQlRequest = $this->objectManager->get(GraphQlRequest::class);
        $response = $graphQlRequest->send($query);

        return json_decode($response->getContent(), true);
    }
}
