<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Integration;

use Magento\Framework\GraphQl\Query\QueryProcessor;
use Magento\Framework\Interception\PluginList\PluginList;
use Yireo\GraphQlRateLimiting\Plugin\QueryProcessorPlugin;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;

class PluginTest extends AbstractTestCase
{
    /**
     * @magentoAppArea graphql
     */
    public function testIfPluginIsEnabledInGraphQlArea(): void
    {
        $this->assertInterceptorPluginIsRegistered(
            QueryProcessor::class,
            QueryProcessorPlugin::class,
            'Yireo_GraphQlRateLimiting_QueryProcessorPlugin'
        );
    }

    /**
     * @magentoAppArea frontend
     */
    public function testIfPluginIsNotEnabledOutsideGraphQlArea(): void
    {
        $pluginInfo = $this->objectManager->get(PluginList::class)->get(QueryProcessor::class, []);
        $this->assertArrayNotHasKey('Yireo_GraphQlRateLimiting_QueryProcessorPlugin', (array)$pluginInfo);
    }
}
