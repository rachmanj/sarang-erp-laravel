<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssistantChatRequest;
use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use App\Models\AssistantRequestLog;
use App\Models\User;
use App\Services\Assistant\AssistantConversationManager;
use App\Services\Assistant\DomainAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DomainAssistantController extends Controller
{
    public function __construct(
        private AssistantConversationManager $conversations,
        private DomainAssistantService $assistant,
    ) {}

    public function index(): View|\Illuminate\Http\RedirectResponse
    {
        if (! $this->featureAvailable()) {
            return redirect()->route('dashboard')->with('error', 'Domain Assistant is not available.');
        }

        $user = Auth::user();
        $canShowAll = $user instanceof User && $user->can('see-all-record-switch');

        return view('assistant.index', [
            'canShowAllRecords' => $canShowAll,
        ]);
    }

    public function listConversations(): JsonResponse
    {
        $this->ensureFeature();

        $userId = Auth::id();
        $rows = AssistantConversation::query()
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get(['id', 'title', 'created_at', 'updated_at']);

        $active = session('domain_assistant.conversation_id');

        return response()->json([
            'conversations' => $rows,
            'active_id' => $active !== null ? (int) $active : null,
        ]);
    }

    public function createConversation(): JsonResponse
    {
        $this->ensureFeature();

        $conv = $this->conversations->createNew();

        return response()->json([
            'id' => $conv->id,
            'title' => $conv->title,
            'created_at' => $conv->created_at,
            'updated_at' => $conv->updated_at,
        ], 201);
    }

    public function loadMessages(AssistantConversation $conversation): JsonResponse
    {
        $this->ensureFeature();

        $messages = $conversation->messages()
            ->orderBy('id')
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json(['messages' => $messages]);
    }

    public function selectConversation(AssistantConversation $conversation): JsonResponse
    {
        $this->ensureFeature();

        $this->conversations->setActiveConversation($conversation);

        return response()->json(['ok' => true]);
    }

    public function deleteConversation(AssistantConversation $conversation): JsonResponse
    {
        $this->ensureFeature();

        $this->conversations->deleteConversation($conversation);

        return response()->json(['ok' => true]);
    }

    public function chat(AssistantChatRequest $request): JsonResponse
    {
        $this->ensureFeature();

        $dailyLimit = (int) config('services.domain_assistant.daily_user_limit', 50);
        if ($dailyLimit > 0) {
            $todayCount = AssistantMessage::query()
                ->where('role', 'user')
                ->whereDate('created_at', today())
                ->whereHas('conversation', fn ($q) => $q->where('user_id', $request->user()->id))
                ->count();

            if ($todayCount >= $dailyLimit) {
                return response()->json([
                    'message' => 'Daily message limit reached. Try again tomorrow.',
                ], 429);
            }
        }

        $validated = $request->validated();
        $showAll = $request->boolean('show_all_records')
            && $request->user()->can('see-all-record-switch');

        $conversation = $this->conversations->resolveOrCreate(
            isset($validated['conversation_id']) ? (int) $validated['conversation_id'] : null
        );

        $historyLimit = (int) config('services.domain_assistant.history_messages', 24);
        $history = $this->conversations->messagesForModel($conversation, $historyLimit);

        $toolsEnabled = (bool) config('services.domain_assistant.tools_enabled', true);

        $started = microtime(true);
        $ip = $request->ip();
        $ua = $request->userAgent();

        try {
            $result = $this->assistant->runChat(
                $validated['message'],
                $history,
                $toolsEnabled,
                $showAll,
            );

            $duration = (int) round((microtime(true) - $started) * 1000);

            $this->conversations->appendUserMessage($conversation, $validated['message']);
            $this->conversations->appendAssistantMessage($conversation, $result['answer']);

            AssistantRequestLog::create([
                'user_id' => $request->user()->id,
                'assistant_conversation_id' => $conversation->id,
                'status' => 'success',
                'tools_invoked' => $result['tools_invoked'],
                'duration_ms' => $duration,
                'error_summary' => null,
                'ip_address' => $ip,
                'user_agent' => $ua !== null ? substr($ua, 0, 500) : null,
            ]);

            return response()->json([
                'answer' => $result['answer'],
                'tools_invoked' => $result['tools_invoked'],
                'tool_traces' => $result['tool_traces'],
                'conversation_id' => $conversation->id,
            ]);
        } catch (\Throwable $e) {
            report($e);

            $duration = (int) round((microtime(true) - $started) * 1000);
            AssistantRequestLog::create([
                'user_id' => $request->user()->id,
                'assistant_conversation_id' => $conversation->id,
                'status' => 'error',
                'tools_invoked' => null,
                'duration_ms' => $duration,
                'error_summary' => substr($e->getMessage(), 0, 500),
                'ip_address' => $ip,
                'user_agent' => $ua !== null ? substr($ua, 0, 500) : null,
            ]);

            return response()->json([
                'message' => 'Domain Assistant failed. Check OPENROUTER_API_KEY and model configuration.',
            ], 503);
        }
    }

    private function featureAvailable(): bool
    {
        if (! config('services.domain_assistant.enabled', false)) {
            return false;
        }

        $key = config('services.openrouter.api_key');

        return is_string($key) && $key !== '';
    }

    private function ensureFeature(): void
    {
        if (! $this->featureAvailable()) {
            abort(503, 'Domain Assistant is not configured.');
        }
    }
}
