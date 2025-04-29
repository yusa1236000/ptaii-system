<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';
    protected $primaryKey = 'setting_id';
    
    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_group',
        'description'
    ];

    /**
     * Get setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue($key, $default = null)
    {
        // Try to get from cache first
        $cacheKey = "setting_{$key}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        // Get from database
        $setting = self::where('setting_key', $key)->first();
        
        if (!$setting) {
            return $default;
        }
        
        // Cache the result for future use
        Cache::put($cacheKey, $setting->setting_value, 3600); // Cache for 1 hour
        
        return $setting->setting_value;
    }

    /**
     * Update setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $group
     * @param string|null $description
     * @return bool
     */
    public static function updateValue($key, $value, $group = null, $description = null)
    {
        $setting = self::firstOrNew(['setting_key' => $key]);
        $setting->setting_value = $value;
        
        if ($group) {
            $setting->setting_group = $group;
        }
        
        if ($description) {
            $setting->description = $description;
        }
        
        $result = $setting->save();
        
        // Update cache
        if ($result) {
            Cache::put("setting_{$key}", $value, 3600);
        }
        
        return $result;
    }
}