<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <h2 class="mb-4 text-sm font-semibold text-gray-600 dark:text-gray-300">Placeholder Data Users</h2>
    <div class="space-y-1.5 text-xs leading-5 text-gray-600 dark:text-gray-300">
        @foreach ([
            ['nama_lengkap', 'Nama lengkap user'], ['email', 'Email user'], ['nis', 'EWANUGK / NPSN / NIS'],
            ['nuptk', 'NUPTK/NPK'], ['nip', 'NIP'], ['tempat_lahir', 'Tempat lahir'], ['tgl_lahir', 'Tanggal lahir format Indonesia'],
            ['tmt', 'TMT format Indonesia'], ['alamat', 'Alamat dari basis'], ['alamat_html', 'Alamat dengan line break HTML'],
            ['nama_kelas', 'Nama madrasah/sekolah'], ['nama_jurusan', 'Status kepegawaian'], ['ketugasan', 'Nama ketugasan'],
            ['periode_sk', 'Periode SK yayasan'], ['tmt_mulai', 'Pendidikan terakhir dan tahun lulus'], ['prodi_studi', 'Program studi'],
            ['nomor_sk', 'Nomor SK/nomor surat keputusan'], ['tanggal_sk', 'Tanggal penetapan SK'], ['tanggal_mulai', 'Tanggal mulai berlaku'],
            ['tanggal_selesai', 'Tanggal selesai berlaku'], ['masa_aktif', 'Masa berlaku atau masa tugas'], ['gaji_pokok', 'Nominal gaji pokok'],
            ['tunjangan_lain', 'Nominal tunjangan lain'], ['tanggal_cetak', 'Tanggal cetak dokumen'], ['waktu_cetak', 'Waktu dan jam cetak'],
            ['tahun', 'Tahun sekarang'], ['bulan', 'Bulan sekarang'], ['logo_url', 'Logo kop surat'],
        ] as [$token, $description])
            <div><span class="placeholder-token">&#123;&#123;{{ $token }}&#125;&#125;</span> <span>— {{ $description }}</span></div>
        @endforeach
    </div>
</div>
<style>
    .placeholder-token { color: #dc2626; font-weight: 600; }
</style>
