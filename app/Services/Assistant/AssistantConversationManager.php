<?php

namespace App\Services\Assistant;

use App\Models\AssistantConversation;
use App\Models\AssistantMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AssistantConversationManager
{
    private const SESSION_KEY = 'domain_assistant.conversation_id';

    public function resolveOrCreate(?int $conversationId = null): AssistantConversation
    {
        $userId = Auth::id();
        if ($userId === null) {
            throw new \RuntimeException('Unauthenticated.');
        }

        if ($conversationId !== null) {
            $conv = AssistantConversation::where('user_id', $userId)->whereKey($conversationId)->first();
            if ($conv === null) {
                abort(404);
            }
            session([self::SESSION_KEY => $conv->id]);

            return $conv;
        }

        $sessionId = session(self::SESSION_KEY);
        if ($sessionId !== null) {
            $conv = AssistantConversation::where('user_id', $userId)->whereKey($sessionId)->first();
            if ($conv !== null) {
                return $conv;
            }
        }

        $conv = AssistantConversation::create([
            'user_id' => $userId,
            'title' => null,
        ]);
        session([self::SESSION_KEY => $conv->id]);

        return $conv;
    }

    public function setActiveConversation(AssistantConversation $conversation): void
    {
        session([self::SESSION_KEY => $conversation->id]);
    }

    public function clearSessionConversation(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function createNew(): AssistantConversation
    {
        $userId = Auth::id();
        if ($userId === null) {
            throw new \RuntimeException('Unauthenticated.');
        }

        $conv = AssistantConversation::create([
            'user_id' => $userId,
            'title' => null,
        ]);
        session([self::SESSION_KEY => $conv->id]);

        return $conv;
    }

    public function appendUserMessage(AssistantConversation $conversation, string $content): AssistantMessage
    {
        $content = trim($content);
        if ($conversation->title === null || $conversation->title === '') {
            $conversation->update(['title' => Str::limit($content, 80)]);
        }

        return $conversation->messages()->create([
            'role' => 'user',
            'content' => $content,
        ]);
    }

    public function appendAssistantMessage(AssistantConversation $conversation, string $content): AssistantMessage
    {
        return $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $content,
        ]);
    }

    public function deleteConversation(AssistantConversation $conversation): void
    {
        $userId = Auth::id();
        if ((int) $conversation->user_id !== (int) $userId) {
            abort(404);
        }
        $active = session(self::SESSION_KEY);
        if ((int) $active === (int) $conversation->id) {
            $this->clearSessionConversation();
        }
        $conversation->delete();
    }

    /**
     * @return list<array{role: string, content: string}>
     */
    public function messagesForModel(AssistantConversation $conversation, int $limit): array
    {
        $total = $conversation->messages()->count();
        $skip = max(0, $total - $limit);
        $rows = $conversation->messages()
            ->orderBy('id')
            ->skip($skip)
            ->take($limit)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'role' => $row->role,
                'content' => $row->content,
            ];
        }

        return $out;
    }
}
