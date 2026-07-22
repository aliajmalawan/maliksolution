</main>
</div><!-- /.admin-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
  'use strict';

  /* ── Sidebar toggle ─────────────────────────── */
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('adminSidebar');
  const overlay = document.getElementById('sidebarOverlay');

  function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('active'); }
  function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('active'); }

  toggle?.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
  overlay?.addEventListener('click', closeSidebar);

  /* ── Courses submenu toggle ─────────────────── */
  document.querySelectorAll('.s-group-toggle').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.s-group').classList.toggle('open'));
  });

  /* ── Bootstrap tooltips ─────────────────────── */
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  /* ── Dark mode toggle ───────────────────────── */
  const themeBtn = document.getElementById('themeToggle');
  themeBtn?.addEventListener('click', () => {
    const html = document.documentElement;
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    document.cookie = 'admincp_theme=' + next + ';path=/;max-age=31536000;SameSite=Lax';
    const icon = themeBtn.querySelector('i');
    icon.classList.toggle('fa-moon', next === 'light');
    icon.classList.toggle('fa-sun', next === 'dark');
  });
})();
</script>
</body>
</html>
