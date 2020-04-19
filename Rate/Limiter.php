<?php

declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Rate;

use GraphQL\Error\Error;
use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;
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
        $type = $this->isMutationString($source);
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
        if (isset($sourceDocument['definitions'][0]['operation'])) {
            return (string)$sourceDocument['definitions'][0]['operation'];
        }

        return 'query';
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
    private function getThrottleSettings(int $maxRequests): ThrottleSettingsInterface
    {
        $timeFrameInSeconds = $this->config->getTimeFrameInSeconds();
        $settings = new ElasticWindowSettings($maxRequests, $timeFrameInSeconds);
        return $settings;
    }
}
