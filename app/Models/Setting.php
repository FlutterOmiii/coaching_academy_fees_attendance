<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public $timestamps = true;

    protected static function booted(): void
    {
        $forget = fn (Setting $setting) => Cache::forget("setting.{$setting->key}");

        static::saved($forget);
        static::deleted($forget);
    }

    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever(
            "setting.{$key}",
            fn () => static::where('key', $key)->value('value') ?? $default
        );
    }

    public static function put(string $key, $value, string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }
}
