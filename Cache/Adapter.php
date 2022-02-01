<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Cache;

use Magento\Framework\Serialize\Serializer\Serialize;
use Sunspikes\Ratelimit\Cache\Adapter\CacheAdapterInterface;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Yireo\GraphQlRateLimiting\Cache\Type\CacheType;
use Yireo\GraphQlRateLimiting\Config\Config;

class Adapter implements CacheAdapterInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CacheType
     */
    private $cacheType;

    /**
     * @var Serialize
     */
    private $serialize;

    /**
     * Adapter constructor.
     * @param Config $config
     * @param CacheType $cacheType
     * @param Serialize $serialize
     */
    public function __construct(
        Config $config,
        CacheType $cacheType,
        Serialize $serialize
    ) {
        $this->config = $config;
        $this->cacheType = $cacheType;
        $this->serialize = $serialize;
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        $value = $this->cacheType->load($key);
        if (!$value) {
            return $value;
        }

        return $this->serialize->unserialize($value);
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value, $ttl = null)
    {
        if (empty($ttl)) {
            $ttl = $this->config->getCacheTtlInSeconds();
        }

        $value = $this->serialize->serialize($value);
        $this->cacheType->save($value, $key, [], $ttl);
        return null;
    }

    /**
     * @inheritDoc
     */
    public function delete($key)
    {
        return $this->cacheType->remove($key);
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return (bool)$this->cacheType->test($key);
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->cacheType->clean();
    }
}
