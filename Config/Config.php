<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 * @package Yireo\GraphQlRateLimiting\Config
 */
class Config
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return int
     */
    public function enabled(): bool
    {
        return (bool)$this->scopeConfig->getValue('graphql_rate_limiting/settings/enabled');
    }

    /**
     * @return bool
     */
    public function limitMutations(): bool
    {
        return (bool)$this->scopeConfig->getValue('graphql_rate_limiting/settings/limit_mutations');
    }

    /**
     * @return bool
     */
    public function limitQueries(): bool
    {
        return (bool)$this->scopeConfig->getValue('graphql_rate_limiting/settings/limit_queries');
    }

    /**
     * @return int
     */
    public function getMaxMutations(): int
    {
        return (int)$this->scopeConfig->getValue('graphql_rate_limiting/settings/max_mutations');
    }

    /**
     * @return int
     */
    public function getMaxQueries(): int
    {
        return (int)$this->scopeConfig->getValue('graphql_rate_limiting/settings/max_queries');
    }

    /**
     * @return int
     */
    public function getTimeFrameInSeconds(): int
    {
        return (int)$this->scopeConfig->getValue('graphql_rate_limiting/settings/timeframe');
    }

    /**
     * @return int
     */
    public function getCacheTtlInSeconds(): int
    {
        return (int)$this->scopeConfig->getValue('graphql_rate_limiting/settings/cache_ttl');
    }

    /**
     * @return bool
     */
    public function identifyByUserAgent(): bool
    {
        return (bool)$this->scopeConfig->getValue('graphql_rate_limiting/settings/identify_by_user_agent');
    }
}
