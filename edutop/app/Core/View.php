<?php

namespace App\Core;

class View
{
    private static function viewsPath(): string
    {
        return dirname(__DIR__) . '/Views';
    }

    /**
     * Render a view file, optionally wrapped in a layout. Inside the layout,
     * $content holds the rendered inner view's HTML.
     */
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::renderFile($view, $data);

        if ($layout !== null) {
            $data['content'] = $content;
            return self::renderFile($layout, $data);
        }

        return $content;
    }

    private static function renderFile(string $view, array $data): string
    {
        $path = self::viewsPath() . '/' . $view . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException("View not found: {$view}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
