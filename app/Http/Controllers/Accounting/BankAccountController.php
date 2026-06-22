<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Bank\BankAccount;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BankAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware('permission:bank_accounts.view')->only(['index', 'data']);
        $this->middleware('permission:bank_accounts.manage')->only(['create', 'store', 'edit', 'update']);
    }

    public function index()
    {
        return view('bank_accounts.index');
    }

    public function create()
    {
        $coaAccounts = Account::query()
            ->where('is_postable', true)
            ->where('code', 'like', '1.1.1%')
            ->orderBy('code')
            ->get();

        return view('bank_accounts.create', compact('coaAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:bank_accounts,code'],
            'name' => ['required', 'string', 'max:150'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:150'],
            'currency' => ['required', 'string', 'max:10'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'is_restricted' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_restricted'] = (bool) ($data['is_restricted'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        BankAccount::create($data);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account created.');
    }

    public function edit(BankAccount $bankAccount)
    {
        $coaAccounts = Account::query()
            ->where('is_postable', true)
            ->where('code', 'like', '1.1.1%')
            ->orderBy('code')
            ->get();

        return view('bank_accounts.edit', compact('bankAccount', 'coaAccounts'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:bank_accounts,code,'.$bankAccount->id],
            'name' => ['required', 'string', 'max:150'],
            'bank_name' => ['nullable', 'string', 'max:150'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'branch' => ['nullable', 'string', 'max:150'],
            'currency' => ['required', 'string', 'max:10'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'is_restricted' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_restricted'] = (bool) ($data['is_restricted'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $bankAccount->update($data);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank account updated.');
    }

    public function data(Request $request)
    {
        $query = BankAccount::query()
            ->with('account')
            ->orderBy('code');

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return DataTables::of($query)
            ->addColumn('coa_code', fn (BankAccount $row) => $row->account?->code ?? '-')
            ->addColumn('coa_name', fn (BankAccount $row) => $row->account?->name ?? '-')
            ->addColumn('status_label', fn (BankAccount $row) => $row->is_active
                ? '<span class="badge badge-success">Active</span>'
                : '<span class="badge badge-secondary">Inactive</span>')
            ->addColumn('actions', function (BankAccount $row) {
                if (! auth()->user()?->can('bank_accounts.manage')) {
                    return '';
                }

                return '<a href="'.route('bank-accounts.edit', $row).'" class="btn btn-xs btn-info">Edit</a>';
            })
            ->rawColumns(['status_label', 'actions'])
            ->make(true);
    }
}
