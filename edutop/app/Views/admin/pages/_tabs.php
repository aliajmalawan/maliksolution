<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'settings' ? 'active' : '' ?>" href="<?= url('/admin/pages/' . $page['id'] . '/edit') ?>">Settings</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'seo' ? 'active' : '' ?>" href="<?= url('/admin/pages/' . $page['id'] . '/seo') ?>">SEO</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'sections' ? 'active' : '' ?>" href="<?= url('/admin/pages/' . $page['id'] . '/sections') ?>">Sections</a>
    </li>
</ul>
