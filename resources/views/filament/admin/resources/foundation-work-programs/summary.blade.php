<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem; margin-bottom: 1rem;">
    <div class="fi-section" style="padding: 1.5rem; border-inline-start: 0.35rem solid #22c55e;">
        <strong style="display: block; margin-bottom: 0.65rem; font-size: 1.15rem;">Terlaksana</strong>
        <span style="font-size: 1rem;">{{ number_format($completedPercentage, 1, ',', '.') }}%</span>
    </div>
    <div class="fi-section" style="padding: 1.5rem; border-inline-start: 0.35rem solid #f59e0b;">
        <strong style="display: block; margin-bottom: 0.65rem; font-size: 1.15rem;">Belum Terlaksana</strong>
        <span style="font-size: 1rem;">{{ number_format($plannedPercentage, 1, ',', '.') }}%</span>
    </div>
    <div class="fi-section" style="padding: 1.5rem; border-inline-start: 0.35rem solid #ef4444;">
        <strong style="display: block; margin-bottom: 0.65rem; font-size: 1.15rem;">Tidak Terlaksana</strong>
        <span style="font-size: 1rem;">{{ number_format($notImplementedPercentage, 1, ',', '.') }}%</span>
    </div>
</div>
