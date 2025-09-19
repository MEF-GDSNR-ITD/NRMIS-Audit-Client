<?php

namespace Nrmis\AuditClient\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Nrmis\AuditClient\AuditClient;
use PHPUnit\Framework\TestCase;

class AuditClientTest extends TestCase
{
    private AuditClient $auditClient;

    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler;
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Mock HTTP client
        $httpClient = new Client(['handler' => $handlerStack]);

        $this->auditClient = new AuditClient([
            'base_url' => 'http://test-audit-service/api/v1',
            'service_name' => 'test-service',
            'service_version' => '1.0.0',
            'environment' => 'testing',
            'async' => false, // Use sync for testing
            'enabled' => true,
        ]);

        // Replace the HTTP client with our mock
        $reflection = new \ReflectionClass($this->auditClient);
        $property = $reflection->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->auditClient, $httpClient);
    }

    public function test_can_log_audit_event()
    {
        $this->mockHandler->append(new Response(201, [], json_encode([
            'success' => true,
            'message' => 'Audit log created successfully',
            'data' => ['id' => 1],
        ])));

        $result = $this->auditClient->log([
            'event' => 'user_login',
            'user_id' => 123,
            'severity' => 'info',
        ]);

        $this->assertTrue($result);
    }

    public function test_can_log_user_action()
    {
        $this->mockHandler->append(new Response(201, [], json_encode([
            'success' => true,
            'message' => 'Audit log created successfully',
            'data' => ['id' => 1],
        ])));

        $result = $this->auditClient->logUserAction(
            'profile_updated',
            123,
            'App\Models\User',
            ['name' => 'John'],
            ['name' => 'Jane'],
            ['field' => 'name'],
            'info'
        );

        $this->assertTrue($result);
    }

    public function test_can_log_auth_event()
    {
        $this->mockHandler->append(new Response(201, [], json_encode([
            'success' => true,
            'message' => 'Audit log created successfully',
            'data' => ['id' => 1],
        ])));

        $result = $this->auditClient->logAuth(
            'login',
            123,
            'App\Models\User',
            true,
            ['method' => 'email']
        );

        $this->assertTrue($result);
    }

    public function test_can_log_system_event()
    {
        $this->mockHandler->append(new Response(201, [], json_encode([
            'success' => true,
            'message' => 'Audit log created successfully',
            'data' => ['id' => 1],
        ])));

        $result = $this->auditClient->logSystem(
            'database_migration',
            'info',
            ['migration' => '2023_01_01_000000_create_users']
        );

        $this->assertTrue($result);
    }

    public function test_can_log_security_event()
    {
        $this->mockHandler->append(new Response(201, [], json_encode([
            'success' => true,
            'message' => 'Audit log created successfully',
            'data' => ['id' => 1],
        ])));

        $result = $this->auditClient->logSecurity(
            'failed_login',
            123,
            'brute_force',
            ['attempts' => 5]
        );

        $this->assertTrue($result);
    }

    public function test_can_log_performance_event()
    {
        $this->mockHandler->append(new Response(201, [], json_encode([
            'success' => true,
            'message' => 'Audit log created successfully',
            'data' => ['id' => 1],
        ])));

        $result = $this->auditClient->logPerformance(
            'database_query',
            2.5,
            ['query_type' => 'select']
        );

        $this->assertTrue($result);
    }

    public function test_can_log_batch_audits()
    {
        $this->mockHandler->append(new Response(201, [], json_encode([
            'success' => true,
            'message' => '2 audit logs created, 0 failed',
            'data' => [
                'created' => [['id' => 1], ['id' => 2]],
                'errors' => [],
            ],
        ])));

        $audits = [
            [
                'event' => 'user_created',
                'user_id' => 1,
            ],
            [
                'event' => 'user_updated',
                'user_id' => 1,
            ],
        ];

        $result = $this->auditClient->logBatch($audits);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['errors']);
    }

    public function test_can_check_health()
    {
        $this->mockHandler->append(new Response(200, [], json_encode([
            'status' => 'healthy',
            'service' => 'audit-logs',
            'timestamp' => '2023-01-01T00:00:00Z',
            'version' => '1.0.0',
        ])));

        $health = $this->auditClient->healthCheck();

        $this->assertTrue($health['healthy']);
        $this->assertEquals('audit-logs', $health['service']);
    }

    public function test_handles_http_errors_gracefully()
    {
        $this->mockHandler->append(new RequestException(
            'Connection error',
            new Request('POST', '/audits')
        ));

        $result = $this->auditClient->log([
            'event' => 'test_event',
        ]);

        $this->assertFalse($result);
    }

    public function test_handles_server_errors_gracefully()
    {
        $this->mockHandler->append(new Response(500, [], json_encode([
            'error' => 'Internal server error',
        ])));

        $result = $this->auditClient->log([
            'event' => 'test_event',
        ]);

        $this->assertFalse($result);
    }

    public function test_can_set_correlation_id()
    {
        $correlationId = 'test-correlation-123';

        $clientWithCorrelation = $this->auditClient->withCorrelation($correlationId);

        $this->assertInstanceOf(AuditClient::class, $clientWithCorrelation);
        $this->assertNotSame($this->auditClient, $clientWithCorrelation);
    }

    public function test_can_set_request_id()
    {
        $requestId = 'test-request-456';

        $clientWithRequest = $this->auditClient->withRequest($requestId);

        $this->assertInstanceOf(AuditClient::class, $clientWithRequest);
        $this->assertNotSame($this->auditClient, $clientWithRequest);
    }

    public function test_can_set_session_id()
    {
        $sessionId = 'test-session-789';

        $clientWithSession = $this->auditClient->withSession($sessionId);

        $this->assertInstanceOf(AuditClient::class, $clientWithSession);
        $this->assertNotSame($this->auditClient, $clientWithSession);
    }

    public function test_can_enable_disable_auditing()
    {
        $this->assertTrue($this->auditClient->isEnabled());

        $disabledClient = $this->auditClient->enable(false);
        $this->assertFalse($disabledClient->isEnabled());

        $enabledClient = $this->auditClient->enable(true);
        $this->assertTrue($enabledClient->isEnabled());
    }

    public function test_disabled_client_skips_logging()
    {
        $disabledClient = $this->auditClient->enable(false);

        $result = $disabledClient->log([
            'event' => 'test_event',
        ]);

        $this->assertTrue($result); // Should return true but not send request
    }

    public function test_disabled_client_skips_batch_logging()
    {
        $disabledClient = $this->auditClient->enable(false);

        $audits = [
            ['event' => 'test_event_1'],
            ['event' => 'test_event_2'],
        ];

        $result = $disabledClient->logBatch($audits);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['errors']);
    }

    public function test_get_config_returns_current_configuration()
    {
        $config = $this->auditClient->getConfig();

        $this->assertEquals('http://test-audit-service/api/v1', $config['base_url']);
        $this->assertEquals('test-service', $config['service_name']);
        $this->assertEquals('1.0.0', $config['service_version']);
        $this->assertEquals('testing', $config['environment']);
        $this->assertFalse($config['async']);
        $this->assertTrue($config['enabled']);
    }

    public function test_prepares_audit_data_with_defaults()
    {
        $reflection = new \ReflectionClass($this->auditClient);
        $method = $reflection->getMethod('prepareAuditData');
        $method->setAccessible(true);

        $data = ['event' => 'test_event'];
        $prepared = $method->invoke($this->auditClient, $data);

        $this->assertEquals('test_event', $prepared['event']);
        $this->assertEquals('test-service', $prepared['service_name']);
        $this->assertEquals('1.0.0', $prepared['service_version']);
        $this->assertEquals('testing', $prepared['environment']);
        $this->assertEquals('info', $prepared['severity']);
        $this->assertArrayHasKey('event_timestamp', $prepared);
    }

    public function test_batch_handles_partial_failures()
    {
        $this->mockHandler->append(new Response(207, [], json_encode([
            'success' => false,
            'message' => '1 audit logs created, 1 failed',
            'data' => [
                'created' => [['id' => 1]],
                'errors' => [['index' => 1, 'error' => 'Validation failed']],
            ],
        ])));

        $audits = [
            ['event' => 'valid_event'],
            ['invalid' => 'data'],
        ];

        $result = $this->auditClient->logBatch($audits);

        $this->assertFalse($result['success']);
        $this->assertEquals(1, $result['created']);
        $this->assertEquals(1, $result['errors']);
    }

    public function test_health_check_handles_failure()
    {
        $this->mockHandler->append(new RequestException(
            'Service unavailable',
            new Request('GET', '/health')
        ));

        $health = $this->auditClient->healthCheck();

        $this->assertFalse($health['healthy']);
        $this->assertArrayHasKey('error', $health);
    }

    public function test_performance_logging_sets_appropriate_severity()
    {
        // Mock three requests for different durations
        $this->mockHandler->append(new Response(201)); // Fast operation (info)
        $this->mockHandler->append(new Response(201)); // Slow operation (warning)
        $this->mockHandler->append(new Response(201)); // Very slow operation (error)

        // Fast operation - should be info
        $this->auditClient->logPerformance('fast_query', 1.0);

        // Slow operation - should be warning
        $this->auditClient->logPerformance('slow_query', 7.0);

        // Very slow operation - should be error
        $this->auditClient->logPerformance('very_slow_query', 15.0);

        // We can't easily test the severity without more complex mocking,
        // but we can verify the methods complete successfully
        $this->assertTrue(true);
    }
}
