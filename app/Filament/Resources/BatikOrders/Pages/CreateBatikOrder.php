<?php

namespace App\Filament\Resources\BatikOrders\Pages;

use App\Filament\Pages\CreateRecord;
use App\Filament\Resources\BatikOrders\BatikOrderResource;
use App\Models\BatikOrder;
use App\Models\BatikProduct;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBatikOrder extends CreateRecord
{
    protected static string $resource = BatikOrderResource::class;

    protected static ?string $title = 'Pesan Kain Seragam Batik';

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        return DB::transaction(function () use ($data, $actor): BatikOrder {
            $product = BatikProduct::query()
                ->where('foundation_id', Foundation::application()->getKey())
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($data['product_id']);
            $school = School::query()
                ->accessibleTo($actor)
                ->where('foundation_id', $product->foundation_id)
                ->where('is_active', true)
                ->findOrFail($data['school_id']);
            $quantity = (float) $data['quantity'];

            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    'data.quantity' => 'Jumlah pesanan harus lebih dari 0.',
                ]);
            }

            $data['foundation_id'] = $product->foundation_id;
            $data['school_id'] = $school->getKey();
            $data['ordered_at'] = now();
            $data['total_amount'] = $quantity * (float) $product->price;

            if (! $actor->isAdminInduk()) {
                $data['status'] = BatikOrder::STATUS_ORDERED;
            }

            $order = BatikOrder::query()->create($data);
            $product->decrement('stock', $quantity);

            return $order;
        });
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan Pesanan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Kembali');
    }
}
