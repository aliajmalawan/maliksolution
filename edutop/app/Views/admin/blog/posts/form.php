<h1 class="h4 mb-1"><?= $post ? 'Edit Post' : 'New Post' ?></h1>
<p class="text-muted mb-4">Fill in the details below</p>

<?php if ($post): ?>
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item"><a class="nav-link active" href="<?= url('/admin/blog/posts/' . $post['id'] . '/edit') ?>">Content</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= url('/admin/blog/posts/' . $post['id'] . '/seo') ?>">Advanced SEO</a></li>
    </ul>
<?php endif; ?>

<form method="POST" action="<?= $post ? url('/admin/blog/posts/' . $post['id']) : url('/admin/blog/posts') ?>">
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Post Details</h6>

                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Enter blog post title..." value="<?= e($post['title'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">URL Slug</label>
                        <div class="input-group">
                            <span class="input-group-text text-muted small">/blog/</span>
                            <input type="text" name="slug" class="form-control" placeholder="auto-generated-from-title" value="<?= e($post['slug'] ?? '') ?>">
                        </div>
                        <div class="form-text">Leave empty to auto-generate from title. Only letters, numbers, hyphens.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Excerpt <span class="text-muted fw-normal">(short summary)</span></label>
                        <textarea name="excerpt" class="form-control" rows="2" maxlength="500" placeholder="Brief description shown in blog listing (max 160 chars)..."><?= e($post['excerpt'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Content</h6>
                    <div class="quill-editor border rounded" data-quill-for="postContent" style="min-height:320px;background:#fff;"><?= $post['content'] ?? '' ?></div>
                    <textarea class="d-none" id="postContent" name="content"><?= e($post['content'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">SEO Settings</h6>
                    <div class="mb-3">
                        <label class="form-label">Meta Title <span class="text-muted fw-normal">(max 60 chars)</span></label>
                        <input type="text" name="seo_title" class="form-control" maxlength="60" placeholder="Leave empty to use post title..." value="<?= e($seo['seo_title'] ?? '') ?>">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Meta Description <span class="text-muted fw-normal">(max 155 chars)</span></label>
                        <textarea name="meta_description" class="form-control" rows="2" maxlength="155" placeholder="Leave empty to use excerpt..."><?= e($seo['meta_description'] ?? '') ?></textarea>
                    </div>
                    <?php if ($post): ?>
                        <div class="form-text mt-2">Need canonical URLs, Open Graph, or schema markup? Use <a href="<?= url('/admin/blog/posts/' . $post['id'] . '/seo') ?>">Advanced SEO</a>.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Publish</h6>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" id="postStatus">
                            <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="scheduled" <?= ($post['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                        <?php if (!can('blog.publish')): ?>
                            <div class="form-text text-warning">You don't have permission to publish — posts will be saved as drafts.</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3" id="scheduledAtGroup">
                        <label class="form-label">Scheduled For</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="<?= e(!empty($post['scheduled_at']) ? str_replace(' ', 'T', substr($post['scheduled_at'], 0, 16)) : '') ?>">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" class="form-check-input" id="isFeatured" name="is_featured" value="1" <?= !empty($post['is_featured']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="isFeatured">Featured Post</label>
                        <div class="form-text">Featured posts appear at the top of the blog listing.</div>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="commentsEnabled" name="comments_enabled" value="1" <?= ($post['comments_enabled'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="commentsEnabled">Allow comments</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100"><?= $post ? 'Save Post' : 'Publish Post' ?></button>
                    <a href="<?= url('/admin/blog/posts') ?>" class="btn btn-link w-100 mt-1">Cancel</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Featured Image</h6>
                    <?= \App\Core\SectionForm::renderStandaloneMedia('featured_image', '', $post['featured_image'] ?? null, '1200×630px') ?>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Categorization</h6>

                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($categories as $cat): ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="categories[]" value="<?= (int) $cat['id'] ?>" id="cat<?= $cat['id'] ?>" <?= in_array($cat['id'], $postCategoryIds) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="cat<?= $cat['id'] ?>"><?= e($cat['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?><span class="text-muted small">No categories yet — <a href="<?= url('/admin/blog/categories') ?>">add one</a>.</span><?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Tags</label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($tags as $tag): ?>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="tags[]" value="<?= (int) $tag['id'] ?>" id="tag<?= $tag['id'] ?>" <?= in_array($tag['id'], $postTagIds) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tag<?= $tag['id'] ?>"><?= e($tag['name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($tags)): ?><span class="text-muted small">No tags yet — <a href="<?= url('/admin/blog/tags') ?>">add one</a>.</span><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    (function () {
        var statusSelect = document.getElementById('postStatus');
        var scheduledGroup = document.getElementById('scheduledAtGroup');
        if (!statusSelect || !scheduledGroup) return;

        function toggleScheduled() {
            scheduledGroup.classList.toggle('d-none', statusSelect.value !== 'scheduled');
        }
        statusSelect.addEventListener('change', toggleScheduled);
        toggleScheduled();
    })();
</script>
