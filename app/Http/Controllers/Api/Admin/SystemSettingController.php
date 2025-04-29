<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class SystemSettingController extends Controller
{
    /**
     * Display a listing of the system settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $settings = SystemSetting::all();
        return response()->json(['data' => $settings], 200);
    }

    /**
     * Get settings by group.
     *
     * @param  string  $group
     * @return \Illuminate\Http\Response
     */
    public function getByGroup($group)
    {
        $settings = SystemSetting::where('setting_group', $group)->get();
        return response()->json(['data' => $settings], 200);
    }

    /**
     * Get inventory settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function getInventorySettings()
    {
        $settings = SystemSetting::where('setting_group', 'inventory')->get();
        
        $formatted = [];
        foreach ($settings as $setting) {
            // Convert string boolean values to actual booleans
            if ($setting->setting_value === 'true') {
                $value = true;
            } elseif ($setting->setting_value === 'false') {
                $value = false;
            } else {
                $value = $setting->setting_value;
            }
            
            $formatted[$setting->setting_key] = $value;
        }
        
        return response()->json(['data' => $formatted], 200);
    }

    /**
     * Update a system setting.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'setting_key' => 'required|string|exists:system_settings,setting_key',
            'setting_value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $setting = SystemSetting::where('setting_key', $request->setting_key)->first();
        $setting->setting_value = $request->setting_value;
        $setting->save();
        
        // Update cache
        Cache::put("setting_{$request->setting_key}", $request->setting_value, 3600);

        return response()->json(['message' => 'Setting updated successfully', 'data' => $setting], 200);
    }

    /**
     * Update multiple system settings at once.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.setting_key' => 'required|string|exists:system_settings,setting_key',
            'settings.*.setting_value' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updatedSettings = [];

        foreach ($request->settings as $settingData) {
            $setting = SystemSetting::where('setting_key', $settingData['setting_key'])->first();
            $setting->setting_value = $settingData['setting_value'];
            $setting->save();
            
            // Update cache
            Cache::put("setting_{$settingData['setting_key']}", $settingData['setting_value'], 3600);
            
            $updatedSettings[] = $setting;
        }

        return response()->json([
            'message' => count($updatedSettings) . ' settings updated successfully',
            'data' => $updatedSettings
        ], 200);
    }

    /**
     * Update inventory settings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateInventorySettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enforce_stock_validation' => 'required|boolean',
            'allow_negative_stock' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update settings
        $settings = [
            [
                'setting_key' => 'inventory_enforce_stock_validation',
                'setting_value' => $request->enforce_stock_validation ? 'true' : 'false'
            ],
            [
                'setting_key' => 'inventory_allow_negative_stock',
                'setting_value' => $request->allow_negative_stock ? 'true' : 'false'
            ]
        ];

        $updatedSettings = [];

        foreach ($settings as $settingData) {
            $setting = SystemSetting::where('setting_key', $settingData['setting_key'])->first();
            if ($setting) {
                $setting->setting_value = $settingData['setting_value'];
                $setting->save();
                
                // Update cache
                Cache::put("setting_{$settingData['setting_key']}", $settingData['setting_value'], 3600);
                
                $updatedSettings[] = $setting;
            }
        }

        return response()->json([
            'message' => 'Inventory settings updated successfully',
            'data' => [
                'enforce_stock_validation' => $request->enforce_stock_validation,
                'allow_negative_stock' => $request->allow_negative_stock
            ]
        ], 200);
    }
}