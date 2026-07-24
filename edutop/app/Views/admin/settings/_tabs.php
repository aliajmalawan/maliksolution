<ul class="nav nav-tabs mb-4">
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'general' ? 'active' : '' ?>" href="<?= url('/admin/settings') ?>">General</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'email' ? 'active' : '' ?>" href="<?= url('/admin/settings/email') ?>">Email</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'integrations' ? 'active' : '' ?>" href="<?= url('/admin/settings/integrations') ?>">Integrations</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'theme' ? 'active' : '' ?>" href="<?= url('/admin/settings/theme') ?>">Theme</a></li>
    <li class="nav-item"><a class="nav-link <?= $activeTab === 'system' ? 'active' : '' ?>" href="<?= url('/admin/settings/system') ?>">System</a></li>
</ul>
