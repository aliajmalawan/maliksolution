<?php

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Sanitizer;
use App\Models\ActivityLog;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;

class MenuController extends Controller
{
    public function edit(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $menu = Menu::findBySlug($slug);
        if (!$menu) {
            $this->flashError('Menu not found.');
            $this->redirect('/admin/pages');
        }

        $this->view('admin/menus/edit', [
            'menu' => $menu,
            'items' => MenuItem::forMenu((int) $menu['id']),
            'pages' => Page::allOrdered(),
        ], 'admin/layouts/main');
    }

    public function addItem(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $menu = Menu::findBySlug($slug);
        if (!$menu) {
            $this->flashError('Menu not found.');
            $this->redirect('/admin/pages');
        }

        $label = Sanitizer::text((string) $request->input('label'));
        $linkType = $request->input('link_type') === 'page' ? 'page' : 'custom';
        $target = $request->input('new_tab') === '1' ? '_blank' : '_self';
        $parentIdRaw = $request->input('parent_id');
        $parentId = ($parentIdRaw !== null && $parentIdRaw !== '') ? (int) $parentIdRaw : null;

        if ($linkType === 'page') {
            $pageIdRaw = (string) $request->input('page_id');
            if ($pageIdRaw === 'special:blog') {
                // The blog listing isn't a row in `pages` (it's served by its own
                // route), so Page::find() can't resolve it like a normal CMS
                // page — special-cased so it's still a one-click option instead
                // of forcing admins to discover "Custom URL" and type /blog.
                $url = '/blog';
            } else {
                $page = Page::find((int) $pageIdRaw);
                $url = $page ? '/' . $page['slug'] : '/';
            }
        } else {
            $url = Sanitizer::text((string) $request->input('custom_url')) ?: '/';
        }

        if ($label === '') {
            $this->flashError('Label is required.');
            $this->redirect("/admin/menus/{$slug}/edit");
        }

        MenuItem::create((int) $menu['id'], $parentId, $label, $url, $target);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'create', 'pages', "Added menu item '{$label}' to menu '{$slug}'", $request->ip());

        $this->flashSuccess('Menu item added.');
        $this->redirect("/admin/menus/{$slug}/edit");
    }

    public function editItem(Request $request): void
    {
        $itemId = (int) $request->param('id');
        $item = MenuItem::find($itemId);
        if (!$item) {
            $this->flashError('Menu item not found.');
            $this->redirect('/admin/pages');
        }

        $menu = Menu::find((int) $item['menu_id']);

        // Infer whether the stored URL is an internal page link, to prefill the form correctly.
        $matchedPageId = null;
        if ($item['url'] === '/blog') {
            $matchedPageId = 'special:blog';
        } else {
            foreach (Page::allOrdered() as $p) {
                if ('/' . $p['slug'] === $item['url']) {
                    $matchedPageId = (int) $p['id'];
                    break;
                }
            }
        }

        $this->view('admin/menus/item_edit', [
            'menu' => $menu,
            'item' => $item,
            'items' => MenuItem::forMenu((int) $menu['id']),
            'pages' => Page::allOrdered(),
            'matchedPageId' => $matchedPageId,
        ], 'admin/layouts/main');
    }

    public function updateItem(Request $request): void
    {
        $itemId = (int) $request->param('id');
        $item = MenuItem::find($itemId);
        if (!$item) {
            $this->flashError('Menu item not found.');
            $this->redirect('/admin/pages');
        }

        $menu = Menu::find((int) $item['menu_id']);
        $slug = $menu['slug'] ?? 'primary';

        $label = Sanitizer::text((string) $request->input('label'));
        $linkType = $request->input('link_type') === 'page' ? 'page' : 'custom';
        $target = $request->input('new_tab') === '1' ? '_blank' : '_self';
        $parentIdRaw = $request->input('parent_id');
        $parentId = ($parentIdRaw !== null && $parentIdRaw !== '') ? (int) $parentIdRaw : null;

        // An item can't be its own parent.
        if ($parentId === $itemId) {
            $parentId = null;
        }

        if ($linkType === 'page') {
            $pageIdRaw = (string) $request->input('page_id');
            if ($pageIdRaw === 'special:blog') {
                $url = '/blog';
            } else {
                $page = Page::find((int) $pageIdRaw);
                $url = $page ? '/' . $page['slug'] : '/';
            }
        } else {
            $url = Sanitizer::text((string) $request->input('custom_url')) ?: '/';
        }

        if ($label === '') {
            $this->flashError('Label is required.');
            $this->redirect("/admin/menus/items/{$itemId}/edit");
        }

        MenuItem::update($itemId, $parentId, $label, $url, $target);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'update', 'pages', "Updated menu item '{$label}'", $request->ip());

        $this->flashSuccess('Menu item updated.');
        $this->redirect("/admin/menus/{$slug}/edit");
    }

    public function deleteItem(Request $request): void
    {
        $itemId = (int) $request->param('id');
        $item = MenuItem::find($itemId);
        if (!$item) {
            $this->flashError('Menu item not found.');
            $this->redirect('/admin/pages');
        }

        $menu = Menu::find((int) $item['menu_id']);
        MenuItem::delete($itemId);

        $actor = Auth::user();
        ActivityLog::record($actor['id'] ?? null, 'delete', 'pages', "Deleted menu item #{$itemId}", $request->ip());

        $this->flashSuccess('Menu item deleted.');
        $this->redirect('/admin/menus/' . ($menu['slug'] ?? 'primary') . '/edit');
    }

    public function reorder(Request $request): void
    {
        $slug = (string) $request->param('slug');
        $menu = Menu::findBySlug($slug);
        if (!$menu) {
            $this->json(['ok' => false], 404);
        }

        $orderedIds = $request->input('order', []);
        $orderedIds = is_array($orderedIds) ? array_map('intval', $orderedIds) : [];

        MenuItem::reorder((int) $menu['id'], $orderedIds);

        $this->json(['ok' => true]);
    }
}
