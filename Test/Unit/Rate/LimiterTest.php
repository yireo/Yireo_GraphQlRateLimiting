<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Unit\Rate;

use GraphQL\Error\Error;
use PHPUnit\Framework\TestCase;
use Yireo\GraphQlRateLimiting\Cache\Adapter;
use Yireo\GraphQlRateLimiting\Config\Config;
use Yireo\GraphQlRateLimiting\Rate\Limiter;
use Yireo\GraphQlRateLimiting\Request\Identification;
use Yireo\GraphQlRateLimiting\Request\Information;

class LimiterTest extends TestCase
{
    public function testDeniedAccessSetsFlagAndPreservesError(): void
    {
        $limiter = $this->createLimiter(2);

        try {
            $limiter->limit('{ storeConfig { store_code } }', 2);
            $this->fail('Expected the rate limit error');
        } catch (Error $error) {
            $this->assertSame('A maximum of 2 queries has been reached', $error->getMessage());
            $this->assertTrue($limiter->isLimitExceeded());
        }
    }

    public function testAllowedAccessDoesNotSetFlag(): void
    {
        $limiter = $this->createLimiter(1);

        $this->assertFalse($limiter->isLimitExceeded());
        $this->assertTrue($limiter->limit('{ storeConfig { store_code } }', 2));
        $this->assertFalse($limiter->isLimitExceeded());
    }

    public function testDisabledLimiterDoesNotSetFlag(): void
    {
        $config = $this->createStub(Config::class);
        $config->method('enabled')->willReturn(false);
        $adapter = $this->createMock(Adapter::class);
        $adapter->expects($this->never())->method('get');
        $limiter = new Limiter(
            $config,
            $adapter,
            $this->createStub(Information::class),
            $this->createStub(Identification::class)
        );

        $this->assertFalse($limiter->limit('{ storeConfig { store_code } }', 2));
        $this->assertFalse($limiter->isLimitExceeded());
    }

    private function createLimiter(int $count): Limiter
    {
        $config = $this->createStub(Config::class);
        $config->method('enabled')->willReturn(true);
        $config->method('getTimeFrameInSeconds')->willReturn(60);
        $adapter = $this->createMock(Adapter::class);
        $adapter->expects($this->once())->method('get')->willReturn($count);
        $adapter->expects($this->once())->method('set')->with($this->callback(static fn ($key): bool => is_string($key)), $count + 1, 60);
        $identification = $this->createStub(Identification::class);
        $identification->method('getUniqIdFromRequestAndSource')->willReturn('request-source');

        return new Limiter($config, $adapter, $this->createStub(Information::class), $identification);
    }
}
