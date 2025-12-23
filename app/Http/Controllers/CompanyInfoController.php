<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ErpParameter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CompanyInfoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage-company-info');
    }

    /**
     * Display the company information form.
     */
    public function index()
    {
        $companyInfo = $this->getCompanyInfo();

        return view('company-info.index', compact('companyInfo'));
    }

    /**
     * Update company information.
     */
    public function update(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:200',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:100',
            'company_tax_number' => 'nullable|string|max:50',
            'company_website' => 'nullable|string|max:100',
        ]);

        try {
            $fields = [
                'company_name',
                'company_address',
                'company_phone',
                'company_email',
                'company_tax_number',
                'company_website'
            ];

            foreach ($fields as $field) {
                $value = $request->input($field);
                $this->updateParameter($field, $value ?? '');
            }

            return redirect()->route('company-info.index')
                ->with('success', 'Company information updated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('company-info.index')
                ->with('error', 'Failed to update company information: ' . $e->getMessage());
        }
    }

    /**
     * Handle logo upload via AJAX.
     */
    public function uploadLogo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('logo')
            ], 422);
        }

        try {
            // Delete old logo if exists
            $oldLogo = ErpParameter::get('company_logo_path');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            // Store new logo
            $logoPath = $request->file('logo')->store('company', 'public');

            // Update parameter
            $this->updateParameter('company_logo_path', $logoPath);

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully.',
                'logo_url' => Storage::url($logoPath)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload logo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all company information parameters.
     */
    private function getCompanyInfo()
    {
        $fields = [
            'company_name',
            'company_address',
            'company_phone',
            'company_email',
            'company_tax_number',
            'company_website',
            'company_logo_path'
        ];

        $info = [];
        foreach ($fields as $field) {
            $info[$field] = ErpParameter::get($field, '');
        }

        return $info;
    }

    /**
     * Update or create a parameter.
     */
    private function updateParameter($key, $value)
    {
        ErpParameter::updateOrCreate(
            [
                'category' => 'company_info',
                'parameter_key' => $key
            ],
            [
                'parameter_name' => ucfirst(str_replace('_', ' ', $key)),
                'parameter_value' => $value,
                'data_type' => 'string',
                'description' => 'Company information parameter',
                'is_active' => true,
                'updated_by' => Auth::id(),
            ]
        );
    }
}
