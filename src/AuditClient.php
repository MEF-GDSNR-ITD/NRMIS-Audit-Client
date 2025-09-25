<?php

namespace Nrmis\AuditClient;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class AuditClient
{
    protected Client $httpClient;

    protected string $baseUrl;

    protected string $serviceName;

    protected string $serviceVersion;

    protected string $environment;

    protected bool $async;

    protected bool $enabled;

    protected array $defaultMetadata;

    public function __construct(array $config = [])
    {
        $this->baseUrl = $config['base_url'] ?? config('audit-client.base_url', 'http://audit-service:7777/api/v1');
        $this->serviceName = $config['service_name'] ?? config('audit-client.service_name', config('app.name', 'unknown-service'));
        $this->serviceVersion = $config['service_version'] ?? config('audit-client.service_version', '1.0.0');
        $this->environment = $config['environment'] ?? config('audit-client.environment', config('app.env', 'production'));
        $this->async = $config['async'] ?? config('audit-client.async', true);
        $this->enabled = $config['enabled'] ?? config('audit-client.enabled', true);
        $this->defaultMetadata = array_merge(
            config('audit-client.default_metadata', []),
            $config['default_metadata'] ?? []
        );

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => $config['timeout'] ?? config('audit-client.timeout', 10),
            'connect_timeout' => $config['connect_timeout'] ?? config('audit-client.connect_timeout', 5),
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => "AuditClient/{$this->serviceName}/{$this->serviceVersion}",
            ],
        ]);
    }

    /**
     * Log an audit event
     */
    public function log(array $data): bool
    {
        if (! $this->enabled) {
            return true; // Silently skip if disabled
        }

        try {
            $auditData = $this->prepareAuditData($data);

            if ($this->async) {
                return $this->sendAsync($auditData);
            }

            return $this->sendSync($auditData);
        } catch (\Exception $e) {
            Log::error('Failed to send audit log', [
                'error' => $e->getMessage(),
                'data' => $data,
                'service' => $this->serviceName,
            ]);

            return false;
        }
    }

    /**
     * Log multiple audit events in batch
     */
    public function logBatch(array $audits): array
    {
        if (! $this->enabled) {
            return [
                'success' => true,
                'created' => count($audits),
                'errors' => 0,
                'details' => ['message' => 'Audit logging is disabled'],
            ];
        }

        $preparedAudits = [];

        foreach ($audits as $audit) {
            $preparedAudits[] = $this->prepareAuditData($audit);
        }

        try {
            $response = $this->httpClient->post('/audits/batch', [
                'json' => ['audits' => $preparedAudits],
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            return [
                'success' => $result['success'] ?? false,
                'created' => count($result['data']['created'] ?? []),
                'errors' => count($result['data']['errors'] ?? []),
                'details' => $result['data'] ?? [],
            ];
        } catch (GuzzleException $e) {
            Log::error('Failed to send batch audit logs', [
                'error' => $e->getMessage(),
                'count' => count($audits),
                'service' => $this->serviceName,
            ]);

            return [
                'success' => false,
                'created' => 0,
                'errors' => count($audits),
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Quick method to log user actions
     */
    public function logUserAction(
        string $action,
        int $userId,
        ?string $userType = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $metadata = [],
        string $severity = 'info'
    ): bool {
        return $this->log([
            'event' => $action,
            'action_type' => strtoupper($action),
            'user_id' => $userId,
            'user_type' => $userType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'severity' => $severity,
        ]);
    }

    /**
     * Log authentication events
     */
    public function logAuth(
        string $event,
        ?int $userId = null,
        ?string $userType = null,
        bool $success = true,
        array $metadata = []
    ): bool {
        return $this->log([
            'event' => $event,
            'action_type' => 'AUTH',
            'user_id' => $userId,
            'user_type' => $userType,
            'severity' => $success ? 'info' : 'warning',
            'metadata' => array_merge($metadata, [
                'success' => $success,
                'auth_event' => $event,
            ]),
        ]);
    }

    /**
     * Log system events
     */
    public function logSystem(
        string $event,
        string $severity = 'info',
        array $metadata = []
    ): bool {
        return $this->log([
            'event' => $event,
            'action_type' => 'SYSTEM',
            'severity' => $severity,
            'metadata' => array_merge($metadata, [
                'system_event' => $event,
            ]),
        ]);
    }

    /**
     * Log security events
     */
    public function logSecurity(
        string $event,
        ?int $userId = null,
        ?string $threat = null,
        array $metadata = []
    ): bool {
        return $this->log([
            'event' => $event,
            'action_type' => 'SECURITY',
            'user_id' => $userId,
            'severity' => 'warning',
            'metadata' => array_merge($metadata, [
                'security_event' => $event,
                'threat_type' => $threat,
            ]),
        ]);
    }

    /**
     * Log performance events
     */
    public function logPerformance(
        string $operation,
        float $duration,
        array $metadata = []
    ): bool {
        $severity = 'info';
        if ($duration > 5.0) {
            $severity = 'warning';
        } elseif ($duration > 10.0) {
            $severity = 'error';
        }

        return $this->log([
            'event' => "performance_{$operation}",
            'action_type' => 'PERFORMANCE',
            'severity' => $severity,
            'metadata' => array_merge($metadata, [
                'operation' => $operation,
                'duration_seconds' => $duration,
                'performance_event' => true,
            ]),
        ]);
    }

    /**
     * Set correlation ID for tracking related events
     */
    public function withCorrelation(string $correlationId): self
    {
        $clone = clone $this;
        $clone->defaultMetadata['correlation_id'] = $correlationId;

        return $clone;
    }

    /**
     * Set request ID for tracking events within a request
     */
    public function withRequest(string $requestId): self
    {
        $clone = clone $this;
        $clone->defaultMetadata['request_id'] = $requestId;

        return $clone;
    }

    /**
     * Set session ID for tracking user session events
     */
    public function withSession(string $sessionId): self
    {
        $clone = clone $this;
        $clone->defaultMetadata['session_id'] = $sessionId;

        return $clone;
    }

    /**
     * Enable/disable audit logging
     */
    public function enable(bool $enabled = true): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Check if audit logging is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get service configuration
     */
    public function getConfig(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'service_name' => $this->serviceName,
            'service_version' => $this->serviceVersion,
            'environment' => $this->environment,
            'async' => $this->async,
            'enabled' => $this->enabled,
            'default_metadata' => $this->defaultMetadata,
        ];
    }

    /**
     * Prepare audit data with default values
     */
    protected function prepareAuditData(array $data): array
    {
        // Get request context if available
        $request = request();

        return array_merge([
            'service_name' => $this->serviceName,
            'service_version' => $this->serviceVersion,
            'environment' => $this->environment,
            'event_timestamp' => Carbon::now()->toISOString(),
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
            'url' => $request ? $request->fullUrl() : null,
            'severity' => 'info',
        ], $this->defaultMetadata, $data);
    }

    /**
     * Send audit data synchronously
     */
    protected function sendSync(array $data): bool
    {
        try {
            $response = $this->httpClient->post('/audits', [
                'json' => $data,
            ]);

            return $response->getStatusCode() === 201;
        } catch (GuzzleException $e) {
            Log::error('Sync audit send failed', [
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);

            return false;
        }
    }

    /**
     * Send audit data asynchronously (fire and forget)
     */
    protected function sendAsync(array $data): bool
    {
        try {
            // For now, we'll use a simple async approach
            // In production, you might want to use Laravel Horizon or a proper queue
            $promise = $this->httpClient->postAsync('/audits', [
                'json' => $data,
            ]);

            // Don't wait for the response in async mode
            return true;
        } catch (\Exception $e) {
            Log::error('Async audit send failed', [
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);

            return false;
        }
    }

    /**
     * Health check for the audit service
     */
    public function healthCheck(): array
    {
        try {
            $response = $this->httpClient->get('/health');
            $data = json_decode($response->getBody()->getContents(), true);

            return [
                'healthy' => $response->getStatusCode() === 200,
                'service' => $data['service'] ?? 'unknown',
                'timestamp' => $data['timestamp'] ?? null,
                'version' => $data['version'] ?? null,
            ];
        } catch (GuzzleException $e) {
            return [
                'healthy' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
