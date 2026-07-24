<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Core\SectionForm;
use App\Core\Validator;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SeoMeta;

class PageController extends Controller
{
    public function index(Request $request): void
    {
        $pages = Page::allOrdered();
        foreach ($pages as &$page) {
            $page['section_count'] = count(PageSection::forPage((int) $page['id']));
        }

        $this->view('admin/pages/index', ['pages' => $pages], 'admin/layouts/main');
    }

    public function create(Request $request): void
    {
        $this->view('admin/pages/create', [], 'admin/layouts/main');
    }

    public function store(Request $request): void
    {
        $title = Sanitizer::text((string) $request->input('title'));
        $slugInput = (string) $request->input('slug');
        $slug = Sanitizer::slug($slugInput !== '' ? $slugInput : $title);

        $validator = new Validator(['title' => $title, 'slug' => $slug]);
        $validator->required('title')->required('slug');

        if (!$validator->fails() && Page::slugExists($slug)) {
            $this->flashError('That slug is already in use.');
            $this->redirect('/admin/pages/create');
        }

        if ($validator->fails()) {
            $this->flashError($validator->firstError());
            $this->redirect('/admin/pages/create');
        }

        $id = Page::create($slug, $title, 'draft');

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'create', 'pages', "Created page '{$title}' (/{$slug})", $request->ip());

        $this->flashSuccess('Page created. Add sections to bring it to life.');
        $this->redirect("/admin/pages/{$id}/sections");
    }

    public function reorder(Request $request): void
    {
        $orderedIds = $request->input('order', []);
        $orderedIds = is_array($orderedIds) ? array_map('intval', $orderedIds) : [];

        Page::reorder($orderedIds);

        $this->json(['ok' => true]);
    }

    public function delete(Request $request): void
    {
        $id = (int) $request->param('id');
        $page = Page::find($id);
        if (!$page) {
            $this->flashError('Page not found.');
            $this->redirect('/admin/pages');
        }

        if (!empty($page['is_home'])) {
            $this->flashError('The home page cannot be deleted.');
            $this->redirect('/admin/pages');
        }

        Page::delete($id);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'pages', "Deleted page '{$page['title']}' (/{$page['slug']})", $request->ip());

        $this->flashSuccess("Page '{$page['title']}' deleted.");
        $this->redirect('/admin/pages');
    }

    public function edit(Request $request): void
    {
        $page = $this->findPageOr404($request);

        $this->view('admin/pages/edit', [
            'page' => $page,
            'activeTab' => 'settings',
        ], 'admin/layouts/main');
    }

    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        $page = Page::find($id);
        if (!$page) {
            $this->flashError('Page not found.');
            $this->redirect('/admin/pages');
        }

        $title = Sanitizer::text((string) $request->input('title'));
        $slug = Sanitizer::slug((string) $request->input('slug'));
        $status = $request->input('status') === 'published' ? 'published' : 'draft';

        $validator = new Validator(['title' => $title, 'slug' => $slug]);
        $validator->required('title')->required('slug');

        if (!$validator->fails() && Page::slugExists($slug, $id)) {
            $this->flashError('That slug is already in use by another page.');
            $this->redirect("/admin/pages/{$id}/edit");
        }

        if ($validator->fails()) {
            $this->flashError($validator->firstError());
            $this->redirect("/admin/pages/{$id}/edit");
        }

        Page::update($id, $slug, $title, $status);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'pages', "Updated page #{$id} settings", $request->ip());

        $this->flashSuccess('Page settings saved.');
        $this->redirect("/admin/pages/{$id}/edit");
    }

    public function seo(Request $request): void
    {
        $page = $this->findPageOr404($request);
        $seo = SeoMeta::forPage((int) $page['id']) ?? [];

        $this->view('admin/pages/seo', [
            'page' => $page,
            'seo' => $seo,
            'activeTab' => 'seo',
        ], 'admin/layouts/main');
    }

    public function updateSeo(Request $request): void
    {
        $id = (int) $request->param('id');
        $page = Page::find($id);
        if (!$page) {
            $this->flashError('Page not found.');
            $this->redirect('/admin/pages');
        }

        $schemaMarkup = trim((string) $request->input('schema_markup'));
        if ($schemaMarkup !== '' && json_decode($schemaMarkup) === null) {
            $this->flashError('Schema Markup must be valid JSON (or left blank).');
            $this->redirect("/admin/pages/{$id}/seo");
        }

        SeoMeta::upsert($id, [
            'seo_title' => Sanitizer::text((string) $request->input('seo_title')) ?: null,
            'meta_description' => Sanitizer::text((string) $request->input('meta_description')) ?: null,
            'meta_keywords' => Sanitizer::text((string) $request->input('meta_keywords')) ?: null,
            'canonical_url' => Sanitizer::text((string) $request->input('canonical_url')) ?: null,
            'og_title' => Sanitizer::text((string) $request->input('og_title')) ?: null,
            'og_description' => Sanitizer::text((string) $request->input('og_description')) ?: null,
            'og_image' => ((int) $request->input('og_image')) ?: null,
            'twitter_card' => in_array($request->input('twitter_card'), ['summary', 'summary_large_image'], true) ? $request->input('twitter_card') : 'summary_large_image',
            'robots' => Sanitizer::text((string) $request->input('robots')) ?: 'index,follow',
            'schema_markup' => $schemaMarkup ?: null,
        ]);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'pages', "Updated SEO for page #{$id}", $request->ip());

        $this->flashSuccess('SEO settings saved.');
        $this->redirect("/admin/pages/{$id}/seo");
    }

    public function sections(Request $request): void
    {
        $page = $this->findPageOr404($request);
        $sectionTypes = require dirname(__DIR__, 3) . '/config/section_types.php';

        $sections = PageSection::forPage((int) $page['id']);
        foreach ($sections as &$section) {
            $section['type_label'] = $sectionTypes[$section['section_type']]['label'] ?? $section['section_type'];
        }

        $this->view('admin/pages/sections', [
            'page' => $page,
            'sections' => $sections,
            'sectionTypes' => $sectionTypes,
            'activeTab' => 'sections',
        ], 'admin/layouts/main');
    }

    public function addSection(Request $request): void
    {
        $pageId = (int) $request->param('id');
        $page = Page::find($pageId);
        if (!$page) {
            $this->flashError('Page not found.');
            $this->redirect('/admin/pages');
        }

        $sectionTypes = require dirname(__DIR__, 3) . '/config/section_types.php';
        $type = (string) $request->input('section_type');

        if (!isset($sectionTypes[$type])) {
            $this->flashError('Unknown section type.');
            $this->redirect("/admin/pages/{$pageId}/sections");
        }

        $sectionId = PageSection::create($pageId, $type, []);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'create', 'pages', "Added '{$type}' section to page #{$pageId}", $request->ip());

        $this->redirect("/admin/pages/{$pageId}/sections/{$sectionId}/edit");
    }

    public function editSection(Request $request): void
    {
        [$page, $section, $schema] = $this->findSectionOr404($request);
        $content = PageSection::decodeContent($section['content']);

        // The current design's actual rendered markup — used to pre-fill the
        // "Custom Design (Code)" panel so admins can tweak real classes/ids
        // instead of starting from a blank textarea.
        $renderedHtml = '';
        $templatePath = dirname(__DIR__, 2) . '/Views/public/sections/' . $section['section_type'] . '.php';
        if (is_file($templatePath)) {
            $renderedContent = $content;
            unset($renderedContent['_custom_code']);
            $renderedHtml = trim(\App\Core\View::render('public/sections/' . $section['section_type'], ['content' => $renderedContent]));
        }

        $this->view('admin/pages/section_edit', [
            'page' => $page,
            'section' => $section,
            'schema' => $schema,
            'content' => $content,
            'renderedHtml' => $renderedHtml,
        ], 'admin/layouts/main');
    }

    public function updateSection(Request $request): void
    {
        [$page, $section, $schema] = $this->findSectionOr404($request);

        // Snapshot which media this section referenced *before* the update,
        // so we can tell afterward which images were actually replaced/removed.
        $oldContent = PageSection::decodeContent($section['content']);
        $oldMediaIds = SectionForm::collectMediaIds($schema['fields'], $oldContent);

        if ($request->input('custom_code_only')) {
            // The "Custom Design (Code)" form only touches _custom_code —
            // leave the schema-driven fields exactly as they already are.
            $sanitized = $oldContent;
        } else {
            $submitted = $request->input('content', []);
            $sanitized = SectionForm::sanitize($schema['fields'], is_array($submitted) ? $submitted : []);
        }

        $customSubmitted = $request->input('custom_code');
        if (is_array($customSubmitted)) {
            $sanitized['_custom_code'] = [
                'enabled' => !empty($customSubmitted['enabled']),
                'html' => Sanitizer::customCode((string) ($customSubmitted['html'] ?? '')),
                'css' => Sanitizer::customCss((string) ($customSubmitted['css'] ?? '')),
            ];
        } elseif (isset($oldContent['_custom_code'])) {
            // Main section form doesn't submit custom_code[...] at all — preserve it.
            $sanitized['_custom_code'] = $oldContent['_custom_code'];
        }

        PageSection::updateContent((int) $section['id'], $sanitized);

        $actor = Auth::user();

        // Cleanup-on-replace: any image this section stopped referencing gets
        // deleted for real (file + Media Library record), but only if it's
        // not still used anywhere else on the site — checked *after* saving,
        // so this section's own now-updated content can't cause a false
        // "still in use" positive.
        $newMediaIds = SectionForm::collectMediaIds($schema['fields'], $sanitized);
        foreach (array_diff($oldMediaIds, $newMediaIds) as $removedMediaId) {
            if (!Media::isUsedElsewhere($removedMediaId)) {
                Media::deleteFileAndRecord($removedMediaId);
                ActivityLog::record($actor['id'] ?? null, 'delete', 'media', "Auto-deleted unused media #{$removedMediaId} (replaced in section #{$section['id']})", $request->ip());
            }
        }

        ActivityLog::record($actor['id'] ?? null, 'update', 'pages', "Updated section #{$section['id']} on page #{$page['id']}", $request->ip());

        $this->flashSuccess('Section saved.');
        $this->redirect("/admin/pages/{$page['id']}/sections");
    }

    public function toggleSection(Request $request): void
    {
        $sectionId = (int) $request->param('sectionId');
        $pageId = (int) $request->param('id');

        PageSection::toggle($sectionId);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'pages', "Toggled section #{$sectionId}", $request->ip());

        $this->redirect("/admin/pages/{$pageId}/sections");
    }

    public function deleteSection(Request $request): void
    {
        $sectionId = (int) $request->param('sectionId');
        $pageId = (int) $request->param('id');

        PageSection::delete($sectionId);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'pages', "Deleted section #{$sectionId}", $request->ip());

        $this->flashSuccess('Section deleted.');
        $this->redirect("/admin/pages/{$pageId}/sections");
    }

    public function reorderSections(Request $request): void
    {
        $pageId = (int) $request->param('id');
        $orderedIds = $request->input('order', []);
        $orderedIds = is_array($orderedIds) ? array_map('intval', $orderedIds) : [];

        PageSection::reorder($pageId, $orderedIds);

        $this->json(['ok' => true]);
    }

    private function findPageOr404(Request $request): array
    {
        $id = (int) $request->param('id');
        $page = Page::find($id);
        if (!$page) {
            $this->flashError('Page not found.');
            $this->redirect('/admin/pages');
        }
        return $page;
    }

    /** @return array{0: array, 1: array, 2: array} [page, section, sectionTypeSchema] */
    private function findSectionOr404(Request $request): array
    {
        $page = $this->findPageOr404($request);
        $sectionId = (int) $request->param('sectionId');
        $section = PageSection::find($sectionId);

        if (!$section || (int) $section['page_id'] !== (int) $page['id']) {
            $this->flashError('Section not found.');
            $this->redirect("/admin/pages/{$page['id']}/sections");
        }

        $sectionTypes = require dirname(__DIR__, 3) . '/config/section_types.php';
        $schema = $sectionTypes[$section['section_type']] ?? null;

        if (!$schema) {
            $this->flashError('This section type no longer exists.');
            $this->redirect("/admin/pages/{$page['id']}/sections");
        }

        return [$page, $section, $schema];
    }
}
