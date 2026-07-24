<section class="py-5">
    <div class="container">
        <h1 class="fw-bold mb-4">Blog</h1>

        <div class="row g-4">
            <div class="col-lg-8">
                <?php if (empty($posts)): ?>
                    <p class="text-muted">No posts found.</p>
                <?php endif; ?>

                <div class="row g-4">
                    <?php foreach ($posts as $post): ?>
                        <?php $imgUrl = media_url($post['featured_image'] ?? null); ?>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <?php if ($imgUrl): ?>
                                    <a href="<?= url('/blog/' . $post['slug']) ?>">
                                        <img src="<?= e($imgUrl) ?>" class="card-img-top" alt="<?= e($post['title']) ?>" style="aspect-ratio:16/9;object-fit:cover;">
                                    </a>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="fw-bold"><a href="<?= url('/blog/' . $post['slug']) ?>" class="text-decoration-none text-dark"><?= e($post['title']) ?></a></h5>
                                    <p class="text-muted small mb-2"><?= e($post['published_at'] ?? $post['created_at']) ?></p>
                                    <p class="text-muted"><?= e($post['excerpt']) ?></p>
                                    <a href="<?= url('/blog/' . $post['slug']) ?>" class="btn btn-sm btn-outline-primary">Read More</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5">
                        <ul class="pagination">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <?php
                                $query = array_filter(array_merge($filters, ['page' => $p]));
                                $qs = http_build_query($query);
                                ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= url('/blog?' . $qs) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Search</h6>
                        <form method="GET" action="<?= url('/blog') ?>" class="d-flex gap-2">
                            <input type="search" name="q" class="form-control" placeholder="Search posts..." value="<?= e($filters['q'] ?? '') ?>">
                            <button class="btn btn-outline-primary">Go</button>
                        </form>
                    </div>
                </div>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Categories</h6>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($categories as $cat): ?>
                                <li class="mb-1"><a href="<?= url('/blog?category=' . $cat['slug']) ?>" class="<?= ($filters['category'] ?? '') === $cat['slug'] ? 'fw-bold' : '' ?>"><?= e($cat['name']) ?></a></li>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?><li class="text-muted">None yet.</li><?php endif; ?>
                        </ul>
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Tags</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($tags as $tag): ?>
                                <a href="<?= url('/blog?tag=' . $tag['slug']) ?>" class="badge <?= ($filters['tag'] ?? '') === $tag['slug'] ? 'bg-primary' : 'bg-light text-dark' ?> text-decoration-none"><?= e($tag['name']) ?></a>
                            <?php endforeach; ?>
                            <?php if (empty($tags)): ?><span class="text-muted">None yet.</span><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
