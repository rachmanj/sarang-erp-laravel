<?php

namespace App\Services;

use App\Models\ErpParameter;
use Illuminate\Support\Facades\Storage;

class CompanyInfoService
{
    /**
     * Get all company information parameters.
     */
    public function getCompanyInfo()
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
     * Get company logo URL with fallback to default.
     */
    public function getCompanyLogoUrl()
    {
        $logoPath = ErpParameter::get('company_logo_path');

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            return Storage::url($logoPath);
        }

        return asset('adminlte/dist/img/AdminLTELogo.png');
    }

    /**
     * Get company logo path for PDF generation.
     */
    public function getCompanyLogoPath()
    {
        $logoPath = ErpParameter::get('company_logo_path');

        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            return public_path('storage/' . $logoPath);
        }

        return public_path('adminlte/dist/img/AdminLTELogo.png');
    }

    /**
     * Update company information parameter.
     */
    public function updateParameter($key, $value, $userId = null)
    {
        return ErpParameter::updateOrCreate(
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
                'updated_by' => $userId,
            ]
        );
    }

    /**
     * Get company name with fallback.
     */
    public function getCompanyName($default = 'Company Name')
    {
        return ErpParameter::get('company_name', $default);
    }

    /**
     * Get company address.
     */
    public function getCompanyAddress($default = '')
    {
        return ErpParameter::get('company_address', $default);
    }

    /**
     * Get company phone.
     */
    public function getCompanyPhone($default = '')
    {
        return ErpParameter::get('company_phone', $default);
    }

    /**
     * Get company email.
     */
    public function getCompanyEmail($default = '')
    {
        return ErpParameter::get('company_email', $default);
    }

    /**
     * Get company tax number.
     */
    public function getCompanyTaxNumber($default = '')
    {
        return ErpParameter::get('company_tax_number', $default);
    }

    /**
     * Get company website.
     */
    public function getCompanyWebsite($default = '')
    {
        return ErpParameter::get('company_website', $default);
    }
}
