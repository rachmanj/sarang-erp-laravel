<?php

namespace App\Http\Controllers;

use App\Models\ControlAccount;
use App\Models\SubsidiaryLedgerAccount;
use App\Services\ControlAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ControlAccountController extends Controller
{
    protected $controlAccountService;

    public function __construct(ControlAccountService $controlAccountService)
    {
        $this->controlAccountService = $controlAccountService;
        $this->middleware(['auth']);
        $this->middleware('permission:accounts.view')->only(['index', 'show', 'reconciliation']);
        $this->middleware('permission:accounts.manage')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    /**
     * Display a listing of control accounts
     */
    public function index()
    {
        $controlAccounts = ControlAccount::with(['account', 'subsidiaryAccounts', 'balances'])
            ->paginate(20);

        return view('control-accounts.index', compact('controlAccounts'));
    }

    /**
     * Show the form for creating a new control account
     */
    public function create()
    {
        $accounts = \App\Models\Accounting\Account::orderBy('code')->get();
        $controlTypes = ['ar', 'ap', 'inventory', 'fixed_assets'];

        return view('control-accounts.create', compact('accounts', 'controlTypes'));
    }

    /**
     * Store a newly created control account
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'control_type' => 'required|in:ar,ap,inventory,fixed_assets',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $controlAccount = $this->controlAccountService->createControlAccount($validated);

        return response()->json([
            'success' => true,
            'message' => 'Control account created successfully.',
            'data' => $controlAccount
        ]);
    }

    /**
     * Display the specified control account
     */
    public function show(ControlAccount $controlAccount)
    {
        $controlAccount->load(['account', 'subsidiaryAccounts.account', 'balances']);

        // Get reconciliation data
        $reconciliation = $this->controlAccountService->reconcileControlAccount($controlAccount->id);

        return view('control-accounts.show', compact('controlAccount', 'reconciliation'));
    }

    /**
     * Show reconciliation dashboard
     */
    public function reconciliation()
    {
        $controlAccounts = ControlAccount::active()->with(['account', 'balances'])->get();
        $reconciliationData = [];

        foreach ($controlAccounts as $controlAccount) {
            $balances = $controlAccount->balances;
            
            foreach ($balances as $balance) {
                $variance = $balance->getReconciliationVarianceAttribute();
                $reconciliationData[] = [
                    'control_account' => $controlAccount,
                    'balance' => $balance,
                    'variance' => $variance,
                    'is_reconciled' => $balance->isReconciled(),
                ];
            }
        }

        $exceptions = $this->controlAccountService->getReconciliationExceptions();

        return view('control-accounts.reconciliation', compact('reconciliationData', 'exceptions'));
    }

    /**
     * Perform reconciliation for a specific control account
     */
    public function reconcile(Request $request, ControlAccount $controlAccount)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'dept_id' => 'nullable|exists:departments,id',
        ]);

        $result = $this->controlAccountService->reconcileControlAccount(
            $controlAccount->id,
            $validated['project_id'] ?? null,
            $validated['dept_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Reconciliation completed.',
            'data' => $result
        ]);
    }

    /**
     * Get control account data for DataTables
     */
    public function data()
    {
        $controlAccounts = ControlAccount::with(['account', 'subsidiaryAccounts'])
            ->select(['id', 'account_id', 'control_type', 'is_active', 'description', 'created_at']);

        return datatables($controlAccounts)
            ->addColumn('account_code', function ($controlAccount) {
                return $controlAccount->account->code ?? '';
            })
            ->addColumn('account_name', function ($controlAccount) {
                return $controlAccount->account->name ?? '';
            })
            ->addColumn('subsidiary_count', function ($controlAccount) {
                return $controlAccount->subsidiaryAccounts->count();
            })
            ->addColumn('status', function ($controlAccount) {
                return $controlAccount->is_active ? 
                    '<span class="badge badge-success">Active</span>' : 
                    '<span class="badge badge-secondary">Inactive</span>';
            })
            ->addColumn('actions', function ($controlAccount) {
                $actions = '<div class="btn-group">';
                $actions .= '<a href="' . route('control-accounts.show', $controlAccount) . '" class="btn btn-sm btn-info">View</a>';
                $actions .= '<button type="button" class="btn btn-sm btn-warning" onclick="reconcileAccount(' . $controlAccount->id . ')">Reconcile</button>';
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }
}
