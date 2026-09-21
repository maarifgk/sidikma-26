<div style="display: grid; gap: 1rem;">
    <section class="fi-section" style="overflow: hidden;">
        <div style="padding: 0.9rem 1rem; background: var(--primary-600); color: var(--primary-contrast-color, white); font-weight: 700;">
            DATA MADRASAH/SEKOLAH
        </div>

        <dl style="margin: 0; padding: 0.5rem 1rem 1rem;">
            @foreach ([
                'Sekolah/Madrasah' => $school->name,
                'NPSN' => $school->npsn,
                'Email' => $school->email,
                'Jenjang' => $school->school_level,
                'Status Akreditasi' => $school->accreditation_status,
                'Masa Akreditasi' => $school->accreditation_expiry_year,
                'Status Tanah' => $school->land_status,
                'Luas Tanah' => filled($school->land_area) ? number_format((float) $school->land_area, 2, ',', '.') . ' m²' : null,
                'Kepemilikan Sertifikat Tanah' => is_null($school->has_land_certificate) ? null : ($school->has_land_certificate ? 'Sudah Memiliki Sertifikat' : 'Belum Memiliki Sertifikat'),
                'Kepemilikan BHPNU' => is_null($school->has_bhpnu_ownership) ? null : ($school->has_bhpnu_ownership ? 'Sudah Memiliki BHPNU' : 'Belum Memiliki BHPNU'),
                'Alamat Madrasah/Sekolah' => $school->address,
                "Jumlah Siswa ({$studentAcademicYear})" => number_format($studentTotal, 0, ',', '.') . ' Siswa',
                "Jumlah Tenaga Pendidik Rekap ({$educatorAcademicYear})" => number_format($educatorTotal, 0, ',', '.') . ' Tenaga Pendidik',
                'Jumlah GTY Sertifikasi Inpassing' => number_format($foundationCertified, 0, ',', '.') . ' Tenaga Pendidik',
                'Jumlah GTY Sertifikasi Non Inpassing' => '0 Tenaga Pendidik',
                'Jumlah GTY Non Sertifikasi' => number_format($foundationUncertified, 0, ',', '.') . ' Tenaga Pendidik',
                'Jumlah PNS Sertifikasi' => number_format($asnCertified, 0, ',', '.') . ' Tenaga Pendidik',
                'Jumlah PNS Non Sertifikasi' => number_format($asnUncertified, 0, ',', '.') . ' Tenaga Pendidik',
                'Jumlah GTT' => number_format($gttTotal, 0, ',', '.') . ' Tenaga Pendidik',
                'Jumlah PTY' => number_format($permanentFoundationStaffTotal, 0, ',', '.') . ' Tenaga Pendidik',
                'Jumlah PTT' => number_format($nonPermanentStaffTotal, 0, ',', '.') . ' Tenaga Pendidik',
                'Jumlah Guru/Pegawai Aktif di Master Data' => number_format($masterEmployeeTotal, 0, ',', '.') . ' Orang',
                'Jumlah Guru Aktif' => number_format($teacherTotal, 0, ',', '.') . ' Orang',
                'Jumlah Pegawai Aktif' => number_format($staffTotal, 0, ',', '.') . ' Orang',
            ] as $label => $value)
                <div style="display: grid; grid-template-columns: minmax(12rem, 34%) 1fr; gap: 1rem; padding: 0.7rem 0.75rem; border-bottom: 1px solid var(--gray-200);">
                    <dt style="color: var(--gray-600);">{{ $label }}</dt>
                    <dd style="margin: 0;"><span aria-hidden="true">:</span> {{ filled($value) ? $value : '-' }}</dd>
                </div>
            @endforeach
        </dl>
    </section>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem;">
        @forelse ($positionSummaries as $position)
            <section class="fi-section" style="padding: 1rem; border-top: 0.3rem solid var(--primary-600);">
                <strong style="display: block;">{{ $position->name }}</strong>
                <span>{{ number_format($position->total, 0, ',', '.') }} Orang</span>
            </section>
        @empty
            <section class="fi-section" style="padding: 1rem;">
                <strong style="display: block;">Ketugasan</strong>
                <span>Belum ada penugasan aktif.</span>
            </section>
        @endforelse
    </div>
</div>
