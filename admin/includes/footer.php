  </div><!-- /.admin-content -->
</div><!-- /.admin-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto dismiss alerts
document.querySelectorAll('.alert-auto-dismiss').forEach(function(el) {
    setTimeout(function() {
        el.style.transition = 'opacity .5s ease';
        el.style.opacity = '0';
        setTimeout(function() { el.remove(); }, 500);
    }, 4000);
});

// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm || 'آیا اطمینان دارید؟')) e.preventDefault();
    });
});

// Preview image before upload
function previewImg(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var prev = document.getElementById(previewId);
            if (prev) { prev.src = e.target.result; prev.style.display = 'block'; }
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
