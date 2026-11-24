<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Unit\Plugin;

use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\GraphQl\Controller\GraphQl;
use PHPUnit\Framework\TestCase;
use Yireo\GraphQlRateLimiting\Plugin\GraphQlPlugin;
use Yireo\GraphQlRateLimiting\Rate\Limiter;

class GraphQlPluginTest extends TestCase
{
    public function testExceededLimitSetsStatusAndRetryAfter(): void
    {
        $limiter = $this->createStub(Limiter::class);
        $limiter->method('getRetryAfter')->willReturn(42);
        $limiter->method('consumeLimitExceeded')->willReturn(true);
        $response = $this->createMock(Http::class);
        $response->expects($this->once())->method('setHttpResponseCode')->with(429);
        $response->expects($this->once())->method('setNoCacheHeaders');
        $response->expects($this->once())->method('setHeader')->with('Retry-After', '42', true);

        $plugin = new GraphQlPlugin($limiter);
        $this->assertSame($response, $plugin->afterDispatch($this->createStub(GraphQl::class), $response));
    }

    public function testResponseIsUntouchedWhenLimitIsNotExceeded(): void
    {
        $limiter = $this->createStub(Limiter::class);
        $limiter->method('consumeLimitExceeded')->willReturn(false);
        $response = $this->createMock(Http::class);
        $response->expects($this->never())->method('setHttpResponseCode');
        $response->expects($this->never())->method('setHeader');
        $response->expects($this->never())->method('setNoCacheHeaders');

        $plugin = new GraphQlPlugin($limiter);
        $this->assertSame($response, $plugin->afterDispatch($this->createStub(GraphQl::class), $response));
    }

    public function testBeforeDispatchResetsLimiter(): void
    {
        $limiter = $this->createMock(Limiter::class);
        $limiter->expects($this->once())->method('resetLimitExceeded');

        (new GraphQlPlugin($limiter))->beforeDispatch($this->createStub(GraphQl::class));
    }

    public function testNonHttpResponseIsUntouched(): void
    {
        $limiter = $this->createMock(Limiter::class);
        $limiter->expects($this->once())->method('consumeLimitExceeded')->willReturn(true);
        $response = $this->createStub(ResponseInterface::class);

        $plugin = new GraphQlPlugin($limiter);
        $this->assertSame($response, $plugin->afterDispatch($this->createStub(GraphQl::class), $response));
    }
}
