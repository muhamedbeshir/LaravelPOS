<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'options',
        'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'options' => 'array'
    ];

    // الحصول على قيمة إعداد معين
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    // تعيين قيمة إعداد معين
    public static function set($key, $value)
    {
        try {
            \Log::info("Setting::set called", [
                'key' => $key,
                'value' => $value,
                'value_type' => gettype($value),
                'exists' => static::where('key', $key)->exists()
            ]);
            
            $setting = static::firstOrNew(['key' => $key]);
            
            if (!$setting->exists) {
                // Set default values for new settings
                $setting->group = 'general';
                $setting->type = is_bool($value) ? 'boolean' : 'text';
                $setting->is_public = true;
            }
            
            // For boolean values, store as 1 or 0
            if (is_bool($value)) {
                $setting->value = $value ? '1' : '0';
            } else {
                $setting->value = $value;
            }
            
            $saved = $setting->save();
            
            \Log::info("Setting::set result", [
                'key' => $key,
                'saved' => $saved,
                'setting_id' => $setting->id ?? null,
                'final_value' => $setting->value,
                'db_query' => 'INSERT INTO settings (key, value, group, type, is_public, updated_at, created_at) VALUES ("' . 
                    $key . '", "' . $setting->value . '", "' . $setting->group . '", "' . $setting->type . '", ' . ($setting->is_public ? '1' : '0') . ', NOW(), NOW())'
            ]);
            
            return $setting;
        } catch (\Exception $e) {
            \Log::error("Setting::set error", [
                'key' => $key,
                'value' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
} 