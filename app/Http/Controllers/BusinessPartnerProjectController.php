<?php

namespace App\Http\Controllers;

use App\Models\BusinessPartner;
use App\Models\BusinessPartnerProject;
use Illuminate\Http\Request;

class BusinessPartnerProjectController extends Controller
{
    public function index(BusinessPartner $businessPartner)
    {
        $this->authorizeCustomer($businessPartner);
        $projects = $businessPartner->projects()->orderBy('code')->get();
        return view('business_partner_projects.index', compact('businessPartner', 'projects'));
    }

    public function store(Request $request, BusinessPartner $businessPartner)
    {
        $this->authorizeCustomer($businessPartner);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,completed,on_hold,cancelled'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $exists = BusinessPartnerProject::where('business_partner_id', $businessPartner->id)
            ->where('code', $data['code'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', "Project code '{$data['code']}' already exists for this customer.");
        }

        $businessPartner->projects()->create($data);
        return redirect()->route('business_partners.show', $businessPartner)
            ->with('success', 'Customer project created successfully.');
    }

    public function update(Request $request, BusinessPartner $businessPartner, BusinessPartnerProject $businessPartnerProject)
    {
        $this->authorizeCustomer($businessPartner);
        if ($businessPartnerProject->business_partner_id !== $businessPartner->id) {
            abort(404);
        }

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,completed,on_hold,cancelled'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $exists = BusinessPartnerProject::where('business_partner_id', $businessPartner->id)
            ->where('code', $data['code'])
            ->where('id', '!=', $businessPartnerProject->id)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', "Project code '{$data['code']}' already exists for this customer.");
        }

        $businessPartnerProject->update($data);
        return redirect()->route('business_partners.show', $businessPartner)
            ->with('success', 'Customer project updated successfully.');
    }

    public function destroy(BusinessPartner $businessPartner, BusinessPartnerProject $businessPartnerProject)
    {
        $this->authorizeCustomer($businessPartner);
        if ($businessPartnerProject->business_partner_id !== $businessPartner->id) {
            abort(404);
        }

        $businessPartnerProject->delete();
        return redirect()->route('business_partners.show', $businessPartner)
            ->with('success', 'Customer project deleted successfully.');
    }

    public function getByPartner(Request $request)
    {
        $businessPartnerId = $request->get('business_partner_id');
        if (!$businessPartnerId) {
            return response()->json([]);
        }

        $projects = BusinessPartnerProject::where('business_partner_id', $businessPartnerId)
            ->active()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return response()->json($projects->map(fn ($p) => [
            'id' => $p->id,
            'text' => "{$p->code} - {$p->name}",
        ]));
    }

    private function authorizeCustomer(BusinessPartner $businessPartner): void
    {
        if ($businessPartner->partner_type !== 'customer') {
            abort(403, 'Customer projects are only available for customers.');
        }
    }
}
