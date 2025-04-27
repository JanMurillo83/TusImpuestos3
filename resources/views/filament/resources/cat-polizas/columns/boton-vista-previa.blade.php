<x-filament::button
    color="secondary"
    icon="heroicon-o-eye"
    size="sm"
    wire:click="$dispatch('open-modal', { id: 'vista-previa-{{ $record->id }}' })"
>
    Ver
</x-filament::button>

<x-filament::modal id="vista-previa-{{ $record->id }}" width="3xl">
    <x-slot name="heading">
        Vista previa - {{ $record->tipo }}-{{ $record->folio }}
    </x-slot>

    <div class="overflow-auto">
        <table class="table table-sm table-bordered">
            <thead class="table-light">
            <tr>
                <th>Código</th>
                <th>Cuenta</th>
                <th>Concepto</th>
                <th class="text-end">Cargo</th>
                <th class="text-end">Abono</th>
                <th>Referencia</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($record->partidas as $p)
                <tr>
                    <td>{{ $p->codigo }}</td>
                    <td>{{ $p->cuenta }}</td>
                    <td>{{ $p->concepto }}</td>
                    <td class="text-end">${{ number_format($p->cargo, 2) }}</td>
                    <td class="text-end">${{ number_format($p->abono, 2) }}</td>
                    <td>{{ $p->factura }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-filament::modal>
