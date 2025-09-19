<?php

namespace Nrmis\AuditClient\Traits;

use Nrmis\AuditClient\Facades\AuditClient;
use Illuminate\Support\Str;

trait Auditable
{
    /**
     * Boot the Auditable trait
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->auditEvent('created');
        });

        static::updated(function ($model) {
            $model->auditEvent('updated');
        });

        static::deleted(function ($model) {
            $model->auditEvent('deleted');
        });
    }

    /**
     * Log an audit event for this model
     */
    public function auditEvent(string $event, array $metadata = []): bool
    {
        if (!config('audit-client.enabled', true)) {
            return true;
        }

        $oldValues = null;
        $newValues = null;

        if ($event === 'updated' && $this->wasChanged()) {
            $oldValues = [];
            $newValues = [];
            
            foreach ($this->getChanges() as $key => $newValue) {
                if (in_array($key, $this->getAuditableAttributes()) && 
                    !in_array($key, $this->getAuditExclude())) {
                    $newValues[$key] = $newValue;
                    $oldValues[$key] = $this->getOriginal($key);
                }
            }
        } elseif ($event === 'created') {
            $newValues = $this->getAuditableData();
        } elseif ($event === 'deleted') {
            $oldValues = $this->getAuditableData();
        }

        // Skip audit if no significant changes
        if ($event === 'updated' && empty($newValues)) {
            return true;
        }

        return AuditClient::log([
            'event' => $event,
            'action_type' => strtoupper($event),
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => array_merge($metadata, [
                'model' => class_basename($this),
                'table' => $this->getTable(),
            ]),
        ]);
    }

    /**
     * Get the attributes that should be audited
     */
    protected function getAuditableAttributes(): array
    {
        if (property_exists($this, 'auditableAttributes')) {
            return $this->auditableAttributes;
        }

        // Default to all fillable attributes minus excluded ones
        return array_diff($this->getFillable(), $this->getAuditExclude());
    }

    /**
     * Get the auditable data for this model
     */
    protected function getAuditableData(): array
    {
        $attributes = $this->getAuditableAttributes();
        $data = [];

        foreach ($attributes as $attribute) {
            if (isset($this->attributes[$attribute])) {
                $data[$attribute] = $this->attributes[$attribute];
            }
        }

        return $data;
    }

    /**
     * Get attributes that should be excluded from auditing
     */
    protected function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude') 
            ? $this->auditExclude 
            : ['password', 'remember_token', 'created_at', 'updated_at'];
    }

    /**
     * Manually log a custom audit event
     */
    public function logAuditEvent(
        string $event,
        array $oldValues = null,
        array $newValues = null,
        array $metadata = []
    ): bool {
        if (!config('audit-client.enabled', true)) {
            return true;
        }

        return AuditClient::log([
            'event' => $event,
            'action_type' => strtoupper(Str::snake($event)),
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => array_merge($metadata, [
                'model' => class_basename($this),
                'table' => $this->getTable(),
                'custom_event' => true,
            ]),
        ]);
    }

    /**
     * Temporarily disable auditing for this model instance
     */
    public function withoutAuditing(callable $callback)
    {
        $originalEnabled = config('audit-client.enabled');
        config(['audit-client.enabled' => false]);
        
        try {
            return $callback($this);
        } finally {
            config(['audit-client.enabled' => $originalEnabled]);
        }
    }

    /**
     * Check if auditing is enabled for this model
     */
    public function isAuditingEnabled(): bool
    {
        return config('audit-client.enabled', true);
    }
}
