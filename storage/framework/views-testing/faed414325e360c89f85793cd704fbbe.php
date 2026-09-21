<footer
    aria-label="Informasi aplikasi"
    style="display: flex; width: 100%; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.5rem 1rem; padding: 1rem 1.5rem; font-size: 0.75rem;"
>
    <span>
        &copy; <?php echo e(now('Asia/Jakarta')->year); ?> <?php echo e(filament()->getBrandName()); ?>.
        Hak cipta dilindungi.
    </span>

    <span>Versi <?php echo e(config('app.version')); ?></span>
</footer>
<?php /**PATH C:\laragon\www\yayasan-app\resources\views/filament/admin/components/footer.blade.php ENDPATH**/ ?>