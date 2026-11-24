<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Integration;

use Magento\Config\Model\Config\Structure;
use Magento\Framework\Acl\AclResource\ProviderInterface;
use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;

/**
 * @magentoAppArea adminhtml
 */
class AclTest extends AbstractTestCase
{
    private const RESOURCE = 'Yireo_GraphQlRateLimiting::config';

    public function testIfConfigSectionResourceIsDefinedInAcl(): void
    {
        $resources = $this->objectManager->get(ProviderInterface::class)->getAclResources();
        $this->assertContains(self::RESOURCE, $this->getResourceIds($resources));
    }

    public function testIfConfigSectionUsesAclResource(): void
    {
        $section = $this->objectManager->get(Structure::class)->getElement('graphql_rate_limiting');
        $this->assertSame(self::RESOURCE, $section->getAttribute('resource'));
    }

    private function getResourceIds(array $resources): array
    {
        $ids = [];
        foreach ($resources as $resource) {
            $ids[] = $resource['id'];
            $ids = array_merge($ids, $this->getResourceIds($resource['children'] ?? []));
        }

        return $ids;
    }
}
