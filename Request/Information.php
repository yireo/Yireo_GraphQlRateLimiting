<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Request;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\FieldNode;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\AST\FragmentSpreadNode;
use GraphQL\Language\AST\InlineFragmentNode;
use GraphQL\Language\AST\OperationDefinitionNode;
use GraphQL\Language\AST\SelectionSetNode;
use GraphQL\Language\Parser;
use Yireo\GraphQlRateLimiting\Exception\RequestParsingException;

class Information
{
    /**
     * @param DocumentNode $document
     * @param string|null $operationName
     * @return OperationDefinitionNode
     * @throws RequestParsingException
     */
    public function getOperation(DocumentNode $document, ?string $operationName = null): OperationDefinitionNode
    {
        $operations = [];
        foreach ($document->definitions as $definition) {
            if ($definition instanceof OperationDefinitionNode) {
                $operations[] = $definition;
            }
        }

        if ($operationName === null || $operationName === '') {
            if (count($operations) !== 1) {
                throw new RequestParsingException('Unable to determine the GraphQL operation');
            }

            return $operations[0];
        }

        foreach ($operations as $operation) {
            if ($operation->name !== null && $operation->name->value === $operationName) {
                return $operation;
            }
        }

        throw new RequestParsingException('Unable to determine the GraphQL operation');
    }

    /**
     * @param DocumentNode $document
     * @param string|null $operationName
     * @return string[]
     * @throws RequestParsingException
     */
    public function getRootFieldNames(DocumentNode $document, ?string $operationName = null): array
    {
        $fragments = [];
        foreach ($document->definitions as $definition) {
            if ($definition instanceof FragmentDefinitionNode) {
                $fragments[$definition->name->value] = $definition;
            }
        }

        $operation = $this->getOperation($document, $operationName);
        $fieldNames = [];
        $visitedFragments = [];
        $this->collectRootFields($operation->selectionSet, $fragments, $visitedFragments, $fieldNames);

        if (empty($fieldNames)) {
            throw new RequestParsingException('Unable to determine the GraphQL fields');
        }

        return array_values($fieldNames);
    }

    /**
     * @param string $source
     * @return string
     * @throws RequestParsingException
     */
    public function getOperationFromSource(string $source): string
    {
        return $this->getOperation(Parser::parse($source))->operation;
    }

    /**
     * @param string $source
     * @return bool
     * @throws RequestParsingException
     */
    public function isMutationString(string $source): bool
    {
        return $this->getOperationFromSource($source) === 'mutation';
    }

    /**
     * @param string $source
     * @return bool
     * @throws RequestParsingException
     */
    public function isQueryString(string $source): bool
    {
        return $this->getOperationFromSource($source) === 'query';
    }

    /**
     * @param SelectionSetNode $selectionSet
     * @param FragmentDefinitionNode[] $fragments
     * @param bool[] $visitedFragments
     * @param string[] $fieldNames
     * @return void
     * @throws RequestParsingException
     */
    private function collectRootFields(
        SelectionSetNode $selectionSet,
        array $fragments,
        array &$visitedFragments,
        array &$fieldNames
    ): void {
        foreach ($selectionSet->selections as $selection) {
            if ($selection instanceof FieldNode) {
                $responseKey = $selection->alias !== null ? $selection->alias->value : $selection->name->value;
                $fieldNames[$responseKey] = $selection->name->value;
                continue;
            }

            if ($selection instanceof InlineFragmentNode) {
                $this->collectRootFields($selection->selectionSet, $fragments, $visitedFragments, $fieldNames);
                continue;
            }

            if ($selection instanceof FragmentSpreadNode) {
                $fragmentName = $selection->name->value;
                if (!isset($fragments[$fragmentName])) {
                    throw new RequestParsingException('Unknown GraphQL fragment');
                }

                if (isset($visitedFragments[$fragmentName])) {
                    continue;
                }

                $visitedFragments[$fragmentName] = true;
                $this->collectRootFields(
                    $fragments[$fragmentName]->selectionSet,
                    $fragments,
                    $visitedFragments,
                    $fieldNames
                );
            }
        }
    }
}
