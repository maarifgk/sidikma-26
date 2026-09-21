<?php

namespace App\Filament\Admin\Pages;

use App\Models\Foundation;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;

class InstitutionProfile extends Page
{
    public const SECTION_IDENTITY = 'identitas';

    public const SECTION_STRUCTURE = 'struktur-pengurus';

    public const SECTION_WORK_PROGRAM = 'program-kerja';

    public const SECTION_ANNUAL_REPORT = 'laporan-tahunan';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'profile-lembaga';

    #[Url]
    public string $section = self::SECTION_IDENTITY;

    public function mount(): void
    {
        if (! array_key_exists($this->section, self::sectionLabels())) {
            $this->section = self::SECTION_IDENTITY;
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('foundation.view') ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return self::sectionLabels()[$this->section] ?? 'Profile Lembaga';
    }

    public function getSubheading(): ?string
    {
        return 'Kelola informasi kelembagaan dalam satu menu yang terstruktur.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.pages.institution-profile-content')
                    ->viewData(fn (): array => [
                        'section' => $this->section,
                        'sectionLabel' => self::sectionLabels()[$this->section],
                        'foundation' => $this->getFoundation(),
                        'boardMembers' => $this->getFoundation()->boardMembers()->get(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadBanner')
                ->label(fn (): string => filled($this->getFoundation()->banner_path)
                    ? 'Ganti Banner'
                    : 'Upload Banner')
                ->icon('heroicon-o-photo')
                ->schema([
                    FileUpload::make('banner_path')
                        ->label('Banner Lembaga')
                        ->disk('public')
                        ->directory('institution-banners')
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatioOptions(['4:1'])
                        ->imageResizeMode('cover')
                        ->imageResizeTargetWidth('1600')
                        ->imageResizeTargetHeight('400')
                        ->imageResizeUpscale(true)
                        ->rules(['dimensions:width=1600,height=400'])
                        ->maxSize(1024000)
                        ->required()
                        ->helperText('Gunakan gambar berukuran tepat 1600 × 400 px. Maksimal 1000 MB.'),
                ])
                ->fillForm(fn (): array => [
                    'banner_path' => $this->getFoundation()->banner_path,
                ])
                ->visible(fn (): bool => $this->section === self::SECTION_IDENTITY)
                ->action(function (array $data, Action $action): void {
                    $foundation = $this->getFoundation();
                    $oldBannerPath = $foundation->banner_path;
                    $newBannerPath = $data['banner_path'];

                    $foundation->update(['banner_path' => $newBannerPath]);

                    if (filled($oldBannerPath) && ($oldBannerPath !== $newBannerPath)) {
                        Storage::disk('public')->delete($oldBannerPath);
                    }

                    $action->successNotificationTitle('Banner lembaga berhasil disimpan')->success();
                }),
            Action::make('deleteBanner')
                ->label('Hapus Banner')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus banner lembaga?')
                ->visible(fn (): bool => (
                    $this->section === self::SECTION_IDENTITY
                    && filled($this->getFoundation()->banner_path)
                ))
                ->action(function (Action $action): void {
                    $foundation = $this->getFoundation();
                    $bannerPath = $foundation->banner_path;

                    $foundation->update(['banner_path' => null]);

                    if (filled($bannerPath)) {
                        Storage::disk('public')->delete($bannerPath);
                    }

                    $action->successNotificationTitle('Banner lembaga berhasil dihapus')->success();
                }),
            Action::make('editIdentity')
                ->label('Edit Identitas')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => $this->section === self::SECTION_IDENTITY)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Lembaga')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('address')
                        ->label('Alamat')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->maxLength(255),
                    TextInput::make('instagram')
                        ->label('Instagram')
                        ->placeholder('@lpm_gunungkidul')
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Nomor Telepon/WhatsApp')
                        ->tel()
                        ->maxLength(30)
                        ->regex('/^[0-9+\-\s().]+$/'),
                ])
                ->fillForm(fn (): array => $this->getFoundation()
                    ->only(['name', 'address', 'email', 'instagram', 'phone']))
                ->action(function (array $data, Action $action): void {
                    $this->getFoundation()->update($data);

                    $action->successNotificationTitle('Identitas lembaga berhasil diperbarui')->success();
                }),
            Action::make('editStructure')
                ->label('Edit Struktur')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => $this->section === self::SECTION_STRUCTURE)
                ->schema([
                    TextInput::make('board_heading')
                        ->label('Judul Susunan Pengurus')
                        ->placeholder("Susunan Pengurus LP Ma'arif NU Gunungkidul")
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('board_term')
                        ->label('Masa Jabatan')
                        ->placeholder('2026-2031')
                        ->required()
                        ->maxLength(100)
                        ->columnSpanFull(),
                    Repeater::make('members')
                        ->label('Daftar Pengurus')
                        ->schema([
                            TextInput::make('position')
                                ->label('Jabatan/Bidang')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name')
                                ->label('Nama Pengurus')
                                ->placeholder('Boleh dikosongkan untuk judul bagian')
                                ->maxLength(255),
                        ])
                        ->columns(2)
                        ->reorderable()
                        ->collapsible()
                        ->addActionLabel('Tambah Pengurus')
                        ->columnSpanFull(),
                ])
                ->fillForm(function (): array {
                    $foundation = $this->getFoundation();

                    return [
                        'board_heading' => $foundation->board_heading
                            ?? "Susunan Pengurus {$foundation->name}",
                        'board_term' => $foundation->board_term ?? '',
                        'members' => $foundation->boardMembers()
                            ->get(['position', 'name'])
                            ->map(fn ($member): array => [
                                'position' => $member->position,
                                'name' => $member->name,
                            ])
                            ->all(),
                    ];
                })
                ->action(function (array $data, Action $action): void {
                    $foundation = $this->getFoundation();

                    DB::transaction(function () use ($data, $foundation): void {
                        $foundation->update([
                            'board_heading' => $data['board_heading'],
                            'board_term' => $data['board_term'],
                        ]);

                        $foundation->boardMembers()->delete();

                        foreach (array_values($data['members'] ?? []) as $index => $member) {
                            $foundation->boardMembers()->create([
                                'position' => $member['position'],
                                'name' => $member['name'] ?? null,
                                'sort_order' => $index + 1,
                            ]);
                        }
                    });

                    $action->successNotificationTitle('Struktur pengurus berhasil diperbarui')->success();
                }),
        ];
    }

    private function getFoundation(): Foundation
    {
        return Foundation::application();
    }

    /** @return array<string, string> */
    public static function sectionLabels(): array
    {
        return [
            self::SECTION_IDENTITY => 'Identitas Lembaga',
            self::SECTION_STRUCTURE => 'Struktur Pengurus',
            self::SECTION_WORK_PROGRAM => 'Program Kerja',
            self::SECTION_ANNUAL_REPORT => 'Laporan Tahunan',
        ];
    }
}
