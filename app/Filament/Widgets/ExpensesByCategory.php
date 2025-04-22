<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;


class ExpensesByCategory extends ChartWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'Gastos por Categoría';
    protected static ?int $sort = 4;

    protected function getType(): string
    {
        return 'bar';
    }



    protected function getData(): array
    {
        $expenses = Expense::selectRaw('categories.category_name, SUM(COALESCE(expenses.amount_pen, expenses.amount_usd * 3.8)) as total')
            ->join('categories', 'expenses.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.category_name')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Gastos',
                    'data' => $expenses->pluck('total')->toArray(),
                    'backgroundColor' => '#FF6384',
                ],
            ],
            'labels' => $expenses->pluck('category_name')->toArray(),
        ];
    }
}
