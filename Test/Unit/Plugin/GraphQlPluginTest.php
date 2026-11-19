<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Unit\Plugin;

use GraphQL\Error\Error;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\GraphQl\Controller\GraphQl;
use PHPUnit\Framework\TestCase;
use Yireo\GraphQlRateLimiting\Cache\Adapter;
use Yireo\GraphQlRateLimiting\Config\Config;
use Yireo\GraphQlRateLimiting\Request\Identification;
use Yireo\GraphQlRateLimiting\Request\Information;
use Yireo\GraphQlRateLimiting\Plugin\GraphQlPlugin;
use Yireo\GraphQlRateLimiting\Rate\Limiter;

class GraphQlPluginTest extends TestCase
{
    public function testExceededLimitSetsStatusAndRetryAfter(): void
    {
        $limiter = $this->createStub(Limiter::class);
        $limiter->method('consumeLimitExceeded')->willReturn(true);
        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('getTimeFrameInSeconds')->willReturn(60);
        $response = $this->createMock(Http::class);
        $response->expects($this->once())->method('setHttpResponseCode')->with(429);
        $response->expects($this->once())->method('setNoCacheHeaders');
        $response->expects($this->once())->method('setHeader')->with('Retry-After', '60', true);
        $plugin = new GraphQlPlugin($limiter, $config);

        $this->assertSame($response, $plugin->afterDispatch($this->createStub(GraphQl::class), $response));
    }

    public function testResponseIsUntouchedWhenLimitIsNotExceeded(): void
    {
        $limiter = $this->createStub(Limiter::class);
        $limiter->method('consumeLimitExceeded')->willReturn(false);
        $config = $this->createMock(Config::class);
        $config->expects($this->never())->method('getTimeFrameInSeconds');
        $response = $this->createMock(Http::class);
        $response->expects($this->never())->method('setHttpResponseCode');
        $response->expects($this->never())->method('setHeader');
        $response->expects($this->never())->method('setNoCacheHeaders');
        $plugin = new GraphQlPlugin($limiter, $config);

        $this->assertSame($response, $plugin->afterDispatch($this->createStub(GraphQl::class), $response));
    }
    public function testDeniedDispatchThenSecondDispatchLeavesResponseUntouched(): void
    {
        $limiter = $this->createLimiter();
        $config = $this->createStub(Config::class);
        $config->method('getTimeFrameInSeconds')->willReturn(60);
        $plugin = new GraphQlPlugin($limiter, $config);
        $controller = $this->createStub(GraphQl::class);
        $plugin->beforeDispatch($controller);
        $this->denyAccess($limiter);
        $firstResponse = $this->createMock(Http::class);
        $firstResponse->expects($this->once())->method('setHttpResponseCode')->with(429);
        $plugin->afterDispatch($controller, $firstResponse);
        $this->assertFalse($limiter->isLimitExceeded());

        $plugin->beforeDispatch($controller);
        $secondResponse = $this->createMock(Http::class);
        $secondResponse->expects($this->never())->method('setHttpResponseCode');
        $secondResponse->expects($this->never())->method('setHeader');
        $secondResponse->expects($this->never())->method('setNoCacheHeaders');
        $this->assertSame($secondResponse, $plugin->afterDispatch($controller, $secondResponse));
    }

    public function testBeforeDispatchClearsFlagWhenPreviousAfterDispatchWasSkipped(): void
    {
        $limiter = $this->createLimiter();
        $this->denyAccess($limiter);
        $plugin = new GraphQlPlugin($limiter, $this->createStub(Config::class));

        $this->assertNull($plugin->beforeDispatch($this->createStub(GraphQl::class)));
        $this->assertFalse($limiter->isLimitExceeded());
    }

    public function testZeroTimeframeUsesCacheTtl(): void
    {
        $limiter = $this->createLimiter();
        $this->denyAccess($limiter);
        $config = $this->createMock(Config::class);
        $config->method('getTimeFrameInSeconds')->willReturn(0);
        $config->expects($this->once())->method('getCacheTtlInSeconds')->willReturn(300);
        $response = $this->createMock(Http::class);
        $response->expects($this->once())->method('setHeader')->with('Retry-After', '300', true);
        $plugin = new GraphQlPlugin($limiter, $config);

        $this->assertSame($response, $plugin->afterDispatch($this->createStub(GraphQl::class), $response));
    }

    public function testNonHttpResponseIsUntouchedAndFlagIsCleared(): void
    {
        $limiter = $this->createLimiter();
        $this->denyAccess($limiter);
        $config = $this->createMock(Config::class);
        $config->expects($this->never())->method('getTimeFrameInSeconds');
        $response = $this->createStub(ResponseInterface::class);
        $plugin = new GraphQlPlugin($limiter, $config);

        $this->assertSame($response, $plugin->afterDispatch($this->createStub(GraphQl::class), $response));
        $this->assertFalse($limiter->isLimitExceeded());
    }

    private function createLimiter(): Limiter
    {
        $config = $this->createStub(Config::class);
        $config->method('enabled')->willReturn(true);
        $config->method('getTimeFrameInSeconds')->willReturn(60);
        $adapter = $this->createStub(Adapter::class);
        $adapter->method('get')->willReturn(2);
        $identification = $this->createStub(Identification::class);
        $identification->method('getUniqIdFromRequestAndSource')->willReturn('request-source');

        return new Limiter($config, $adapter, $this->createStub(Information::class), $identification);
    }

    private function denyAccess(Limiter $limiter): void
    {
        try {
            $limiter->limit('{ storeConfig { store_code } }', 2);
            $this->fail('Expected the rate limit error');
        } catch (Error $error) {
            $this->assertTrue($limiter->isLimitExceeded());
        }
    }
}
