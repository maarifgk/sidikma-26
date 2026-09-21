<div class="sipinter-template-panel">
    <a
        href="<?php echo e(route('sipinter.template.download')); ?>"
        class="sipinter-template-button"
    >
        Download PDF
    </a>

    <p>Silakan download dan sesuaikan template surat permohonan sebelum input data!</p>
</div>

<style>
    .sipinter-template-panel {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .75rem;
        margin: 0 -1.5rem 1rem;
        padding: 1rem 1.5rem 1.25rem;
        border-top: 1px solid rgb(209 213 219);
        border-bottom: 1px solid rgb(209 213 219);
        text-align: center;
    }

    .sipinter-template-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2.5rem;
        padding: .625rem 1.25rem;
        border-radius: .5rem;
        background: #ef4444;
        color: #fff;
        font-weight: 600;
        box-shadow: 0 2px 5px rgb(0 0 0 / 18%);
        text-decoration: none;
    }

    .sipinter-template-button:hover {
        background: #dc2626;
    }

    .sipinter-template-panel p {
        margin: 0;
        color: #ef4444;
        font-size: .95rem;
    }
</style>
<?php /**PATH C:\laragon\www\yayasan-app\resources\views/filament/admin/resources/sipinter-updates/template-download.blade.php ENDPATH**/ ?>