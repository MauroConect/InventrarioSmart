<?php

namespace App\Services;

use App\Models\SupportRequest;

class SupportFallbackResponder
{
    /**
     * @param  array{type: string, priority: string, confidence: float}  $classification
     */
    public function reply(array $classification): string
    {
        return match ($classification['type']) {
            SupportRequest::TYPE_BUG => 'Registré el problema para que el equipo de plataforma lo revise. Si podés, agregá los pasos exactos para reproducirlo y qué esperabas que ocurriera.',
            SupportRequest::TYPE_FEATURE_REQUEST => 'Registré tu propuesta como solicitud de mejora. El equipo de plataforma podrá responderte por este mismo chat.',
            default => 'Registré tu consulta de soporte. El equipo de plataforma podrá continuar la conversación por este mismo chat.',
        };
    }
}
