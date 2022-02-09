<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Integration;

use Magento\Framework\GraphQl\Query\QueryProcessor;
use Yireo\GraphQlRateLimiting\Plugin\QueryProcessorPlugin;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;

class PluginTest extends AbstractTestCase
{
    public function testIfPluginIsEnabled()
    {
        $this->assertInterceptorPluginIsRegistered(
            QueryProcessor::class,
            QueryProcessorPlugin::class,
            'Yireo_GraphQlRateLimiting_QueryProcessorPlugin'
        );
    }
}
