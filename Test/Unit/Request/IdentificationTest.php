<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Unit\Request;

use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yireo\GraphQlRateLimiting\Config\Config;
use Yireo\GraphQlRateLimiting\Request\Identification;

class IdentificationTest extends TestCase
{
    #[DataProvider('provideAddresses')]
    public function testNormalizeAddress(string $address, string $expected): void
    {
        $this->assertSame($expected, $this->createIdentification($address, false)->normalizeAddress($address));
    }

    public static function provideAddresses(): array
    {
        return [
            'ipv4' => ['192.0.2.10', '192.0.2.10'],
            'ipv6' => ['2001:db8:1:2:3:4:5:6', '2001:db8:1:2::/64'],
            'ipv6 same network' => ['2001:db8:1:2:ffff::1', '2001:db8:1:2::/64'],
            'empty' => ['', 'unknown'],
            'invalid' => ['not-an-ip', 'unknown'],
        ];
    }

    public function testClientIdWithoutUserAgent(): void
    {
        $this->assertSame('192.0.2.10', $this->createIdentification('192.0.2.10', false)->getClientId());
    }

    public function testClientIdWithTruncatedUserAgent(): void
    {
        $clientId = $this->createIdentification('192.0.2.10', true, str_repeat('a', 1000))->getClientId();

        $this->assertSame('192.0.2.10|' . str_repeat('a', 255), $clientId);
    }

    public function testClientIdWithoutRemoteAddress(): void
    {
        $remoteAddress = $this->createStub(RemoteAddress::class);
        $remoteAddress->method('getRemoteAddress')->willReturn(false);
        $identification = new Identification(
            $this->createStub(Header::class),
            $remoteAddress,
            $this->createStub(Config::class)
        );

        $this->assertSame('unknown', $identification->getClientId());
    }

    private function createIdentification(string $address, bool $byUserAgent, string $userAgent = ''): Identification
    {
        $remoteAddress = $this->createStub(RemoteAddress::class);
        $remoteAddress->method('getRemoteAddress')->willReturn($address);
        $header = $this->createStub(Header::class);
        $header->method('getHttpUserAgent')->willReturn($userAgent);
        $config = $this->createStub(Config::class);
        $config->method('identifyByUserAgent')->willReturn($byUserAgent);

        return new Identification($header, $remoteAddress, $config);
    }
}
