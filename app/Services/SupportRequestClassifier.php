<?php

namespace App\Services;

use App\Models\SupportRequest;
use Illuminate\Support\Str;

class SupportRequestClassifier
{
    /**
     * @return array{type: string, priority: string, confidence: float}
     */
    public function classify(string $message): array
    {
        $normalized = Str::lower(Str::ascii($message));

        if ($this->containsAny($normalized, [
            'error', 'falla', 'fallo', 'bug', 'no funciona', 'se rompe',
            'pantalla blanca', 'excepcion', 'exception', '500', '404',
        ])) {
            return [
                'type' => SupportRequest::TYPE_BUG,
                'priority' => $this->containsAny($normalized, [
                    'urgente', 'bloqueado', 'no puedo vender', 'perdida de datos',
                ]) ? 'high' : 'normal',
                'confidence' => 0.9,
            ];
        }

        if ($this->containsAny($normalized, [
            'sugerencia', 'mejora', 'nueva funcion', 'nueva funcionalidad',
            'seria bueno', 'quisiera que', 'podrian agregar', 'feature',
        ])) {
            return [
                'type' => SupportRequest::TYPE_FEATURE_REQUEST,
                'priority' => 'normal',
                'confidence' => 0.85,
            ];
        }

        return [
            'type' => SupportRequest::TYPE_SUPPORT,
            'priority' => 'normal',
            'confidence' => 0.65,
        ];
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (Str::contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }
}
