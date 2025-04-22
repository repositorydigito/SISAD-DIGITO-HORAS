<!-- resources/views/filament/tables/expense-budget-matrix.blade.php -->
<div class="p-6 bg-gray-800 rounded-lg shadow-lg">
    <style>
        .business-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #e5e7eb;
        }

        .business-table th {
            background-color: #1f2937;
            padding: 10px 12px;
            text-align: center;
            font-weight: 600;
            border-bottom: 2px solid #374151;
            font-size: 13px;
            text-transform: uppercase;
            color: #e5e7eb;
        }

        .business-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #374151;
            color: #e5e7eb;
        }

        .business-table tr {
            background-color: #1f2937;
        }

        .business-table tr:hover {
            background-color: #374151;
        }

        .business-table .line-name {
            text-align: left;
            font-weight: 500;
            font-size: 13px;
            color: #e5e7eb;
        }

        .business-table .amount {
            text-align: right;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            color: #e5e7eb;
        }

        /* Estilo específico para las filas de centro de costo */
        .business-table tr.cost-center-row {
            background-color: #2d3748;
            border-top: 2px solid #4a5568;
        }

        .business-table tr.cost-center-row td {
            font-weight: 600;
            color: #90cdf4;
            text-transform: uppercase;
            font-size: 14px;
            padding-top: 15px;
            padding-bottom: 15px;
        }

        .business-table tr.cost-center-row:hover {
            background-color: #3c4655;
        }

        /* Estilo para las filas de categorías */
        .business-table tr.category-row {
            background-color: #1f2937;
        }

        .business-table tr.category-row:hover {
            background-color: #374151;
        }

        .business-table tr.category-row td.amount {
            color: #e5e7eb;
        }

        .table-container {
            overflow-x: auto;
            margin: 0 auto;
            width: 100%;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .btn-warning {
            background-color: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background-color: #d97706;
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-draft {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }
    </style>

    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-100">
                    Presupuesto de Gastos - Versión {{ $version->version_number }}
                </h2>
                <div class="mt-2">
                    <span class="status-badge {{ $version->status === 'draft' ? 'status-draft' : 'status-approved' }}">
                        {{ $version->status === 'draft' ? 'Borrador' : 'Aprobado' }}
                    </span>
                </div>
            </div>

            @if($version->status === 'draft')
            <div class="action-buttons">
                <a href="{{ route('filament.admin.resources.expense-budgets.edit', $version) }}"
                   class="btn btn-warning">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>

                <button onclick="confirmDelete('{{ $version->id }}')"
                        class="btn btn-danger">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Eliminar
                </button>
            </div>
            @endif
        </div>
    </div>

    <div class="table-container">
        <table class="business-table">
            <thead>
                <tr>
                    <th class="line-name">CENTRO DE COSTO / CATEGORÍA</th>
                    @foreach($months as $month)
                        <th>{{ $month }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($costCenters as $center)
                    <tr class="cost-center-row">
                        <td class="line-name">{{ $center->center_name }}</td>
                        @foreach($months as $key => $month)
                            <td class="amount">
                                {{ number_format(collect($expenseBudgets)
                                    ->filter(fn($budget, $budgetKey) => str_starts_with($budgetKey, $center->id.'-'))
                                    ->sum(fn($budgets) => $budgets[0]->{$key.'_amount'} ?? 0), 2) }}
                            </td>
                        @endforeach
                    </tr>
                    @foreach($center->categories as $category)
                        <tr class="category-row">
                            <td class="line-name pl-8">{{ $category->category_name }}</td>
                            @foreach($months as $key => $month)
                                <td class="amount">
                                    @php
                                        $budget = $expenseBudgets[$center->id.'-'.$category->id][0] ?? null;
                                        $amount = $budget ? $budget->{$key.'_amount'} : 0;
                                    @endphp
                                    {{ number_format($amount, 2) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(id) {
        if (confirm('¿Está seguro de eliminar esta versión?')) {
            window.livewire.emit('deleteVersion', id);
        }
    }
</script>
@endpush
