<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Unit\Rate;

use Magento\Framework\App\CacheInterface;

class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, string>
     */
    public $data = [];

    /**
     * @var array<string, array>
     */
    public $tags = [];

    public function getFrontend()
    {
        throw new \LogicException('Not supported');
    }

    public function load($identifier)
    {
        return $this->data[$identifier] ?? false;
    }

    public function save($data, $identifier, $tags = [], $lifeTime = null)
    {
        $this->data[$identifier] = (string)$data;
        $this->tags[$identifier] = $tags;
        return true;
    }

    public function remove($identifier)
    {
        unset($this->data[$identifier]);
        return true;
    }

    public function clean($tags = [])
    {
        $this->data = [];
        return true;
    }
}
