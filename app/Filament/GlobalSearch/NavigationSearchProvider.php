<?php

namespace App\Filament\GlobalSearch;

use Filament\Facades\Filament;
use Filament\GlobalSearch\GlobalSearchResult;
use Filament\GlobalSearch\GlobalSearchResults;
use Filament\GlobalSearch\Providers\Contracts\GlobalSearchProvider;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Str;

class NavigationSearchProvider implements GlobalSearchProvider
{
    private const RESULT_LIMIT = 10;

    /**
     * Additional terms are kept here so menu wording can evolve without
     * spreading search-specific aliases throughout the panel providers.
     *
     * @var array<string, array<int, string>>
     */
    private const ALIASES = [
        'Admin' => ['administrator', 'admin induk', 'akun admin', 'pengguna'],
        'Administrasi' => ['pengajuan', 'layanan', 'service'],
        'Asal Madrasah' => ['madrasah', 'sekolah', 'lembaga'],
        'Pesanan Batik' => ['batik', 'produk batik', "batik ma'arif"],
        'Bendahara' => ['kas', 'keuangan', 'transaksi'],
        'Data Akun Tenaga Pendidik' => ['guru', 'pegawai', 'akun guru', 'tenaga pendidik'],
        'Data Jumlah Siswa' => ['siswa', 'murid', 'peserta didik'],
        'Data Jumlah Tenaga Pendidik' => ['guru', 'pegawai', 'pendidik'],
        'Edit Info Pembayaran' => ['rekening', 'informasi bayar', 'payment'],
        'File SK' => ['sk', 'surat keputusan', 'dokumen sk'],
        'Guru dan Pegawai' => ['guru', 'pegawai', 'tenaga pendidik', 'akun guru'],
        'Kelembagaan' => ['lembaga', 'madrasah', 'sekolah'],
        'Laporan Keuangan' => ['laporan', 'keuangan', 'finance'],
        'Modul' => ['materi', 'dokumen modul'],
        'Pembayaran' => ['bayar', 'tagihan', 'billing', 'payment'],
        'Pengajuan Penonaktifan' => ['keaktifan', 'nonaktif', 'penonaktifan'],
        'Pengajuan Persuratan' => ['surat', 'persuratan'],
        'Pengajuan Proposal' => ['proposal'],
        'Perbaikan SK' => ['revisi sk', 'koreksi sk', 'perbaikan'],
        'Presensi' => ['absen', 'absensi', 'kehadiran'],
        'Profile Lembaga' => ['profil lembaga', 'yayasan'],
        'Profile Sekolah' => ['profil sekolah', 'profil madrasah'],
        'Profil' => ['profile', 'akun saya', 'biodata'],
        'SK Yayasan' => ['sk', 'surat keputusan', 'file sk'],
        'Update Data Sipinter' => ['sipinter', 'update data'],
        'Usulan Mutasi' => ['mutasi', 'pindah'],
        'Usulan SK Baru' => ['sk', 'surat keputusan', 'pengajuan sk'],
    ];

    public function getResults(string $query): ?GlobalSearchResults
    {
        $query = $this->normalize($query);

        if ($query === '') {
            return GlobalSearchResults::make();
        }

        $matches = collect(Filament::getNavigation())
            ->flatMap(function ($group): array {
                $groupLabel = $group->getLabel() ?: 'Menu';

                return collect($group->getItems())
                    ->flatMap(fn (NavigationItem $item): array => $this->flattenItem($item, $groupLabel))
                    ->all();
            })
            ->filter(fn (array $entry): bool => filled($entry['url']))
            ->map(function (array $entry) use ($query): array {
                $entry['score'] = $this->score($entry, $query);

                return $entry;
            })
            ->filter(fn (array $entry): bool => $entry['score'] !== null)
            ->sortBy([
                ['score', 'asc'],
                ['title', 'asc'],
            ])
            ->unique(fn (array $entry): string => $entry['title'].'|'.$entry['url'])
            ->take(self::RESULT_LIMIT)
            ->map(fn (array $entry): GlobalSearchResult => new GlobalSearchResult(
                title: $entry['title'],
                url: $entry['url'],
                details: ['Lokasi' => $entry['location']],
            ));

        return GlobalSearchResults::make()
            ->category('Menu dan Halaman', $matches);
    }

    /**
     * @return array<int, array{title: string, url: ?string, location: string, searchable: array<int, string>}>
     */
    private function flattenItem(NavigationItem $item, string $group, ?string $parent = null): array
    {
        $title = $item->getLabel();
        $location = $parent ?? $group;
        $entries = [[
            'title' => $title,
            'url' => $item->getUrl(),
            'location' => $location,
            'searchable' => [
                $title,
                $parent,
                $group,
                ...(self::ALIASES[$title] ?? []),
            ],
        ]];

        foreach ($item->getChildItems() as $childItem) {
            $entries = [
                ...$entries,
                ...$this->flattenItem($childItem, $group, $title),
            ];
        }

        return $entries;
    }

    /**
     * @param  array{title: string, searchable: array<int, string|null>}  $entry
     */
    private function score(array $entry, string $query): ?int
    {
        $title = $this->normalize($entry['title']);

        if ($title === $query) {
            return 0;
        }

        if (str_starts_with($title, $query)) {
            return 10;
        }

        if (str_contains($title, $query)) {
            return 20;
        }

        foreach ($entry['searchable'] as $term) {
            $term = $this->normalize((string) $term);

            if ($term === $query) {
                return 30;
            }

            if (str_starts_with($term, $query)) {
                return 40;
            }

            if (str_contains($term, $query)) {
                return 50;
            }
        }

        return null;
    }

    private function normalize(string $value): string
    {
        return (string) Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish();
    }
}
