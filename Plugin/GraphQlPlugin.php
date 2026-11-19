<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Plugin;

use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\GraphQl\Controller\GraphQl;
use Yireo\GraphQlRateLimiting\Config\Config;
use Yireo\GraphQlRateLimiting\Rate\Limiter;

class GraphQlPlugin
{
    public function __construct(
        private Limiter $limiter,
        private Config $config
    ) {
    }

    public function beforeDispatch(GraphQl $subject): void
    {
        $this->limiter->resetLimitExceeded();
    }

    public function afterDispatch(GraphQl $subject, ResponseInterface $response): ResponseInterface
    {
        $limitExceeded = $this->limiter->consumeLimitExceeded();
        if ($limitExceeded && $response instanceof Http) {
            $retryAfter = $this->config->getTimeFrameInSeconds();
            if ($retryAfter <= 0) {
                $retryAfter = $this->config->getCacheTtlInSeconds();
            }

            $response->setHttpResponseCode(429);
            $response->setHeader('Retry-After', (string)$retryAfter, true);
            $response->setNoCacheHeaders();
        }

        return $response;
    }
}
