<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Request;

use GraphQL\Error\SyntaxError;
use Magento\Framework\HTTP\Header;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Yireo\GraphQlRateLimiting\Config\Config;
use Yireo\GraphQlRateLimiting\Exception\RequestParsingException;

/**
 * Class Identification to identify a specific combination of unique visitor and GraphQL request
 */
class Identification
{
    /**
     * @var Information
     */
    private $information;

    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * @var RemoteAddress
     */
    private $remoteIp;
    /**
     * @var Config
     */
    private $config;

    /**
     * Identification constructor.
     * @param Information $information
     * @param Header $httpHeader
     * @param RemoteAddress $remoteIp
     * @param Config $config
     */
    public function __construct(
        Information $information,
        Header $httpHeader,
        RemoteAddress $remoteIp,
        Config $config
    ) {
        $this->information = $information;
        $this->httpHeader = $httpHeader;
        $this->remoteIp = $remoteIp;
        $this->config = $config;
    }

    /**
     * @param string $source
     * @return string
     * @throws SyntaxError
     * @throws RequestParsingException
     */
    public function getUniqIdFromRequestAndSource(string $source): string
    {
        $parts = [];
        $parts[] = $this->information->getOperationFromSource($source);
        $parts[] = $this->information->getEndpointFromSource($source);
        $parts[] = $this->remoteIp->getRemoteAddress();

        if ($this->config->identifyByUserAgent()) {
            $parts[] = $this->httpHeader->getHttpUserAgent();
        }

        return implode('/', $parts);
    }
}
