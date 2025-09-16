<div>
    <x-filament::page>
        <div class="space-y-6">
            <!-- Filtros de fecha -->
            <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="space-y-4">
                    <!-- Rangos predefinidos -->
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button wire:click="setCurrentMonth" size="sm" color="gray">
                            Mes Actual
                        </x-filament::button>
                        <x-filament::button wire:click="setPreviousMonth" size="sm" color="gray">
                            Mes Anterior
                        </x-filament::button>
                        <x-filament::button wire:click="setCurrentWeek" size="sm" color="gray">
                            Semana Actual
                        </x-filament::button>
                        <x-filament::button wire:click="setLast30Days" size="sm" color="gray">
                            Últimos 30 Días
                        </x-filament::button>
                    </div>

                    <!-- Filtros personalizados -->
                    <div class="flex items-end gap-4">
                        <div class="flex-1">
                            {{ $this->form }}
                        </div>
                        <div class="pb-2 flex gap-2">
                            <x-filament::button wire:click="applyFilters" color="primary" class="flex flex-row">
                                Aplicar Filtros
                            </x-filament::button>
                            @if(count($this->dateRange) > 0)
                                <x-filament::button wire:click="exportData" color="success">
                                    Exportar Excel
                                </x-filament::button>
                            @endif

                        </div>
                    </div>
                </div>
            </div>

            @if($isLoading)
                <!-- Indicador de carga -->
                <div class="bg-white dark:bg-gray-800 p-8 rounded-lg text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto mb-4"></div>
                    <p class="text-gray-500">Cargando datos del reporte...</p>
                </div>
            @elseif(count($this->dateRange) > 0)
                <!-- Tabla de reporte -->
                <div class="relative overflow-x-auto overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 max-h-[75vh]">
                    <table class="user-hours-table w-full border-collapse">
                        <!-- Encabezado fijo -->
                        <thead>
                            <tr class="sticky top-0 z-10 table-header-bg">
                                <!-- Primera columna (Usuario) con sticky left-0 -->
                                <th class="sticky left-0 z-20 sticky-left-bg px-4 py-3 text-left font-semibold" style="min-width: 200px;">
                                    USUARIO
                                </th>
                                <!-- Encabezados de fechas -->
                                @foreach ($this->dateRange as $date)
                                    @php
                                        $carbonDate = \Carbon\Carbon::parse($date);
                                        $isToday = $carbonDate->isToday();
                                        $isWeekend = $carbonDate->isWeekend();
                                    @endphp
                                    <th class="sticky-header px-3 py-3 text-center {{ $isToday ? 'current-day' : '' }} {{ $isWeekend ? 'weekend-day' : '' }}" style="min-width: 80px;">
                                        <div class="text-sm font-semibold">
                                            {{ $carbonDate->format('d') }}
                                        </div>
                                        <div class="text-xs opacity-80">
                                            {{ $carbonDate->format('D') }}
                                        </div>
                                        <div class="text-xs opacity-60">
                                            {{ $carbonDate->format('M') }}
                                        </div>
                                    </th>
                                @endforeach
                                <!-- Columna de total -->
                                <th class="sticky-header px-4 py-3 text-center font-semibold bg-gray-100 dark:bg-gray-700" style="min-width: 100px;">
                                    TOTAL
                                </th>
                            </tr>
                        </thead>

                        <!-- Cuerpo de la tabla -->
                        <tbody>
                            @forelse($this->users as $user)
                                <tr class="user-row hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <!-- Primera columna sticky (nombre del usuario) -->
                                    <td class="user-cell sticky left-0 z-10 sticky-left-bg px-4 py-3 font-medium">
                                        <div class="flex items-center gap-4">
                                            <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                            <span class="truncate">{{ $user->name }}</span>
                                        </div>
                                    </td>

                                    <!-- Celdas de fechas con horas -->
                                    @foreach ($this->dateRange as $date)
                                        @php
                                            $carbonDate = \Carbon\Carbon::parse($date);
                                            $isToday = $carbonDate->isToday();
                                            $isWeekend = $carbonDate->isWeekend();
                                            $dayData = $this->userHours[$user->id]['dates'][$date] ?? ['total' => 0, 'entries' => []];
                                            $totalHours = $dayData['total'];
                                        @endphp
                                        <td class="px-3 py-3 text-center {{ $isToday ? 'current-day-cell' : '' }} {{ $isWeekend ? 'weekend-cell' : '' }} {{ $totalHours > 0 ? 'cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900' : '' }}"
                                            @if($totalHours > 0) wire:click="showUserDateDetail({{ $user->id }}, '{{ $date }}')" @endif>
                                            @if ($totalHours > 0)
                                                <div class="flex flex-col items-center">
                                                    <span class="hours-badge">
                                                        {{ number_format($totalHours, 1) }}h
                                                    </span>
                                                    @if (count($dayData['entries']) > 1)
                                                        <div class="text-xs text-gray-500 mt-1">
                                                            {{ count($dayData['entries']) }} proyectos
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <!-- Columna de total del usuario -->
                                    <td class="px-4 py-3 text-center font-bold bg-gray-50 dark:bg-gray-800">
                                        <span class="total-hours-badge">
                                            {{ number_format($this->getUserTotal($user->id), 1) }}h
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($this->dateRange) + 2 }}" class="px-4 py-8 text-center text-gray-500">
                                        No hay usuarios registrados
                                    </td>
                                </tr>
                            @endforelse

                            <!-- Fila de totales por día -->
                            <tr class="total-row">
                                <td class="sticky left-0 z-10 sticky-left-bg px-4 py-3 font-bold">
                                    TOTAL POR DÍA
                                </td>
                                @foreach ($this->dateRange as $date)
                                    @php
                                        $carbonDate = \Carbon\Carbon::parse($date);
                                        $isWeekend = $carbonDate->isWeekend();
                                        $dayTotal = $this->getDayTotal($date);
                                    @endphp
                                    <td class="px-3 py-3 text-center font-bold {{ $isWeekend ? 'weekend-cell' : '' }}">
                                        <span class="total-day-badge">
                                            {{ number_format($dayTotal, 1) }}h
                                        </span>
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-center font-bold bg-primary-100 dark:bg-primary-900">
                                    <span class="grand-total-badge">
                                        {{ number_format(collect($this->dateRange)->sum(fn($date) => $this->getDayTotal($date)), 1) }}h
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Resumen -->
                <div class="bg-white dark:bg-gray-900 rounded-lg p-4">
                    <h3 class="text-lg font-medium mb-3 flex items-center">
                        <x-heroicon-o-chart-bar class="w-5 h-5 mr-2 text-primary-500" />
                        Resumen del Período
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Horas</div>
                            <div class="text-xl font-bold text-primary-600">
                                {{ number_format(collect($this->dateRange)->sum(fn($date) => $this->getDayTotal($date)), 1) }}h
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Usuarios Activos</div>
                            <div class="text-xl font-bold text-green-600">
                                {{ collect($this->userHours)->filter(fn($userData) => collect($userData['dates'])->sum('total') > 0)->count() }}
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Días en Rango</div>
                            <div class="text-xl font-bold text-blue-600">
                                {{ count($this->dateRange) }}
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Promedio por Día</div>
                            <div class="text-xl font-bold text-purple-600">
                                {{ count($this->dateRange) > 0 ? number_format(collect($this->dateRange)->sum(fn($date) => $this->getDayTotal($date)) / count($this->dateRange), 1) : 0 }}h
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 p-8 rounded-lg text-center">
                    <x-heroicon-o-calendar class="w-12 h-12 mx-auto text-gray-400 mb-4" />
                    <p class="text-gray-500">Selecciona un rango de fechas para ver el reporte</p>
                </div>
            @endif
        </div>

        <!-- Modal de detalle -->
        @if($showDetailModal && $selectedUserDate)
            <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeDetailModal"></div>
                    
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                                    <x-heroicon-o-clock class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                        Detalle de Horas
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                            <strong>{{ $selectedUserDate['user_name'] }}</strong> - 
                                            {{ \Carbon\Carbon::parse($selectedUserDate['date'])->format('d/m/Y') }}
                                        </p>
                                        
                                        @if(count($selectedUserDate['entries']) > 0)
                                            <div class="space-y-2">
                                                @foreach($selectedUserDate['entries'] as $projectName => $hours)
                                                    <div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ $projectName }}
                                                        </span>
                                                        <span class="text-sm text-blue-600 dark:text-blue-400 font-semibold">
                                                            {{ number_format($hours, 1) }}h
                                                        </span>
                                                    </div>
                                                @endforeach
                                                
                                                <div class="border-t pt-2 mt-3">
                                                    <div class="flex justify-between items-center font-bold">
                                                        <span class="text-gray-900 dark:text-white">Total:</span>
                                                        <span class="text-blue-600 dark:text-blue-400">
                                                            {{ number_format(array_sum($selectedUserDate['entries']), 1) }}h
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                No hay registros de horas para este día.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <x-filament::button wire:click="closeDetailModal" color="primary">
                                Cerrar
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </x-filament::page>

    <!-- Estilos CSS -->
    <style>
        /* TABLA BASE */
        .user-hours-table {
            border-collapse: collapse;
            font-size: 13px;
            width: 100%;
        }

        .user-hours-table th,
        .user-hours-table td {
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #f3f4f6;
        }

        .dark .user-hours-table th,
        .dark .user-hours-table td {
            border-bottom: 1px solid #374151;
            border-right: 1px solid #374151;
        }

        /* ENCABEZADO STICKY */
        .sticky-header {
            position: sticky !important;
            top: 0;
            z-index: 10;
            font-weight: 600;
        }

        .table-header-bg {
            background-color: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
        }

        .dark .table-header-bg {
            background-color: #1f2937;
            border-bottom: 2px solid #374151;
        }

        /* PRIMERA COLUMNA STICKY */
        .sticky-left-bg {
            background-color: #f8fafc;
            position: sticky !important;
            left: 0;
            border-right: 2px solid #e5e7eb !important;
        }

        .dark .sticky-left-bg {
            background-color: #1f2937;
            border-right: 2px solid #374151 !important;
        }

        /* Z-INDEX PARA STICKY */
        th.sticky.left-0 {
            position: sticky !important;
            left: 0;
            z-index: 20;
        }

        td.sticky.left-0 {
            position: sticky !important;
            left: 0;
            z-index: 10;
        }

        /* DÍA ACTUAL */
        .current-day {
            background-color: #dbeafe;
            color: #1d4ed8;
        }

        .dark .current-day {
            background-color: #1e3a8a;
            color: #bfdbfe;
        }

        .current-day-cell {
            background-color: #eff6ff;
        }

        .dark .current-day-cell {
            background-color: #1e3a8a;
        }

        /* FIN DE SEMANA */
        .weekend-day {
            background-color: #fef3c7;
            color: #92400e;
        }

        .dark .weekend-day {
            background-color: #451a03;
            color: #fbbf24;
        }

        .weekend-cell {
            background-color: #fffbeb;
        }

        .dark .weekend-cell {
            background-color: #451a03;
        }

        /* FILAS DE USUARIOS */
        .user-row {
            background-color: #ffffff;
        }

        .dark .user-row {
            background-color: #111827;
        }

        .user-cell {
            text-align: left !important;
            font-weight: 500;
        }

        /* FILA DE TOTALES */
        .total-row {
            background-color: #f3f4f6;
            font-weight: 600;
            border-top: 2px solid #d1d5db;
        }

        .dark .total-row {
            background-color: #374151;
            border-top: 2px solid #4b5563;
        }

        /* BADGES DE HORAS */
        .hours-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            background-color: #dbeafe;
            color: #1d4ed8;
            font-weight: 500;
            font-size: 11px;
        }

        .dark .hours-badge {
            background-color: #1e3a8a;
            color: #bfdbfe;
        }

        .total-hours-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 16px;
            background-color: #dcfce7;
            color: #166534;
            font-weight: 600;
            font-size: 12px;
        }

        .dark .total-hours-badge {
            background-color: #14532d;
            color: #bbf7d0;
        }

        .total-day-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 14px;
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 600;
            font-size: 11px;
        }

        .dark .total-day-badge {
            background-color: #451a03;
            color: #fbbf24;
        }

        .grand-total-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 16px;
            border-radius: 20px;
            background-color: #e0e7ff;
            color: #3730a3;
            font-weight: 700;
            font-size: 14px;
        }

        .dark .grand-total-badge {
            background-color: #312e81;
            color: #c7d2fe;
        }

        /* CONTENEDOR CON SCROLL */
        .relative.overflow-x-auto.overflow-y-auto {
            max-height: 75vh;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }

        .dark .relative.overflow-x-auto.overflow-y-auto {
            border-color: #374151;
        }

        /* RESPONSIVIDAD */
        @media (max-width: 768px) {
            .user-hours-table th,
            .user-hours-table td {
                padding: 6px 8px;
                font-size: 10px;
            }
            
            .user-cell {
                min-width: 150px !important;
            }
            
            .hours-badge {
                padding: 1px 6px;
                font-size: 10px;
            }
        }

        /* SCROLL SUAVE */
        .user-hours-table {
            scroll-behavior: smooth;
        }

        /* HOVER EFFECTS */
        .user-row:hover .hours-badge {
            background-color: #bfdbfe;
            transform: scale(1.05);
            transition: all 0.2s ease;
        }

        .dark .user-row:hover .hours-badge {
            background-color: #1e40af;
        }
    </style>
</div>