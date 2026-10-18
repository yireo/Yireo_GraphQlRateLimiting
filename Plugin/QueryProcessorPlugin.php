<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Plugin;

use GraphQL\Error\Error;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Printer;
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
     * @param DocumentNode|string $source
     * @param ContextInterface|null $contextValue
     * @param mixed[]|null $variableValues
     * @param string|null $operationName
     * @return mixed[]
     * @throws Error
     */
    public function beforeProcess(
        QueryProcessor $queryProcessor,
        Schema $schema,
        DocumentNode|string $source,
        ?ContextInterface $contextValue = null,
        ?array $variableValues = null,
        ?string $operationName = null
    ): array {
        $sourceString = $source instanceof DocumentNode ? Printer::doPrint($source) : $source;
        $this->rateLimiter->limitBySource($sourceString);
        return [$schema, $source, $contextValue, $variableValues, $operationName];
    }
}
