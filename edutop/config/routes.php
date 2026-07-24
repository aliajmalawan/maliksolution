<?php

/** @var \App\Core\Router $router */

// ---- Public site ----
$router->get('/', 'Public\HomeController@index');

// ---- Admin authentication ----
$router->get('/admin/login', 'Admin\AuthController@showLogin');
$router->post('/admin/login', 'Admin\AuthController@login', ['CsrfMiddleware']);
$router->post('/admin/logout', 'Admin\AuthController@logout', ['AuthMiddleware', 'CsrfMiddleware']);

// ---- Admin dashboard ----
$router->get('/admin/dashboard', 'Admin\DashboardController@index', ['AuthMiddleware']);
$router->get('/admin/analytics', 'Admin\AnalyticsController@index', ['AuthMiddleware']);

// ---- Admin profile (any authenticated user) ----
$router->get('/admin/profile', 'Admin\ProfileController@edit', ['AuthMiddleware']);
$router->post('/admin/profile', 'Admin\ProfileController@update', ['AuthMiddleware', 'CsrfMiddleware']);

// ---- Admin user management ----
$router->get('/admin/users', 'Admin\UserController@index', ['RoleMiddleware:users.view']);
$router->get('/admin/users/create', 'Admin\UserController@create', ['RoleMiddleware:users.manage']);
$router->post('/admin/users', 'Admin\UserController@store', ['RoleMiddleware:users.manage', 'CsrfMiddleware']);
$router->get('/admin/users/:id/edit', 'Admin\UserController@edit', ['RoleMiddleware:users.view']);
$router->post('/admin/users/:id', 'Admin\UserController@update', ['RoleMiddleware:users.manage', 'CsrfMiddleware']);
$router->post('/admin/users/:id/delete', 'Admin\UserController@destroy', ['RoleMiddleware:users.manage', 'CsrfMiddleware']);

// ---- Admin roles & permissions ----
$router->get('/admin/roles', 'Admin\RoleController@index', ['RoleMiddleware:roles.view']);
$router->get('/admin/roles/matrix', 'Admin\RoleController@matrix', ['RoleMiddleware:roles.view']);
$router->get('/admin/roles/:id/edit', 'Admin\RoleController@edit', ['RoleMiddleware:roles.view']);
$router->post('/admin/roles/:id', 'Admin\RoleController@update', ['RoleMiddleware:roles.manage', 'CsrfMiddleware']);

// ---- Admin pages / section builder / SEO ----
$router->get('/admin/pages', 'Admin\PageController@index', ['RoleMiddleware:pages.view']);
$router->get('/admin/pages/create', 'Admin\PageController@create', ['RoleMiddleware:pages.manage']);
$router->post('/admin/pages', 'Admin\PageController@store', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->post('/admin/pages/reorder', 'Admin\PageController@reorder', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->get('/admin/pages/:id/edit', 'Admin\PageController@edit', ['RoleMiddleware:pages.view']);
$router->post('/admin/pages/:id/delete', 'Admin\PageController@delete', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->post('/admin/pages/:id', 'Admin\PageController@update', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->get('/admin/pages/:id/seo', 'Admin\PageController@seo', ['RoleMiddleware:pages.view']);
$router->post('/admin/pages/:id/seo', 'Admin\PageController@updateSeo', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->get('/admin/pages/:id/sections', 'Admin\PageController@sections', ['RoleMiddleware:pages.view']);
$router->post('/admin/pages/:id/sections', 'Admin\PageController@addSection', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->post('/admin/pages/:id/sections/reorder', 'Admin\PageController@reorderSections', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->get('/admin/pages/:id/sections/:sectionId/edit', 'Admin\PageController@editSection', ['RoleMiddleware:pages.view']);
$router->post('/admin/pages/:id/sections/:sectionId', 'Admin\PageController@updateSection', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->post('/admin/pages/:id/sections/:sectionId/toggle', 'Admin\PageController@toggleSection', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->post('/admin/pages/:id/sections/:sectionId/delete', 'Admin\PageController@deleteSection', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);

// ---- Admin media library ----
$router->get('/admin/media', 'Admin\MediaController@index', ['RoleMiddleware:media.view']);
$router->get('/admin/media/picker', 'Admin\MediaController@picker', ['RoleMiddleware:media.view']);
$router->post('/admin/media/upload', 'Admin\MediaController@upload', ['RoleMiddleware:media.manage', 'CsrfMiddleware']);
$router->post('/admin/media/:id/replace', 'Admin\MediaController@replace', ['RoleMiddleware:media.manage', 'CsrfMiddleware']);
$router->post('/admin/media/:id/resize', 'Admin\MediaController@resize', ['RoleMiddleware:media.manage', 'CsrfMiddleware']);
$router->post('/admin/media/:id/toggle-hidden', 'Admin\MediaController@toggleHidden', ['RoleMiddleware:media.manage', 'CsrfMiddleware']);
$router->post('/admin/media/:id/delete', 'Admin\MediaController@delete', ['RoleMiddleware:media.manage', 'CsrfMiddleware']);
$router->post('/admin/media/folders', 'Admin\MediaController@createFolder', ['RoleMiddleware:media.manage', 'CsrfMiddleware']);
$router->post('/admin/media/folders/:id/delete', 'Admin\MediaController@deleteFolder', ['RoleMiddleware:media.manage', 'CsrfMiddleware']);

// ---- Admin menus ----
$router->get('/admin/menus/:slug/edit', 'Admin\MenuController@edit', ['RoleMiddleware:pages.view']);
$router->post('/admin/menus/:slug/items', 'Admin\MenuController@addItem', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->post('/admin/menus/:slug/reorder', 'Admin\MenuController@reorder', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->get('/admin/menus/items/:id/edit', 'Admin\MenuController@editItem', ['RoleMiddleware:pages.view']);
$router->post('/admin/menus/items/:id', 'Admin\MenuController@updateItem', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);
$router->post('/admin/menus/items/:id/delete', 'Admin\MenuController@deleteItem', ['RoleMiddleware:pages.manage', 'CsrfMiddleware']);

// ---- Admin settings ----
$router->get('/admin/settings', 'Admin\SettingsController@edit', ['RoleMiddleware:settings.manage']);
$router->post('/admin/settings', 'Admin\SettingsController@update', ['RoleMiddleware:settings.manage', 'CsrfMiddleware']);
$router->get('/admin/settings/email', 'Admin\SettingsController@editEmail', ['RoleMiddleware:settings.manage']);
$router->post('/admin/settings/email', 'Admin\SettingsController@updateEmail', ['RoleMiddleware:settings.manage', 'CsrfMiddleware']);
$router->get('/admin/settings/integrations', 'Admin\SettingsController@editIntegrations', ['RoleMiddleware:settings.manage']);
$router->post('/admin/settings/integrations', 'Admin\SettingsController@updateIntegrations', ['RoleMiddleware:settings.manage', 'CsrfMiddleware']);
$router->get('/admin/settings/theme', 'Admin\SettingsController@editTheme', ['RoleMiddleware:settings.manage']);
$router->post('/admin/settings/theme', 'Admin\SettingsController@updateTheme', ['RoleMiddleware:settings.manage', 'CsrfMiddleware']);
$router->get('/admin/settings/system', 'Admin\SettingsController@editSystem', ['RoleMiddleware:settings.manage']);
$router->post('/admin/settings/system', 'Admin\SettingsController@updateSystem', ['RoleMiddleware:settings.manage', 'CsrfMiddleware']);

// ---- Admin blog: posts, SEO, categories, tags, comments ----
$router->get('/admin/blog/posts', 'Admin\BlogPostController@index', ['RoleMiddleware:blog.view']);
$router->get('/admin/blog/posts/create', 'Admin\BlogPostController@create', ['RoleMiddleware:blog.manage']);
$router->post('/admin/blog/posts', 'Admin\BlogPostController@store', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);
$router->get('/admin/blog/posts/:id/edit', 'Admin\BlogPostController@edit', ['RoleMiddleware:blog.view']);
$router->post('/admin/blog/posts/:id', 'Admin\BlogPostController@update', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);
$router->post('/admin/blog/posts/:id/delete', 'Admin\BlogPostController@destroy', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);
$router->get('/admin/blog/posts/:id/seo', 'Admin\BlogPostController@seo', ['RoleMiddleware:blog.view']);
$router->post('/admin/blog/posts/:id/seo', 'Admin\BlogPostController@updateSeo', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);

$router->get('/admin/blog/categories', 'Admin\BlogCategoryController@index', ['RoleMiddleware:blog.view']);
$router->post('/admin/blog/categories', 'Admin\BlogCategoryController@store', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);
$router->post('/admin/blog/categories/:id/delete', 'Admin\BlogCategoryController@destroy', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);

$router->get('/admin/blog/tags', 'Admin\BlogTagController@index', ['RoleMiddleware:blog.view']);
$router->post('/admin/blog/tags', 'Admin\BlogTagController@store', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);
$router->post('/admin/blog/tags/:id/delete', 'Admin\BlogTagController@destroy', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);

$router->get('/admin/blog/comments', 'Admin\BlogCommentController@index', ['RoleMiddleware:blog.view']);
$router->post('/admin/blog/comments/:id/approve', 'Admin\BlogCommentController@approve', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);
$router->post('/admin/blog/comments/:id/spam', 'Admin\BlogCommentController@spam', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);
$router->post('/admin/blog/comments/:id/delete', 'Admin\BlogCommentController@destroy', ['RoleMiddleware:blog.manage', 'CsrfMiddleware']);

// ---- Admin leads ----
$router->get('/admin/leads', 'Admin\LeadController@index', ['RoleMiddleware:leads.view']);
$router->get('/admin/leads/:id', 'Admin\LeadController@show', ['RoleMiddleware:leads.view']);
$router->post('/admin/leads/:id/status', 'Admin\LeadController@updateStatus', ['RoleMiddleware:leads.manage', 'CsrfMiddleware']);
$router->post('/admin/leads/:id/delete', 'Admin\LeadController@destroy', ['RoleMiddleware:leads.manage', 'CsrfMiddleware']);

// ---- Admin activity/login log viewers ----
$router->get('/admin/logs/activity', 'Admin\LogController@activity', ['RoleMiddleware:logs.view']);
$router->get('/admin/logs/logins', 'Admin\LogController@logins', ['RoleMiddleware:logs.view']);

// ---- Admin file manager (sandboxed to storage/files/) ----
$router->get('/admin/files', 'Admin\FileManagerController@index', ['RoleMiddleware:files.manage']);
$router->post('/admin/files/upload', 'Admin\FileManagerController@upload', ['RoleMiddleware:files.manage', 'CsrfMiddleware']);
$router->post('/admin/files/folder', 'Admin\FileManagerController@createFolder', ['RoleMiddleware:files.manage', 'CsrfMiddleware']);
$router->post('/admin/files/rename', 'Admin\FileManagerController@rename', ['RoleMiddleware:files.manage', 'CsrfMiddleware']);
$router->post('/admin/files/delete', 'Admin\FileManagerController@delete', ['RoleMiddleware:files.manage', 'CsrfMiddleware']);
$router->get('/admin/files/download', 'Admin\FileManagerController@download', ['RoleMiddleware:files.manage']);

// ---- Admin backups ----
$router->get('/admin/backups', 'Admin\BackupController@index', ['RoleMiddleware:backups.manage']);
$router->post('/admin/backups/database', 'Admin\BackupController@createDatabase', ['RoleMiddleware:backups.manage', 'CsrfMiddleware']);
$router->post('/admin/backups/files', 'Admin\BackupController@createFiles', ['RoleMiddleware:backups.manage', 'CsrfMiddleware']);
$router->get('/admin/backups/download', 'Admin\BackupController@download', ['RoleMiddleware:backups.manage']);
$router->post('/admin/backups/delete', 'Admin\BackupController@delete', ['RoleMiddleware:backups.manage', 'CsrfMiddleware']);
$router->post('/admin/backups/restore', 'Admin\BackupController@restore', ['RoleMiddleware:backups.manage', 'CsrfMiddleware']);

// ---- Public blog (must be registered before the catch-all "/:slug" below) ----
$router->get('/blog', 'Public\BlogController@index');
$router->get('/blog/:slug', 'Public\BlogController@show');
$router->post('/blog/:slug/comments', 'Public\CommentController@store', ['CsrfMiddleware']);

// ---- Public lead capture (contact form + sitewide demo modal) ----
$router->post('/leads/contact', 'Public\LeadController@storeContact', ['CsrfMiddleware']);
$router->post('/leads/demo', 'Public\LeadController@storeDemo', ['CsrfMiddleware']);
$router->post('/admissions/apply', 'Public\LeadController@storeAdmission', ['CsrfMiddleware']);

// ---- Public generic page route (must stay last: matches any single-segment slug) ----
$router->get('/:slug', 'Public\PageController@show');
