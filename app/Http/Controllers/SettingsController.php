<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Display settings page
     */
    public function index()
    {
        $settings = Setting::getAllSettings();
        return view('settings.index', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        // Handle invoice logo upload / removal separately
        if ($request->hasFile('invoice_logo')) {
            $oldLogo = Setting::get('invoice_logo');
            if ($oldLogo && str_starts_with($oldLogo, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLogo));
            }
            $path = $request->file('invoice_logo')->store('logos', 'public');
            Setting::set('invoice_logo', Storage::url($path));
        } elseif ($request->boolean('remove_invoice_logo')) {
            $oldLogo = Setting::get('invoice_logo');
            if ($oldLogo && str_starts_with($oldLogo, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLogo));
            }
            Setting::set('invoice_logo', '');
        }

        // Handle system logo (top navbar / sidebar) upload / removal separately
        if ($request->hasFile('system_logo')) {
            $request->validate([
                'system_logo' => ['image', 'mimes:jpeg,jpg,png,gif,svg,webp', 'max:2048'],
            ]);
            $oldLogo = Setting::get('system_logo');
            if ($oldLogo && str_starts_with($oldLogo, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLogo));
            }
            $path = $request->file('system_logo')->store('logos', 'public');
            Setting::set('system_logo', Storage::url($path));
        } elseif ($request->boolean('remove_system_logo')) {
            $oldLogo = Setting::get('system_logo');
            if ($oldLogo && str_starts_with($oldLogo, '/storage/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldLogo));
            }
            Setting::set('system_logo', '');
        }

        $settingKeys = [
            'app_name', 'currency', 'timezone',
            'store_name', 'store_email', 'store_phone', 'store_address',
            'tax_rate', 'discount_enabled', 'invoice_prefix',
            // Invoice / Receipt settings
            'invoice_shop_name', 'invoice_tagline', 'invoice_address',
            'invoice_phone', 'invoice_email', 'invoice_thank_you',
            'invoice_footer_line1', 'invoice_footer_line2', 'invoice_footer_line3',
            // Barcode settings
            'barcode_bar_width', 'barcode_bar_height',
            'barcode_name_font_size', 'barcode_price_font_size',
        ];

        $checkboxKeys = ['discount_enabled'];

        foreach ($settingKeys as $key) {
            if (in_array($key, $checkboxKeys)) {
                Setting::set($key, $request->has($key) ? '1' : '0');
            } else {
                if ($request->has($key)) {
                    Setting::set($key, $request->input($key));
                }
            }
        }

        return back()->with('success', 'Settings updated successfully');
    }

    /**
     * Change username (email) for the logged-in admin user
     */
    public function changeUsername(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'new_email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class, 'email')->ignore($user->id),
            ],
            'new_email_confirmation' => ['required', 'same:new_email'],
        ], [
            'new_email.unique' => 'This email is already taken by another user.',
            'new_email_confirmation.same' => 'The email confirmation does not match.',
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password_username' => 'The current password is incorrect.'])->withInput();
        }

        $user->email = $request->new_email;
        $user->email_verified_at = null;
        $user->save();

        return back()->with('success', 'Username (email) updated successfully.');
    }
}
