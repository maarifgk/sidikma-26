<?php

namespace App\Filament\Resources\FoundationSkTemplates;

use App\Models\FoundationSkTemplate;
use Filament\Actions\{Action, ActionGroup, DeleteAction, EditAction};
use Filament\Forms\Components\{FileUpload, Hidden, Select, Textarea, TextInput, Toggle};
use Filament\Resources\Resource;
use Filament\Schemas\Components\{Grid, Section, View};
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FoundationSkTemplateResource extends Resource
{
    protected static ?string $model = FoundationSkTemplate::class;
    protected static ?string $slug = 'foundation-sk-templates';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationLabel = 'Template SK';
    protected static ?string $modelLabel = 'Template SK';
    protected static ?string $pluralModelLabel = 'Template SK';
    protected static UnitEnum|string|null $navigationGroup = 'SK Yayasan';

    public static function canViewAny(): bool { return auth()->user()?->isAdminInduk() || (auth()->user()?->can('sk-yayasan.view') ?? false); }
    public static function canCreate(): bool { return auth()->user()?->isAdminInduk() || (auth()->user()?->can('sk-yayasan.manage') ?? false); }
    public static function canEdit($record): bool { return static::canCreate(); }
    public static function canDelete($record): bool { return static::canCreate(); }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'xl' => 2])
                ->extraAttributes(['class' => 'foundation-sk-template-form'])
                ->schema([
                Grid::make(1)->extraAttributes(['class' => 'foundation-sk-editor-fields'])->schema([
                    Hidden::make('html_template')->default(''),
                    Section::make('Identitas Template')->description('Nama template dan judul file yang akan digunakan saat membuat PDF.')->schema([
                        Grid::make(2)->schema([
                        TextInput::make('name')->label('Nama Template')->required(),
                        TextInput::make('document_title')->label('Judul Dokumen / Nama File PDF')->required(),
                        ]),
                    ])->columns(1),
                    Section::make('Informasi Dasar Template')->description('Isi informasi utama terlebih dahulu, kemudian lanjutkan ke bagian isi SK.')->schema([
                        Textarea::make('description')->label('Deskripsi')->live(debounce: 500),
                        Grid::make(2)->schema([
                            Select::make('paper_size')->label('Ukuran Kertas')->options(['A4' => 'A4', 'LEGAL' => 'LEGAL', 'LETTER' => 'LETTER'])->required()->live(),
                            Select::make('orientation')->label('Orientasi')->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])->required()->live(),
                        ]),
                        Toggle::make('is_active')->label('Template Aktif')->default(true)->live(),
                    ]),
                    Section::make('Kepala Surat')->description('Isi teks kop surat yang tampil di bagian paling atas dokumen.')->schema([
                        Grid::make(2)->schema([
                            Textarea::make('builder_data.header.top_text')->label('Baris Atas')->rows(2)->live(debounce: 500),
                            TextInput::make('builder_data.header.font_top_text')->label('Ukuran Font')->numeric()->default(13)->live(debounce: 500),
                            TextInput::make('builder_data.header.institution_name')->label('Judul Lembaga')->live(debounce: 500),
                            TextInput::make('builder_data.header.font_institution_name')->label('Ukuran Font Judul')->numeric()->default(26)->live(debounce: 500),
                            TextInput::make('builder_data.header.address')->label('Alamat')->live(debounce: 500),
                            TextInput::make('builder_data.header.font_address')->label('Ukuran Font Alamat')->numeric()->default(11)->live(debounce: 500),
                            TextInput::make('builder_data.header.whatsapp')->label('Nomor WhatsApp')->live(debounce: 500),
                            TextInput::make('builder_data.header.font_contact')->label('Ukuran Font Kontak')->numeric()->default(11)->live(debounce: 500),
                            TextInput::make('builder_data.header.email')->label('Email Kop Surat')->live(debounce: 500),
                        ]),
                        FileUpload::make('builder_data.header.logo_path')
                            ->label('Logo Kop Surat')
                            ->helperText('Gunakan PNG, JPG, JPEG, atau WEBP. Maksimal 1,5 MB.')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->disk('public')
                            ->visibility('public')
                            ->directory('foundation-sk/logos')
                            ->maxSize(1536)
                            ->downloadable()
                            ->openable()
                            ->reorderable(false),
                    ])->columns(1),
                    Section::make('Judul Keputusan')->description('Atur judul, nomor, dan kalimat pembuka SK.')->schema([
                        TextInput::make('builder_data.decision.title')->label('Judul SK')->live(debounce: 500),
                        TextInput::make('builder_data.decision.font_title')->label('Font Judul SK')->numeric()->default(13)->live(debounce: 500),
                        TextInput::make('builder_data.decision.number')->label('Nomor SK')->live(debounce: 500),
                        TextInput::make('builder_data.decision.font_number')->label('Font Nomor SK')->numeric()->default(12)->live(debounce: 500),
                        Textarea::make('builder_data.decision.opening')->label('Kalimat Pembuka')->rows(2)->live(debounce: 500),
                        TextInput::make('builder_data.decision.font_opening')->label('Font Pembuka')->numeric()->default(11)->live(debounce: 500),
                    ])->columns(2),
                    Section::make('Konsideran')->description('Boleh memakai placeholder seperti {{nama_kelas}}, {{ketugasan}}, dan lainnya.')->schema([
                        Textarea::make('builder_data.sections.menimbang')->label('Menimbang')->rows(3)->live(debounce: 500),
                        TextInput::make('builder_data.fonts.section_title')->label('Ukuran Font Konsideran')->numeric()->default(11)->live(debounce: 500),
                        Textarea::make('builder_data.sections.mengingat')->label('Mengingat')->rows(3)->live(debounce: 500),
                        Textarea::make('builder_data.sections.memperhatikan')->label('Memperhatikan')->rows(3)->live(debounce: 500),
                    ])->columns(2),
                    Section::make('Isi Keputusan')->schema([
                        TextInput::make('builder_data.sections.judul_tengah')->label('Judul Tengah')->live(debounce: 500),
                        TextInput::make('builder_data.fonts.judul_tengah')->label('Ukuran Font Judul')->numeric()->default(11)->live(debounce: 500),
                        Textarea::make('builder_data.sections.memutuskan')->label('Teks Keputusan')->rows(3)->live(debounce: 500),
                        TextInput::make('builder_data.fonts.body')->label('Ukuran Font Isi')->numeric()->default(11)->live(debounce: 500),
                        Textarea::make('builder_data.sections.menetapkan')->label('Teks Penetapan')->rows(3)->live(debounce: 500),
                        TextInput::make('builder_data.fonts.data_user')->label('Ukuran Font Data Guru')->numeric()->default(11)->live(debounce: 500),
                    ])->columns(2),
                    Section::make('Area Tanda Tangan')->description('Bagian ini hanya area teks penetapan, tanpa gambar tanda tangan.')->schema([
                        TextInput::make('builder_data.signature.ditetapkan_di')->label('Ditetapkan Di')->live(debounce: 500),
                        TextInput::make('builder_data.signature.label_tanggal')->label('Label Tanggal')->live(debounce: 500),
                        TextInput::make('builder_data.signature.tanggal_penetapan')->label('Tanggal Penetapan')->live(debounce: 500),
                        TextInput::make('builder_data.signature.baris_pengurus')->label('Baris Pengurus')->live(debounce: 500),
                        TextInput::make('builder_data.signature.jabatan')->label('Jabatan')->live(debounce: 500),
                        TextInput::make('builder_data.signature.nama')->label('Nama Penanda Tangan')->live(debounce: 500),
                        TextInput::make('builder_data.fonts.signature')->label('Ukuran Font Tanda Tangan')->numeric()->default(11)->live(debounce: 500)->columnStart(2),
                    ])->columns(2),
                    Section::make('Tembusan')->description('Opsional. Buka bagian ini jika dokumen memiliki daftar tembusan.')->schema([
                        TextInput::make('builder_data.copies.title')->label('Judul Tembusan')->live(debounce: 500),
                        TextInput::make('builder_data.fonts.copies_title')->label('Ukuran Font Judul')->numeric()->default(11)->live(debounce: 500),
                        Textarea::make('builder_data.copies.items')->label('Daftar Tembusan')->rows(3)->live(debounce: 500),
                        TextInput::make('builder_data.fonts.copies')->label('Ukuran Font Isi')->numeric()->default(11)->live(debounce: 500),
                    ])->columns(2)->collapsible()->collapsed(),
                ]),
                Grid::make(1)->extraAttributes(['class' => 'foundation-sk-preview-column'])->schema([
                    View::make('filament.foundation-sk.template-editor-preview'),
                ])->columnSpan(1),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Template')->searchable()->sortable()->weight('bold')->description(fn (FoundationSkTemplate $record): string => $record->document_title ?: 'Tanpa judul dokumen'),
                TextColumn::make('paper_size')->label('Format')->formatStateUsing(fn (?string $state, FoundationSkTemplate $record): string => strtoupper($state ?: 'A4').' · '.($record->orientation === 'landscape' ? 'Landscape' : 'Portrait'))->badge()->color('gray'),
                TextColumn::make('is_active')->label('Status')->badge()->formatStateUsing(fn ($state): string => $state ? 'Aktif' : 'Nonaktif')->color(fn ($state): string => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')->label('Dibuat')->date('d M Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->searchPlaceholder('Cari nama atau judul template...')
            ->recordActions([
                ActionGroup::make([
                    Action::make('preview')->label('Preview')->icon('heroicon-o-eye')->url(fn (FoundationSkTemplate $record): string => route('foundation-sk.templates.preview', ['template' => $record->getKey()]))->openUrlInNewTab(),
                    Action::make('generate')->label('Generate PDF')->icon('heroicon-o-document-arrow-down')->visible(fn (): bool => static::canCreate())->modalHeading('Generate PDF SK')->modalContent(fn (FoundationSkTemplate $record) => view('filament.foundation-sk.generate-template', ['template' => $record]))->modalSubmitAction(false),
                    Action::make('toggle')->label(fn (FoundationSkTemplate $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')->icon('heroicon-o-power')->visible(fn (): bool => static::canCreate())->action(fn (FoundationSkTemplate $record) => $record->update(['is_active' => ! $record->is_active])),
                    EditAction::make()->label('Edit'),
                    DeleteAction::make()->label('Hapus'),
                ])->label('Aksi')->iconButton(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginationPageOptions([10, 25, 50]);
    }

    public static function getPages(): array
    {
        return ['index' => \App\Filament\Resources\FoundationSkTemplates\Pages\ListFoundationSkTemplates::route('/'), 'create' => \App\Filament\Resources\FoundationSkTemplates\Pages\CreateFoundationSkTemplate::route('/create'), 'edit' => \App\Filament\Resources\FoundationSkTemplates\Pages\EditFoundationSkTemplate::route('/{record}/edit')];
    }
}
