<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Unit\Rate;

use PHPUnit\Framework\TestCase;
use Yireo\GraphQlRateLimiting\Cache\Type\CacheType;
use Yireo\GraphQlRateLimiting\Rate\Counter;

class CounterTest extends TestCase
{
    public function testUnknownBucketIsEmpty(): void
    {
        $this->assertSame([0, 0], (new Counter(new InMemoryCache()))->get('foo'));
    }

    public function testSavedValueIsReturnedAndTagged(): void
    {
        $cache = new InMemoryCache();
        $counter = new Counter($cache);
        $expiresAt = time() + 60;
        $counter->save('foo', 3, $expiresAt);

        $this->assertSame([3, $expiresAt], $counter->get('foo'));
        $this->assertSame([CacheType::CACHE_TAG], $cache->tags['GRAPHQL_RATE_LIMITING_foo']);
    }

    public function testExpiredWindowIsEmpty(): void
    {
        $counter = new Counter(new InMemoryCache());
        $counter->save('foo', 3, time() - 1);

        $this->assertSame([0, 0], $counter->get('foo'));
    }

    public function testCorruptValueIsEmpty(): void
    {
        $cache = new InMemoryCache();
        $cache->data['GRAPHQL_RATE_LIMITING_foo'] = 'O:8:"stdClass":0:{}';

        $this->assertSame([0, 0], (new Counter($cache))->get('foo'));
    }
}
