<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerApiKeyRequest;
use App\Models\BusinessPartner;
use App\Models\CustomerApiKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CustomerApiKeyController extends Controller
{
    public function index(BusinessPartner $businessPartner): View
    {
        abort_unless(auth()->user()?->can('business_partners.manage'), 403);
        abort_unless($businessPartner->partner_type === 'customer', 404);

        $keys = $businessPartner->apiKeys()->latest()->get();

        return view('admin.customers.api-keys', [
            'businessPartner' => $businessPartner,
            'keys' => $keys,
        ]);
    }

    public function store(StoreCustomerApiKeyRequest $request, BusinessPartner $businessPartner): RedirectResponse
    {
        abort_unless(auth()->user()?->can('business_partners.manage'), 403);
        abort_unless($businessPartner->partner_type === 'customer', 404);

        $validated = $request->validated();
        $expiresAt = isset($validated['expires_at'])
            ? Carbon::parse($validated['expires_at'])
            : null;

        $created = CustomerApiKey::createForPartner($businessPartner, $validated['name'], $expiresAt);

        return redirect()
            ->route('admin.customers.api-keys.index', $businessPartner)
            ->with('success', 'API key created.')
            ->with('new_api_token', $created['plain_text_token']);
    }

    public function destroy(BusinessPartner $businessPartner, CustomerApiKey $customerApiKey): RedirectResponse
    {
        abort_unless(auth()->user()?->can('business_partners.manage'), 403);
        abort_unless($businessPartner->partner_type === 'customer', 404);
        abort_unless((int) $customerApiKey->business_partner_id === (int) $businessPartner->id, 404);

        $customerApiKey->delete();

        return redirect()
            ->route('admin.customers.api-keys.index', $businessPartner)
            ->with('success', 'API key revoked.');
    }
}
