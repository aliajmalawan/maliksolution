document.addEventListener('DOMContentLoaded', function () {
  function setCookie(name, value) {
    document.cookie = name + '=' + value + ';path=/;max-age=31536000;SameSite=Lax';
  }

  // Sidebar: collapse on desktop, slide-in on mobile
  var wrap = document.getElementById('adminWrap');
  var toggle = document.getElementById('sidebarToggle');
  if (toggle && wrap) {
    toggle.addEventListener('click', function () {
      if (window.innerWidth <= 900) {
        wrap.classList.toggle('sb-open');
      } else {
        wrap.classList.toggle('sb-collapsed');
        setCookie('admin_sb', wrap.classList.contains('sb-collapsed') ? 'collapsed' : 'expanded');
      }
    });
  }

  // Dark / light mode
  var themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      var root = document.documentElement;
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      setCookie('admin_theme', next);
      themeToggle.textContent = next === 'dark' ? '☀️' : '🌙';
    });
  }

  // Topbar dropdowns (notifications, profile)
  document.querySelectorAll('[data-drop-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var drop = btn.closest('.top-drop');
      document.querySelectorAll('.top-drop.open').forEach(function (d) {
        if (d !== drop) d.classList.remove('open');
      });
      drop.classList.toggle('open');
    });
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.top-drop-panel')) {
      document.querySelectorAll('.top-drop.open').forEach(function (d) {
        d.classList.remove('open');
      });
    }
  });

  // Homepage builder: drag & drop section reordering
  var hbList = document.getElementById('hbList');
  if (hbList) {
    var dragging = null;
    hbList.querySelectorAll('.hb-row').forEach(function (row) {
      row.addEventListener('dragstart', function () {
        dragging = row;
        setTimeout(function () { row.classList.add('hb-dragging'); }, 0);
      });
      row.addEventListener('dragend', function () {
        row.classList.remove('hb-dragging');
        dragging = null;
      });
    });
    hbList.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (!dragging) return;
      var after = null;
      hbList.querySelectorAll('.hb-row:not(.hb-dragging)').forEach(function (row) {
        var box = row.getBoundingClientRect();
        if (e.clientY < box.top + box.height / 2 && after === null) {
          after = row;
        }
      });
      if (after) {
        hbList.insertBefore(dragging, after);
      } else {
        hbList.appendChild(dragging);
      }
      // Keep the hidden order[] inputs in DOM order.
      hbList.querySelectorAll('.hb-row').forEach(function (row) {
        row.appendChild(row.querySelector('input[name="order[]"]'));
      });
    });

    // Show/hide toggle buttons submit the separate toggle form
    document.querySelectorAll('.hb-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.getElementById('hbToggleId').value = btn.getAttribute('data-id');
        document.getElementById('hbToggleForm').submit();
      });
    });
  }

  // Menu builder: drag & drop + indent/outdent with unlimited nesting
  var menuTree = document.getElementById('menuTree');
  if (menuTree) {
    var mDragging = null;

    function rowDepth(row) {
      return parseInt(row.getAttribute('data-depth') || '0', 10);
    }
    function setDepth(row, depth) {
      row.setAttribute('data-depth', depth);
      row.style.marginLeft = (depth * 26) + 'px';
      row.querySelector('input[name="depth[]"]').value = depth;
    }
    function clampDepths() {
      // Depth may exceed previous row's depth by at most 1; first row is 0.
      var prev = -1;
      menuTree.querySelectorAll('.menu-row').forEach(function (row) {
        var d = Math.min(rowDepth(row), prev + 1);
        if (d < 0) d = 0;
        setDepth(row, d);
        prev = d;
      });
    }

    menuTree.querySelectorAll('.menu-row').forEach(function (row) {
      row.addEventListener('dragstart', function () {
        mDragging = row;
        setTimeout(function () { row.classList.add('hb-dragging'); }, 0);
      });
      row.addEventListener('dragend', function () {
        row.classList.remove('hb-dragging');
        mDragging = null;
        clampDepths();
      });
      row.querySelector('.menu-indent').addEventListener('click', function () {
        setDepth(row, rowDepth(row) + 1);
        clampDepths();
      });
      row.querySelector('.menu-outdent').addEventListener('click', function () {
        setDepth(row, Math.max(0, rowDepth(row) - 1));
        clampDepths();
      });
    });

    menuTree.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (!mDragging) return;
      var after = null;
      menuTree.querySelectorAll('.menu-row:not(.hb-dragging)').forEach(function (row) {
        var box = row.getBoundingClientRect();
        if (e.clientY < box.top + box.height / 2 && after === null) {
          after = row;
        }
      });
      if (after) {
        menuTree.insertBefore(mDragging, after);
      } else {
        menuTree.appendChild(mDragging);
      }
    });

    var structureForm = document.getElementById('menuStructureForm');
    if (structureForm) {
      structureForm.addEventListener('submit', function () {
        clampDepths();
      });
    }
  }

  // Collapsible sidebar groups (Pages)
  document.querySelectorAll('.nav-collapse-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var wrap = btn.closest('.nav-collapse');
      var list = document.getElementById(btn.getAttribute('aria-controls'));
      var open = wrap.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (list) list.hidden = !open;
    });
  });

  // Confirm dialogs
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });
});
