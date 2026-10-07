<?php

namespace App\Services\Llm;

use RuntimeException;

class GeminiException extends RuntimeException
{
    public const MISSING_KEY = 'missing_key';

    public const INVALID_KEY = 'invalid_key';

    public const RATE_LIMITED = 'rate_limited';

    public const UNAVAILABLE = 'unavailable';

    // retryable: geçici bir hata mı (yoğunluk, limit), yedek modelle denenebilir mi
    public function __construct(
        public readonly string $reason,
        string $message = '',
        public readonly bool $retryable = false,
    ) {
        parent::__construct($message ?: $reason);
    }

    public function userMessage(): string
    {
        return match ($this->reason) {
            self::MISSING_KEY => '⚠️ AI servisi yapılandırılmamış: `GEMINI_API_KEY` tanımlı değil. Anahtarı `.env` dosyasına ekleyip uygulamayı yeniden başlatın.',
            self::INVALID_KEY => '⚠️ Gemini API anahtarı geçersiz veya bu modele erişimi yok. `.env` dosyasındaki `GEMINI_API_KEY` değerini kontrol edin.',
            self::RATE_LIMITED => '⏳ Ücretsiz kullanım limiti şu an dolu. Lütfen bir dakika sonra tekrar deneyin.',
            default => '⚠️ AI servisine şu an ulaşılamıyor. Lütfen biraz sonra tekrar deneyin.',
        };
    }
}
