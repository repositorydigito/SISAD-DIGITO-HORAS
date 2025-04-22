<?php

namespace App\Filament\Widgets;

use App\Models\Income;
use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class IncomeExpensesOverview extends ChartWidget
{

    use HasWidgetShield;
    protected static ?string $heading = 'Ingresos vs Gastos';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 'full';

    public ?string $filter = '';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getFilters(): ?array
    {
        $currentYear = date('Y');
        return [
            $currentYear - 1 => (string)($currentYear - 1),
            $currentYear => (string)$currentYear,
            $currentYear + 1 => (string)($currentYear + 1),
        ];
    }

    protected function getData(): array
    {
        $year = $this->filter ?: date('Y');
        $data = $this->getMonthlyData($year);

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => $data['incomes'],
                    'backgroundColor' => '#22c55e',
                ],
                [
                    'label' => 'Gastos',
                    'data' => $data['expenses'],
                    'backgroundColor' => '#ef4444',
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getMonthlyData($year): array
    {
        $incomes = Income::selectRaw('MONTH(document_date) as month, SUM(COALESCE(amount_pen, amount_usd * 3.8)) as total')
            ->whereYear('document_date', $year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $expenses = Expense::selectRaw('MONTH(document_date) as month, SUM(COALESCE(amount_pen, amount_usd * 3.8)) as total')
            ->whereYear('document_date', $year)
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $labels = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

        return [
            'labels' => $labels,
            'incomes' => array_map(fn($month) => round($incomes[$month] ?? 0, 2), range(1, 12)),
            'expenses' => array_map(fn($month) => round($expenses[$month] ?? 0, 2), range(1, 12)),
        ];
    }
}
