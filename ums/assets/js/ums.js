/**
 * Malik UMS — shell interactions (Phase 1)
 * Sidebar toggle · dark mode (persisted) · topbar dropdown panels
 */
(function () {
  'use strict';

  /* ── Dark mode: cookie-persisted, applied server-side to avoid flash ── */
  var themeBtn = document.getElementById('umsTheme');
  function setTheme(next) {
    document.documentElement.setAttribute('data-theme', next);
    document.cookie = 'ums_theme=' + next + ';path=/;max-age=31536000;SameSite=Lax';
    if (themeBtn) {
      var icon = themeBtn.querySelector('i');
      icon.classList.toggle('fa-moon', next === 'light');
      icon.classList.toggle('fa-sun', next === 'dark');
    }
    // let charts re-read the palette
    document.dispatchEvent(new CustomEvent('ums:theme', { detail: next }));
  }
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      setTheme(cur === 'dark' ? 'light' : 'dark');
    });
  }

  /* ── Sidebar (mobile) ── */
  var burger = document.getElementById('umsBurger');
  var backdrop = document.getElementById('umsBackdrop');
  if (burger) burger.addEventListener('click', function () { document.body.classList.toggle('side-open'); });
  if (backdrop) backdrop.addEventListener('click', function () { document.body.classList.remove('side-open'); });

  /* ── Topbar popovers (notifications, profile) ── */
  document.querySelectorAll('[data-pop]').forEach(function (btn) {
    var pop = document.getElementById(btn.dataset.pop);
    if (!pop) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      document.querySelectorAll('.u-pop.open').forEach(function (p) { if (p !== pop) p.classList.remove('open'); });
      pop.classList.toggle('open');
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('.u-pop.open').forEach(function (p) { p.classList.remove('open'); });
  });
  document.querySelectorAll('.u-pop').forEach(function (p) {
    p.addEventListener('click', function (e) { e.stopPropagation(); });
  });
})();
