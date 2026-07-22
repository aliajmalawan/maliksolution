  </div><!-- /.page-content -->
</div><!-- /.main-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
var sidebar   = document.getElementById('sidebar');
var overlay   = document.getElementById('sbOverlay');
var hamburger = document.getElementById('hamburger');
function openSidebar()  { sidebar.classList.add('open');    overlay.classList.add('show');    document.body.style.overflow = 'hidden'; }
function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('show'); document.body.style.overflow = ''; }
hamburger.addEventListener('click', function() { sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
overlay.addEventListener('click', closeSidebar);
<?php if (!empty($page_js)) echo $page_js; ?>
</script>
</body>
</html>
