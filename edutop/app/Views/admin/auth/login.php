<?php if (!empty($error)): ?>
    <div class="alert alert-danger py-2"><?= e($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= url('/admin/login') ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" id="email" name="email" value="<?= old('email') ?>" required autofocus>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" required>
            <button type="button" class="btn btn-outline-secondary" id="togglePasswordBtn" aria-label="Show password"><i class="bi bi-eye"></i></button>
        </div>
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
        <label class="form-check-label" for="remember">Remember me for 30 days</label>
    </div>
    <button type="submit" class="btn btn-primary w-100">Sign in</button>
</form>

<script>
document.getElementById('togglePasswordBtn').addEventListener('click', function () {
    var input = document.getElementById('password');
    var showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    this.querySelector('i').className = showing ? 'bi bi-eye' : 'bi bi-eye-slash';
    this.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
});
</script>
