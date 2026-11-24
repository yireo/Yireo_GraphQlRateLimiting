<?php declare(strict_types=1);

namespace Yireo\GraphQlRateLimiting\Rate;

use GraphQL\Error\Error;
use GraphQL\Language\AST\DocumentNode;
use Magento\Framework\Lock\LockManagerInterface;
use Yireo\GraphQlRateLimiting\Config\Config;
use Yireo\GraphQlRateLimiting\Request\Identification;
use Yireo\GraphQlRateLimiting\Request\Information;

class Limiter
{
    public const ERROR_MSG = 'Too many requests, please try again later';

    private const LOCK_TIMEOUT = 5;

    private bool $limitExceeded = false;

    private int $retryAfter = 0;

    /**
     * @param Config $config
     * @param Counter $counter
     * @param Information $requestInformation
     * @param Identification $requestIdentification
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        private readonly Config $config,
        private readonly Counter $counter,
        private readonly Information $requestInformation,
        private readonly Identification $requestIdentification,
        private readonly LockManagerInterface $lockManager
    ) {
    }

    /**
     * Count one hit per root field of the executed operation and deny the request when a maximum is exceeded
     *
     * @param DocumentNode $document
     * @param string|null $operationName
     * @return bool
     * @throws Error
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
            $this->deny($timeFrame);
        }

        try {
            $now = time();
            $buckets = [];
            foreach ($hits as $fieldName => $hitCount) {
                $bucket = sha1($operationType . '|' . $fieldName . '|' . $clientId);
                [$count, $expiresAt] = $this->counter->get($bucket);
                if ($count + $hitCount > $maxRequests) {
                    $this->deny($expiresAt > 0 ? $expiresAt - $now : $timeFrame);
                }

                $buckets[$bucket] = [$count + $hitCount, $expiresAt > 0 ? $expiresAt : $now + $timeFrame];
            }

            foreach ($buckets as $bucket => [$count, $expiresAt]) {
                $this->counter->save($bucket, $count, $expiresAt);
            }
        } finally {
            $this->lockManager->unlock($lockName);
        }

        return true;
    }

    /**
     * @return int
     */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }

    /**
     * @return void
     */
    public function resetLimitExceeded(): void
    {
        $this->limitExceeded = false;
        $this->retryAfter = 0;
    }

    /**
     * @return bool
     */
    public function consumeLimitExceeded(): bool
    {
        $limitExceeded = $this->limitExceeded;
        $this->resetLimitExceeded();

        return $limitExceeded;
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
     * @param int $retryAfter
     * @return void
     * @throws Error
     */
    private function deny(int $retryAfter): void
    {
        $this->limitExceeded = true;
        $this->retryAfter = max(1, $retryAfter);
        throw new Error((string)__('Too many requests, please try again later'));
    }
}
