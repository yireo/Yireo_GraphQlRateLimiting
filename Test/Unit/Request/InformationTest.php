<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Unit\Request;

use GraphQL\Language\Parser;
use PHPUnit\Framework\TestCase;
use Yireo\GraphQlRateLimiting\Exception\RequestParsingException;
use Yireo\GraphQlRateLimiting\Request\Information;

class InformationTest extends TestCase
{
    /**
     * @dataProvider provideDocuments
     */
    public function testOperationTypeAndRootFields(
        string $source,
        ?string $operationName,
        string $expectedType,
        array $expectedFields
    ): void {
        $information = new Information();
        $document = Parser::parse($source);

        $this->assertSame($expectedType, $information->getOperation($document, $operationName)->operation);
        $this->assertSame($expectedFields, $information->getRootFieldNames($document, $operationName));
    }

    public static function provideDocuments(): array
    {
        return [
            'query' => ['query { helloWorld { hello } }', null, 'query', ['helloWorld']],
            'shorthand query' => ['{ helloWorld { hello } }', null, 'query', ['helloWorld']],
            'mutation' => ['mutation { createEmptyCart }', null, 'mutation', ['createEmptyCart']],
            'named fragment spread' => [
                'mutation { ...Random123 } fragment Random123 on Mutation { generateCustomerToken { token } }',
                null,
                'mutation',
                ['generateCustomerToken'],
            ],
            'fragment defined before mutation' => [
                'fragment F on Mutation { generateCustomerToken { token } } mutation { ...F }',
                null,
                'mutation',
                ['generateCustomerToken'],
            ],
            'aliases' => [
                'mutation { a: generateCustomerToken { token } b: generateCustomerToken { token } createEmptyCart }',
                null,
                'mutation',
                ['generateCustomerToken', 'generateCustomerToken', 'createEmptyCart'],
            ],
            'inline fragment' => [
                'mutation { ... on Mutation { a: createEmptyCart b: createEmptyCart } }',
                null,
                'mutation',
                ['createEmptyCart', 'createEmptyCart'],
            ],
            'same response key is merged' => [
                'query { products { total_count } products { total_count } }',
                null,
                'query',
                ['products'],
            ],
            'operation selected by name' => [
                'query Q { products { total_count } } mutation M { createEmptyCart }',
                'M',
                'mutation',
                ['createEmptyCart'],
            ],
            'cyclic fragments' => [
                'query { ...A } fragment A on Query { ...B storeConfig { id } } fragment B on Query { ...A }',
                null,
                'query',
                ['storeConfig'],
            ],
        ];
    }

    public function testOperationTypeFromSource(): void
    {
        $information = new Information();

        $this->assertTrue($information->isQueryString('{ helloWorld { hello } }'));
        $this->assertFalse($information->isMutationString('{ helloWorld { hello } }'));
        $this->assertTrue($information->isMutationString('fragment F on Mutation { a } mutation { ...F }'));
        $this->assertFalse($information->isQueryString('fragment F on Mutation { a } mutation { ...F }'));
    }

    /**
     * @dataProvider provideInvalidDocuments
     */
    public function testUnresolvableDocumentThrowsException(string $source, ?string $operationName): void
    {
        $this->expectException(RequestParsingException::class);
        (new Information())->getRootFieldNames(Parser::parse($source), $operationName);
    }

    public static function provideInvalidDocuments(): array
    {
        return [
            'multiple operations without name' => ['query Q { a } mutation M { b }', null],
            'unknown operation name' => ['query Q { a }', 'Other'],
            'only fragments' => ['fragment F on Query { a }', null],
            'unknown fragment' => ['query { ...Missing }', null],
        ];
    }
}
