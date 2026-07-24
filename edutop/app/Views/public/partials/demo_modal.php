<div class="modal fade" id="demoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule a Campus Visit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="<?= url('/leads/demo') ?>">
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="<?= e(current_path()) ?>">
                    <div class="d-none" aria-hidden="true">
                        <label>Leave this field blank<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Child's Class / Grade of Interest</label>
                        <input type="text" name="school_name" class="form-control" placeholder="e.g. Class 3, Intermediate Pre-Medical">
                    </div>
                    <?= recaptcha_field() ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Request Visit</button>
                </div>
            </form>
        </div>
    </div>
</div>
