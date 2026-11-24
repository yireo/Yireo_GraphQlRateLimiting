<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Plugin;

use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\GraphQl\Controller\GraphQl;
use Yireo\GraphQlRateLimiting\Rate\Limiter;

class GraphQlPlugin
{
    /**
     * @param Limiter $limiter
     */
    public function __construct(
        private readonly Limiter $limiter
    ) {
    }

    /**
     * @param GraphQl $subject
     * @return void
     */
    public function beforeDispatch(GraphQl $subject): void
    {
        $this->limiter->resetLimitExceeded();
    }

    /**
     * @param GraphQl $subject
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function afterDispatch(GraphQl $subject, ResponseInterface $response): ResponseInterface
    {
        $retryAfter = $this->limiter->getRetryAfter();
        if ($this->limiter->consumeLimitExceeded() && $response instanceof Http) {
            $response->setHttpResponseCode(429);
            $response->setHeader('Retry-After', (string)$retryAfter, true);
            $response->setNoCacheHeaders();
        }

        return $response;
    }
}
