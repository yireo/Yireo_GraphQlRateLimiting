<?php

declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Rate;

use GraphQL\Error\Error;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
use Sunspikes\Ratelimit\Cache\Adapter\CacheAdapterInterface;
use Sunspikes\Ratelimit\Cache\Adapter\DesarrollaCacheAdapter;
use Sunspikes\Ratelimit\Cache\Factory\DesarrollaCacheFactory;
use Sunspikes\Ratelimit\RateLimiter;
use Sunspikes\Ratelimit\Throttle\Factory\ThrottlerFactory;
use Sunspikes\Ratelimit\Throttle\Hydrator\HydratorFactory;
use Sunspikes\Ratelimit\Throttle\Settings\ElasticWindowSettings;
use Sunspikes\Ratelimit\Throttle\Settings\ThrottleSettingsInterface;
use Yireo\GraphQlRateLimiting\Cache\Adapter;
use Yireo\GraphQlRateLimiting\Config\Config;

/**
 * Class Limiter
 * @package Yireo\GraphQlRateLimiting\Rate
 */
class Limiter
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Adapter
     */
    private $cacheAdapter;

    /**
     * QueryComplexityLimiterPlugin constructor.
     * @param Config $config
     * @param Adapter $cacheAdapter
     */
    public function __construct(
        Config $config,
        Adapter $cacheAdapter
    ) {
        $this->config = $config;
        $this->cacheAdapter = $cacheAdapter;
    }

    /**
     * @param string $source
     * @return bool
     * @throws Error
     * @throws SyntaxError
     */
    public function execute(string $source): bool
    {
        if (!$this->config->enabled()) {
            return false;
        }

        $maxRequests = $this->getMaxRequests($source);
        $cacheAdapter = $this->cacheAdapter;
        $rateLimiter = new RateLimiter(
            new ThrottlerFactory($cacheAdapter),
            new HydratorFactory(),
            $this->getThrottleSettings($source)
        );

        $throttler = $rateLimiter->get($source);
        if ($throttler->access() === false) {
            $msg = 'A maximum of ' . $maxRequests . ' queries has been reached.';
            throw new Error($msg);
        }

        return true;
    }

    /**
     * @param string $source
     * @return int
     * @throws SyntaxError
     */
    private function getMaxRequests(string $source): int
    {
        if ($this->isMutationString($source)) {
            return $this->config->getMaxMutations();
        }

        return $this->config->getMaxQueries();
    }

    /**
     * @param string $source
     * @return string
     * @throws SyntaxError
     */
    private function getOperationFromSource(string $source)
    {
        $sourceDocument = Parser::parse($source)->toArray(true);
        if (isset($sourceDocument['definitions']['operation'])) {
            return $sourceDocument['definitions']['operation'];
        }

        return 'unknown';
    }

    /**
     * @param string $source
     * @return bool
     * @throws SyntaxError
     */
    private function isMutationString(string $source): bool
    {
        return ($this->getOperationFromSource($source) === 'mutation');
    }

    /**
     * @return ThrottleSettingsInterface
     * @throws SyntaxError
     */
    private function getThrottleSettings(string $source): ThrottleSettingsInterface
    {
        $maxRequests = $this->getMaxRequests($source);
        $timeFrameInSeconds = $this->config->getTimeFrameInSeconds();
        $settings = new ElasticWindowSettings($maxRequests, $timeFrameInSeconds);
        return $settings;
    }
}
