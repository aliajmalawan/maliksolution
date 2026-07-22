<?php
declare(strict_types=1);

/**
 * System Settings module — shared init.
 * Institute profile, academic defaults, finance defaults and branding,
 * stored in ums_settings (name/value). These flow into transcripts,
 * challans, salary slips and the wider system via ums_setting().
 * Restricted to super_admin / admin.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/crud.php';
require_login(['super_admin', 'admin']);

$user = ums_user();

ums_db()->query('CREATE TABLE IF NOT EXISTS ' . tbl('settings') . ' (
    name VARCHAR(100) PRIMARY KEY,
    value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

/**
 * Editable settings, grouped for the form.
 * key => [label, type(text|email|number|textarea), placeholder]
 */
function ums_setting_groups(): array
{
    return [
        'Institute Profile' => [
            'institute_name'    => ['Institute Name', 'text', 'e.g. Malik University'],
            'institute_short'   => ['Short Name / Abbreviation', 'text', 'e.g. MU'],
            'institute_email'   => ['Email', 'email', 'info@institute.edu'],
            'institute_phone'   => ['Phone', 'text', '+92 xx xxxxxxx'],
            'institute_website' => ['Website', 'text', 'https://…'],
            'institute_address' => ['Address', 'textarea', 'Campus postal address'],
        ],
        'Academic' => [
            'current_session'  => ['Current Session', 'text', 'e.g. Fall 2026'],
            'academic_year'    => ['Academic Year', 'text', 'e.g. 2026–2027'],
        ],
        'Finance' => [
            'currency'      => ['Currency Symbol', 'text', 'Rs'],
            'fee_due_days'  => ['Default Fee Due (days)', 'number', '15'],
        ],
    ];
}

/** Sensible defaults per key. */
function ums_setting_default(mysqli $db, string $key): string
{
    return match ($key) {
        'institute_name'  => ums_inst_name($db),
        'institute_short' => UMS_NAME,
        'currency'        => 'Rs',
        'fee_due_days'    => '15',
        'current_session' => 'Fall 2026',
        default           => '',
    };
}

function set_url(string $path = ''): string
{
    return UMS_URL . '/modules/settings/' . $path;
}
