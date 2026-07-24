(function () {
    'use strict';

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Turn an HTTP error status into a message the user can act on. 403 on an
    // AJAX post almost always means the login session expired (or this tab was
    // opened before a re-login), so the page's CSRF token is stale.
    function httpErrorMessage(status) {
        if (status === 403) {
            return 'Your login session has expired (or this tab is out of date). Refresh the page — log in again if asked — then retry.';
        }
        if (status === 413) {
            return 'The file is too large for the server to accept.';
        }
        return 'Server returned HTTP ' + status + '. Please refresh the page and try again.';
    }

    // ---- data-confirm: replaces inline onsubmit="return confirm(...)" (blocked by CSP) ----
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.hasAttribute && form.hasAttribute('data-confirm')) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        }
    });

    // ---- File Manager: rename prompt ----
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-rename-trigger]');
        if (!trigger) return;
        var newName = window.prompt('New name:', trigger.getAttribute('data-name'));
        if (!newName || newName === trigger.getAttribute('data-name')) return;
        document.getElementById('renamePath').value = trigger.getAttribute('data-path');
        document.getElementById('renameNewName').value = newName;
        document.getElementById('renameForm').submit();
    });

    // ---- Custom Design (Code) panel: reload the section's current rendered markup into the textarea ----
    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-load-current-design]');
        if (!trigger) return;
        var target = document.querySelector(trigger.getAttribute('data-target'));
        var template = document.getElementById('customCodeCurrentDesign');
        if (!target || !template) return;
        if (target.value.trim() !== '' && !window.confirm('This will replace the current contents of the Custom HTML box. Continue?')) {
            return;
        }
        // <template> keeps its parsed children in .content, not as direct child nodes.
        target.value = (template.content ? template.content.textContent : template.textContent).trim();
    });

    // ---- Filter <select> that submits its form on change (replaces inline onchange, blocked by CSP) ----
    document.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('select[data-auto-submit-select]')) {
            var form = e.target.closest('form');
            if (form) form.submit();
        }
    });

    // ---- Gallery bulk upload: multiple files + one category -> one repeater row each ----
    var bulkCategorySelect = document.getElementById('bulkUploadCategorySelect');
    if (bulkCategorySelect) {
        var bulkNewCategory = document.getElementById('bulkUploadNewCategory');
        bulkCategorySelect.addEventListener('change', function () {
            bulkNewCategory.classList.toggle('d-none', bulkCategorySelect.value !== '__new__');
        });

        document.getElementById('bulkUploadSubmit').addEventListener('click', function () {
            var btn = this;
            var filesInput = document.getElementById('bulkUploadFiles');
            var status = document.getElementById('bulkUploadStatus');
            var files = filesInput.files;

            if (!files.length) {
                status.textContent = 'Choose at least one image first.';
                return;
            }

            var category = bulkCategorySelect.value === '__new__'
                ? bulkNewCategory.value.trim()
                : bulkCategorySelect.value;

            var repeater = document.querySelector('.repeater[data-repeater-group="' + btn.getAttribute('data-repeater-group') + '"]');
            var template = repeater ? repeater.querySelector('.repeater-template') : null;
            var rowsContainer = repeater ? repeater.querySelector('.repeater-rows') : null;
            if (!repeater || !template || !rowsContainer) {
                status.textContent = 'Could not find the photo list on this page.';
                return;
            }

            var formData = new FormData();
            for (var i = 0; i < files.length; i++) {
                formData.append('files[]', files[i]);
            }
            formData.append('_csrf', csrfToken());

            btn.disabled = true;
            status.textContent = 'Uploading…';

            fetch(btn.getAttribute('data-upload-url'), {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) {
                    if (!r.ok) throw new Error(httpErrorMessage(r.status));
                    return r.json();
                })
                .then(function (data) {
                    var created = data.created || [];
                    var lastRow = null;

                    created.forEach(function (media, idx) {
                        var index = Date.now() + '' + idx;
                        var html = template.innerHTML.split('__INDEX__').join(index);
                        var wrapper = document.createElement('div');
                        wrapper.innerHTML = html;
                        var row = wrapper.firstElementChild;

                        var imageInput = row.querySelector('input[name$="[image]"]');
                        if (imageInput) imageInput.value = media.id;
                        var preview = row.querySelector('.media-field-preview');
                        if (preview) preview.innerHTML = '<img src="' + media.url + '" style="max-width:100%;max-height:100%;object-fit:cover;">';
                        var categoryInput = row.querySelector('input[name$="[category]"]');
                        if (categoryInput) categoryInput.value = category;

                        rowsContainer.appendChild(row);
                        lastRow = row;
                    });

                    if (created.length > 0) {
                        status.textContent = created.length + ' photo(s) added below — remember to click "Save Section".';
                        filesInput.value = '';
                        if (lastRow) lastRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        var modalEl = document.getElementById('bulkUploadModal');
                        var modal = window.bootstrap && window.bootstrap.Modal.getOrCreateInstance(modalEl);
                        if (modal) modal.hide();
                    } else {
                        var reason = (data.errors && data.errors[0]) ? data.errors[0] : 'Upload failed for an unknown reason.';
                        status.textContent = reason;
                    }
                })
                .catch(function (err) {
                    status.textContent = 'Upload failed: ' + err.message;
                })
                .finally(function () {
                    btn.disabled = false;
                });
        });
    }

    // ---- Repeater fields (stats items, testimonials, FAQ, pricing plans, etc.) ----
    document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('[data-repeater-add]');
        if (addBtn) {
            var repeater = addBtn.closest('.repeater');
            var template = repeater.querySelector('.repeater-template');
            var rowsContainer = repeater.querySelector('.repeater-rows');
            var index = Date.now() + '' + Math.floor(Math.random() * 1000);
            var html = template.innerHTML.split('__INDEX__').join(index);
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            var newRow = wrapper.firstElementChild;
            rowsContainer.appendChild(newRow);
            // The add button now sits above the list, so the new (empty) row
            // lands off-screen at the bottom — bring it into view automatically.
            newRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        var removeBtn = e.target.closest('[data-repeater-remove]');
        if (removeBtn) {
            removeBtn.closest('.repeater-row').remove();
            return;
        }

        var upBtn = e.target.closest('[data-repeater-up]');
        if (upBtn) {
            var rowUp = upBtn.closest('.repeater-row');
            var prev = rowUp.previousElementSibling;
            if (prev) rowUp.parentNode.insertBefore(rowUp, prev);
            return;
        }

        var downBtn = e.target.closest('[data-repeater-down]');
        if (downBtn) {
            var rowDown = downBtn.closest('.repeater-row');
            var next = rowDown.nextElementSibling;
            if (next) rowDown.parentNode.insertBefore(next, rowDown);
            return;
        }
    });

    // ---- Rich text (Quill) fields: sync HTML into the hidden textarea before submit ----
    var quillInstances = [];
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.quill-editor[data-quill-for]').forEach(function (el) {
            var textarea = document.getElementById(el.getAttribute('data-quill-for'));
            if (!textarea || typeof Quill === 'undefined') return;
            var quill = new Quill(el, {
                theme: 'snow',
                modules: { toolbar: [['bold', 'italic', 'underline'], [{ header: [2, 3, 4, false] }], ['link', 'blockquote'], [{ list: 'ordered' }, { list: 'bullet' }], ['clean']] },
            });
            if (textarea.value) quill.root.innerHTML = textarea.value;
            quillInstances.push({ quill: quill, textarea: textarea });
        });

        document.querySelectorAll('[data-sortable-sections]').forEach(function (el) {
            if (typeof Sortable === 'undefined') return;
            Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function () { postOrder(el, 'section-id'); },
            });
        });

        document.querySelectorAll('[data-sortable-pages]').forEach(function (el) {
            if (typeof Sortable === 'undefined') return;
            Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function () { postOrder(el, 'page-id'); },
            });
        });

        document.querySelectorAll('[data-sortable-menu]').forEach(function (el) {
            if (typeof Sortable === 'undefined') return;
            Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function () { postOrder(el, 'item-id'); },
            });
        });
    });

    document.addEventListener('submit', function (e) {
        quillInstances.forEach(function (pair) {
            if (e.target.contains(pair.textarea)) {
                pair.textarea.value = pair.quill.root.innerHTML;
            }
        });
    });

    function postOrder(container, idAttr) {
        var url = container.getAttribute('data-reorder-url');
        if (!url) return;
        var ids = Array.prototype.map.call(container.children, function (row) {
            return row.getAttribute('data-' + idAttr);
        });
        var formData = new FormData();
        ids.forEach(function (id) { formData.append('order[]', id); });
        formData.append('_csrf', csrfToken());
        fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    }

    // ---- Media picker modal ----
    var mediaPickerTarget = null;
    var mediaPickerUrl = null;

    function loadMediaPicker(url) {
        var body = document.getElementById('mediaPickerBody');
        body.innerHTML = '<div class="text-muted">Loading&hellip;</div>';
        return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.text(); })
            .then(function (html) { body.innerHTML = html; })
            .catch(function () { body.innerHTML = '<div class="text-danger">Failed to load media.</div>'; });
    }

    var mediaPickerUploadInput = document.getElementById('mediaPickerUploadInput');
    if (mediaPickerUploadInput) {
        mediaPickerUploadInput.addEventListener('change', function () {
            if (!mediaPickerUploadInput.files.length) return;
            var status = document.getElementById('mediaPickerUploadStatus');
            var formData = new FormData();
            for (var i = 0; i < mediaPickerUploadInput.files.length; i++) {
                formData.append('files[]', mediaPickerUploadInput.files[i]);
            }
            formData.append('_csrf', csrfToken());
            status.textContent = 'Uploading…';
            fetch(mediaPickerUploadInput.getAttribute('data-upload-url'), {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (r) {
                    if (!r.ok) throw new Error(httpErrorMessage(r.status));
                    return r.json();
                })
                .then(function (data) {
                    var createdCount = data.created ? data.created.length : 0;
                    if (data.ok && createdCount > 0) {
                        status.textContent = 'Uploaded.';
                        window.alert(createdCount === 1 ? 'Image uploaded successfully!' : createdCount + ' images uploaded successfully!');
                    } else {
                        var reason = (data.errors && data.errors[0]) ? data.errors[0] : 'Upload failed for an unknown reason.';
                        status.textContent = reason;
                        window.alert('Image upload failed: ' + reason);
                    }
                    mediaPickerUploadInput.value = '';
                    if (mediaPickerUrl) loadMediaPicker(mediaPickerUrl);
                })
                .catch(function (err) {
                    status.textContent = 'Upload failed.';
                    window.alert('Image upload failed: ' + err.message);
                });
        });
    }

    // ---- Per-field "Upload" button: pick a file from your device, upload it,
    // and immediately swap this field's value/preview to the new image — no
    // need to open the picker modal at all for a fresh, one-off image. ----
    document.addEventListener('change', function (e) {
        var fileInput = e.target.closest('[data-media-upload-field]');
        if (!fileInput || !fileInput.files.length) return;

        var wrapper = fileInput.closest('.d-flex');
        var statusEl = wrapper ? wrapper.querySelector('.media-upload-status') : null;
        var targetInput = document.querySelector(fileInput.getAttribute('data-target'));
        var preview = wrapper ? wrapper.querySelector('.media-field-preview') : null;

        var formData = new FormData();
        formData.append('files[]', fileInput.files[0]);
        formData.append('_csrf', csrfToken());
        if (statusEl) statusEl.textContent = 'Uploading…';

        fetch(fileInput.getAttribute('data-upload-url'), {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) {
                if (!r.ok) throw new Error('Server returned HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                var uploaded = data.created && data.created[0];
                if (data.ok && uploaded) {
                    if (targetInput) targetInput.value = uploaded.id;
                    if (preview) {
                        preview.innerHTML = '<img src="' + uploaded.url + '" style="max-width:100%;max-height:100%;object-fit:cover;">';
                    }
                    if (statusEl) statusEl.textContent = 'Uploaded — image updated.';
                    window.alert('Image uploaded and updated successfully!');
                } else {
                    var reason = (data.errors && data.errors[0]) ? data.errors[0] : 'Upload failed for an unknown reason.';
                    if (statusEl) statusEl.textContent = reason;
                    window.alert('Image upload failed: ' + reason);
                }
                fileInput.value = '';
            })
            .catch(function (err) {
                if (statusEl) statusEl.textContent = 'Upload failed.';
                window.alert('Image upload failed: ' + err.message);
                fileInput.value = '';
            });
    });

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-media-picker]');
        if (trigger) {
            e.preventDefault();
            mediaPickerTarget = trigger.getAttribute('data-target');
            mediaPickerUrl = trigger.getAttribute('data-picker-url');
            var uploadStatus = document.getElementById('mediaPickerUploadStatus');
            if (uploadStatus) uploadStatus.textContent = '';
            loadMediaPicker(mediaPickerUrl);
            var modalEl = document.getElementById('mediaPickerModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(modalEl).show();
            }
            return;
        }

        var clearBtn = e.target.closest('[data-media-clear]');
        if (clearBtn) {
            var clearInput = document.querySelector(clearBtn.getAttribute('data-target'));
            if (clearInput) {
                clearInput.value = '';
                var clearPreview = clearInput.closest('.d-flex').querySelector('.media-field-preview');
                if (clearPreview) clearPreview.innerHTML = '<span class="text-muted small">None</span>';
            }
            return;
        }

        var pickItem = e.target.closest('[data-media-id]');
        if (pickItem && mediaPickerTarget) {
            var input = document.querySelector(mediaPickerTarget);
            if (input) {
                input.value = pickItem.getAttribute('data-media-id');
                var preview = input.closest('.d-flex').querySelector('.media-field-preview');
                var mediaUrl = pickItem.getAttribute('data-media-url');
                if (preview) {
                    preview.innerHTML = mediaUrl
                        ? '<img src="' + mediaUrl + '" style="max-width:100%;max-height:100%;object-fit:cover;">'
                        : '<span class="text-muted small">File</span>';
                }
            }
            var modalEl2 = document.getElementById('mediaPickerModal');
            if (modalEl2 && typeof bootstrap !== 'undefined') {
                var instance = bootstrap.Modal.getInstance(modalEl2);
                if (instance) instance.hide();
            }
        }
    });

    // ---- Menu item form: toggle Internal Page vs Custom URL fields ----
    document.addEventListener('change', function (e) {
        if (e.target.id === 'menuLinkType') {
            var isPage = e.target.value === 'page';
            var pageSelect = document.getElementById('menuPageSelect');
            var customUrl = document.getElementById('menuCustomUrl');
            if (pageSelect) pageSelect.classList.toggle('d-none', !isPage);
            if (customUrl) customUrl.classList.toggle('d-none', isPage);
        }
    });

    // ---- Any file input marked data-auto-submit submits its form on change (e.g. per-item "replace") ----
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (input.matches && input.matches('input[type="file"][data-auto-submit]')) {
            var form = input.closest('form');
            if (form && input.files && input.files.length) form.submit();
        }
    });

    // ---- Copy media URL to clipboard ----
    document.addEventListener('click', function (e) {
        var copyBtn = e.target.closest('[data-copy-url]');
        if (copyBtn) {
            var text = copyBtn.getAttribute('data-copy-url');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function () {
                    var original = copyBtn.textContent;
                    copyBtn.textContent = 'Copied!';
                    setTimeout(function () { copyBtn.textContent = original; }, 1500);
                });
            }
        }
    });

    // ---- Show/hide password toggle (button with data-toggle-password="#inputId") ----
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-toggle-password]');
        if (!toggle) return;
        var input = document.querySelector(toggle.getAttribute('data-toggle-password'));
        if (!input) return;
        var showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        var icon = toggle.querySelector('i');
        if (icon) icon.className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
        toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
    });

    // ---- Media library: drag & drop upload ----
    document.addEventListener('DOMContentLoaded', function () {
        var dropzone = document.getElementById('mediaDropzone');
        var fileInput = document.getElementById('mediaFileInput');
        var uploadForm = document.getElementById('mediaUploadForm');
        if (!dropzone || !fileInput || !uploadForm) return;

        ['dragenter', 'dragover'].forEach(function (evt) {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                dropzone.classList.add('border-primary');
            });
        });
        ['dragleave', 'drop'].forEach(function (evt) {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                dropzone.classList.remove('border-primary');
            });
        });
        dropzone.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                uploadForm.submit();
            }
        });
        fileInput.addEventListener('change', function () {
            if (fileInput.files.length) uploadForm.submit();
        });
        dropzone.addEventListener('click', function () { fileInput.click(); });
    });
})();
