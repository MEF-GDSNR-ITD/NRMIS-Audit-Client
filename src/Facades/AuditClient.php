<?php

namespace Nrmis\AuditClient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool log(array $data)
 * @method static array logBatch(array $audits)
 * @method static bool logUserAction(string $action, int $userId, string $userType = null, array $oldValues = null, array $newValues = null, array $metadata = [], string $severity = 'info')
 * @method static bool logAuth(string $event, int $userId = null, string $userType = null, bool $success = true, array $metadata = [])
 * @method static bool logSystem(string $event, string $severity = 'info', array $metadata = [])
 * @method static bool logSecurity(string $event, int $userId = null, string $threat = null, array $metadata = [])
 * @method static bool logPerformance(string $operation, float $duration, array $metadata = [])
 * @method static \Nrmis\AuditClient\AuditClient withCorrelation(string $correlationId)
 * @method static \Nrmis\AuditClient\AuditClient withRequest(string $requestId)
 * @method static \Nrmis\AuditClient\AuditClient withSession(string $sessionId)
 * @method static \Nrmis\AuditClient\AuditClient enable(bool $enabled = true)
 * @method static bool isEnabled()
 * @method static array getConfig()
 * @method static array healthCheck()
 *
 * @see \Nrmis\AuditClient\AuditClient
 */
class AuditClient extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Nrmis\AuditClient\AuditClient::class;
    }
}
