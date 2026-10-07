<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\Llm\ChatService;
use App\Services\Llm\GeminiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(GeminiClient $gemini): View
    {
        $session = ChatSession::firstWhere('uuid', session('chat_session'));

        return view('chat', [
            'model' => $gemini->model(),
            'configured' => $gemini->isConfigured(),
            'messages' => $session ? $session->messages()->orderBy('id')->get() : collect(),
        ]);
    }

    public function send(Request $request, ChatService $chat): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $session = ChatSession::firstOrCreate([
            'uuid' => session('chat_session') ?? (string) Str::uuid(),
        ]);
        session(['chat_session' => $session->uuid]);

        $reply = $chat->handle($session, $data['message']);

        return response()->json([
            'html' => view('partials.bubble', ['role' => 'assistant', 'content' => $reply])->render(),
        ]);
    }

    public function reset(): Response
    {
        session()->forget('chat_session');

        return response()->noContent();
    }
}
