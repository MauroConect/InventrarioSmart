<?php

namespace App\Services;

use App\Models\PlatformAuditEvent;
use App\Models\Tenant;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stringable;

class PlatformAuditLogger
{
    private const REDACTED = '[REDACTED]';

    public function log(
        Request $request,
        string $action,
        ?Tenant $tenant = null,
        ?Model $subject = null,
        array $before = [],
        array $after = [],
        array $metadata = [],
    ): PlatformAuditEvent {
        $requestId = (string) $request->header('X-Request-Id', '');
        $correlationId = Str::isUuid($requestId) ? $requestId : (string) Str::uuid();
        $actor = $request->user();

        return PlatformAuditEvent::query()->create([
            'event_uuid' => (string) Str::uuid(),
            'correlation_id' => $correlationId,
            'tenant_id' => $tenant?->getKey(),
            'actor_user_id' => $actor?->getAuthIdentifier(),
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject ? (string) $subject->getKey() : null,
            'before' => $this->sanitize($before),
            'after' => $this->sanitize($after),
            'metadata' => $this->sanitize($metadata),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1024, ''),
        ]);
    }

    /**
     * @return array<string|int, mixed>
     */
    public function sanitize(array $payload, int $depth = 0): array
    {
        if ($depth >= 8) {
            return ['truncated' => true];
        }

        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $sanitized[$key] = self::REDACTED;

                continue;
            }

            $sanitized[$key] = $this->sanitizeValue($value, $depth + 1);
        }

        return $sanitized;
    }

    private function sanitizeValue(mixed $value, int $depth): mixed
    {
        if (is_array($value)) {
            return $this->sanitize($value, $depth);
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof Model) {
            return [
                'type' => $value::class,
                'id' => (string) $value->getKey(),
            ];
        }

        if ($value instanceof Stringable) {
            return Str::limit((string) $value, 4000, '…');
        }

        if (is_string($value)) {
            return Str::limit($value, 4000, '…');
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return '[UNSUPPORTED]';
    }

    private function isSensitiveKey(string $key): bool
    {
        return (bool) preg_match(
            '/password|passwd|passphrase|secret|token|authorization|cookie|certificate|certificado|clave.?privada|private.?key|credential|api.?key/i',
            $key
        );
    }
}
