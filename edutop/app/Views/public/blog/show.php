<?php $imgUrl = media_url($post['featured_image'] ?? null); ?>
<article class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="fw-bold mb-2"><?= e($post['title']) ?></h1>
                <p class="text-muted mb-4">
                    <?= e($post['published_at'] ?? $post['created_at']) ?>
                    <?php if (!empty($categories)): ?>
                        &middot;
                        <?php $categoryLinks = array_map(fn($cat) => '<a href="' . e(url('/blog?category=' . $cat['slug'])) . '">' . e($cat['name']) . '</a>', $categories); ?>
                        <?= implode(', ', $categoryLinks) ?>
                    <?php endif; ?>
                </p>

                <?php if ($imgUrl): ?>
                    <img src="<?= e($imgUrl) ?>" alt="<?= e($post['title']) ?>" class="img-fluid rounded mb-4 w-100" style="aspect-ratio:16/9;object-fit:cover;">
                <?php endif; ?>

                <?php /* Sanitized server-side via App\Core\Sanitizer::richText() on save — safe to output raw. */ ?>
                <div class="blog-content mb-4"><?= $post['content'] ?? '' ?></div>

                <?php if (!empty($tags)): ?>
                    <div class="d-flex flex-wrap gap-2 mb-5">
                        <?php foreach ($tags as $tag): ?>
                            <a href="<?= url('/blog?tag=' . $tag['slug']) ?>" class="badge bg-light text-dark text-decoration-none">#<?= e($tag['name']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($related)): ?>
                    <h5 class="fw-bold mb-3">Related Posts</h5>
                    <div class="row g-3 mb-5">
                        <?php foreach ($related as $rp): ?>
                            <div class="col-md-4">
                                <a href="<?= url('/blog/' . $rp['slug']) ?>" class="text-decoration-none text-dark">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body">
                                            <div class="fw-bold small"><?= e($rp['title']) ?></div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <hr>

                <h5 class="fw-bold mb-3">Comments (<?= count($comments) ?>)</h5>
                <?php foreach ($comments as $comment): ?>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="fw-bold"><?= e($comment['author_name']) ?></div>
                        <div class="text-muted small mb-1"><?= e($comment['created_at']) ?></div>
                        <p class="mb-0"><?= e($comment['body']) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($comments)): ?>
                    <p class="text-muted">No comments yet. Be the first to comment!</p>
                <?php endif; ?>

                <?php if ($post['comments_enabled']): ?>
                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">Leave a Comment</h6>
                        <form method="POST" action="<?= url('/blog/' . $post['slug'] . '/comments') ?>">
                            <?= csrf_field() ?>
                            <div class="d-none" aria-hidden="true">
                                <label>Leave this field blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <input type="text" name="name" class="form-control" placeholder="Your name" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="email" name="email" class="form-control" placeholder="Your email" required>
                                </div>
                            </div>
                            <div class="mb-2">
                                <textarea name="body" class="form-control" rows="3" placeholder="Your comment" required></textarea>
                            </div>
                            <?= recaptcha_field() ?>
                            <button type="submit" class="btn btn-primary">Submit Comment</button>
                            <div class="form-text">Comments are moderated and will appear after approval.</div>
                        </form>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Comments are closed for this post.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</article>
