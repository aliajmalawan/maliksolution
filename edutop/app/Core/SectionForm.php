<?php

namespace App\Core;

/**
 * Generic form renderer + sanitizer driven by config/section_types.php field
 * schemas. One implementation handles every section type (and Settings'
 * simple field groups) instead of hand-written forms per type.
 */
class SectionForm
{
    // ---- Rendering (admin edit form) ----

    public static function renderFields(array $fields, array $values, string $namePrefix = 'content'): string
    {
        $html = '';
        foreach ($fields as $key => $field) {
            $html .= self::renderField($key, $field, $values[$key] ?? null, $namePrefix);
        }
        return $html;
    }

    private static function renderField(string $key, array $field, mixed $value, string $namePrefix): string
    {
        $name = $namePrefix . '[' . $key . ']';
        $id = 'f_' . preg_replace('/[^A-Za-z0-9_]/', '_', $namePrefix . '_' . $key);
        $label = $field['label'] ?? ucfirst($key);

        return match ($field['type']) {
            'text' => self::wrap($label, $id, '<input type="text" class="form-control" id="' . $id . '" name="' . e($name) . '" value="' . e((string) ($value ?? '')) . '">'),
            'textarea' => self::wrap($label, $id, '<textarea class="form-control" id="' . $id . '" name="' . e($name) . '" rows="3">' . e((string) ($value ?? '')) . '</textarea>'),
            'number' => self::wrap($label, $id, '<input type="number" step="any" class="form-control" id="' . $id . '" name="' . e($name) . '" value="' . e((string) ($value ?? '')) . '">'),
            'richtext' => self::wrap($label, $id, self::renderRichText($id, $name, (string) ($value ?? ''))),
            'select' => self::wrap($label, $id, self::renderSelect($id, $name, $field['options'] ?? [], (string) ($value ?? ''))),
            'media' => self::wrap($label, $id, self::renderMedia($id, $name, $value, $field['recommended_size'] ?? null)),
            'checkbox' => self::renderCheckbox($id, $name, $label, (bool) $value),
            'repeater' => self::renderRepeater($key, $field, is_array($value) ? $value : [], $namePrefix),
            default => '',
        };
    }

    private static function wrap(string $label, string $id, string $inputHtml): string
    {
        return '<div class="mb-3"><label class="form-label" for="' . $id . '">' . e($label) . '</label>' . $inputHtml . '</div>';
    }

    private static function renderRichText(string $id, string $name, string $value): string
    {
        return '<div class="quill-editor border rounded" data-quill-for="' . $id . '" style="min-height:180px;background:#fff;">' . $value . '</div>'
            . '<textarea class="d-none" id="' . $id . '" name="' . e($name) . '">' . e($value) . '</textarea>';
    }

    private static function renderSelect(string $id, string $name, array $options, string $selected): string
    {
        $optionsHtml = '';
        foreach ($options as $value => $label) {
            $optionsHtml .= '<option value="' . e((string) $value) . '" ' . ($selected === (string) $value ? 'selected' : '') . '>' . e($label) . '</option>';
        }
        return '<select class="form-select" id="' . $id . '" name="' . e($name) . '">' . $optionsHtml . '</select>';
    }

    /** Standalone media field with a flat (non-nested) field name, for one-off uses outside a section schema. */
    public static function renderStandaloneMedia(string $name, string $label, mixed $value, ?string $recommendedSize = null): string
    {
        $id = 'f_' . preg_replace('/[^A-Za-z0-9_]/', '_', $name);
        return self::wrap($label, $id, self::renderMedia($id, $name, $value, $recommendedSize));
    }

    private static function renderMedia(string $id, string $name, mixed $value, ?string $recommendedSize = null): string
    {
        $mediaId = (int) $value;
        $url = $mediaId > 0 ? media_url($mediaId) : null;

        return '<div class="d-flex align-items-center gap-2 flex-wrap">'
            . '<div class="media-field-preview border rounded d-flex align-items-center justify-content-center" style="width:80px;height:80px;overflow:hidden;background:#f8f9fa;">'
            . ($url ? '<img src="' . e($url) . '" style="max-width:100%;max-height:100%;object-fit:cover;">' : '<span class="text-muted small">None</span>')
            . '</div>'
            . '<input type="hidden" id="' . $id . '" name="' . e($name) . '" value="' . ($mediaId ?: '') . '">'
            . '<label class="btn btn-sm btn-primary mb-0">Upload'
                . '<input type="file" class="d-none" data-media-upload-field data-target="#' . $id . '" data-upload-url="' . e(url('/admin/media/upload')) . '" accept="image/*,application/pdf,video/*">'
            . '</label>'
            . '<button type="button" class="btn btn-sm btn-outline-secondary" data-media-picker data-target="#' . $id . '" data-picker-url="' . e(url('/admin/media/picker')) . '">Choose Existing</button>'
            . '<button type="button" class="btn btn-sm btn-outline-danger" data-media-clear data-target="#' . $id . '">Clear</button>'
            . '<span class="media-upload-status small text-muted w-100"></span>'
            . ($recommendedSize ? '<div class="w-100 form-text mb-0">Recommended size: ' . e($recommendedSize) . '</div>' : '')
            . '</div>';
    }

    private static function renderCheckbox(string $id, string $name, string $label, bool $checked): string
    {
        return '<div class="form-check mb-3"><input type="checkbox" class="form-check-input" id="' . $id . '" name="' . e($name) . '" value="1" ' . ($checked ? 'checked' : '') . '>'
            . '<label class="form-check-label" for="' . $id . '">' . e($label) . '</label></div>';
    }

    private static function renderRepeater(string $key, array $field, array $rows, string $namePrefix): string
    {
        $subFields = $field['fields'];
        $groupName = $namePrefix . '[' . $key . ']';
        $label = $field['label'] ?? ucfirst($key);

        $rowsHtml = '';
        foreach (array_values($rows) as $i => $row) {
            $rowsHtml .= self::renderRepeaterRow($subFields, is_array($row) ? $row : [], $groupName, (string) $i);
        }

        $templateHtml = self::renderRepeaterRow($subFields, [], $groupName, '__INDEX__');

        return '<div class="mb-4">'
            . '<label class="form-label fw-bold">' . e($label) . '</label>'
            . '<div class="repeater" data-repeater-group="' . e($groupName) . '">'
            . '<button type="button" class="btn btn-sm btn-outline-primary mb-2" data-repeater-add>+ Add Item</button>'
            . '<div class="repeater-rows">' . $rowsHtml . '</div>'
            . '<template class="repeater-template">' . $templateHtml . '</template>'
            . '</div>'
            . '</div>';
    }

    private static function renderRepeaterRow(array $subFields, array $row, string $groupName, string $index): string
    {
        return '<div class="repeater-row border rounded p-3 mb-2 bg-light">'
            . '<div class="d-flex justify-content-end gap-1 mb-2">'
            . '<button type="button" class="btn btn-sm btn-outline-secondary" data-repeater-up title="Move up">&uarr;</button>'
            . '<button type="button" class="btn btn-sm btn-outline-secondary" data-repeater-down title="Move down">&darr;</button>'
            . '<button type="button" class="btn btn-sm btn-outline-danger" data-repeater-remove title="Remove">&times;</button>'
            . '</div>'
            . self::renderFields($subFields, $row, $groupName . '[' . $index . ']')
            . '</div>';
    }

    // ---- Sanitizing (on save) ----

    public static function sanitize(array $fields, array $submitted): array
    {
        $result = [];
        foreach ($fields as $key => $field) {
            $result[$key] = self::sanitizeField($field, $submitted[$key] ?? null);
        }
        return $result;
    }

    private static function sanitizeField(array $field, mixed $value): mixed
    {
        return match ($field['type']) {
            'text', 'select' => Sanitizer::text(is_string($value) ? $value : ''),
            'textarea' => Sanitizer::text(is_string($value) ? $value : ''),
            'richtext' => Sanitizer::richText(is_string($value) ? $value : ''),
            'number' => is_numeric($value) ? $value + 0 : null,
            'checkbox' => $value ? 1 : 0,
            'media' => ((int) $value) > 0 ? (int) $value : null,
            'repeater' => self::sanitizeRepeater($field['fields'], is_array($value) ? $value : []),
            default => null,
        };
    }

    private static function sanitizeRepeater(array $subFields, array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $result[] = self::sanitize($subFields, $row);
            }
        }
        return $result;
    }

    // ---- Media usage tracking (for cleanup-on-replace) ----

    /** Walks a schema + its saved content (including inside repeaters) and returns every media ID actually referenced. */
    public static function collectMediaIds(array $fields, array $content): array
    {
        $ids = [];
        foreach ($fields as $key => $field) {
            $value = $content[$key] ?? null;

            if ($field['type'] === 'media') {
                $id = (int) $value;
                if ($id > 0) {
                    $ids[] = $id;
                }
            } elseif ($field['type'] === 'repeater' && is_array($value)) {
                foreach ($value as $row) {
                    if (is_array($row)) {
                        $ids = array_merge($ids, self::collectMediaIds($field['fields'], $row));
                    }
                }
            }
        }
        return $ids;
    }
}
