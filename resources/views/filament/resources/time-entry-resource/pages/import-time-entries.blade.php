<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-header-heading text-xl font-semibold leading-6 text-gray-950 dark:text-white">
                    Importar Registros de Tiempo
                </h3>
            </div>
        </div>

        <form wire:submit.prevent="import" enctype="multipart/form-data">
            <div class="mb-4">
                <div class="fi-form-component">
                    <div class="fi-form-field-wrp">
                        <div class="grid gap-y-2">
                            <label for="file" class="fi-form-field-label text-sm font-medium leading-6 text-gray-950 dark:text-white">
                                Archivo CSV
                            </label>

                            <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 ring-gray-950/10 dark:ring-white/20">
                                <input
                                    type="file"
                                    id="file"
                                    wire:model="file"
                                    accept=".csv,.txt"
                                    class="fi-input block w-full border-none py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 ps-3 pe-3"
                                >
                            </div>

                            @error('file')
                                <p class="fi-form-field-error-message text-sm text-danger-600 dark:text-danger-400">
                                    {{ $message }}
                                </p>
                            @enderror

                            <p class="fi-form-field-helper-text text-sm text-gray-500 dark:text-gray-400">
                                El archivo debe tener las columnas: project_id, user_id, date, hours, phase, description
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex space-x-4">
                <x-filament::button type="submit">
                    Importar Datos
                </x-filament::button>

                <x-filament::button
                    type="button"
                    color="gray"
                    tag="a"
                    href="{{ route('filament.admin.resources.time-entries.index') }}"
                >
                    Cancelar
                </x-filament::button>
            </div>
        </form>
    </div>

    <div class="mt-6 fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-header-heading text-lg font-semibold leading-6 text-gray-950 dark:text-white">
                    Instrucciones
                </h3>
            </div>
        </div>

        <p class="mb-2">Para importar registros de tiempo, sigue estos pasos:</p>
        <ol class="list-decimal list-inside space-y-1 mb-4">
            <li>Prepara un archivo CSV con las columnas requeridas</li>
            <li>Sube el archivo usando el formulario anterior</li>
            <li>Revisa los resultados de la importación</li>
        </ol>

        <h4 class="font-medium mt-4">Ejemplo de formato:</h4>
        <pre class="mt-1 p-2 bg-gray-100 rounded text-xs overflow-x-auto">project_id;user_id;date;hours;phase;description
1;2;02/12/2024;1.50;planificacion;"Reunión de planificación del proyecto"
1;2;03/12/2024;4.00;ejecucion;"Desarrollo de funcionalidad principal"</pre>
        <p class="text-xs text-gray-500 mt-1">Nota: La fecha puede estar en formato dd/mm/yyyy (02/12/2024) o yyyy-mm-dd (2024-12-02)</p>

        <div class="mt-4">
            <x-filament::button
                tag="a"
                href="{{ route('time-entries.template') }}"
                color="success"
                size="sm"
                icon="heroicon-o-arrow-down-tray"
            >
                Descargar archivo de ejemplo
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
