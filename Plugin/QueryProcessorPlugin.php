<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Plugin;

use GraphQL\Error\Error;
use Yireo\GraphQlRateLimiting\Rate\Limiter as RateLimiter;
use Magento\Framework\GraphQl\Query\QueryProcessor;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema;

class QueryProcessorPlugin
{
    /**
     * @var RateLimiter
     */
    private $rateLimiter;

    /**
     * QueryComplexityLimiterPlugin constructor.
     * @param RateLimiter $rateLimiter
     */
    public function __construct(
        RateLimiter $rateLimiter
    ) {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * @param QueryProcessor $queryProcessor
     * @param Schema $schema
     * @param string $source
     * @param ContextInterface|null $contextValue
     * @param array|null $variableValues
     * @param string|null $operationName
     * @return array
     * @throws Error
     */
    public function beforeProcess(
        QueryProcessor $queryProcessor,
        Schema $schema,
        string $source,
        ContextInterface $contextValue = null,
        array $variableValues = null,
        string $operationName = null
    ) {
        $this->rateLimiter->limitBySource($source);
        return [$schema, $source, $contextValue, $variableValues, $operationName];
    }
}
