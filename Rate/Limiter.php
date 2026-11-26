<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Rate;

use GraphQL\Error\Error;
use GraphQL\Language\AST\DocumentNode;
use Magento\Framework\Lock\LockManagerInterface;
use Yireo\GraphQlRateLimiting\Config\Config;
use Yireo\GraphQlRateLimiting\Exception\RequestParsingException;
use Yireo\GraphQlRateLimiting\Request\Identification;
use Yireo\GraphQlRateLimiting\Request\Information;

class Limiter
{
    public const ERROR_MSG = 'Too many requests, please try again later';

    private const LOCK_TIMEOUT = 5;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Counter
     */
    private $counter;

    /**
     * @var Information
     */
    private $requestInformation;

    /**
     * @var Identification
     */
    private $requestIdentification;

    /**
     * @var LockManagerInterface
     */
    private $lockManager;

    /**
     * @param Config $config
     * @param Counter $counter
     * @param Information $requestInformation
     * @param Identification $requestIdentification
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        Config $config,
        Counter $counter,
        Information $requestInformation,
        Identification $requestIdentification,
        LockManagerInterface $lockManager
    ) {
        $this->config = $config;
        $this->counter = $counter;
        $this->requestInformation = $requestInformation;
        $this->requestIdentification = $requestIdentification;
        $this->lockManager = $lockManager;
    }

    /**
     * @param DocumentNode $document
     * @param string|null $operationName
     * @return bool
     * @throws Error
     * @throws RequestParsingException
     */
    public function limitDocument(DocumentNode $document, ?string $operationName = null): bool
    {
        if (!$this->config->enabled()) {
            return false;
        }

        $operationType = $this->requestInformation->getOperation($document, $operationName)->operation;
        $maxRequests = $this->getMaxRequests($operationType);
        $timeFrame = $this->config->getTimeFrameInSeconds();
        if ($maxRequests <= 0 || $timeFrame <= 0) {
            return false;
        }

        $hits = array_count_values($this->requestInformation->getRootFieldNames($document, $operationName));
        $clientId = $this->requestIdentification->getClientId();
        $lockName = 'graphql_rate_limiting_' . sha1($clientId);
        if (!$this->lockManager->lock($lockName, self::LOCK_TIMEOUT)) {
            $this->deny();
        }

        try {
            $now = time();
            $buckets = [];
            foreach ($hits as $fieldName => $hitCount) {
                $bucket = sha1($operationType . '|' . $fieldName . '|' . $clientId);
                [$count, $expiresAt] = $this->counter->get($bucket);
                if ($count + $hitCount > $maxRequests) {
                    $this->deny();
                }

                $buckets[$bucket] = [$count + $hitCount, $expiresAt > 0 ? $expiresAt : $now + $timeFrame];
            }

            foreach ($buckets as $bucket => [$count, $expiresAt]) {
                $this->counter->save((string)$bucket, $count, $expiresAt);
            }
        } finally {
            $this->lockManager->unlock($lockName);
        }

        return true;
    }

    /**
     * @param string $operationType
     * @return int
     */
    private function getMaxRequests(string $operationType): int
    {
        if ($operationType === 'mutation') {
            return $this->config->limitMutations() ? $this->config->getMaxMutations() : 0;
        }

        return $this->config->limitQueries() ? $this->config->getMaxQueries() : 0;
    }

    /**
     * @return void
     * @throws Error
     */
    private function deny(): void
    {
        throw new Error((string)__(self::ERROR_MSG));
    }
}
