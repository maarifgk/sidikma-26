<?php
    $today = now('Asia/Jakarta');
    $dashboardUser = auth()->user();
    $dashboardScope = $dashboardUser instanceof \App\Models\User
        ? app(\App\Services\DashboardMetrics::class)->scopeLabel($dashboardUser)
        : 'Tidak tersedia';
?>

<?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <div
        style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem 2rem;"
    >
        <div style="min-width: min(100%, 18rem);">
            <p style="margin-bottom: 0.25rem; font-size: 0.875rem;">Selamat datang di</p>
            <h1 style="font-size: 1.5rem; font-weight: 700; line-height: 1.25;">
                Dashboard Sistem Informasi Yayasan
            </h1>
            <p style="margin-top: 0.5rem; max-width: 48rem; font-size: 0.875rem;">
                Pusat pengelolaan administrasi yayasan, sekolah, pengguna, dan sumber daya manusia.
            </p>
            <p style="margin-top: 0.25rem; max-width: 48rem; font-size: 0.8rem; font-weight: 600;">
                Cakupan data: <?php echo e($dashboardScope); ?>

            </p>
        </div>

        <time
            datetime="<?php echo e($today->toDateString()); ?>"
            style="white-space: nowrap; font-size: 0.875rem; font-weight: 600;"
        >
            <?php echo e($today->locale('id')->translatedFormat('l, d F Y')); ?>

        </time>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\yayasan-app\resources\views/filament/admin/components/dashboard-banner.blade.php ENDPATH**/ ?>