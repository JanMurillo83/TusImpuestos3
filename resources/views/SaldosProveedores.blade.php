<!doctype html>
<html lang="en">
<head>
    <?php
    use \Illuminate\Support\Facades\DB;
    $empresa = DB::table('teams')->where('id',$team)->first();
    $clientes = DB::table('proveedores')->where('team_id',$team)->get();
    $saldo = 0;
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{$empresa->name}}</title>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-4"></div>
        <div class="col-4">
            <center>
                <h1>Saldo Proveedores</h1>
            </center>
        </div>
        <div class="col-4">
            <label style="font-weight: bold; width: 5rem">Empresa:</label>
            <label>{{$empresa->name}}</label>
            <br>
            <label style="font-weight: bold;width: 5rem">Fecha:</label>
            <label>{{\Carbon\Carbon::now()->format('d-m-Y')}}</label>
        </div>
    </div>
    <hr>
    <div class="row">
        <div class="col-12">
            <table class="table table-bordered" style="width: 100%">
                <thead>
                <tr>
                    <th style="font-weight: bold">Clave</th>
                    <th style="font-weight: bold" colspan="2">Nombre</th>
                    <th style="font-weight: bold">Teléfono</th>
                    <th style="font-weight: bold">Contacto</th>
                    <th style="font-weight: bold">Saldo</th>
                </tr>
                </thead>
                <tbody>
                @foreach($clientes as $cliente)
                    <tr>
                        <td>{{$cliente->clave}}</td>
                        <td colspan="2">{{$cliente->nombre}}</td>
                        <td>{{$cliente->telefono}}</td>
                        <td>{{$cliente->contacto}}</td>
                        <td style="text-align: right;">{{'$ '.number_format($cliente->saldo,2)}}</td>
                    </tr>
                    @php
                        $saldo += $cliente->saldo;
                    @endphp
                @endforeach
                </tbody>
                <hr>
                <tfoot>
                <tr>
                    <td colspan="5">Total: </td>
                    <td style="text-align: right;font-weight: bold">{{'$ '.number_format($saldo,2)}}</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
</body>
</html>
