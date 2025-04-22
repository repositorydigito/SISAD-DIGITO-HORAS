<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class ExpensesByCostCenter extends ChartWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Gastos por Centro de Costo';
    protected static ?int $sort = 3;
    protected array|string|int $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $expenses = Expense::selectRaw('cost_centers.center_name, SUM(COALESCE(expenses.amount_pen, expenses.amount_usd * 3.8)) as total')
            ->join('cost_centers', 'expenses.cost_center_id', '=', 'cost_centers.id')
            ->groupBy('cost_centers.id', 'cost_centers.center_name')
            ->get();

        return [
            'datasets' => [
                [
                    'data' => $expenses->pluck('total')->toArray(),
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56'],
                ],
            ],
            'labels' => $expenses->pluck('center_name')->toArray(),
        ];
    }
}
