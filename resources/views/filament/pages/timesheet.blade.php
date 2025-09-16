<div>
    <x-filament::page>
        <div class="space-y-6">

            <!-- Header Navigation -->
            <div class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-lg shadow">
                <div class="flex items-center space-x-4">
                    <button wire:click="previousMonth" class="filament-button filament-button-size-md">
                        <x-heroicon-o-chevron-left class="w-5 h-5" />
                    </button>

                    <h2 class="text-xl font-medium flex items-center">
                        <x-heroicon-o-calendar class="w-5 h-5 mr-2 text-primary-500" />
                        {{ Carbon\Carbon::parse($currentDate)->format('F Y') }}
                    </h2>

                    <button wire:click="nextMonth" class="filament-button filament-button-size-md">
                        <x-heroicon-o-chevron-right class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- Timesheet Table -->
            <!-- Contenedor con scroll (horizontal y vertical) para que funcione position:sticky -->
            <div class="relative overflow-x-auto overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 max-h-[70vh]">
                <table class="timesheet-table w-full border-collapse">
                    <!-- Encabezados -->
                    <thead>
                        <tr class="sticky top-0 z-10 table-header-bg">
                            <!-- Primera columna (Proyecto) con sticky left-0 y fondo -->
                            <th
                                class="sticky left-0 z-20 sticky-left-bg px-4 py-2"
                                style="min-width: 200px; text-align: left;"
                            >
                                PROYECTO
                            </th>
                            <!-- Encabezados de días -->
                            @foreach (range(1, Carbon\Carbon::parse($currentDate)->daysInMonth) as $day)
                                <th
                                    class="sticky-header px-4 py-2 {{ $day === now()->day && $currentDate->format('m Y') === now()->format('m Y') ? 'current-day' : '' }}"
                                >
                                    {{ $day }}
                                    <div class="text-xs opacity-80">
                                        {{ Carbon\Carbon::parse($currentDate)->setDay($day)->format('D') }}
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <!-- Cuerpo de la tabla -->
                    <tbody>
                        @php
                            // Obtener los proyectos asignados al usuario actual
                            $userProjects = auth()->user()->projects;

                            // Agrupar los proyectos por línea de negocio
                            $businessLinesWithProjects = App\Models\BusinessLine::with(['projects' => function($query) use ($userProjects) {
                                $query->whereIn('id', $userProjects->pluck('id'));
                            }])
                            ->get()
                            ->filter(function($businessLine) {
                                return $businessLine->projects->count() > 0;
                            })
                            ->sortByDesc(fn($businessLine) => $businessLine->projects->count());
                        @endphp

                        @foreach ($businessLinesWithProjects as $businessLine)
                            <!-- Fila de la LÍNEA DE NEGOCIO -->
                            <tr class="business-line-row">
                                <td class="business-line-cell sticky left-0 z-10 sticky-left-bg px-4 py-2">
                                    {{ $businessLine->name }}
                                </td>
                                @foreach (range(1, Carbon\Carbon::parse($currentDate)->daysInMonth) as $day)
                                    <td class="px-4 py-2"></td>
                                @endforeach
                            </tr>

                            <!-- Filas de PROYECTOS -->
                            @forelse($businessLine->projects as $project)
                                <tr class="project-row">
                                    <!-- Primera columna sticky (nombre del proyecto) -->
                                    <td
                                        class="project-cell sticky left-0 z-10 sticky-left-bg px-4 py-2"
                                        wire:click="selectCell({{ $project->id }}, 1)"
                                    >
                                        <span class="block truncate">
                                            {{ $project->name }}
                                        </span>
                                    </td>

                                    <!-- Celdas de días con horas -->
                                    @foreach (range(1, Carbon\Carbon::parse($currentDate)->daysInMonth) as $day)
                                        <td
                                            wire:click="selectCell({{ $project->id }}, {{ $day }})"
                                            wire:dblclick="openModal({{ $project->id }}, {{ $day }})"
                                            class="cursor-pointer px-4 py-2 text-center {{ $selectedCell && $selectedCell['project_id'] == $project->id && $selectedCell['day'] == $day ? 'selected-cell' : '' }}"
                                        >
                                            @php
                                                $hours = $this->getCellEntries($project->id, $day);
                                            @endphp
                                            @if ($hours > 0)
                                                <div class="flex justify-center items-center">
                                                    <span class="highlighted-cell flex items-center gap-1">
                                                        <x-heroicon-o-clock class="w-4 h-4" />
                                                        {{ number_format($hours, 1) }}h
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-xs opacity-70">0.0h</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr class="project-row">
                                    <td class="project-cell sticky left-0 z-10 sticky-left-bg opacity-70 px-4 py-2">
                                        No hay proyectos en esta línea
                                    </td>
                                    @foreach (range(1, Carbon\Carbon::parse($currentDate)->daysInMonth) as $day)
                                        <td class="px-4 py-2"></td>
                                    @endforeach
                                </tr>
                            @endforelse
                        @endforeach

                        @if ($businessLinesWithProjects->count() == 0)
                            <tr class="project-row">
                                <td class="project-cell sticky left-0 z-10 sticky-left-bg opacity-70 px-4 py-2">
                                    No tienes proyectos asignados
                                </td>
                                @foreach (range(1, Carbon\Carbon::parse($currentDate)->daysInMonth) as $day)
                                    <td class="px-4 py-2"></td>
                                @endforeach
                            </tr>
                        @endif

                        <!-- TOTAL POR DÍA -->
                        <tr class="timesheet-total-cell">
                            <td class="sticky left-0 z-10 sticky-left-bg px-4 py-2">
                                Total por día
                            </td>
                            @foreach (range(1, Carbon\Carbon::parse($currentDate)->daysInMonth) as $day)
                                <td class="px-4 py-2 text-center">
                                    {{ number_format($this->getDayTotal($day), 1) }}h
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Resumen del Mes -->
            <div class="bg-white dark:bg-gray-900 rounded-lg p-4">
                <h3 class="text-lg font-medium mb-3 flex items-center">
                    <x-heroicon-o-chart-bar class="w-5 h-5 mr-2 text-primary-500" />
                    Resumen del Mes
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                        <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                            <x-heroicon-o-clock class="w-4 h-4 mr-2" />
                            Total Horas
                        </div>
                        <div class="text-xl font-bold">
                            {{ number_format(collect($this->entries)->sum('hours'), 1) }}h
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded">
                        <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                            <x-heroicon-o-calendar-days class="w-4 h-4 mr-2" />
                            Días Trabajados
                        </div>
                        <div class="text-xl font-bold">
                            {{ collect($this->entries)->pluck('date')->unique()->count() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::page>

    <!-- Estilos CSS para Light/Dark y para sticky row + column -->
    <style>
  /* TABLA BASE */
.timesheet-table {
    border-collapse: collapse;
    font-size: 13px;
    width: 100%;
}

.timesheet-table th,
.timesheet-table td {
    border-bottom: 1px solid #e5e7eb;
    padding: 8px 12px;
    text-align: center;
    font-size: 11px;
}

.dark .timesheet-table th,
.dark .timesheet-table td {
    border-bottom: 1px solid #2d2d2d;
}

/* ENCABEZADO (primera fila) STICKY */
.sticky-header {
    position: sticky !important;
    top: 0;
    z-index: 10;
    font-weight: 600;
}

.table-header-bg {
    background-color: #f8fafc;
}

.dark .table-header-bg {
    background-color: #1e1e1e;
}

/* PRIMERA COLUMNA STICKY */
.sticky-left-bg {
    background-color: #f8fafc;
    position: sticky !important;
    left: 0;
}

.dark .sticky-left-bg {
    background-color: #1e1e1e;
}

/* Aseguramos los z-index correctos */
th.sticky.left-0 {
    position: sticky !important;
    left: 0;
    z-index: 20; /* Encima de las celdas td */
}

td.sticky.left-0 {
    position: sticky !important;
    left: 0;
    z-index: 10;
}

/* DÍA ACTUAL */
.current-day {
    background-color: #eef2ff;
    font-weight: 600;
    color: #4f46e5;
}

.dark .current-day {
    background-color: #312e81;
    color: #e0e7ff;
}

/* LÍNEA DE NEGOCIO */
.business-line-row {
    background-color: #f1f5f9;
}

.dark .business-line-row {
    background-color: #262626;
}

.business-line-cell {
    text-align: left !important;
    font-size: 15px;
    font-weight: 600;
    color: #0f172a;
    padding: 16px 20px !important;
}

.dark .business-line-cell {
    color: #e5e7eb;
}

/* PROYECTOS */
.project-row {
    background-color: #ffffff;
}

.dark .project-row {
    background-color: #1a1a1a;
}

.project-cell {
    text-align: left !important;
    padding: 8px 20px 8px 36px !important;
    font-weight: 400;
    font-size: 11px;
    color: #334155;
    line-height: 1.3;
}

.dark .project-cell {
    color: #cbd5e1;
}

/* TOTAL POR DÍA */
.timesheet-total-cell {
    background-color: #f8fafc;
    font-weight: 600;
    font-size: 14px;
}

.dark .timesheet-total-cell {
    background-color: #1e1e1e;
    color: #e5e7eb;
}

/* CELDA SELECCIONADA */
.selected-cell {
    background-color: #eef2ff !important;
}

.dark .selected-cell {
    background-color: #312e81 !important;
}

/* Horas con icono */
.highlighted-cell {
    font-weight: 500;
    color: #4f46e5;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 4px;
    background-color: rgba(79, 70, 229, 0.1);
}

.dark .highlighted-cell {
    color: #818cf8;
    background-color: rgba(99, 102, 241, 0.1);
}

/* Contenedor con scroll */
.relative.overflow-x-auto.overflow-y-auto {
    max-height: 70vh;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
}

.dark .relative.overflow-x-auto.overflow-y-auto {
    border-color: #2d2d2d;
}

/* Ajustes de iconos */
.w-4 {
    width: 1rem !important;
    height: 1rem !important;
}

.w-5 {
    width: 1.25rem !important;
    height: 1.25rem !important;
}

/* Aseguramos que los elementos sticky mantengan su fondo */
.sticky-left-bg::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
}

/* Para el scroll suave */
.timesheet-table {
    scroll-behavior: smooth;
}
    </style>
</div>
