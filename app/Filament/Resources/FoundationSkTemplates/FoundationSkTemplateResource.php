<?php

namespace App\Filament\Resources\FoundationSkTemplates;

use App\Models\FoundationSkTemplate;
use Filament\Actions\{Action, DeleteAction, EditAction};
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
            Grid::make(2)->schema([
                Grid::make(1)->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')->label('Nama Template')->required(),
                        TextInput::make('document_title')->label('Judul Dokumen / Nama File PDF')->required(),
                    ]),
                    Hidden::make('slug')->default(fn (): string => 'template-'.uniqid())->required()->unique(ignoreRecord: true),
                    Section::make('Buat Template SK')->description('Atur identitas dan format dasar template.')->schema([
                        Textarea::make('description')->label('Deskripsi'),
                        Grid::make(3)->schema([
                            Select::make('paper_size')->label('Ukuran Kertas')->options(['A4' => 'A4', 'LEGAL' => 'LEGAL', 'LETTER' => 'LETTER'])->required()->live(),
                            Select::make('orientation')->label('Orientasi')->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])->required()->live(),
                        ]),
                        Toggle::make('is_active')->label('Template Aktif')->default(true),
                    ]),
                    Section::make('Kepala Surat')->description('Isi langsung teks yang tampil di bagian atas SK.')->schema([
                        Textarea::make('builder_data.header.top_text')->label('Baris Atas')->rows(2)->live(onBlur: true),
                        Grid::make(2)->schema([
                            TextInput::make('builder_data.header.font_top_text')->label('Font Baris Atas')->numeric()->default(13),
                            TextInput::make('builder_data.header.institution_name')->label('Judul Lembaga'),
                            TextInput::make('builder_data.header.font_institution_name')->label('Font Judul')->numeric()->default(26),
                            TextInput::make('builder_data.header.address')->label('Alamat'),
                            TextInput::make('builder_data.header.whatsapp')->label('Nomor WhatsApp'),
                            TextInput::make('builder_data.header.font_address')->label('Font Alamat')->numeric()->default(11),
                            TextInput::make('builder_data.header.font_contact')->label('Font Kontak')->numeric()->default(11),
                            TextInput::make('builder_data.header.email')->label('Email Kop Surat'),
                        ]),
                        FileUpload::make('builder_data.header.logo_path')->label('Upload Logo Kop Surat')->image()->disk('public')->directory('foundation-sk/logos')->preserveFilenames()->maxSize(2048),
                    ]),
                    Section::make('Judul Keputusan')->schema([
                        TextInput::make('builder_data.decision.title')->label('Judul SK'),
                        TextInput::make('builder_data.decision.font_title')->label('Font Judul SK')->numeric()->default(13),
                        TextInput::make('builder_data.decision.number')->label('Nomor SK'),
                        TextInput::make('builder_data.decision.font_number')->label('Font Nomor SK')->numeric()->default(12),
                        Textarea::make('builder_data.decision.opening')->label('Kalimat Pembuka')->rows(2),
                        TextInput::make('builder_data.decision.font_opening')->label('Font Pembuka')->numeric()->default(11),
                    ])->columns(2),
                    Section::make('Konsideran')->description('Boleh memakai placeholder seperti {{nama_kelas}}, {{ketugasan}}, dan lainnya.')->schema([
                        Textarea::make('builder_data.sections.menimbang')->label('Menimbang')->rows(3),
                        Textarea::make('builder_data.sections.mengingat')->label('Mengingat')->rows(3),
                        Textarea::make('builder_data.sections.memperhatikan')->label('Memperhatikan')->rows(3),
                        TextInput::make('builder_data.fonts.section_title')->label('Font Konsideran')->numeric()->default(11),
                    ]),
                    Section::make('Isi Keputusan')->schema([
                        TextInput::make('builder_data.sections.judul_tengah')->label('Judul Tengah'),
                        TextInput::make('builder_data.fonts.judul_tengah')->label('Font Judul Tengah')->numeric()->default(11),
                        Textarea::make('builder_data.sections.memutuskan')->label('Teks Keputusan')->rows(3),
                        Textarea::make('builder_data.sections.menetapkan')->label('Teks Penetapan')->rows(3),
                        TextInput::make('builder_data.fonts.body')->label('Font Isi Keputusan')->numeric()->default(11),
                        TextInput::make('builder_data.fonts.data_user')->label('Font Data Guru')->numeric()->default(11),
                    ])->columns(2),
                    Section::make('Area Tanda Tangan')->description('Bagian ini hanya area teks penetapan, tanpa gambar tanda tangan.')->schema([
                        TextInput::make('builder_data.signature.ditetapkan_di')->label('Ditetapkan Di'),
                        TextInput::make('builder_data.signature.label_tanggal')->label('Label Tanggal'),
                        TextInput::make('builder_data.signature.tanggal_penetapan')->label('Tanggal Penetapan'),
                        TextInput::make('builder_data.signature.baris_pengurus')->label('Baris Pengurus'),
                        TextInput::make('builder_data.signature.jabatan')->label('Jabatan'),
                        TextInput::make('builder_data.signature.nama')->label('Nama Penanda Tangan'),
                        TextInput::make('builder_data.fonts.signature')->label('Font Area Tanda Tangan')->numeric()->default(11),
                    ])->columns(2),
                    Section::make('Tembusan')->schema([
                        TextInput::make('builder_data.copies.title')->label('Judul Tembusan'),
                        TextInput::make('builder_data.fonts.copies_title')->label('Font Judul Tembusan')->numeric()->default(11),
                        Textarea::make('builder_data.copies.items')->label('Daftar Tembusan')->rows(3),
                        TextInput::make('builder_data.fonts.copies')->label('Font Isi Tembusan')->numeric()->default(11),
                    ])->columns(2),
                ]),
                Grid::make(1)->schema([
                    View::make('filament.foundation-sk.template-editor-preview'),
                    View::make('filament.foundation-sk.template-editor-user-placeholders'),
                ])->columnSpan(1),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nama Template')->searchable(),
            TextColumn::make('document_title')->label('Judul Dokumen')->searchable(),
            TextColumn::make('paper_size')->label('Kertas'),
            TextColumn::make('is_active')->label('Status')->badge()->formatStateUsing(fn ($state): string => $state ? 'Aktif' : 'Nonaktif'),
            TextColumn::make('created_at')->label('Dibuat')->dateTime(),
        ])->actions([
            Action::make('preview')->label('Preview')->url(fn (FoundationSkTemplate $record): string => route('foundation-sk.templates.preview', ['template' => $record->getKey()]))->openUrlInNewTab(),
            Action::make('generate')->label('Generate')->visible(fn (): bool => static::canCreate())->modalHeading('Generate PDF SK')->modalContent(fn (FoundationSkTemplate $record) => view('filament.foundation-sk.generate-template', ['template' => $record]))->modalSubmitAction(false),
            Action::make('toggle')->label(fn (FoundationSkTemplate $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')->visible(fn (): bool => static::canCreate())->action(fn (FoundationSkTemplate $record) => $record->update(['is_active' => ! $record->is_active])),
            EditAction::make(), DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => \App\Filament\Resources\FoundationSkTemplates\Pages\ListFoundationSkTemplates::route('/'), 'create' => \App\Filament\Resources\FoundationSkTemplates\Pages\CreateFoundationSkTemplate::route('/create'), 'edit' => \App\Filament\Resources\FoundationSkTemplates\Pages\EditFoundationSkTemplate::route('/{record}/edit')];
    }
}
