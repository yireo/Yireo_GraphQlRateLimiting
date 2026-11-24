<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Request;

use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Yireo\GraphQlRateLimiting\Config\Config;

/**
 * Identify the unique visitor of the current GraphQL request
 */
class Identification
{
    private const MAX_USER_AGENT_LENGTH = 255;

    /**
     * @param Header $httpHeader
     * @param RemoteAddress $remoteAddress
     * @param Config $config
     */
    public function __construct(
        private readonly Header $httpHeader,
        private readonly RemoteAddress $remoteAddress,
        private readonly Config $config
    ) {
    }

    /**
     * @return string
     */
    public function getClientId(): string
    {
        $parts = [$this->normalizeAddress((string)$this->remoteAddress->getRemoteAddress())];

        if ($this->config->identifyByUserAgent()) {
            $parts[] = mb_substr((string)$this->httpHeader->getHttpUserAgent(), 0, self::MAX_USER_AGENT_LENGTH);
        }

        return implode('|', $parts);
    }

    /**
     * Group IPv6 addresses by their /64 network, because a single client often controls a whole /64
     *
     * @param string $address
     * @return string
     */
    public function normalizeAddress(string $address): string
    {
        $binary = filter_var($address, FILTER_VALIDATE_IP) ? inet_pton($address) : false;
        if ($binary === false) {
            return 'unknown';
        }

        if (strlen($binary) !== 16) {
            return (string)inet_ntop($binary);
        }

        return inet_ntop(substr($binary, 0, 8) . str_repeat("\0", 8)) . '/64';
    }
}
