<?php

namespace App\Logging;

use App\Support\Tenancy\TenantContext;
use Monolog\LogRecord;

class TenantLogContext
{
    public function __invoke($logger): void
    {
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            $context = app(TenantContext::class);
            return $record->with(context: app(\App\Services\PlatformAuditLogger::class)->sanitize($record->context), extra: array_merge($record->extra, ['tenant_id' => $context->hasTenant() ? $context->id() : null]));
        });
    }
}
