<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Rate;

use Magento\Framework\App\CacheInterface;
use Yireo\GraphQlRateLimiting\Cache\Type\CacheType;

/**
 * Stores counters in the cache frontend directly, so that disabling the cache type does not disable rate limiting
 */
class Counter
{
    private const KEY_PREFIX = 'GRAPHQL_RATE_LIMITING_';

    /**
     * @param CacheInterface $cache
     */
    public function __construct(
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @param string $bucket
     * @return int[] Count and expiration timestamp of the current window, both 0 when there is no active window
     */
    public function get(string $bucket): array
    {
        $parts = explode(':', (string)$this->cache->load(self::KEY_PREFIX . $bucket));
        if (count($parts) !== 2 || !ctype_digit($parts[0]) || !ctype_digit($parts[1])) {
            return [0, 0];
        }

        $expiresAt = (int)$parts[1];
        if ($expiresAt <= time()) {
            return [0, 0];
        }

        return [(int)$parts[0], $expiresAt];
    }

    /**
     * @param string $bucket
     * @param int $count
     * @param int $expiresAt
     * @return void
     */
    public function save(string $bucket, int $count, int $expiresAt): void
    {
        $this->cache->save(
            $count . ':' . $expiresAt,
            self::KEY_PREFIX . $bucket,
            [CacheType::CACHE_TAG],
            max(1, $expiresAt - time())
        );
    }
}
