<?php

declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Test\Functional;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\GraphQl\Controller\GraphQl;
use Magento\Framework\App\Request\Http as Request;
use Yireo\GraphQlRateLimiting\Cache\Adapter;
use Zend\Http\Headers;
use Zend\Json\Json;

/**
 * Class CacheTypeTest
 * @package Yireo\GraphQlRateLimiting\Test\Functional
 */
class BlockQueriesTest extends AbstractTestCase
{
    /**
     * Test whether a mutation that fires repeatedly fails after some requests
     */
    public function testExpectMutationToBeBlockedAfterSomeRequests()
    {
        $this->setConfig('enabled', 1);
        $this->setConfig('limit_mutations', 1);
        $this->setConfig('max_mutations', 4);
        $this->setConfig('timeframe', 20);

        /** @var Adapter $cacheAdapter */
        $cacheAdapter = $this->getObjectManager()->create(Adapter::class);
        $cacheAdapter->clear();

        for ($i = 0; $i <= 4; $i++) {
            /** @var GraphQl $graphQlController */
            $graphQlController = $this->getObjectManager()->create(GraphQl::class);
            $request = $this->getMutationRequest();
            $response = $graphQlController->dispatch($request);

            $body = (string)$response->getBody();

            if ($i === 4) {
                $this->assertContains('A maximum of 4 mutations has been reached', $body, $body);
            } else {
                $this->assertContains('createEmptyCart', $body, $body);
            }
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    private function setConfig(string $name, $value)
    {
        /** @var WriterInterface $configWriter */
        $configWriter = $this->getObjectManager()->get(WriterInterface::class);
        $configWriter->save('graphql_rate_limiting/settings/' . $name, $value);
    }

    /**
     * @return Request
     */
    private function getMutationRequest(): Request
    {
        /** @var Request $request */
        $request = $this->getObjectManager()->create(Request::class);
        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json');
        $request->setMethod(Request::METHOD_POST);
        $request->setHeaders($headers);

        $graphQlRequest = 'mutation { createEmptyCart }';
        $request->setContent(Json::encode(['query' => $graphQlRequest, 'variables' => []]));
        return $request;
    }
}
