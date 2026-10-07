<?php

namespace App\Services\Llm;

use App\Services\Hotel\HotelTools;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    // Model araç çağırmaya devam ettikçe en fazla bu kadar istek atılır
    private const MAX_ROUNDS = 5;

    public function __construct(private HotelTools $tools) {}

    public function model(): string
    {
        return config('turizm.gemini.model');
    }

    public function isConfigured(): bool
    {
        return filled(config('turizm.gemini.api_key'));
    }

    public function reply(array $history, ?int $chatSessionId = null): string
    {
        if (! $this->isConfigured()) {
            throw new GeminiException(GeminiException::MISSING_KEY);
        }

        $contents = array_map(fn ($message) => [
            'role' => $message['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $message['content']]],
        ], $history);

        $models = $this->models();
        $userMessage = end($history)['content'];

        for ($round = 0; $round < self::MAX_ROUNDS; $round++) {
            [$model, $content] = $this->generate($contents, $models);

            // İlk cevabı veren modelle devam et; araç döngüsü ortasında model değişmesin
            $models = [$model];

            $calls = array_filter($content['parts'], fn ($part) => isset($part['functionCall']));

            if (empty($calls)) {
                return trim(collect($content['parts'])->pluck('text')->implode(''));
            }

            // Modelin cevabını olduğu gibi geçmişe ekle, sonra araç sonuçlarını gönder
            $contents[] = $this->fixEmptyArgs($content);
            $contents[] = [
                'role' => 'user',
                'parts' => array_values(array_map(function ($part) use ($chatSessionId, $userMessage) {
                    $name = $part['functionCall']['name'];
                    $args = $part['functionCall']['args'] ?? [];
                    $result = $this->tools->call($name, $args, $chatSessionId, $userMessage);

                    Log::info('Gemini tool call', ['tool' => $name, 'args' => $args]);

                    return ['functionResponse' => ['name' => $name, 'response' => ['result' => $result]]];
                }, $calls)),
            ];
        }

        throw new GeminiException(GeminiException::UNAVAILABLE, 'Araç çağrısı sınırı aşıldı.');
    }

    // Ana model yoğunsa veya limiti dolduysa sıradaki yedek modeli dener
    private function generate(array $contents, array $models): array
    {
        foreach ($models as $model) {
            try {
                return [$model, $this->request($model, $contents)];
            } catch (GeminiException $e) {
                if (! $e->retryable) {
                    throw $e;
                }
                Log::warning("Gemini modeli $model cevap vermedi: {$e->getMessage()}");
            }
        }

        throw $e;
    }

    private function request(string $model, array $contents): array
    {
        $url = config('turizm.gemini.base_url')."/models/$model:generateContent";

        try {
            $response = Http::withHeaders(['x-goog-api-key' => config('turizm.gemini.api_key')])
                ->timeout(config('turizm.gemini.timeout'))
                ->post($url, [
                    'systemInstruction' => ['parts' => [['text' => SystemPrompt::build()]]],
                    'contents' => $contents,
                    'tools' => [['functionDeclarations' => HotelTools::DEFINITIONS]],
                ]);
        } catch (ConnectionException $e) {
            throw new GeminiException(GeminiException::UNAVAILABLE, $e->getMessage(), retryable: true);
        }

        if ($response->failed()) {
            $status = $response->status();
            $message = $response->json('error.message', '');
            Log::warning('Gemini API hatası', compact('model', 'status', 'message'));

            $reason = match (true) {
                $status === 429 => GeminiException::RATE_LIMITED,
                $status === 403 || str_contains($message, 'API key') => GeminiException::INVALID_KEY,
                default => GeminiException::UNAVAILABLE,
            };

            // 429 (limit), 404 (model yok) ve 5xx (yoğunluk) durumlarında yedek model denenebilir
            $retryable = $status === 429 || $status === 404 || $status >= 500;

            throw new GeminiException($reason, $message, $retryable);
        }

        $content = $response->json('candidates.0.content');

        // Güvenlik filtresi gibi durumlarda Gemini boş cevap dönebiliyor
        if (empty($content['parts'])) {
            return ['role' => 'model', 'parts' => [['text' => 'Bu konuda yardımcı olamıyorum, tatil planınızla ilgili başka bir şey sorabilirsiniz.']]];
        }

        return $content;
    }

    // PHP boş JSON nesnesini {} diziye [] çeviriyor, Gemini de [] gelince hata veriyor
    private function fixEmptyArgs(array $content): array
    {
        foreach ($content['parts'] as $i => $part) {
            if (isset($part['functionCall']) && empty($part['functionCall']['args'])) {
                $content['parts'][$i]['functionCall']['args'] = new \stdClass;
            }
        }

        return $content;
    }

    private function models(): array
    {
        $fallbacks = explode(',', config('turizm.gemini.fallback_models'));

        return array_unique(array_filter(array_map('trim', [$this->model(), ...$fallbacks])));
    }
}
