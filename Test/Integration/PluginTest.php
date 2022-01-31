<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Integration;

use Magento\Developer\Model\Di\PluginList;
use Magento\Framework\GraphQl\Query\QueryProcessor;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;
use Yireo\GraphQlRateLimiting\Plugin\QueryProcessorPlugin;

class PluginTest extends TestCase
{
    public function testIfPluginIsEnabled()
    {
        $objectManager = ObjectManager::getInstance();
        $pluginList = $objectManager->get(PluginList::class);
        $plugins = $pluginList->getPluginsListByClass(QueryProcessor::class);
        $this->assertTrue(!empty($plugins));
        $this->assertArrayHasKey('before', $plugins);
        $this->assertArrayHasKey(QueryProcessorPlugin::class, $plugins['before']);
    }
}
