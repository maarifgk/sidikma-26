<div style="display: grid; gap: 1rem;">
    <div style="display: flex; justify-content: flex-end;">
        <label>
            <span class="sr-only">Tahun Pelajaran</span>
            <select
                wire:model.live="academicYear"
                style="min-width: 8.5rem; border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.625rem 0.75rem; background: transparent;"
            >
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $academicYearOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </select>
        </label>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr)); gap: 1rem;">
        <div class="fi-section" style="padding: 1.25rem; text-align: center;">
            <strong style="display: block; font-size: 1.5rem;"><?php echo e(number_format($totalEducators, 0, ',', '.')); ?></strong>
            <span>Total Tenaga (rekap)</span>
        </div>
        <div class="fi-section" style="padding: 1.25rem; text-align: center;">
            <strong style="display: block; font-size: 1.5rem;"><?php echo e(number_format($completedSchools, 0, ',', '.')); ?></strong>
            <span>Madrasah Sudah Mengisi</span>
        </div>
        <div class="fi-section" style="padding: 1.25rem; text-align: center;">
            <strong style="display: block; font-size: 1.5rem;"><?php echo e(number_format($incompleteSchools, 0, ',', '.')); ?></strong>
            <span>Madrasah Belum Mengisi</span>
        </div>
        <div class="fi-section" style="padding: 1.25rem; text-align: center;">
            <strong style="display: block; font-size: 1.5rem;"><?php echo e(number_format($totalSchools, 0, ',', '.')); ?></strong>
            <span>Total Madrasah</span>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\yayasan-app\resources\views/filament/admin/resources/educator-recaps/summary.blade.php ENDPATH**/ ?>