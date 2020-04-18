<?php

declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * Class CacheType
 * @package Yireo\GraphQlRateLimiting\Cache\Type
 */
class CacheType extends TagScope
{
    const TYPE_IDENTIFIER = 'graphql_rate_limiting';

    const CACHE_TAG = 'GRAPHQL_RATE_LIMITING';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
