(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // ---- Scroll-reveal: fade/slide elements in as they enter the viewport ----
        var revealEls = document.querySelectorAll('.animate-on-scroll, .animate-stagger');
        if ('IntersectionObserver' in window && revealEls.length) {
            var revealObserver = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        obs.unobserve(entry.target);
                    }
                });
            // threshold 0 (any pixel visible): a fractional threshold can never
            // fire for elements taller than the viewport (e.g. long forms on
            // phones), which would leave them permanently invisible.
            }, { threshold: 0, rootMargin: '0px 0px -60px 0px' });
            revealEls.forEach(function (el) { revealObserver.observe(el); });
        } else {
            revealEls.forEach(function (el) { el.classList.add('is-visible'); });
        }

        // ---- Animated counters: e.g. <span data-count-to="500" data-count-suffix="+"> ----
        var counters = document.querySelectorAll('[data-count-to]');
        if ('IntersectionObserver' in window && counters.length) {
            var counterObserver = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    animateCounter(entry.target);
                    obs.unobserve(entry.target);
                });
            }, { threshold: 0.4 });
            counters.forEach(function (el) { counterObserver.observe(el); });
        } else {
            counters.forEach(function (el) { animateCounter(el); });
        }

        function animateCounter(el) {
            var target = parseFloat(el.getAttribute('data-count-to'));
            if (isNaN(target)) return;
            var prefix = el.getAttribute('data-count-prefix') || '';
            var suffix = el.getAttribute('data-count-suffix') || '';
            var duration = 1400;
            var start = null;

            function step(timestamp) {
                if (!start) start = timestamp;
                var progress = Math.min((timestamp - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3); // ease-out-cubic
                var current = Math.floor(eased * target);
                el.textContent = prefix + current.toLocaleString() + suffix;
                if (progress < 1) {
                    requestAnimationFrame(step);
                } else {
                    el.textContent = prefix + target.toLocaleString() + suffix;
                }
            }
            requestAnimationFrame(step);
        }

        // ---- Sticky header solidifies once the page scrolls ----
        var header = document.querySelector('header.sticky-top');
        if (header) {
            var applyShadow = function () {
                header.classList.toggle('edu-scrolled', window.scrollY > 8);
            };
            window.addEventListener('scroll', applyShadow, { passive: true });
            applyShadow();
        }

        // ---- Carousels: force-start autoplay, never pause on hover ----
        // (Bootstrap's data-api waits for window "load" and pauses on hover by
        // default, which reads as "the slider doesn't move" while inspecting.)
        if (window.bootstrap && window.bootstrap.Carousel) {
            document.querySelectorAll('.carousel[data-bs-ride]').forEach(function (el) {
                var instance = window.bootstrap.Carousel.getOrCreateInstance(el, { pause: false, ride: 'carousel' });
                instance.cycle();
            });
        }

        // ---- Back-to-top button ----
        var backTop = document.createElement('button');
        backTop.type = 'button';
        backTop.className = 'edu-back-top';
        backTop.setAttribute('aria-label', 'Back to top');
        backTop.innerHTML = '<i class="bi bi-arrow-up"></i>';
        document.body.appendChild(backTop);

        var toggleBackTop = function () {
            backTop.classList.toggle('show', window.scrollY > 500);
        };
        window.addEventListener('scroll', toggleBackTop, { passive: true });
        toggleBackTop();

        backTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // ---- Form validation: show which required fields are empty on submit ----
        // novalidate + was-validated is used instead of relying on native browser
        // tooltips, since those don't render for custom controls like a
        // Bootstrap btn-check radio group (e.g. admin-defined "Radio Buttons"
        // fields on the admission form) — each .edu-radio-group is checked
        // manually below instead.
        document.querySelectorAll('form.needs-validation').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var anyRadioGroupInvalid = false;

                form.querySelectorAll('.edu-radio-group').forEach(function (group) {
                    var name = group.getAttribute('data-radio-name');
                    var isRequired = !!group.querySelector('input[required]');
                    var checked = name && form.querySelector('input[name="' + name + '"]:checked');
                    var missing = isRequired && !checked;
                    var feedback = group.parentElement ? group.parentElement.querySelector('.edu-radio-feedback') : null;

                    if (feedback) {
                        feedback.classList.toggle('d-block', missing);
                    }
                    if (missing) {
                        anyRadioGroupInvalid = true;
                    }
                });

                if (!form.checkValidity() || anyRadioGroupInvalid) {
                    event.preventDefault();
                    event.stopPropagation();

                    var firstInvalid = form.querySelector(':invalid, .edu-radio-feedback.d-block');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (typeof firstInvalid.focus === 'function') {
                            firstInvalid.focus({ preventScroll: true });
                        }
                    }
                }

                form.classList.add('was-validated');
            });
        });

        // ---- Image gallery category filter ----
        document.querySelectorAll('[data-gallery-filters]').forEach(function (filterBar) {
            var galleryId = filterBar.getAttribute('data-gallery-filters');
            var grid = document.querySelector('[data-gallery-grid="' + galleryId + '"]');
            if (!grid) return;

            filterBar.addEventListener('click', function (event) {
                var btn = event.target.closest('.edu-gallery-filter-btn');
                if (!btn) return;

                filterBar.querySelectorAll('.edu-gallery-filter-btn').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');

                var filter = btn.getAttribute('data-filter');
                grid.querySelectorAll('.edu-gallery-item').forEach(function (item) {
                    var show = filter === 'all' || item.getAttribute('data-category') === filter;
                    item.classList.toggle('d-none', !show);
                });
            });
        });

        // ---- Image gallery lightbox: clicking a thumbnail opens it full-size,
        // with prev/next arrows to browse the rest of that gallery ----
        document.querySelectorAll('.edu-lightbox-modal').forEach(function (modalEl) {
            var img = modalEl.querySelector('.edu-lightbox-img');
            var prevBtn = modalEl.querySelector('.edu-lightbox-prev');
            var nextBtn = modalEl.querySelector('.edu-lightbox-next');
            var triggers = [];
            var currentIndex = -1;

            // Only currently-visible tiles (category filter may have hidden
            // some), so arrows never navigate to a photo the filter is hiding.
            function visibleTriggers(fromTrigger) {
                var grid = fromTrigger.closest('[data-gallery-grid]');
                if (!grid) return [fromTrigger];
                return Array.prototype.filter.call(grid.querySelectorAll('[data-lightbox-src]'), function (el) {
                    var item = el.closest('.edu-gallery-item');
                    return !item || !item.classList.contains('d-none');
                });
            }

            function showAt(index) {
                if (!triggers.length) return;
                currentIndex = (index + triggers.length) % triggers.length;
                var src = triggers[currentIndex].getAttribute('data-lightbox-src');
                if (img && src) img.src = src;
            }

            modalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;
                triggers = visibleTriggers(trigger);
                showAt(triggers.indexOf(trigger));
            });

            if (prevBtn) prevBtn.addEventListener('click', function () { showAt(currentIndex - 1); });
            if (nextBtn) nextBtn.addEventListener('click', function () { showAt(currentIndex + 1); });

            modalEl.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowLeft') showAt(currentIndex - 1);
                if (event.key === 'ArrowRight') showAt(currentIndex + 1);
            });
        });
    });
})();
