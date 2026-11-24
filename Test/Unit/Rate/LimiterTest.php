<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Unit\Rate;

use GraphQL\Error\Error;
use GraphQL\Language\Parser;
use Magento\Framework\Lock\LockManagerInterface;
use PHPUnit\Framework\TestCase;
use Yireo\GraphQlRateLimiting\Config\Config;
use Yireo\GraphQlRateLimiting\Rate\Counter;
use Yireo\GraphQlRateLimiting\Rate\Limiter;
use Yireo\GraphQlRateLimiting\Request\Identification;
use Yireo\GraphQlRateLimiting\Request\Information;

class LimiterTest extends TestCase
{
    private InMemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache();
    }

    public function testRequestsAreDeniedAfterMaximum(): void
    {
        $limiter = $this->createLimiter(['max_mutations' => 2]);

        $this->assertTrue($this->limit($limiter, 'mutation { createEmptyCart }'));
        $this->assertTrue($this->limit($limiter, 'mutation { createEmptyCart }'));
        $this->assertDenied($limiter, 'mutation { createEmptyCart }');
        $this->assertTrue($limiter->consumeLimitExceeded());
        $this->assertFalse($limiter->consumeLimitExceeded());
        $this->assertSame(0, $limiter->getRetryAfter());
    }

    public function testDeniedRequestsAreNotCounted(): void
    {
        $limiter = $this->createLimiter(['max_mutations' => 1]);
        $this->limit($limiter, 'mutation { createEmptyCart }');
        $this->assertDenied($limiter, 'mutation { createEmptyCart }');
        $this->assertDenied($limiter, 'mutation { createEmptyCart }');

        $this->assertSame(['1'], array_map(
            static fn (string $value): string => explode(':', $value)[0],
            array_values($this->cache->data)
        ));
    }

    public function testAliasesAreCountedPerRootField(): void
    {
        $limiter = $this->createLimiter(['max_mutations' => 2]);

        $this->assertDenied($limiter, 'mutation { a: createEmptyCart b: createEmptyCart c: createEmptyCart }');
        $this->assertSame([], $this->cache->data);
    }

    public function testFragmentNamesDoNotCreateNewBuckets(): void
    {
        $limiter = $this->createLimiter(['max_mutations' => 1]);

        $this->limit($limiter, 'mutation { ...A } fragment A on Mutation { createEmptyCart }');
        $this->assertDenied($limiter, 'mutation { ...B } fragment B on Mutation { createEmptyCart }');
    }

    public function testFragmentFirstMutationUsesMutationLimit(): void
    {
        $limiter = $this->createLimiter(['max_mutations' => 1, 'max_queries' => 100]);

        $this->limit($limiter, 'fragment F on Mutation { createEmptyCart } mutation { ...F }');
        $this->assertDenied($limiter, 'fragment F on Mutation { createEmptyCart } mutation { ...F }');
    }

    public function testDifferentRootFieldsUseDifferentBuckets(): void
    {
        $limiter = $this->createLimiter(['max_mutations' => 1]);

        $this->assertTrue($this->limit($limiter, 'mutation { createEmptyCart }'));
        $this->assertTrue($this->limit($limiter, 'mutation { generateCustomerToken { token } }'));
    }

    public function testRetryAfterIsRemainingWindow(): void
    {
        $limiter = $this->createLimiter(['max_mutations' => 1, 'timeframe' => 60]);
        $this->limit($limiter, 'mutation { createEmptyCart }');
        $this->assertDenied($limiter, 'mutation { createEmptyCart }');

        $this->assertGreaterThanOrEqual(59, $limiter->getRetryAfter());
        $this->assertLessThanOrEqual(60, $limiter->getRetryAfter());
    }

    public function testFlagsAreHonoured(): void
    {
        $limiter = $this->createLimiter([
            'limit_queries' => false,
            'limit_mutations' => false,
            'max_queries' => 1,
            'max_mutations' => 1,
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->assertFalse($this->limit($limiter, '{ storeConfig { id } }'));
            $this->assertFalse($this->limit($limiter, 'mutation { createEmptyCart }'));
        }
    }

    public function testNonPositiveSettingsDisableLimiting(): void
    {
        $this->assertFalse($this->limit($this->createLimiter(['max_mutations' => 0]), 'mutation { createEmptyCart }'));
        $this->assertFalse($this->limit($this->createLimiter(['timeframe' => -1]), 'mutation { createEmptyCart }'));
    }

    public function testDisabledLimiter(): void
    {
        $limiter = $this->createLimiter(['enabled' => false, 'max_mutations' => 1]);

        $this->assertFalse($this->limit($limiter, 'mutation { createEmptyCart }'));
        $this->assertFalse($this->limit($limiter, 'mutation { createEmptyCart }'));
    }

    public function testFailedLockDeniesRequest(): void
    {
        $limiter = $this->createLimiter([], false);

        $this->assertDenied($limiter, 'mutation { createEmptyCart }');
    }

    public function testLockIsReleasedWhenDenied(): void
    {
        $lockManager = $this->createMock(LockManagerInterface::class);
        $lockManager->method('lock')->willReturn(true);
        $lockManager->expects($this->exactly(2))->method('unlock');
        $limiter = $this->createLimiter(['max_mutations' => 1], true, $lockManager);

        $this->limit($limiter, 'mutation { createEmptyCart }');
        $this->assertDenied($limiter, 'mutation { createEmptyCart }');
    }

    private function limit(Limiter $limiter, string $source): bool
    {
        return $limiter->limitDocument(Parser::parse($source));
    }

    private function assertDenied(Limiter $limiter, string $source): void
    {
        try {
            $this->limit($limiter, $source);
            $this->fail('Expected the rate limit error');
        } catch (Error $error) {
            $this->assertSame(Limiter::ERROR_MSG, $error->getMessage());
        }
    }

    private function createLimiter(
        array $settings,
        bool $lockResult = true,
        ?LockManagerInterface $lockManager = null
    ): Limiter {
        $settings += [
            'enabled' => true,
            'limit_queries' => true,
            'limit_mutations' => true,
            'max_queries' => 10,
            'max_mutations' => 10,
            'timeframe' => 60,
        ];

        $config = $this->createStub(Config::class);
        $config->method('enabled')->willReturn($settings['enabled']);
        $config->method('limitQueries')->willReturn($settings['limit_queries']);
        $config->method('limitMutations')->willReturn($settings['limit_mutations']);
        $config->method('getMaxQueries')->willReturn($settings['max_queries']);
        $config->method('getMaxMutations')->willReturn($settings['max_mutations']);
        $config->method('getTimeFrameInSeconds')->willReturn($settings['timeframe']);

        $identification = $this->createStub(Identification::class);
        $identification->method('getClientId')->willReturn('192.0.2.10');

        if ($lockManager === null) {
            $lockManager = $this->createStub(LockManagerInterface::class);
            $lockManager->method('lock')->willReturn($lockResult);
        }

        return new Limiter($config, new Counter($this->cache), new Information(), $identification, $lockManager);
    }
}
