<table class="table table-sm table-bordered mt-2">
    <thead class="table-light">
    <tr>
        <th>Código</th>
        <th>Cuenta</th>
        <th>Concepto</th>
        <th>Cargo</th>
        <th>Abono</th>
        <th>Referencia</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($partidas as $p)
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
