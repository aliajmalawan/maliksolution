  </main>
  <footer style="background:var(--navy);color:rgba(255,255,255,.35);font-size:.7rem;text-align:center;padding:1rem 0;margin-top:auto">
    &copy; <?= date('Y') ?> Malik Solution &nbsp;&middot;&nbsp; Student Portal
  </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
var sidebar   = document.getElementById('sidebar');
var overlay   = document.getElementById('sbOverlay');
var hamburger = document.getElementById('hamburger');
function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('show');    document.body.style.overflow='hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); document.body.style.overflow=''; }
hamburger.addEventListener('click', function() { sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
overlay.addEventListener('click', closeSidebar);

/* Quick Links sidebar dropdown */
var qlToggle = document.getElementById('qlToggle');
var qlMenu   = document.getElementById('qlMenu');
if (qlToggle && qlMenu) {
  if (sessionStorage.getItem('ql_open') !== 'false') {
    qlMenu.classList.add('open'); qlToggle.classList.add('open');
  }
  qlToggle.addEventListener('click', function() {
    var isOpen = qlMenu.classList.toggle('open');
    qlToggle.classList.toggle('open', isOpen);
    sessionStorage.setItem('ql_open', isOpen);
  });
}
<?php if (!empty($extra_js)) echo $extra_js; ?>
</script>
</body>
</html>
