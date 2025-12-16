<?php
// super_admin/includes/footer.php - Common footer for all super_admin pages
?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($extra_js)): ?>
    <script><?php echo $extra_js; ?></script>
    <?php endif; ?>
</body>
</html>
