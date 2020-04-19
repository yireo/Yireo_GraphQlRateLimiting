<?php

declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Functional;

use Magento\Framework\App\Bootstrap;
use PHPUnit\Runner\Exception;
use Yireo\GraphQlRateLimiting\Cache\Type\CacheType;

/**
 * Class CacheTypeTest
 * @package Yireo\GraphQlRateLimiting\Test\Functional
 */
class CacheTypeTest extends AbstractTestCase
{
    /**
     * For this test to work, the GRAPHQL_RATE_LIMITING cache needs to be enabled
     */
    public function testIfCacheTypeIsUsable()
    {
        /** @var CacheType $cacheType */
        $cacheType = $this->getObjectManager()->create(CacheType::class);
        $cacheType->save('Hello World', 'helloworld', [CacheType::CACHE_TAG]);
        $this->assertNotEmpty($cacheType->test('helloworld'));

        $cacheEntry = $cacheType->load('helloWorld');
        $this->assertEquals('Hello World', $cacheEntry);
    }
}
