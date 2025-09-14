<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSetting extends Model
{
    protected $fillable = [
        'setting_key',
        'setting_name',
        'setting_value',
        'data_type',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey($query, $key)
    {
        return $query->where('setting_key', $key);
    }

    // Methods
    public function getValue()
    {
        switch ($this->data_type) {
            case 'boolean':
                return filter_var($this->setting_value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
                return is_numeric($this->setting_value) ? (float)$this->setting_value : 0;
            case 'json':
                return json_decode($this->setting_value, true);
            default:
                return $this->setting_value;
        }
    }

    public function setValue($value)
    {
        switch ($this->data_type) {
            case 'boolean':
                $this->setting_value = $value ? 'true' : 'false';
                break;
            case 'number':
                $this->setting_value = (string)$value;
                break;
            case 'json':
                $this->setting_value = json_encode($value);
                break;
            default:
                $this->setting_value = (string)$value;
        }

        $this->save();
    }

    // Static methods for easy access
    public static function get($key, $default = null)
    {
        $setting = self::active()->byKey($key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->getValue();
    }

    public static function set($key, $value, $dataType = 'string')
    {
        $setting = self::byKey($key)->first();

        if (!$setting) {
            $setting = self::create([
                'setting_key' => $key,
                'setting_name' => ucwords(str_replace('_', ' ', $key)),
                'setting_value' => (string)$value,
                'data_type' => $dataType,
                'is_active' => true,
            ]);
        } else {
            $setting->setValue($value);
        }

        return $setting;
    }

    public static function getTaxRates()
    {
        return [
            'ppn_rate' => self::get('ppn_rate', 11.0),
            'pph_21_rate' => self::get('pph_21_rate', 5.0),
            'pph_22_rate' => self::get('pph_22_rate', 1.5),
            'pph_23_rate' => self::get('pph_23_rate', 2.0),
            'pph_26_rate' => self::get('pph_26_rate', 20.0),
            'pph_4_2_rate' => self::get('pph_4_2_rate', 0.5),
        ];
    }

    public static function getCompanyInfo()
    {
        return [
            'company_name' => self::get('company_name', 'PT Sarange'),
            'company_npwp' => self::get('company_npwp', ''),
            'company_address' => self::get('company_address', ''),
            'company_phone' => self::get('company_phone', ''),
            'company_email' => self::get('company_email', ''),
        ];
    }

    public static function getTaxOfficeInfo()
    {
        return [
            'tax_office_code' => self::get('tax_office_code', ''),
            'tax_office_name' => self::get('tax_office_name', ''),
            'tax_office_address' => self::get('tax_office_address', ''),
        ];
    }

    public static function getReportingSettings()
    {
        return [
            'auto_generate_reports' => self::get('auto_generate_reports', false),
            'report_due_day' => self::get('report_due_day', 20),
            'send_reminders' => self::get('send_reminders', true),
            'reminder_days_before' => self::get('reminder_days_before', 7),
        ];
    }

    public static function initializeDefaultSettings()
    {
        $defaultSettings = [
            // Tax Rates
            ['key' => 'ppn_rate', 'name' => 'PPN Rate', 'value' => '11.0', 'type' => 'number', 'description' => 'PPN tax rate percentage'],
            ['key' => 'pph_21_rate', 'name' => 'PPh 21 Rate', 'value' => '5.0', 'type' => 'number', 'description' => 'PPh 21 tax rate percentage'],
            ['key' => 'pph_22_rate', 'name' => 'PPh 22 Rate', 'value' => '1.5', 'type' => 'number', 'description' => 'PPh 22 tax rate percentage'],
            ['key' => 'pph_23_rate', 'name' => 'PPh 23 Rate', 'value' => '2.0', 'type' => 'number', 'description' => 'PPh 23 tax rate percentage'],
            ['key' => 'pph_26_rate', 'name' => 'PPh 26 Rate', 'value' => '20.0', 'type' => 'number', 'description' => 'PPh 26 tax rate percentage'],
            ['key' => 'pph_4_2_rate', 'name' => 'PPh 4(2) Rate', 'value' => '0.5', 'type' => 'number', 'description' => 'PPh 4(2) tax rate percentage'],

            // Company Information
            ['key' => 'company_name', 'name' => 'Company Name', 'value' => 'PT Sarange', 'type' => 'string', 'description' => 'Company legal name'],
            ['key' => 'company_npwp', 'name' => 'Company NPWP', 'value' => '', 'type' => 'string', 'description' => 'Company tax identification number'],
            ['key' => 'company_address', 'name' => 'Company Address', 'value' => '', 'type' => 'string', 'description' => 'Company registered address'],
            ['key' => 'company_phone', 'name' => 'Company Phone', 'value' => '', 'type' => 'string', 'description' => 'Company phone number'],
            ['key' => 'company_email', 'name' => 'Company Email', 'value' => '', 'type' => 'string', 'description' => 'Company email address'],

            // Tax Office Information
            ['key' => 'tax_office_code', 'name' => 'Tax Office Code', 'value' => '', 'type' => 'string', 'description' => 'Tax office code'],
            ['key' => 'tax_office_name', 'name' => 'Tax Office Name', 'value' => '', 'type' => 'string', 'description' => 'Tax office name'],
            ['key' => 'tax_office_address', 'name' => 'Tax Office Address', 'value' => '', 'type' => 'string', 'description' => 'Tax office address'],

            // Reporting Settings
            ['key' => 'auto_generate_reports', 'name' => 'Auto Generate Reports', 'value' => 'false', 'type' => 'boolean', 'description' => 'Automatically generate tax reports'],
            ['key' => 'report_due_day', 'name' => 'Report Due Day', 'value' => '20', 'type' => 'number', 'description' => 'Day of month when reports are due'],
            ['key' => 'send_reminders', 'name' => 'Send Reminders', 'value' => 'true', 'type' => 'boolean', 'description' => 'Send reminder notifications'],
            ['key' => 'reminder_days_before', 'name' => 'Reminder Days Before', 'value' => '7', 'type' => 'number', 'description' => 'Days before due date to send reminders'],
        ];

        foreach ($defaultSettings as $setting) {
            self::firstOrCreate(
                ['setting_key' => $setting['key']],
                [
                    'setting_name' => $setting['name'],
                    'setting_value' => $setting['value'],
                    'data_type' => $setting['type'],
                    'description' => $setting['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
