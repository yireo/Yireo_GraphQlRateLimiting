<?php

declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Rate;

use GraphQL\Error\Error;
use GraphQL\Error\SyntaxError;
use Sunspikes\Ratelimit\RateLimiter;
use Sunspikes\Ratelimit\Throttle\Factory\ThrottlerFactory;
use Sunspikes\Ratelimit\Throttle\Hydrator\HydratorFactory;
use Sunspikes\Ratelimit\Throttle\Settings\ElasticWindowSettings;
use Sunspikes\Ratelimit\Throttle\Settings\ThrottleSettingsInterface;
use Yireo\GraphQlRateLimiting\Cache\Adapter;
use Yireo\GraphQlRateLimiting\Config\Config;
use Yireo\GraphQlRateLimiting\Request\Information;

/**
 * Class Limiter
 * @package Yireo\GraphQlRateLimiting\Rate
 */
class Limiter
{
    const ERROR_MSG_LIMIT_QUERIES = 'A maximum of %d queries has been reached';

    const ERROR_MSG_LIMIT_MUTATIONS = 'A maximum of %d mutations has been reached';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Adapter
     */
    private $cacheAdapter;
    /**
     * @var Information
     */
    private $requestInformation;

    /**
     * QueryComplexityLimiterPlugin constructor.
     * @param Config $config
     * @param Adapter $cacheAdapter
     * @param Information $requestInformation
     */
    public function __construct(
        Config $config,
        Adapter $cacheAdapter,
        Information $requestInformation
    ) {
        $this->config = $config;
        $this->cacheAdapter = $cacheAdapter;
        $this->requestInformation = $requestInformation;
    }

    /**
     * @param string $identifier
     * @param int $maxRequests
     * @param string $msg
     * @return bool
     * @throws Error
     * @throws SyntaxError
     */
    public function limit(
        string $identifier,
        int $maxRequests,
        string $msg = self::ERROR_MSG_LIMIT_QUERIES
    ): bool {
        if (!$this->config->enabled()) {
            return false;
        }

        $rateLimiter = $this->getRateLimiter($maxRequests);
        $throttler = $rateLimiter->get($identifier);
        if ($throttler->access() === false) {
            throw new Error(sprintf(__($msg), $maxRequests));
        }

        return true;
    }

    /**
     * @param string $source
     * @return bool
     * @throws Error
     * @throws SyntaxError
     */
    public function limitBySource(string $source): bool
    {
        $type = $this->requestInformation->isMutationString($source);
        $msg = ($type) ? self::ERROR_MSG_LIMIT_MUTATIONS : self::ERROR_MSG_LIMIT_QUERIES;
        return $this->limit($source, $this->getMaxRequests($source), $msg);
    }

    /**
     * @param string $identifier
     * @return bool
     * @throws Error
     * @throws SyntaxError
     */
    public function limitQuery(string $identifier): bool
    {
        if (!$this->config->enabled()) {
            return false;
        }

        return $this->limit($identifier, $this->config->getMaxQueries());
    }

    /**
     * @param string $identifier
     * @return bool
     * @throws Error
     * @throws SyntaxError
     */
    public function limitMutation(string $identifier): bool
    {
        if (!$this->config->enabled()) {
            return false;
        }

        $msg = self::ERROR_MSG_LIMIT_MUTATIONS;
        return $this->limit($identifier, $this->config->getMaxMutations(), $msg);
    }

    /**
     * @param int $maxRequests
     * @return RateLimiter
     * @throws SyntaxError
     */
    private function getRateLimiter(int $maxRequests): RateLimiter
    {
        return new RateLimiter(
            new ThrottlerFactory($this->cacheAdapter),
            new HydratorFactory(),
            $this->getThrottleSettings($maxRequests)
        );
    }

    /**
     * @param string $source
     * @return int
     * @throws SyntaxError
     */
    private function getMaxRequests(string $source): int
    {
        if ($this->requestInformation->isMutationString($source)) {
            return $this->config->getMaxMutations();
        }

        return $this->config->getMaxQueries();
    }

    /**
     * @return ThrottleSettingsInterface
     * @throws SyntaxError
     */
    private function getThrottleSettings(int $maxRequests): ThrottleSettingsInterface
    {
        $timeFrameInSeconds = $this->config->getTimeFrameInSeconds();
        $settings = new ElasticWindowSettings($maxRequests, $timeFrameInSeconds);
        return $settings;
    }
}
