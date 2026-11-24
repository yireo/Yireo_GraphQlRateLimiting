<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Plugin;

use GraphQL\Error\Error;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\Parser;
use Magento\Framework\GraphQl\Query\QueryProcessor;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema;
use Yireo\GraphQlRateLimiting\Rate\Limiter;

class QueryProcessorPlugin
{
    /**
     * @param Limiter $limiter
     */
    public function __construct(
        private readonly Limiter $limiter
    ) {
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
        $document = $source instanceof DocumentNode ? $source : Parser::parse($source);
        $this->limiter->limitDocument($document, $operationName);

        return [$schema, $source, $contextValue, $variableValues, $operationName];
    }
}
