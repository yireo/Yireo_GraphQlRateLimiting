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
     * @var Limiter
     */
    private $limiter;

    /**
     * @param Limiter $limiter
     */
    public function __construct(
        Limiter $limiter
    ) {
        $this->limiter = $limiter;
    }

    /**
     * @param QueryProcessor $queryProcessor
     * @param Schema $schema
     * @param DocumentNode|string $source
     * @param ContextInterface|null $contextValue
     * @param array|null $variableValues
     * @param string|null $operationName
     * @return array
     * @throws Error
     */
    public function beforeProcess(
        QueryProcessor $queryProcessor,
        Schema $schema,
        $source,
        ?ContextInterface $contextValue = null,
        ?array $variableValues = null,
        ?string $operationName = null
    ): array {
        $document = $source instanceof DocumentNode ? $source : Parser::parse((string)$source);
        $this->limiter->limitDocument($document, $operationName);

        return [$schema, $source, $contextValue, $variableValues, $operationName];
    }
}
