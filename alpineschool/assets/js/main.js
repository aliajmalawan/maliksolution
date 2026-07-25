document.addEventListener('DOMContentLoaded', function () {
  var prefersReducedMotion = window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---------- Mobile slide-in drawer ----------
  var drawer = document.getElementById('mobileDrawer');
  var overlay = document.getElementById('drawerOverlay');
  var drawerToggle = document.getElementById('navToggle');
  var drawerClose = document.getElementById('drawerClose');

  function openDrawer() {
    if (!drawer) return;
    overlay.hidden = false;
    requestAnimationFrame(function () { overlay.classList.add('show'); });
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    drawerToggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    var firstLink = drawer.querySelector('a, button');
    if (firstLink) firstLink.focus();
  }
  function closeDrawer() {
    if (!drawer) return;
    overlay.classList.remove('show');
    setTimeout(function () { overlay.hidden = true; }, 260);
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    drawerToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    drawerToggle.focus();
  }
  if (drawerToggle && drawer) {
    drawerToggle.addEventListener('click', openDrawer);
    drawerClose.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && drawer.classList.contains('open')) closeDrawer();
    });
    // Accordion sub-menus inside the drawer.
    drawer.querySelectorAll('.edu-drawer-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var sub = document.getElementById(btn.getAttribute('aria-controls'));
        var open = sub.classList.toggle('open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
    });
  }

  // ---------- Desktop icon-navbar dropdowns (mouse + keyboard) ----------
  function closeDropdown(d) {
    d.classList.remove('open');
    var btn = d.querySelector('.edu-nav-drop-btn');
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }
  function closeAllDropdowns(except) {
    document.querySelectorAll('.edu-nav-dropdown.open').forEach(function (d) {
      if (d !== except) closeDropdown(d);
    });
  }

  document.querySelectorAll('.edu-nav-dropdown').forEach(function (dropdown) {
    var btn = dropdown.querySelector('.edu-nav-drop-btn');
    var menu = dropdown.querySelector('.nav-drop-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = dropdown.classList.contains('open');
      closeAllDropdowns(dropdown);
      if (isOpen) { closeDropdown(dropdown); }
      else { dropdown.classList.add('open'); btn.setAttribute('aria-expanded', 'true'); }
    });

    btn.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        closeAllDropdowns(dropdown);
        dropdown.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        var first = menu.querySelector('a');
        if (first) first.focus();
      } else if (e.key === 'Escape') {
        closeDropdown(dropdown);
      }
    });

    menu.addEventListener('keydown', function (e) {
      var links = Array.prototype.slice.call(menu.querySelectorAll('a'));
      var idx = links.indexOf(document.activeElement);
      if (e.key === 'Escape') { e.preventDefault(); closeDropdown(dropdown); btn.focus(); }
      else if (e.key === 'ArrowDown') { e.preventDefault(); if (idx < links.length - 1) links[idx + 1].focus(); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); if (idx > 0) { links[idx - 1].focus(); } else { btn.focus(); } }
    });

    dropdown.addEventListener('focusout', function (e) {
      if (!dropdown.contains(e.relatedTarget)) closeDropdown(dropdown);
    });
  });

  document.addEventListener('click', function () { closeAllDropdowns(null); });

  // ---------- Shrink-on-scroll shadow ----------
  var header = document.getElementById('siteHeader');
  if (header) {
    window.addEventListener('scroll', function () {
      header.classList.toggle('edu-scrolled', window.scrollY > 10);
    }, { passive: true });
  }

  // ---------- Hero: defer off-screen slide backgrounds (perf) ----------
  function loadDeferredBackgrounds() {
    document.querySelectorAll('.hero-slide[data-bg]').forEach(function (el) {
      el.style.backgroundImage = "url('" + el.getAttribute('data-bg') + "')";
      el.removeAttribute('data-bg');
    });
  }
  if (document.querySelector('.hero-slide[data-bg]')) {
    if ('requestIdleCallback' in window) {
      requestIdleCallback(loadDeferredBackgrounds, { timeout: 2500 });
    } else {
      window.addEventListener('load', loadDeferredBackgrounds);
    }
  }

  // ---------- Hero slider ----------
  var slides = document.querySelectorAll('.hero-slide');
  var dotsWrap = document.getElementById('heroDots');
  if (slides.length > 1 && dotsWrap) {
    var current = 0;
    var dots = [];
    var timer = null;

    slides.forEach(function (slide, i) {
      slide.setAttribute('role', 'group');
      slide.setAttribute('aria-roledescription', 'slide');
      slide.setAttribute('aria-label', 'Slide ' + (i + 1) + ' of ' + slides.length);
      slide.setAttribute('aria-hidden', i === 0 ? 'false' : 'true');

      var dot = document.createElement('button');
      dot.type = 'button';
      dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
      if (i === 0) {
        dot.classList.add('active');
        dot.setAttribute('aria-current', 'true');
      }
      dot.addEventListener('click', function () {
        goTo(i);
        restart();
      });
      dotsWrap.appendChild(dot);
      dots.push(dot);
    });

    function goTo(index) {
      slides[current].classList.remove('active');
      slides[current].setAttribute('aria-hidden', 'true');
      dots[current].classList.remove('active');
      dots[current].removeAttribute('aria-current');

      current = index;

      slides[current].classList.add('active');
      slides[current].setAttribute('aria-hidden', 'false');
      dots[current].classList.add('active');
      dots[current].setAttribute('aria-current', 'true');
    }
    function start() {
      // Auto-advancing carousels are a WCAG 2.2.2 problem — don't move for
      // people who asked for reduced motion.
      if (prefersReducedMotion) return;
      timer = setInterval(function () { goTo((current + 1) % slides.length); }, 6000);
    }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }
    function restart() { stop(); start(); }

    // Pause on hover and while any control inside has keyboard focus.
    var slider = document.querySelector('.hero-slider');
    if (slider) {
      slider.addEventListener('mouseenter', stop);
      slider.addEventListener('mouseleave', start);
      slider.addEventListener('focusin', stop);
      slider.addEventListener('focusout', start);
    }
    start();
  }

  // ---------- Lightbox (accessible modal dialog) ----------
  var lightbox = document.getElementById('lightbox');
  if (lightbox) {
    var lbItems = Array.prototype.slice.call(document.querySelectorAll('.lb-item'));
    var lbContent = document.getElementById('lbContent');
    var lbCaption = document.getElementById('lbCaption');
    var lbClose = document.getElementById('lbClose');
    var lbPrev = document.getElementById('lbPrev');
    var lbNext = document.getElementById('lbNext');
    var lbIndex = -1;
    var lastFocused = null;

    function lbShow(i) {
      if (i < 0) i = lbItems.length - 1;
      if (i >= lbItems.length) i = 0;
      if (lbIndex === -1) lastFocused = document.activeElement;
      lbIndex = i;

      var el = lbItems[i];
      var type = el.getAttribute('data-lb-type');
      var src = el.getAttribute('data-lb-src');
      var caption = el.getAttribute('data-lb-caption') || '';
      var alt = el.getAttribute('data-lb-alt') || caption;

      if (type === 'video') {
        lbContent.innerHTML = '<div class="lb-video"><iframe src="' + src +
          '" title="' + caption.replace(/"/g, '&quot;') +
          '" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe></div>';
      } else {
        var img = document.createElement('img');
        img.src = src;
        img.alt = alt;
        lbContent.innerHTML = '';
        lbContent.appendChild(img);
      }

      lbCaption.textContent = caption
        ? caption + ' (' + (i + 1) + ' of ' + lbItems.length + ')'
        : 'Item ' + (i + 1) + ' of ' + lbItems.length;
      lightbox.hidden = false;
      document.body.style.overflow = 'hidden';
      lbClose.focus();
    }

    function lbHide() {
      lightbox.hidden = true;
      lbContent.innerHTML = '';
      document.body.style.overflow = '';
      lbIndex = -1;
      // Return focus to whatever opened the dialog.
      if (lastFocused && lastFocused.focus) lastFocused.focus();
    }

    lbItems.forEach(function (el, i) {
      el.addEventListener('click', function () { lbShow(i); });
      // Gallery tiles are divs — make them behave like buttons for keyboards.
      el.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          lbShow(i);
        }
      });
    });

    lbClose.addEventListener('click', lbHide);
    lbPrev.addEventListener('click', function () { lbShow(lbIndex - 1); });
    lbNext.addEventListener('click', function () { lbShow(lbIndex + 1); });
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) lbHide();
    });

    document.addEventListener('keydown', function (e) {
      if (lightbox.hidden) return;

      if (e.key === 'Escape') {
        lbHide();
      } else if (e.key === 'ArrowLeft') {
        lbShow(lbIndex - 1);
      } else if (e.key === 'ArrowRight') {
        lbShow(lbIndex + 1);
      } else if (e.key === 'Tab') {
        // Focus trap: keep Tab inside the dialog while it's open.
        var focusable = lightbox.querySelectorAll('button, iframe, [href], [tabindex]:not([tabindex="-1"])');
        if (!focusable.length) return;
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    });
  }
});
