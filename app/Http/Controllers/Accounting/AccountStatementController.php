<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountStatement;
use App\Models\Accounting\Account;
use App\Models\BusinessPartner;
use App\Services\Accounting\AccountStatementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class AccountStatementController extends Controller
{
    public function __construct(
        private AccountStatementService $accountStatementService
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:account_statements.view')->only(['index', 'show']);
        $this->middleware('permission:account_statements.create')->only(['create', 'store', 'generate']);
        $this->middleware('permission:account_statements.update')->only(['edit', 'update', 'finalize']);
        $this->middleware('permission:account_statements.delete')->only(['destroy']);
    }

    /**
     * Display a listing of account statements
     */
    public function index(Request $request)
    {
        $query = AccountStatement::with(['account', 'businessPartner', 'creator'])
            ->orderBy('created_at', 'desc');

        // Filter by statement type
        if ($request->filled('statement_type')) {
            $query->where('statement_type', $request->statement_type);
        }

        // Filter by account
        if ($request->filled('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Filter by business partner
        if ($request->filled('business_partner_id')) {
            $query->where('business_partner_id', $request->business_partner_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('from_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('to_date', '<=', $request->to_date);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $statements = $query->paginate(20);

        // Get filter options
        $accounts = Account::postable()->orderBy('code')->get();
        $businessPartners = BusinessPartner::active()->orderBy('name')->get();

        return view('account-statements.index', compact(
            'statements',
            'accounts',
            'businessPartners'
        ));
    }

    /**
     * Show the form for creating a new account statement
     */
    public function create(Request $request)
    {
        $accounts = Account::postable()->orderBy('code')->get();
        $businessPartners = BusinessPartner::active()->orderBy('name')->get();

        $selectedAccountId = $request->get('account_id');
        $selectedBusinessPartnerId = $request->get('business_partner_id');
        $selectedType = $request->get('statement_type', 'gl_account');

        return view('account-statements.create', compact(
            'accounts',
            'businessPartners',
            'selectedAccountId',
            'selectedBusinessPartnerId',
            'selectedType'
        ));
    }

    /**
     * Generate a new account statement
     */
    public function store(Request $request)
    {
        $request->validate([
            'statement_type' => 'required|in:gl_account,business_partner',
            'account_id' => 'required_if:statement_type,gl_account|exists:accounts,id',
            'business_partner_id' => 'required_if:statement_type,business_partner|exists:business_partners,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'project_id' => 'nullable|exists:projects,id',
            'dept_id' => 'nullable|exists:departments,id',
        ]);

        try {
            $fromDate = Carbon::parse($request->from_date);
            $toDate = Carbon::parse($request->to_date);

            if ($request->statement_type === 'gl_account') {
                $statement = $this->accountStatementService->generateGlAccountStatement(
                    $request->account_id,
                    $fromDate,
                    $toDate,
                    $request->project_id,
                    $request->dept_id,
                    auth()->id()
                );
            } else {
                $statement = $this->accountStatementService->generateBusinessPartnerStatement(
                    $request->business_partner_id,
                    $fromDate,
                    $toDate,
                    $request->project_id,
                    $request->dept_id,
                    auth()->id()
                );
            }

            return redirect()->route('account-statements.show', $statement)
                ->with('success', 'Account statement generated successfully');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Failed to generate account statement: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified account statement
     */
    public function show(AccountStatement $accountStatement)
    {
        $accountStatement->load(['account', 'businessPartner', 'creator', 'finalizer', 'lines.project', 'lines.department']);

        $summary = $this->accountStatementService->getStatementSummary($accountStatement);

        return view('account-statements.show', compact('accountStatement', 'summary'));
    }

    /**
     * Show the form for editing the specified account statement
     */
    public function edit(AccountStatement $accountStatement)
    {
        if ($accountStatement->status === 'finalized') {
            return redirect()->route('account-statements.show', $accountStatement)
                ->with('error', 'Cannot edit finalized statement');
        }

        $accounts = Account::postable()->orderBy('code')->get();
        $businessPartners = BusinessPartner::active()->orderBy('name')->get();

        return view('account-statements.edit', compact(
            'accountStatement',
            'accounts',
            'businessPartners'
        ));
    }

    /**
     * Update the specified account statement
     */
    public function update(Request $request, AccountStatement $accountStatement)
    {
        if ($accountStatement->status === 'finalized') {
            return back()->with('error', 'Cannot update finalized statement');
        }

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $accountStatement->update([
            'notes' => $request->notes,
        ]);

        return redirect()->route('account-statements.show', $accountStatement)
            ->with('success', 'Account statement updated successfully');
    }

    /**
     * Finalize the account statement
     */
    public function finalize(AccountStatement $accountStatement)
    {
        if ($accountStatement->status !== 'draft') {
            return back()->with('error', 'Only draft statements can be finalized');
        }

        if (!$accountStatement->canBeFinalized()) {
            return back()->with('error', 'Statement must have transactions to be finalized');
        }

        $accountStatement->finalize(auth()->id());

        return redirect()->route('account-statements.show', $accountStatement)
            ->with('success', 'Account statement finalized successfully');
    }

    /**
     * Cancel the account statement
     */
    public function cancel(AccountStatement $accountStatement)
    {
        if ($accountStatement->status === 'finalized') {
            return back()->with('error', 'Cannot cancel finalized statement');
        }

        $accountStatement->cancel();

        return redirect()->route('account-statements.show', $accountStatement)
            ->with('success', 'Account statement cancelled successfully');
    }

    /**
     * Remove the specified account statement
     */
    public function destroy(AccountStatement $accountStatement)
    {
        if ($accountStatement->status === 'finalized') {
            return back()->with('error', 'Cannot delete finalized statement');
        }

        try {
            $this->accountStatementService->deleteStatement($accountStatement);

            return redirect()->route('account-statements.index')
                ->with('success', 'Account statement deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete statement: ' . $e->getMessage());
        }
    }

    /**
     * Export statement data
     */
    public function export(AccountStatement $accountStatement)
    {
        $data = $this->accountStatementService->exportStatement($accountStatement);

        return response()->json($data);
    }

    /**
     * Print statement
     */
    public function print(AccountStatement $accountStatement)
    {
        $accountStatement->load(['account', 'businessPartner', 'creator', 'finalizer', 'lines.project', 'lines.department']);

        $summary = $this->accountStatementService->getStatementSummary($accountStatement);

        return view('account-statements.print', compact('accountStatement', 'summary'));
    }

    /**
     * Get account balance at specific date
     */
    public function getAccountBalance(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'date' => 'required|date',
            'project_id' => 'nullable|exists:projects,id',
            'dept_id' => 'nullable|exists:departments,id',
        ]);

        try {
            $account = Account::findOrFail($request->account_id);

            if (!$account->is_postable) {
                return response()->json(['error' => 'Account is not postable'], 400);
            }

            $balance = $this->accountStatementService->calculateOpeningBalance(
                $request->account_id,
                Carbon::parse($request->date),
                $request->project_id,
                $request->dept_id
            );

            return response()->json(['balance' => $balance]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get business partner balance at specific date
     */
    public function getBusinessPartnerBalance(Request $request): JsonResponse
    {
        $request->validate([
            'business_partner_id' => 'required|exists:business_partners,id',
            'date' => 'required|date',
            'project_id' => 'nullable|exists:projects,id',
            'dept_id' => 'nullable|exists:departments,id',
        ]);

        try {
            $balance = $this->accountStatementService->calculateBusinessPartnerOpeningBalance(
                $request->business_partner_id,
                Carbon::parse($request->date),
                $request->project_id,
                $request->dept_id
            );

            return response()->json(['balance' => $balance]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
