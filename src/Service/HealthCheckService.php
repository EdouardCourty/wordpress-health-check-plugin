<?php

declare(strict_types=1);

namespace HealthCheck\Service;

use HealthCheck\Check\HealthCheckInterface;
use HealthCheck\Dto\HealthStatus;

final class HealthCheckService
{
    private const CACHE_KEY = 'health_check_result';

    /** @var HealthCheckInterface[] */
    private array $checks;

    private int $timeout;
    private int $cacheTtl;

    /** @param HealthCheckInterface[] $checks */
    public function __construct(
        array $checks,
        ?int $timeout = null,
        ?int $cacheTtl = null,
    ) {
        $this->checks = $checks;
        $this->timeout = $timeout ?? self::getDefaultTimeout();
        $this->cacheTtl = $cacheTtl ?? self::getDefaultCacheTtl();
    }

    /**
     * @return array{
     *     status: HealthStatus,
     *     checks: array<string, array{status: HealthStatus, error?: string}>
     * }
     */
    public function runAll(): array
    {
        if ($this->cacheTtl > 0) {
            $cached = get_transient(self::CACHE_KEY);

            if ($cached !== false) {
                /** @var array{status: HealthStatus, checks: array<string, array{status: HealthStatus, error?: string}>} $cached */
                return $cached;
            }
        }

        $result = $this->executeChecks();

        if ($this->cacheTtl > 0) {
            set_transient(self::CACHE_KEY, $result, $this->cacheTtl);
        }

        return $result;
    }

    public static function clearCache(): void
    {
        delete_transient(self::CACHE_KEY);
    }

    /**
     * @return array{
     *     status: HealthStatus,
     *     checks: array<string, array{status: HealthStatus, error?: string}>
     * }
     */
    private function executeChecks(): array
    {
        $globalStatus = HealthStatus::Ok;
        $checks = [];

        foreach ($this->checks as $check) {
            $result = $this->executeCheck($check);

            if ($result['status'] === HealthStatus::Ko) {
                $globalStatus = HealthStatus::Ko;
            }

            $checks[$check->getName()] = $result;
        }

        return [
            'status' => $globalStatus,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: HealthStatus, error?: string}
     */
    private function executeCheck(HealthCheckInterface $check): array
    {
        $startTime = microtime(true);

        try {
            $result = $check->check();
            $elapsed = microtime(true) - $startTime;

            if ($elapsed > $this->timeout) {
                return [
                    'status' => HealthStatus::Ko,
                    'error' => sprintf('Check timed out (%.1fs > %ds)', $elapsed, $this->timeout),
                ];
            }

            $checkData = ['status' => $result->success ? HealthStatus::Ok : HealthStatus::Ko];

            if (!$result->success) {
                $checkData['error'] = $result->error ?? 'Unknown error';
            }

            return $checkData;
        } catch (\Throwable $e) {
            return ['status' => HealthStatus::Ko, 'error' => $e->getMessage()];
        }
    }

    private static function getDefaultTimeout(): int
    {
        return defined('HEALTH_CHECK_TIMEOUT') ? (int) HEALTH_CHECK_TIMEOUT : 5;
    }

    private static function getDefaultCacheTtl(): int
    {
        return defined('HEALTH_CHECK_CACHE_TTL') ? (int) HEALTH_CHECK_CACHE_TTL : 300;
    }
}
