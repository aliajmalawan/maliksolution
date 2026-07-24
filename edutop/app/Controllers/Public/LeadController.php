<?php

namespace App\Controllers\Public;

use App\Core\Controller;
use App\Core\Recaptcha;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Core\Validator;
use App\Models\Lead;
use App\Models\PageSection;

class LeadController extends Controller
{
    public function storeContact(Request $request): void
    {
        $this->store($request, 'contact');
    }

    public function storeDemo(Request $request): void
    {
        $this->store($request, 'demo');
    }

    public function storeAdmission(Request $request): void
    {
        $redirectTo = $this->safeRedirectPath((string) $request->input('redirect_to'));

        // Honeypot: hidden field bots auto-fill; humans never see or fill it.
        if (trim((string) $request->input('website')) !== '') {
            $this->redirect($redirectTo);
        }

        if (!Recaptcha::verify($request->input('g-recaptcha-response'))) {
            $this->flashError('Please complete the reCAPTCHA verification.');
            $this->redirect($redirectTo);
        }

        // Field definitions are looked up server-side by section id rather than
        // trusted from the submitted form, so a forged/edited hidden input can't
        // bypass required-field checks or relabel what gets stored.
        $section = PageSection::find((int) $request->input('section_id'));
        $fieldDefs = $section ? (PageSection::decodeContent($section['content'])['fields'] ?? []) : [];

        if (!is_array($fieldDefs) || empty($fieldDefs)) {
            $this->flashError('This form is not available right now. Please contact us directly.');
            $this->redirect($redirectTo);
        }

        $lines = [];
        $contact = ['name' => '', 'email' => '', 'phone' => ''];
        $missingLabel = null;

        foreach ($fieldDefs as $field) {
            $type = $field['field_type'] ?? 'text';
            if ($type === 'heading') {
                continue; // visual-only group break, not a real field
            }

            $key = Sanitizer::fieldKey((string) ($field['name'] ?? ''));
            $label = Sanitizer::text((string) ($field['label'] ?? $key));
            $required = !empty($field['required']);

            if ($type === 'checkbox') {
                $value = $request->input($key) ? 'Yes' : '';
            } else {
                $value = Sanitizer::text((string) $request->input($key));
            }

            if ($required && $value === '' && $missingLabel === null) {
                $missingLabel = $label;
            }

            if ($value !== '') {
                $lines[] = $label . ': ' . $value;
            }

            $mapTo = $field['map_to'] ?? '';
            if (in_array($mapTo, ['name', 'email', 'phone'], true) && $contact[$mapTo] === '') {
                $contact[$mapTo] = $value;
            }
        }

        if ($missingLabel !== null) {
            $this->flashError($missingLabel . ' is required.');
            $this->redirect($redirectTo);
        }

        if ($contact['email'] !== '' && !filter_var($contact['email'], FILTER_VALIDATE_EMAIL)) {
            $this->flashError('Please enter a valid email address.');
            $this->redirect($redirectTo);
        }

        Lead::create([
            'type' => 'admission',
            'name' => $contact['name'] !== '' ? $contact['name'] : 'Admission applicant',
            'email' => $contact['email'],
            'phone' => $contact['phone'] ?: null,
            'school_name' => null,
            'message' => implode("\n", $lines),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->flashSuccess('Your application has been submitted! Our admissions team will contact you shortly. Please bring original documents when visiting the campus.');
        $this->redirect($redirectTo);
    }

    private function store(Request $request, string $type): void
    {
        $redirectTo = $this->safeRedirectPath((string) $request->input('redirect_to'));

        // Honeypot: hidden field bots auto-fill; humans never see or fill it.
        if (trim((string) $request->input('website')) !== '') {
            $this->redirect($redirectTo);
        }

        if (!Recaptcha::verify($request->input('g-recaptcha-response'))) {
            $this->flashError('Please complete the reCAPTCHA verification.');
            $this->redirect($redirectTo);
        }

        $name = Sanitizer::text((string) $request->input('name'));
        $email = trim((string) $request->input('email'));
        $phone = Sanitizer::text((string) $request->input('phone'));
        $schoolName = Sanitizer::text((string) $request->input('school_name'));
        $message = Sanitizer::text((string) $request->input('message'));

        // Optional subject dropdown (contact form) — folded into the message so
        // no schema change is needed and it shows up in the admin Leads view.
        $subject = Sanitizer::text((string) $request->input('subject'));
        if ($subject !== '') {
            $message = trim('[' . $subject . '] ' . $message);
        }

        $validator = new Validator(['name' => $name, 'email' => $email]);
        $validator->required('name')->required('email')->email('email');

        if ($validator->fails()) {
            $this->flashError($validator->firstError());
            $this->redirect($redirectTo);
        }

        Lead::create([
            'type' => $type,
            'name' => $name,
            'email' => $email,
            'phone' => $phone ?: null,
            'school_name' => $schoolName ?: null,
            'message' => $message ?: null,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->flashSuccess($type === 'demo'
            ? "Thanks! Our admissions team will be in touch shortly to schedule your visit."
            : "Thanks for reaching out! We'll get back to you soon.");
        $this->redirect($redirectTo);
    }

    /** Only allow same-site relative paths, blocking open-redirect via an absolute/protocol-relative URL. */
    private function safeRedirectPath(string $path): string
    {
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || str_contains($path, '://')) {
            return '/';
        }
        return $path;
    }
}
