<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Unit\Request;

use GraphQL\Error\SyntaxError;
use PHPUnit\Framework\TestCase;
use Yireo\GraphQlRateLimiting\Request\Information;

class InformationTest extends TestCase
{
    /**
     * @throws SyntaxError
     */
    public function testGetEndpointFromSource()
    {
        $requestInformation = new Information();
        foreach ($this->getValidQueries() as $query) {
            $endpoint = $requestInformation->getEndpointFromSource($query);
            $this->assertEquals('helloWorld', $endpoint);
        }
    }

    /**
     * @throws SyntaxError
     */
    public function testGetOperationFromSource()
    {
        $requestInformation = new Information();
        foreach ($this->getValidQueries() as $query) {
            $operation = $requestInformation->getOperationFromSource($query);
            $this->assertEquals('query', $operation);
        }

        foreach ($this->getValidMutations() as $query) {
            $operation = $requestInformation->getOperationFromSource($query);
            $this->assertEquals('mutation', $operation);
        }
    }

    /**
     * @throws SyntaxError
     */
    public function testIsQueryString()
    {
        $requestInformation = new Information();
        foreach ($this->getValidQueries() as $query) {
            $this->assertTrue($requestInformation->isQueryString($query));
            $this->assertFalse($requestInformation->isMutationString($query));
        }
    }

    /**
     * @throws SyntaxError
     */
    public function testIsMutationString()
    {
        $requestInformation = new Information();
        foreach ($this->getValidMutations() as $query) {
            $this->assertTrue($requestInformation->isMutationString($query));
            $this->assertFalse($requestInformation->isQueryString($query));
        }
    }

    /**
     * @return string[]
     */
    public function getValidQueries(): array
    {
        return [
            'query1' => <<<EOT
query { helloWorld { hello } }
EOT,
            'query2' => <<<EOT
query { 
    helloWorld(name: "World") { 
        hello 
    } 
}
EOT,
            'query3' => <<<EOT
{ helloWorld { hello } }
EOT,
        ];
    }

    /**
     * @return string[]
     */
    public function getValidMutations(): array
    {
        return [
            'query1' => <<<EOT
mutation { helloWorld { hello } }
EOT,
            'query2' => <<<EOT
mutation { 
    helloWorld(name: "World") { 
        hello 
    } 
}
EOT
        ];
    }
}
