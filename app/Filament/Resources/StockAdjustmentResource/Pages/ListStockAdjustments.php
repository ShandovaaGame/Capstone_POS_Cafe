<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Services\StockReconciliationService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListStockAdjustments extends ListRecords
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): ?Model {
                    try {
                        $rawQuantity    = (string) $data['quantity'];
                        $parsedQuantity = (float) str_replace(',', '.', str_replace('.', '', $rawQuantity));

                        /** @var StockReconciliationService $service */
                        $service = app(StockReconciliationService::class);

                        return $service->createManualAdjustment(
                            adjustableType: (string) ($data['adjustable_type'] ?? StockAdjustment::ADJUSTABLE_TYPE_INGREDIENT),
                            ingredientId: isset($data['ingredient_id']) ? (int) $data['ingredient_id'] : null,
                            menuId: isset($data['menu_id']) ? (int) $data['menu_id'] : null,
                            quantity: $parsedQuantity,
                            adjustmentType: (string) $data['adjustment_type'],
                            category: $data['category'] ?? null,
                            reason: $data['reason'] ?? null,
                            reportedBy: $data['recorded_by'] ?? null,
                            adjustedAt: $data['adjusted_at'] ?? null,
                        );
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal membuat penyesuaian')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        $this->halt();

                        return null;
                    }
                }),
        ];
    }
}
