<?php

namespace App\Filament\Admin\Pages;

use App\Models\Foundation;
use App\Models\PaymentFeeItem;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class EditPaymentInfo extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'edit-info-pembayaran';

    protected static ?string $title = 'Edit Informasi Pembayaran';

    /** @var array<int, array{id: int|null, section: string, label: string, amount: int|float|string}> */
    public array $items = [];

    public function mount(): void
    {
        $this->loadItems();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('update', Foundation::application()) ?? false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.pages.edit-payment-info')
                    ->viewData(fn (): array => [
                        'items' => $this->items,
                        'sectionOptions' => PaymentFeeItem::sectionOptions(),
                    ]),
            ]);
    }

    public function addItem(): void
    {
        $this->items[] = [
            'id' => null,
            'section' => PaymentFeeItem::SECTION_DUES,
            'label' => '',
            'amount' => 0,
        ];
    }

    public function removeItem(int $index): void
    {
        if (! array_key_exists($index, $this->items)) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'items' => ['array'],
            'items.*.section' => ['required', 'in:dues,decree'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
        ], [], [
            'items.*.section' => 'kelompok',
            'items.*.label' => 'label',
            'items.*.amount' => 'nominal',
        ]);

        $foundation = Foundation::application();

        DB::transaction(function () use ($foundation, $validated): void {
            $foundation->paymentFeeItems()->delete();
            $positions = [];

            foreach ($validated['items'] as $item) {
                $positions[$item['section']] = ($positions[$item['section']] ?? 0) + 1;

                PaymentFeeItem::query()->create([
                    'foundation_id' => $foundation->getKey(),
                    'section' => $item['section'],
                    'label' => $item['label'],
                    'amount' => $item['amount'],
                    'sort_order' => $positions[$item['section']],
                ]);
            }

            $foundation->forceFill(['payment_fees_initialized' => true])->save();
        });

        $this->loadItems();

        Notification::make()
            ->title('Informasi pembayaran berhasil disimpan')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(PaymentPage::getUrl(panel: 'admin', isAbsolute: false)),
        ];
    }

    private function loadItems(): void
    {
        $this->items = PaymentFeeItem::ensureDefaults(Foundation::application())
            ->map(fn (PaymentFeeItem $item): array => [
                'id' => $item->getKey(),
                'section' => $item->section,
                'label' => $item->label,
                'amount' => (float) $item->amount,
            ])
            ->all();
    }
}
