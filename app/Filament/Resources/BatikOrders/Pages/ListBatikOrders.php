<?php

namespace App\Filament\Resources\BatikOrders\Pages;

use App\Filament\Pages\ListRecords;
use App\Filament\Resources\BatikOrders\BatikOrderResource;
use App\Filament\Resources\BatikProducts\BatikProductResource;
use App\Models\BatikProduct;
use App\Models\Foundation;
use App\Models\User;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ListBatikOrders extends ListRecords
{
    protected static string $resource = BatikOrderResource::class;

    protected static ?string $title = 'Pemesanan Kain Seragam Batik';

    public function getSubheading(): ?string
    {
        return 'Katalog produk, stok, dan pemesanan batik dalam tampilan yang lebih bersih seperti halaman produk modern.';
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.admin.resources.batik-orders.catalog')
                    ->viewData(fn (): array => ['products' => $this->products()]),
                EmbeddedTable::make(),
            ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function products(): array
    {
        $panelId = filament()->getCurrentPanel()?->getId() ?? 'admin';
        $user = auth()->user();

        return BatikProduct::ensureDefaults(Foundation::application())
            ->map(fn (BatikProduct $product): array => [
                'name' => $product->name,
                'stock' => (float) $product->stock,
                'price' => (float) $product->price,
                'sizeLabel' => $product->size_label,
                'imageUrl' => filled($product->image_path)
                    ? Storage::disk('public')->url($product->image_path)
                    : null,
                'editUrl' => $user instanceof User && $user->isAdminInduk()
                    ? BatikProductResource::getUrl(
                        'edit',
                        ['record' => $product],
                        panel: 'admin',
                        isAbsolute: false,
                    )
                    : null,
                'orderUrl' => BatikOrderResource::getUrl(
                    'create',
                    ['product' => $product->getKey()],
                    panel: $panelId,
                    isAbsolute: false,
                ),
            ])
            ->all();
    }
}
