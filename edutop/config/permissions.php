<?php

/**
 * Canonical permission slugs, grouped by module. Used by the database seeder
 * to populate the `permissions` table and to define default role grants.
 * Modules beyond Phase 1 (pages, media, blog, settings, backups, file manager)
 * are pre-declared here so RBAC doesn't need schema changes when those land.
 */
return [
    'dashboard' => [
        'dashboard.view' => 'View admin dashboard',
    ],
    'users' => [
        'users.view' => 'View users',
        'users.manage' => 'Create, edit, and delete users',
    ],
    'roles' => [
        'roles.view' => 'View roles & permissions',
        'roles.manage' => 'Edit role permissions',
    ],
    'pages' => [
        'pages.view' => 'View pages',
        'pages.manage' => 'Edit page sections & SEO',
    ],
    'media' => [
        'media.view' => 'View media library',
        'media.manage' => 'Upload, replace, and delete media',
    ],
    'blog' => [
        'blog.view' => 'View blog posts',
        'blog.manage' => 'Create and edit blog posts',
        'blog.publish' => 'Publish or schedule blog posts',
    ],
    'leads' => [
        'leads.view' => 'View contact & demo request leads',
        'leads.manage' => 'Update lead status and delete leads',
    ],
    'settings' => [
        'settings.manage' => 'Edit site-wide settings',
    ],
    'backups' => [
        'backups.manage' => 'Run and restore backups',
    ],
    'files' => [
        'files.manage' => 'Use the file manager',
    ],
    'logs' => [
        'logs.view' => 'View activity logs & login history',
    ],
];
