<?php

if (!function_exists('getCompanyInfo')) {
    /**
     * Get company information parameter value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function getCompanyInfo($key, $default = '')
    {
        return \App\Models\ErpParameter::get($key, $default);
    }
}

if (!function_exists('getCompanyLogo')) {
    /**
     * Get company logo URL with fallback to default.
     *
     * @return string
     */
    function getCompanyLogo()
    {
        $logo = getCompanyInfo('company_logo_path');

        if ($logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo)) {
            return \Illuminate\Support\Facades\Storage::url($logo);
        }

        return asset('adminlte/dist/img/AdminLTELogo.png');
    }
}

if (!function_exists('getCompanyLogoPath')) {
    /**
     * Get company logo path for PDF generation.
     *
     * @return string
     */
    function getCompanyLogoPath()
    {
        $logo = getCompanyInfo('company_logo_path');

        if ($logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo)) {
            return public_path('storage/' . $logo);
        }

        return public_path('adminlte/dist/img/AdminLTELogo.png');
    }
}

if (!function_exists('getCompanyName')) {
    /**
     * Get company name with fallback.
     *
     * @param string $default
     * @return string
     */
    function getCompanyName($default = 'Company Name')
    {
        return getCompanyInfo('company_name', $default);
    }
}

if (!function_exists('getCompanyAddress')) {
    /**
     * Get company address.
     *
     * @param string $default
     * @return string
     */
    function getCompanyAddress($default = '')
    {
        return getCompanyInfo('company_address', $default);
    }
}

if (!function_exists('getCompanyPhone')) {
    /**
     * Get company phone.
     *
     * @param string $default
     * @return string
     */
    function getCompanyPhone($default = '')
    {
        return getCompanyInfo('company_phone', $default);
    }
}

if (!function_exists('getCompanyEmail')) {
    /**
     * Get company email.
     *
     * @param string $default
     * @return string
     */
    function getCompanyEmail($default = '')
    {
        return getCompanyInfo('company_email', $default);
    }
}

if (!function_exists('getCompanyTaxNumber')) {
    /**
     * Get company tax number.
     *
     * @param string $default
     * @return string
     */
    function getCompanyTaxNumber($default = '')
    {
        return getCompanyInfo('company_tax_number', $default);
    }
}

if (!function_exists('getCompanyWebsite')) {
    /**
     * Get company website.
     *
     * @param string $default
     * @return string
     */
    function getCompanyWebsite($default = '')
    {
        return getCompanyInfo('company_website', $default);
    }
}
