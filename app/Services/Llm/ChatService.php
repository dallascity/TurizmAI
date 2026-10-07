<?php

namespace App\Services\Llm;

use App\Models\ChatSession;

class ChatService
{
    private const HISTORY_LIMIT = 20;

    public function __construct(private GeminiClient $gemini) {}

    /**
     * Kullanıcı mesajını yapay zekâya iletir ve cevabı döner.
     */
    public function handle(ChatSession $session, string $message): string
    {
        $history = $session->messages()->latest('id')->take(self::HISTORY_LIMIT - 1)->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->all();

        $history[] = ['role' => 'user', 'content' => $message];

        // Model geçmişi kullanıcı mesajıyla başlamalı
        while ($history[0]['role'] !== 'user') {
            array_shift($history);
        }

        try {
            $reply = $this->gemini->reply($history, $session->id);
        } catch (GeminiException $e) {
            // Hata cevapları geçmişe yazılmaz; kullanıcı aynı mesajı tekrar gönderebilir.
            return $e->userMessage();
        }

        if ($reply === '') {
            $reply = 'Üzgünüm, bir cevap oluşturamadım. Mesajınızı biraz farklı yazabilir misiniz?';
        }

        $session->messages()->createMany([
            ['role' => 'user', 'content' => $message],
            ['role' => 'assistant', 'content' => $reply],
        ]);

        return $reply;
    }
}
