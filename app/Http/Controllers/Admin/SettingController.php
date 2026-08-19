<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\StorageHelper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Academy-wide branding and the signed-in admin's own profile. Everything the
 * software renders repeatedly — academy name, logo, currency symbol, WhatsApp
 * country code — reads from the cached Setting store, so one save here updates
 * the sidebar, header, login page, receipts and WhatsApp messages everywhere.
 */
class SettingController extends Controller
{
    public function index()
    {
        return view('admin.settings.index', [
            'settings' => [
                'academy_name' => Setting::get('academy_name', 'Cricket Academy'),
                'academy_logo' => Setting::get('academy_logo'),
                'currency_symbol' => Setting::get('currency_symbol', '₹'),
                'whatsapp_country_code' => Setting::get('whatsapp_country_code', '91'),
            ],
        ]);
    }

    /** Academy branding: name, logo, currency, WhatsApp country code. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'academy_name' => 'required|string|max:100',
            'currency_symbol' => 'required|string|max:5',
            'whatsapp_country_code' => 'required|digits_between:1,4',
            'logo' => 'nullable|image|max:2048',
            'remove_logo' => 'nullable|boolean',
        ]);

        Setting::put('academy_name', $data['academy_name']);
        Setting::put('currency_symbol', $data['currency_symbol']);
        Setting::put('whatsapp_country_code', $data['whatsapp_country_code']);

        $currentLogo = Setting::get('academy_logo');

        if ($request->hasFile('logo')) {
            StorageHelper::delete($currentLogo);
            Setting::put('academy_logo', StorageHelper::upload($request->file('logo'), 'branding'));
        } elseif ($request->boolean('remove_logo') && $currentLogo) {
            StorageHelper::delete($currentLogo);
            Setting::put('academy_logo', null);
        }

        return back()->with('success', 'Academy settings saved — the whole software now shows the new branding.');
    }

    /** The signed-in admin's own display name (the "Welcome back" name), email and phone. */
    public function updateProfile(Request $request)
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin->id)],
            'phone' => 'nullable|string|max:20',
        ]);

        $admin->update($data);

        return back()->with('success', 'Profile updated — the dashboard now greets '.explode(' ', $data['name'])[0].'.');
    }
}
