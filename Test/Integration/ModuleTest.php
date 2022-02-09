<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Integration;

use Yireo\IntegrationTestHelper\Test\Integration\AbstractTestCase;

class ModuleTest extends AbstractTestCase
{
    public function testIfModuleIsWorking()
    {
        $this->assertModuleIsRegistered('Yireo_GraphQlRateLimiting');
        $this->assertModuleIsEnabled('Yireo_GraphQlRateLimiting');
    }
}
