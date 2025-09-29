<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibrarySetting extends Model
{
    protected $guarded = [];

    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return $default;
        if ($setting->type === 'json') {
            return json_decode($setting->value, true) ?? $default;
        }
        if ($setting->type === 'integer') return (int) $setting->value;
        if ($setting->type === 'boolean') return filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
        return $setting->value ?? $default;
    }
}
