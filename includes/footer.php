<?php if ($current_user): ?>
    </div><!-- content-area -->
  </div><!-- main-content -->
</div><!-- wrapper -->
<?php endif; ?>
<script>window.APP_URL = <?= json_encode(APP_URL) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/assets/vendor/html5-qrcode/html5-qrcode.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
</body>
</html>
