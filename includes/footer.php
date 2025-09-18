    <!-- Footer -->
    <footer class="bg-light text-center py-3 mt-5 no-print">
        <div class="container">
            <p class="mb-0 text-muted">
                &copy; <?php echo date('Y'); ?> OneClick Insurance Web Aggregator Pvt Ltd. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo isset($js_path) ? $js_path : '../assets/js/script.js'; ?>"></script>
    
    <!-- Page specific JS -->
    <?php if (isset($custom_js)): ?>
        <script><?php echo $custom_js; ?></script>
    <?php endif; ?>
</body>
</html>
