<?php

namespace App\Http\Controllers;

use App\Models\HelpFeedback;
use App\Services\Help\HelpAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class HelpController extends Controller
{
    public function ask(Request $request, HelpAssistantService $assistant): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'locale' => ['nullable', 'in:id,en,auto'],
        ]);

        $locale = $validated['locale'] ?? 'auto';
        if ($locale === 'auto') {
            $locale = app()->getLocale() === 'id' ? 'id' : 'en';
        }

        try {
            $result = $assistant->answer($validated['message'], $locale);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => $locale === 'id'
                    ? 'Bantuan sedang tidak tersedia. Periksa konfigurasi OPENROUTER_API_KEY.'
                    : 'Help is unavailable. Please verify OPENROUTER_API_KEY configuration.',
            ], 503);
        }

        return response()->json($result);
    }

    public function storeFeedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:bug,feature'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'steps_to_reproduce' => ['nullable', 'string', 'max:10000'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $feedback = HelpFeedback::create([
            'user_id' => $user->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'steps_to_reproduce' => $validated['steps_to_reproduce'] ?? null,
        ]);

        $notify = config('services.help_feedback.notify_email');
        if (is_string($notify) && $notify !== '') {
            try {
                $body = $this->feedbackEmailBody($user->name ?? '', (string) $user->email, $validated);
                Mail::raw($body, function ($message) use ($notify, $validated) {
                    $message->to($notify)
                        ->subject('[Sarang ERP] Help feedback: '.$validated['type'].' — '.$validated['title']);
                });
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json(['message' => 'ok', 'id' => $feedback->id], 201);
    }

    /**
     * @param  array{type: string, title: string, body: string, steps_to_reproduce?: string|null}  $validated
     */
    private function feedbackEmailBody(string $userName, string $userEmail, array $validated): string
    {
        $lines = [
            'Type: '.$validated['type'],
            'User: '.$userName.' ('.$userEmail.')',
            'Title: '.$validated['title'],
            '',
            $validated['body'],
        ];
        if (! empty($validated['steps_to_reproduce'])) {
            $lines[] = '';
            $lines[] = 'Steps to reproduce:';
            $lines[] = $validated['steps_to_reproduce'];
        }

        return implode("\n", $lines);
    }
}
