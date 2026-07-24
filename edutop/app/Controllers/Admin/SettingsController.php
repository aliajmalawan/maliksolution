<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Models\ActivityLog;
use App\Models\Setting;

class SettingsController extends Controller
{
    private const GROUPS = [
        'company' => ['site_name', 'tagline', 'logo', 'favicon'],
        'contact' => ['phone', 'email', 'address', 'whatsapp'],
        'social' => ['facebook', 'twitter', 'linkedin', 'instagram', 'youtube'],
        'footer' => ['copyright_text'],
    ];

    /** Per-network "show in header" toggles — independent of the footer, which always shows every filled-in link. */
    private const SOCIAL_HEADER_TOGGLES = ['facebook_header', 'twitter_header', 'linkedin_header', 'instagram_header', 'youtube_header'];

    public function edit(Request $request): void
    {
        $this->view('admin/settings/edit', [
            'company' => Setting::group('company'),
            'contact' => Setting::group('contact'),
            'social' => Setting::group('social'),
            'footer' => Setting::group('footer'),
            'activeTab' => 'general',
        ], 'admin/layouts/main');
    }

    public function update(Request $request): void
    {
        foreach (self::GROUPS as $group => $keys) {
            foreach ($keys as $key) {
                $type = in_array($key, ['logo', 'favicon'], true) ? 'media' : 'text';
                $raw = (string) $request->input($key, '');
                $value = $type === 'media' ? (string) ((int) $raw ?: '') : Sanitizer::text($raw);
                Setting::set($key, $value, $group, $type);
            }
        }

        foreach (self::SOCIAL_HEADER_TOGGLES as $key) {
            Setting::set($key, $request->input($key) === '1' ? '1' : '0', 'social', 'checkbox');
        }

        $this->logAndRedirect($request, 'Updated general settings', '/admin/settings');
    }

    public function editEmail(Request $request): void
    {
        $this->view('admin/settings/email', [
            'email' => Setting::group('email'),
            'activeTab' => 'email',
        ], 'admin/layouts/main');
    }

    public function updateEmail(Request $request): void
    {
        $fields = ['smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'mail_from_address', 'mail_from_name'];
        foreach ($fields as $key) {
            Setting::set($key, Sanitizer::text((string) $request->input($key, '')), 'email');
        }

        // "Leave blank to keep current" — the password is never re-rendered into the form.
        $password = (string) $request->input('smtp_password', '');
        if ($password !== '') {
            Setting::set('smtp_password', $password, 'email');
        }

        $this->logAndRedirect($request, 'Updated email (SMTP) settings', '/admin/settings/email');
    }

    public function editIntegrations(Request $request): void
    {
        $this->view('admin/settings/integrations', [
            'integrations' => Setting::group('integrations'),
            'activeTab' => 'integrations',
        ], 'admin/layouts/main');
    }

    public function updateIntegrations(Request $request): void
    {
        $fields = ['google_analytics_id', 'google_tag_manager_id', 'facebook_pixel_id', 'recaptcha_site_key', 'recaptcha_secret_key'];
        foreach ($fields as $key) {
            Setting::set($key, Sanitizer::text((string) $request->input($key, '')), 'integrations');
        }

        $this->logAndRedirect($request, 'Updated integrations settings', '/admin/settings/integrations');
    }

    public function editTheme(Request $request): void
    {
        $this->view('admin/settings/theme', [
            'theme' => Setting::group('theme'),
            'activeTab' => 'theme',
        ], 'admin/layouts/main');
    }

    private const THEME_COLOR_KEYS = [
        'primary_color', 'secondary_color', 'background_color', 'card_color', 'heading_color', 'text_color',
    ];

    public function updateTheme(Request $request): void
    {
        foreach (self::THEME_COLOR_KEYS as $key) {
            $value = (string) $request->input($key, '');
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
                $this->flashError('Colors must be a valid hex code (e.g. #2D1B7B).');
                $this->redirect('/admin/settings/theme');
            }
        }

        foreach (self::THEME_COLOR_KEYS as $key) {
            Setting::set($key, (string) $request->input($key), 'theme');
        }

        $this->logAndRedirect($request, 'Updated theme settings', '/admin/settings/theme');
    }

    public function editSystem(Request $request): void
    {
        $this->view('admin/settings/system', [
            'system' => Setting::group('system'),
            'activeTab' => 'system',
        ], 'admin/layouts/main');
    }

    public function updateSystem(Request $request): void
    {
        Setting::set('maintenance_mode', $request->input('maintenance_mode') === '1' ? '1' : '0', 'system');
        Setting::set('custom_api_notes', Sanitizer::text((string) $request->input('custom_api_notes', '')), 'system');

        $this->logAndRedirect($request, 'Updated system settings', '/admin/settings/system');
    }

    private function logAndRedirect(Request $request, string $description, string $redirectPath): void
    {
        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'settings', $description, $request->ip());

        $this->flashSuccess('Settings saved.');
        $this->redirect($redirectPath);
    }
}
