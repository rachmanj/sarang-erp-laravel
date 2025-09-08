<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Services\Accounting\PostingService;
use Illuminate\Http\Request;

class PostingDemoController extends Controller
{
    public function __construct(private PostingService $service)
    {
        $this->middleware(['auth', 'permission:journals.create']);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'period_id' => ['nullable', 'integer'],
            'source_type' => ['required', 'string'],
            'source_id' => ['required', 'integer'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.fund_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        $payload['posted_by'] = $request->user()->id;

        $journalId = $this->service->postJournal($payload);

        return response()->json(['journal_id' => $journalId]);
    }
}
