<?php
declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Request;

use GraphQL\Error\SyntaxError;
use GraphQL\Language\Parser;

/**
 * Class Information
 * @package Yireo\GraphQlRateLimiting\Request
 */
class Information
{
    /**
     * @param string $source
     * @return string
     * @throws SyntaxError
     */
    public function getOperationFromSource(string $source)
    {
        $sourceDocument = Parser::parse($source)->toArray(true);
        if (isset($sourceDocument['definitions'][0]['operation'])) {
            return (string)$sourceDocument['definitions'][0]['operation'];
        }

        return 'query';
    }

    /**
     * @param string $source
     * @return bool
     * @throws SyntaxError
     */
    public function isMutationString(string $source): bool
    {
        return ($this->getOperationFromSource($source) === 'mutation');
    }

    /**
     * @param string $source
     * @return bool
     * @throws SyntaxError
     */
    public function isQueryString(string $source): bool
    {
        return ($this->getOperationFromSource($source) === 'query');
    }
}
