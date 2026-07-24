<?php

namespace App\Services;

use App\Core\Response;
use App\Core\View;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SeoMeta;

class PageRenderer
{
    public static function renderPage(array $page): void
    {
        $sections = PageSection::forPageEnabled((int) $page['id']);

        $sectionsHtml = '';
        foreach ($sections as $section) {
            $type = $section['section_type'];
            $content = PageSection::decodeContent($section['content']);
            $custom = $content['_custom_code'] ?? [];

            if (!empty($custom['enabled']) && (($custom['html'] ?? '') !== '' || ($custom['css'] ?? '') !== '')) {
                $sectionsHtml .= self::renderCustomCode($custom);
                continue;
            }

            if (!is_file(dirname(__DIR__) . '/Views/public/sections/' . $type . '.php')) {
                continue; // section type removed from the registry after content was created
            }
            $sectionsHtml .= View::render('public/sections/' . $type, ['content' => $content, 'page' => $page, 'section' => $section]);
        }

        $seo = SeoMeta::forPage((int) $page['id']) ?? [];

        echo View::render('public/page', [
            'sectionsHtml' => $sectionsHtml,
            'page' => $page,
            'seo' => $seo,
        ], 'public/layouts/main');
    }

    private static function renderCustomCode(array $custom): string
    {
        $html = $custom['html'] ?? '';
        $css = $custom['css'] ?? '';

        $out = '';
        if ($css !== '') {
            $out .= '<style>' . $css . '</style>';
        }
        $out .= $html;
        return $out;
    }

    public static function renderNotFound(): void
    {
        $page = Page::findPublishedBySlug('404');
        if ($page) {
            http_response_code(404);
            self::renderPage($page);
            return;
        }

        Response::abort(404, 'Page not found');
    }
}
