<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Functional;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Exception;

class AbstractTestCase extends TestCase
{
    /**
     * @return ObjectManagerInterface
     */
    protected function getObjectManager(): ObjectManagerInterface
    {
        if (!defined('BP')) {
            throw new Exception('Constant BP is not defined. Are you calling this test with the right bootstrap?');
        }

        $bootstrap = Bootstrap::create(BP, $_SERVER);
        return $bootstrap->getObjectManager();
    }
}
