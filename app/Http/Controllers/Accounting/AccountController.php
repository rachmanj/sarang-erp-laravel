<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware('permission:accounts.view')->only(['index']);
        $this->middleware('permission:accounts.manage')->only(['create', 'store', 'edit', 'update']);
    }

    public function index(Request $request)
    {
        $query = Account::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $accounts = $query->orderBy('code')->paginate(20)->appends($request->query());

        $accountTypes = [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'net_assets' => 'Net Assets',
            'income' => 'Income',
            'expense' => 'Expense',
        ];

        return view('accounts.index', compact('accounts', 'accountTypes'));
    }

    public function create()
    {
        $parents = Account::query()->orderBy('code')->get();
        return view('accounts.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,net_assets,income,expense'],
            'is_postable' => ['required', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);
        Account::create($data);
        return redirect()->route('accounts.index')->with('success', 'Account created');
    }

    public function edit(Account $account)
    {
        $parents = Account::where('id', '!=', $account->id)->orderBy('code')->get();
        return view('accounts.edit', compact('account', 'parents'));
    }

    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:accounts,code,' . $account->id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,net_assets,income,expense'],
            'is_postable' => ['required', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);
        $account->update($data);
        return redirect()->route('accounts.index')->with('success', 'Account updated');
    }
}
