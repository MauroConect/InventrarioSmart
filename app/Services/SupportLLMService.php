<?php

namespace App\Services;

use App\Models\SupportMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SupportLLMService
{
    public function __construct(
        private readonly SupportFallbackResponder $fallback,
    ) {}

    /**
     * @param  Collection<int, SupportMessage>  $history
     * @param  array{type: string, priority: string, confidence: float}  $classification
     * @return array{body: string, provider: string, fallback: bool}
     */
    public function reply(Collection $history, array $classification): array
    {
        $apiKey = (string) config('services.openrouter.key', '');

        if ($apiKey === '') {
            return $this->fallbackResult($classification);
        }

        try {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout((int) config('services.openrouter.timeout', 20))
                ->withHeaders(array_filter([
                    'HTTP-Referer' => config('services.openrouter.site_url'),
                    'X-Title' => config('services.openrouter.app_name', config('app.name')),
                ]))
                ->post(
                    rtrim((string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/').'/chat/completions',
                    [
                        'model' => config('services.openrouter.model', 'openai/gpt-4o-mini'),
                        'temperature' => 0.2,
                        'messages' => $this->messages($history, $classification),
                    ]
                );

            $response->throw();
            $body = trim((string) $response->json('choices.0.message.content'));

            if ($body === '') {
                throw new RuntimeException('OpenRouter devolvió una respuesta vacía.');
            }

            return [
                'body' => $body,
                'provider' => 'openrouter',
                'fallback' => false,
            ];
        } catch (Throwable $exception) {
            Log::warning('Support chat usó la respuesta de contingencia.', [
                'exception' => $exception->getMessage(),
            ]);

            return $this->fallbackResult($classification);
        }
    }

    /**
     * @param  Collection<int, SupportMessage>  $history
     * @param  array{type: string, priority: string, confidence: float}  $classification
     * @return array<int, array{role: string, content: string}>
     */
    private function messages(Collection $history, array $classification): array
    {
        $promptPath = resource_path('prompts/support/system.md');
        $prompt = is_file($promptPath)
            ? (string) file_get_contents($promptPath)
            : 'Respondé en español como asistente de soporte de InventarioSmart.';

        $messages = [[
            'role' => 'system',
            'content' => $prompt
                ."\n\nClasificación interna: {$classification['type']}; prioridad: {$classification['priority']}."
                .$this->safeContext($history),
        ]];

        foreach ($history->take(-20) as $message) {
            $messages[] = [
                'role' => match ($message->role) {
                    SupportMessage::ROLE_USER => 'user',
                    SupportMessage::ROLE_SYSTEM => 'system',
                    default => 'assistant',
                },
                'content' => $message->body,
            ];
        }

        return $messages;
    }

    private function safeContext(Collection $history): string
    {
        $context = $history
            ->where('role', SupportMessage::ROLE_USER)
            ->last()?->metadata['context'] ?? [];

        return "\nContexto permitido (no asumir nada fuera de esto): "
            .json_encode([
                'pantalla' => $context['screen'] ?? null,
                'rol' => $context['role'] ?? null,
                'modulos' => array_values($context['modules'] ?? []),
            ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array{type: string, priority: string, confidence: float}  $classification
     * @return array{body: string, provider: string, fallback: bool}
     */
    private function fallbackResult(array $classification): array
    {
        return [
            'body' => $this->fallback->reply($classification),
            'provider' => 'fallback',
            'fallback' => true,
        ];
    }
}
