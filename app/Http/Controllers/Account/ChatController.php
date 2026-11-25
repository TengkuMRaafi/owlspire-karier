<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiQuota;
use App\Services\Ai\CareerChatbot;
use App\Services\Billing\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

/** Asisten AI: chat hari ini (direset tiap hari), arsip per hari, dan kuota pesan harian (Gratis vs Premium). */
class ChatController extends Controller
{
    public function index(Request $request, CareerChatbot $bot, EntitlementService $entitlements)
    {
        $user = $request->user();

        return view('account.chat', [
            'messages' => $bot->history($user),
            'limit' => $bot->dailyLimit($user),
            'remaining' => $bot->remaining($user),
            'premium' => $entitlements->isPremium($user->id),
            'enabled' => $bot->enabled(),
            'maxChars' => (int) config('platform.chatbot.max_chars'),
        ]);
    }

    public function send(Request $request, CareerChatbot $bot): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:'.(int) config('platform.chatbot.max_chars')]]);
        $user = $request->user();

        if (! $bot->enabled()) {
            return response()->json(['message' => 'Asisten AI belum diaktifkan.'], 503);
        }
        if ($bot->remaining($user) <= 0) {
            return response()->json([
                'message' => 'Kuota pesan hari ini sudah habis. Kuota diisi ulang besok pagi.',
                'remaining' => 0,
                'limit' => $bot->dailyLimit($user),
                'reset_at' => AiQuota::resetsAt()->toIso8601String(),
            ], 429);
        }

        $answer = $bot->ask($user, trim($data['message']));
        if ($answer === null) {
            return response()->json(['message' => 'Asisten belum bisa menjawab. Coba kirim ulang sebentar lagi; kuotamu tidak terpakai.', 'remaining' => $bot->remaining($user)], 503);
        }

        return response()->json(['answer' => $answer, 'remaining' => $bot->remaining($user), 'limit' => $bot->dailyLimit($user)]);
    }

    public function clear(Request $request, CareerChatbot $bot)
    {
        $bot->clear($request->user());

        return redirect()->route('account.chat')->with('status', 'Percakapan hari ini dihapus.');
    }

    /** Arsip: daftar hari-hari sebelumnya beserta jumlah pertanyaan dan cuplikannya. */
    public function days(Request $request, CareerChatbot $bot)
    {
        return view('account.chat-days', ['days' => $bot->days($request->user())]);
    }

    /** Isi percakapan pada satu hari sebelumnya (hanya baca). */
    public function day(Request $request, CareerChatbot $bot, string $day)
    {
        $date = $this->parseDay($day);
        $messages = $bot->dayMessages($request->user(), $date);
        abort_if($messages->isEmpty(), 404);

        return view('account.chat-day', ['date' => $date, 'day' => $day, 'messages' => $messages]);
    }

    public function clearDay(Request $request, CareerChatbot $bot, string $day)
    {
        $bot->clearDay($request->user(), $this->parseDay($day));

        return redirect()->route('account.chat.days')->with('status', 'Percakapan tanggal itu dihapus.');
    }

    /** Hanya tanggal sebelum hari ini yang valid; hari ini ada di halaman chat. */
    private function parseDay(string $day): Carbon
    {
        try {
            $date = Carbon::createFromFormat('!Y-m-d', $day);
        } catch (\Throwable) {
            $date = false;
        }
        abort_if(! $date || $date->format('Y-m-d') !== $day || $date->greaterThanOrEqualTo(now()->startOfDay()), 404);

        return $date;
    }
}
