<?php

namespace Database\Seeders;

use App\Http\Controllers\Funciones;
use App\Models\CatCuentas;
use App\Models\User;
use App\Models\Team;
use Carbon\Carbon;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Team::create([
            'name' => 'Tus Impuestos',
            'taxid' => 'TUSIMPUESTOS',
        ]);
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@tusimpuestos.com',
            'password'=>Hash::make('admin'),
            'is_admin'=> 'SI',
            'team_id'=>1,
        ]);
        DB::table('team_user')->insert([
            'team_id'=>1,
            'user_id'=>1
        ]);

        // Seed roles
        $this->call(RoleSeeder::class);
        $this->call(CondicionesPagoSeeder::class);

        // Assign admin role to the admin user
        DB::table('role_user')->insert([
            'role_id' => 1, // admin role
            'user_id' => 1  // admin user
        ]);
        $empresa =1;
        $data = [
            ['codigo'=>'10000000','nombre'=>'Activo','acumula'=>'0','tipo'=>'A','naturaleza'=>'D','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'10001000','nombre'=>'Activo a corto plazo','acumula'=>'10000000','tipo'=>'A','naturaleza'=>'D','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'10002000','nombre'=>'Activo a largo plazo','acumula'=>'10000000','tipo'=>'A','naturaleza'=>'D','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'10100000','nombre'=>'Caja','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'101','team_id'=>$empresa],
            ['codigo'=>'10101000','nombre'=>'Caja y Efectivo','acumula'=>'10100000','tipo'=>'A','naturaleza'=>'D','csat'=>'101.01','team_id'=>$empresa],
            ['codigo'=>'10200000','nombre'=>'Bancos','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'102','team_id'=>$empresa],
            ['codigo'=>'10300000','nombre'=>'Inversiones','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'103','team_id'=>$empresa],
            ['codigo'=>'10400000','nombre'=>'Otros instrumentos financieros','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'104','team_id'=>$empresa],
            ['codigo'=>'10500000','nombre'=>'Clientes','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'105','team_id'=>$empresa],
            ['codigo'=>'10501000','nombre'=>'Clientes nacionales','acumula'=>'10500000','tipo'=>'A','naturaleza'=>'D','csat'=>'105.01','team_id'=>$empresa],
            ['codigo'=>'10501001','nombre'=>'Clientes Globales','acumula'=>'10501000','tipo'=>'D','naturaleza'=>'D','csat'=>'105.01','team_id'=>$empresa],
            ['codigo'=>'10502000','nombre'=>'Clientes extranjeros','acumula'=>'10500000','tipo'=>'A','naturaleza'=>'D','csat'=>'105.02','team_id'=>$empresa],
            ['codigo'=>'10700000','nombre'=>'Deudores diversos','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'107','team_id'=>$empresa],
            ['codigo'=>'11000000','nombre'=>'Subsidio al empleo por aplicar','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'110','team_id'=>$empresa],
            ['codigo'=>'11001000','nombre'=>'Subsidio al empleo por aplicar','acumula'=>'11000000','tipo'=>'A','naturaleza'=>'D','csat'=>'110.01','team_id'=>$empresa],
            ['codigo'=>'11300000','nombre'=>'Impuestos a Favor','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'113','team_id'=>$empresa],
            ['codigo'=>'11301000','nombre'=>'IVA a Favor','acumula'=>'11300000','tipo'=>'A','naturaleza'=>'D','csat'=>'113.01','team_id'=>$empresa],
            ['codigo'=>'11400000','nombre'=>'Pagos provisionales','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'114','team_id'=>$empresa],
            ['codigo'=>'11401000','nombre'=>'Pagos provisionales de ISR','acumula'=>'11400000','tipo'=>'A','naturaleza'=>'D','csat'=>'114.01','team_id'=>$empresa],
            ['codigo'=>'11402000','nombre'=>'ISR Retenido por intereses.','acumula'=>'11400000','tipo'=>'A','naturaleza'=>'D','csat'=>'114.01','team_id'=>$empresa],
            ['codigo'=>'11500000','nombre'=>'Inventario','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'115','team_id'=>$empresa],
            ['codigo'=>'11501000','nombre'=>'Inventario','acumula'=>'11500000','tipo'=>'A','naturaleza'=>'D','csat'=>'115.01','team_id'=>$empresa],
            ['codigo'=>'11502000','nombre'=>'Materia prima y materiales','acumula'=>'11500000','tipo'=>'A','naturaleza'=>'D','csat'=>'115.02','team_id'=>$empresa],
            ['codigo'=>'11503000','nombre'=>'Produccion en proceso','acumula'=>'11500000','tipo'=>'A','naturaleza'=>'D','csat'=>'115.03','team_id'=>$empresa],
            ['codigo'=>'11504000','nombre'=>'Productos terminados','acumula'=>'11500000','tipo'=>'A','naturaleza'=>'D','csat'=>'115.04','team_id'=>$empresa],
            ['codigo'=>'11505000','nombre'=>'Mercancias en transito','acumula'=>'11500000','tipo'=>'A','naturaleza'=>'D','csat'=>'115.05','team_id'=>$empresa],
            ['codigo'=>'11506000','nombre'=>'Mercancias en poder de terceros','acumula'=>'11500000','tipo'=>'A','naturaleza'=>'D','csat'=>'115.06','team_id'=>$empresa],
            ['codigo'=>'11507000','nombre'=>'Otros','acumula'=>'11500000','tipo'=>'A','naturaleza'=>'D','csat'=>'115.07','team_id'=>$empresa],
            ['codigo'=>'11600000','nombre'=>'Estimacion de inv obsoletos y de lento movto','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'116','team_id'=>$empresa],
            ['codigo'=>'11601000','nombre'=>'Estimacion de inv obsoletos y de lento movto','acumula'=>'11600000','tipo'=>'A','naturaleza'=>'D','csat'=>'116.01','team_id'=>$empresa],
            ['codigo'=>'11700000','nombre'=>'Obras en proceso de inmuebles','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'117','team_id'=>$empresa],
            ['codigo'=>'11701000','nombre'=>'Obras en proceso de inmuebles','acumula'=>'11700000','tipo'=>'A','naturaleza'=>'D','csat'=>'117.01','team_id'=>$empresa],
            ['codigo'=>'11800000','nombre'=>'Impuestos acreditables pagados','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'118','team_id'=>$empresa],
            ['codigo'=>'11801000','nombre'=>'IVA acreditable pagado','acumula'=>'11800000','tipo'=>'D','naturaleza'=>'D','csat'=>'118.01','team_id'=>$empresa],
            ['codigo'=>'11802000','nombre'=>'IVA acreditable de importacion pagado','acumula'=>'11800000','tipo'=>'D','naturaleza'=>'D','csat'=>'118.02','team_id'=>$empresa],
            ['codigo'=>'11900000','nombre'=>'Impuestos acreditables por pagar','acumula'=>'10001000','tipo'=>'A','naturaleza'=>'D','csat'=>'119','team_id'=>$empresa],
            ['codigo'=>'11901000','nombre'=>'IVA pendiente de pago','acumula'=>'11900000','tipo'=>'D','naturaleza'=>'D','csat'=>'119.01','team_id'=>$empresa],
            ['codigo'=>'11902000','nombre'=>'IVA de importacion pendiente de pago','acumula'=>'11900000','tipo'=>'D','naturaleza'=>'D','csat'=>'119.02','team_id'=>$empresa],
            ['codigo'=>'15100000','nombre'=>'Terrenos','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'151','team_id'=>$empresa],
            ['codigo'=>'15101000','nombre'=>'Terrenos','acumula'=>'15100000','tipo'=>'A','naturaleza'=>'D','csat'=>'151.01','team_id'=>$empresa],
            ['codigo'=>'15200000','nombre'=>'Edificios','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'152','team_id'=>$empresa],
            ['codigo'=>'15201000','nombre'=>'Edificios','acumula'=>'15200000','tipo'=>'A','naturaleza'=>'D','csat'=>'152.01','team_id'=>$empresa],
            ['codigo'=>'15300000','nombre'=>'Maquinaria y equipo','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'153','team_id'=>$empresa],
            ['codigo'=>'15301000','nombre'=>'Maquinaria y equipo','acumula'=>'15300000','tipo'=>'A','naturaleza'=>'D','csat'=>'153.01','team_id'=>$empresa],
            ['codigo'=>'15400000','nombre'=>'Automoviles, autobuses, camiones de carga','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'154','team_id'=>$empresa],
            ['codigo'=>'15401000','nombre'=>'Automoviles, autobuses, camiones de carga','acumula'=>'15400000','tipo'=>'A','naturaleza'=>'D','csat'=>'154.01','team_id'=>$empresa],
            ['codigo'=>'15500000','nombre'=>'Mobiliario y equipo de oficina','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'155','team_id'=>$empresa],
            ['codigo'=>'15501000','nombre'=>'Mobiliario y equipo de oficina','acumula'=>'15500000','tipo'=>'A','naturaleza'=>'D','csat'=>'155.01','team_id'=>$empresa],
            ['codigo'=>'15600000','nombre'=>'Equipo de computo','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'156','team_id'=>$empresa],
            ['codigo'=>'15601000','nombre'=>'Equipo de computo','acumula'=>'15600000','tipo'=>'A','naturaleza'=>'D','csat'=>'156.01','team_id'=>$empresa],
            ['codigo'=>'15700000','nombre'=>'Equipo de comunicacion','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'157','team_id'=>$empresa],
            ['codigo'=>'15701000','nombre'=>'Equipo de comunicacion','acumula'=>'15700000','tipo'=>'A','naturaleza'=>'D','csat'=>'157.01','team_id'=>$empresa],
            ['codigo'=>'15800000','nombre'=>'Activos biologicos, vegetales y semovientes','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'158','team_id'=>$empresa],
            ['codigo'=>'15801000','nombre'=>'Activos biologicos, vegetales y semovientes','acumula'=>'15800000','tipo'=>'A','naturaleza'=>'D','csat'=>'158.01','team_id'=>$empresa],
            ['codigo'=>'15900000','nombre'=>'Obras en proceso de activos fijos','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'159','team_id'=>$empresa],
            ['codigo'=>'15901000','nombre'=>'Obras en proceso de activos fijos','acumula'=>'15900000','tipo'=>'A','naturaleza'=>'D','csat'=>'159.01','team_id'=>$empresa],
            ['codigo'=>'16000000','nombre'=>'Otros activos fijos','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'160','team_id'=>$empresa],
            ['codigo'=>'16001000','nombre'=>'Otros activos fijos','acumula'=>'16000000','tipo'=>'A','naturaleza'=>'D','csat'=>'160.01','team_id'=>$empresa],
            ['codigo'=>'16100000','nombre'=>'Ferrocariles','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'161','team_id'=>$empresa],
            ['codigo'=>'16101000','nombre'=>'Ferrocariles','acumula'=>'16100000','tipo'=>'A','naturaleza'=>'D','csat'=>'161.01','team_id'=>$empresa],
            ['codigo'=>'16200000','nombre'=>'Embarcaciones','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'162','team_id'=>$empresa],
            ['codigo'=>'16201000','nombre'=>'Embarcaciones','acumula'=>'16200000','tipo'=>'A','naturaleza'=>'D','csat'=>'162.01','team_id'=>$empresa],
            ['codigo'=>'16300000','nombre'=>'Aviones','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'163','team_id'=>$empresa],
            ['codigo'=>'16301000','nombre'=>'Aviones','acumula'=>'16300000','tipo'=>'A','naturaleza'=>'D','csat'=>'163.01','team_id'=>$empresa],
            ['codigo'=>'16400000','nombre'=>'Troqueles, moldes, matrices y herramental','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'164','team_id'=>$empresa],
            ['codigo'=>'16401000','nombre'=>'Troqueles, moldes, matrices y herramental','acumula'=>'16400000','tipo'=>'A','naturaleza'=>'D','csat'=>'164.01','team_id'=>$empresa],
            ['codigo'=>'16500000','nombre'=>'Equipo de comunicaciones telefonicas','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'165','team_id'=>$empresa],
            ['codigo'=>'16501000','nombre'=>'Equipo de comunicaciones telefonicas','acumula'=>'16500000','tipo'=>'A','naturaleza'=>'D','csat'=>'165.01','team_id'=>$empresa],
            ['codigo'=>'16600000','nombre'=>'Equipo de comunicacion satelital','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'166','team_id'=>$empresa],
            ['codigo'=>'16601000','nombre'=>'Equipo de comunicacion satelital','acumula'=>'16600000','tipo'=>'A','naturaleza'=>'D','csat'=>'166.01','team_id'=>$empresa],
            ['codigo'=>'16700000','nombre'=>'Eq de adaptaciones para personas con capac dif','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'167','team_id'=>$empresa],
            ['codigo'=>'16701000','nombre'=>'Eq de adaptaciones para personas con capac dif','acumula'=>'16700000','tipo'=>'A','naturaleza'=>'D','csat'=>'167.01','team_id'=>$empresa],
            ['codigo'=>'16800000','nombre'=>'Maq y eq de generacion de energia de ftes renov','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'168','team_id'=>$empresa],
            ['codigo'=>'16801000','nombre'=>'Maq y eq de generacion de energia de ftes renov','acumula'=>'16800000','tipo'=>'A','naturaleza'=>'D','csat'=>'168.01','team_id'=>$empresa],
            ['codigo'=>'16900000','nombre'=>'Otra maquinaria y equipo','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'169','team_id'=>$empresa],
            ['codigo'=>'16901000','nombre'=>'Otra maquinaria y equipo','acumula'=>'16900000','tipo'=>'A','naturaleza'=>'D','csat'=>'169.01','team_id'=>$empresa],
            ['codigo'=>'17000000','nombre'=>'Adaptaciones y mejoras','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'D','csat'=>'170','team_id'=>$empresa],
            ['codigo'=>'17001000','nombre'=>'Adaptaciones y mejoras','acumula'=>'17000000','tipo'=>'A','naturaleza'=>'D','csat'=>'170.01','team_id'=>$empresa],
            ['codigo'=>'17100000','nombre'=>'Depreciacion acumulada de activos fijos','acumula'=>'10002000','tipo'=>'A','naturaleza'=>'A','csat'=>'171','team_id'=>$empresa],
            ['codigo'=>'17101000','nombre'=>'Depreciacion acumulada de edificios','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.01','team_id'=>$empresa],
            ['codigo'=>'17102000','nombre'=>'Depreciacion acumulada de maquinaria y equipo','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.02','team_id'=>$empresa],
            ['codigo'=>'17103000','nombre'=>'Depr acum de automoviles, autobuses, camiones','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.03','team_id'=>$empresa],
            ['codigo'=>'17104000','nombre'=>'Depr acum de mobiliario y equipo de oficina','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.04','team_id'=>$empresa],
            ['codigo'=>'17105000','nombre'=>'Depreciacion acumulada de eq de computo','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.05','team_id'=>$empresa],
            ['codigo'=>'17106000','nombre'=>'Depreciacion acumulada de eq de comunicacion','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.06','team_id'=>$empresa],
            ['codigo'=>'17107000','nombre'=>'Depr acum activos biologicos vegetales y semov','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.07','team_id'=>$empresa],
            ['codigo'=>'17108000','nombre'=>'Depreciacion acumulada de otros activos fijos','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.08','team_id'=>$empresa],
            ['codigo'=>'17109000','nombre'=>'Depreciacion acumulada de ferrocarriles','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.09','team_id'=>$empresa],
            ['codigo'=>'17110000','nombre'=>'Depreciacion acumulada de embarcaciones','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.1','team_id'=>$empresa],
            ['codigo'=>'17111000','nombre'=>'Depreciacion acumulada de aviones','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.11','team_id'=>$empresa],
            ['codigo'=>'17112000','nombre'=>'Depr acum de troqueles moldes matrices y herram','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.12','team_id'=>$empresa],
            ['codigo'=>'17113000','nombre'=>'Depr acum de eq de comunicaciones telefonicas','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.13','team_id'=>$empresa],
            ['codigo'=>'17114000','nombre'=>'Depr acum de eq de comunicacion satelital','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.14','team_id'=>$empresa],
            ['codigo'=>'17115000','nombre'=>'Depr acum eq de adapt para pers capac dif','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.15','team_id'=>$empresa],
            ['codigo'=>'17116000','nombre'=>'Depr acum maq y eq gener energia fuentes renov','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.16','team_id'=>$empresa],
            ['codigo'=>'17117000','nombre'=>'Depr acumulada de adaptaciones y mejoras','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.17','team_id'=>$empresa],
            ['codigo'=>'17118000','nombre'=>'Depr acumulada de otra maquinaria y equipo','acumula'=>'17100000','tipo'=>'A','naturaleza'=>'A','csat'=>'171.18','team_id'=>$empresa],
            ['codigo'=>'20000000','nombre'=>'Pasivo','acumula'=>'0','tipo'=>'A','naturaleza'=>'A','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'20001000','nombre'=>'Pasivo a Corto Plazo','acumula'=>'20000000','tipo'=>'A','naturaleza'=>'A','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'20002000','nombre'=>'Pasivo a largo Plazo','acumula'=>'20000000','tipo'=>'A','naturaleza'=>'A','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'20100000','nombre'=>'Proveedores','acumula'=>'20001000','tipo'=>'A','naturaleza'=>'A','csat'=>'201','team_id'=>$empresa],
            ['codigo'=>'20101000','nombre'=>'Proveedores nacionales','acumula'=>'20100000','tipo'=>'A','naturaleza'=>'A','csat'=>'201.01','team_id'=>$empresa],
            ['codigo'=>'20101001','nombre'=>'Proveedor Global','acumula'=>'20101000','tipo'=>'D','naturaleza'=>'A','csat'=>'201.01','team_id'=>$empresa],
            ['codigo'=>'20102000','nombre'=>'Proveedores extranjeros','acumula'=>'20100000','tipo'=>'A','naturaleza'=>'A','csat'=>'201.02','team_id'=>$empresa],
            ['codigo'=>'20500000','nombre'=>'Acreedores Diversos','acumula'=>'20001000','tipo'=>'A','naturaleza'=>'A','csat'=>'205','team_id'=>$empresa],
            ['codigo'=>'20800000','nombre'=>'Impuestos trasladados cobrados','acumula'=>'20001000','tipo'=>'A','naturaleza'=>'A','csat'=>'208','team_id'=>$empresa],
            ['codigo'=>'20801000','nombre'=>'IVA trasladado cobrado','acumula'=>'20800000','tipo'=>'D','naturaleza'=>'A','csat'=>'208.01','team_id'=>$empresa],
            ['codigo'=>'20900000','nombre'=>'Impuestos trasladados no cobrados','acumula'=>'20001000','tipo'=>'A','naturaleza'=>'A','csat'=>'209','team_id'=>$empresa],
            ['codigo'=>'20901000','nombre'=>'IVA trasladado no cobrado','acumula'=>'20900000','tipo'=>'D','naturaleza'=>'A','csat'=>'209.01','team_id'=>$empresa],
            ['codigo'=>'21300000','nombre'=>'Impuestos y derechos por pagar','acumula'=>'20001000','tipo'=>'A','naturaleza'=>'A','csat'=>'213','team_id'=>$empresa],
            ['codigo'=>'21301000','nombre'=>'IVA por pagar','acumula'=>'21300000','tipo'=>'D','naturaleza'=>'A','csat'=>'213.01','team_id'=>$empresa],
            ['codigo'=>'21600000','nombre'=>'Impuestos retenidos','acumula'=>'20001000','tipo'=>'A','naturaleza'=>'A','csat'=>'216','team_id'=>$empresa],
            ['codigo'=>'21601000','nombre'=>'Impuestos ret de ISR x sdos y salarios','acumula'=>'21600000','tipo'=>'D','naturaleza'=>'A','csat'=>'216.01','team_id'=>$empresa],
            ['codigo'=>'21602000','nombre'=>'Impuestos ret de ISR x asim salarios','acumula'=>'21600000','tipo'=>'D','naturaleza'=>'A','csat'=>'216.02','team_id'=>$empresa],
            ['codigo'=>'21603000','nombre'=>'Impuestos ret de ISR x arrend','acumula'=>'21600000','tipo'=>'D','naturaleza'=>'A','csat'=>'216.03','team_id'=>$empresa],
            ['codigo'=>'21604000','nombre'=>'Impuestos ret de ISR x servicios prof','acumula'=>'21600000','tipo'=>'D','naturaleza'=>'A','csat'=>'216.04','team_id'=>$empresa],
            ['codigo'=>'21610000','nombre'=>'Impuestos retenidos de IVA','acumula'=>'21600000','tipo'=>'D','naturaleza'=>'A','csat'=>'216.1','team_id'=>$empresa],
            ['codigo'=>'30000000','nombre'=>'Capital','acumula'=>'0','tipo'=>'A','naturaleza'=>'A','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'30100000','nombre'=>'Capital Social','acumula'=>'30000000','tipo'=>'A','naturaleza'=>'A','csat'=>'301','team_id'=>$empresa],
            ['codigo'=>'30101000','nombre'=>'Socio 1','acumula'=>'30100000','tipo'=>'D','naturaleza'=>'A','csat'=>'301.01','team_id'=>$empresa],
            ['codigo'=>'30102000','nombre'=>'Socio 2','acumula'=>'30100000','tipo'=>'D','naturaleza'=>'A','csat'=>'301.01','team_id'=>$empresa],
            ['codigo'=>'30103000','nombre'=>'Aportaciones p fut aumentos de capital','acumula'=>'30100000','tipo'=>'D','naturaleza'=>'A','csat'=>'301.03','team_id'=>$empresa],
            ['codigo'=>'30200000','nombre'=>'Patrimonio','acumula'=>'30000000','tipo'=>'A','naturaleza'=>'A','csat'=>'302','team_id'=>$empresa],
            ['codigo'=>'30201000','nombre'=>'Patrimonio','acumula'=>'30200000','tipo'=>'A','naturaleza'=>'D','csat'=>'302.01','team_id'=>$empresa],
            ['codigo'=>'30202000','nombre'=>'Aportacion patrimonial','acumula'=>'30200000','tipo'=>'D','naturaleza'=>'A','csat'=>'302.02','team_id'=>$empresa],
            ['codigo'=>'30300000','nombre'=>'Reserva Legal','acumula'=>'30000000','tipo'=>'A','naturaleza'=>'A','csat'=>'303','team_id'=>$empresa],
            ['codigo'=>'30301000','nombre'=>'Reserva legal','acumula'=>'30300000','tipo'=>'D','naturaleza'=>'A','csat'=>'303.01','team_id'=>$empresa],
            ['codigo'=>'40000000','nombre'=>'Ingresos','acumula'=>'0','tipo'=>'A','naturaleza'=>'A','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'40100000','nombre'=>'Ingresos','acumula'=>'40000000','tipo'=>'A','naturaleza'=>'A','csat'=>'401','team_id'=>$empresa],
            ['codigo'=>'40101000','nombre'=>'Ventas','acumula'=>'40100000','tipo'=>'D','naturaleza'=>'A','csat'=>'401.01','team_id'=>$empresa],
            ['codigo'=>'40104000','nombre'=>'Ventas y/o servicios grav al 0%','acumula'=>'40100000','tipo'=>'D','naturaleza'=>'A','csat'=>'401.04','team_id'=>$empresa],
            ['codigo'=>'40300000','nombre'=>'Otros Ingresos','acumula'=>'40000000','tipo'=>'A','naturaleza'=>'A','csat'=>'403','team_id'=>$empresa],
            ['codigo'=>'40301000','nombre'=>'Otros ingresos','acumula'=>'40300000','tipo'=>'D','naturaleza'=>'A','csat'=>'403.01','team_id'=>$empresa],
            ['codigo'=>'50000000','nombre'=>'Costos','acumula'=>'0','tipo'=>'A','naturaleza'=>'D','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'50100000','nombre'=>'Costos de venta y/o servicio','acumula'=>'50000000','tipo'=>'A','naturaleza'=>'D','csat'=>'501','team_id'=>$empresa],
            ['codigo'=>'50101000','nombre'=>'Costo de Ventas','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.01','team_id'=>$empresa],
            ['codigo'=>'50102000','nombre'=>'Sueldos y salarios','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.01','team_id'=>$empresa],
            ['codigo'=>'50103000','nombre'=>'Cuotas al IMSS','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50104000','nombre'=>'Servicios profesionales','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50105000','nombre'=>'Combustibles y lubricantes','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50106000','nombre'=>'Costos comerciales / representacion','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50107000','nombre'=>'Arendamiento inmuebles','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50108000','nombre'=>'Telefono, internet','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50109000','nombre'=>'Agua','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50110000','nombre'=>'Energia electrica','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50111000','nombre'=>'Papeleria y articulos de oficina','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50112000','nombre'=>'Mantenimiento y conservacion','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50113000','nombre'=>'Cuotas y suscripciones','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50114000','nombre'=>'Propaganda y publicidad','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50115000','nombre'=>'Capacitacion al personal','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50116000','nombre'=>'Fletes y acarreos','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50117000','nombre'=>'Comisiones sobre ventas','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50118000','nombre'=>'Uniformes','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50119000','nombre'=>'Gastos de Importacion','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50120000','nombre'=>'Depreciacion de edificios','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50121000','nombre'=>'Depreciacion de maquinaria y equipo','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50122000','nombre'=>'Depr de autom, autob, camiones, tractoc','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50123000','nombre'=>'Depreciacion de mobiliario y eq de ofic','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'50124000','nombre'=>'Depreciacion de equipo de computo','acumula'=>'50100000','tipo'=>'D','naturaleza'=>'D','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60000000','nombre'=>'Gastos','acumula'=>'0','tipo'=>'A','naturaleza'=>'A','csat'=>'0','team_id'=>$empresa],
            ['codigo'=>'60200000','nombre'=>'Gastos de venta','acumula'=>'60000000','tipo'=>'A','naturaleza'=>'A','csat'=>'602','team_id'=>$empresa],
            ['codigo'=>'60201000','nombre'=>'Sueldos y salarios','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.01','team_id'=>$empresa],
            ['codigo'=>'60202000','nombre'=>'Cuotas al IMSS','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.26','team_id'=>$empresa],
            ['codigo'=>'60203000','nombre'=>'Servicios profesionales','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.34','team_id'=>$empresa],
            ['codigo'=>'60204000','nombre'=>'Arendamiento inmuebles','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.45','team_id'=>$empresa],
            ['codigo'=>'60248000','nombre'=>'Combustibles y lubricantes','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.48','team_id'=>$empresa],
            ['codigo'=>'60250000','nombre'=>'Telefono, internet','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.5','team_id'=>$empresa],
            ['codigo'=>'60251000','nombre'=>'Agua','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.51','team_id'=>$empresa],
            ['codigo'=>'60252000','nombre'=>'Energia electrica','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.52','team_id'=>$empresa],
            ['codigo'=>'60255000','nombre'=>'Papeleria y articulos de oficina','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.55','team_id'=>$empresa],
            ['codigo'=>'60256000','nombre'=>'Mantenimiento y conservacion','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.56','team_id'=>$empresa],
            ['codigo'=>'60260000','nombre'=>'Cuotas y suscripciones','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.6','team_id'=>$empresa],
            ['codigo'=>'60261000','nombre'=>'Propaganda y publicidad','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.61','team_id'=>$empresa],
            ['codigo'=>'60262000','nombre'=>'Capacitacion al personal','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.62','team_id'=>$empresa],
            ['codigo'=>'60272000','nombre'=>'Fletes y acarreos','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.72','team_id'=>$empresa],
            ['codigo'=>'60274000','nombre'=>'Comisiones sobre ventas','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.74','team_id'=>$empresa],
            ['codigo'=>'60277000','nombre'=>'Uniformes','acumula'=>'60200000','tipo'=>'D','naturaleza'=>'A','csat'=>'602.77','team_id'=>$empresa],
            ['codigo'=>'60300000','nombre'=>'Gastos de Administracion ','acumula'=>'60000000','tipo'=>'A','naturaleza'=>'A','csat'=>'603','team_id'=>$empresa],
            ['codigo'=>'60301000','nombre'=>'Sueldos y salarios','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.01','team_id'=>$empresa],
            ['codigo'=>'60302000','nombre'=>'Cuotas al IMSS','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60303000','nombre'=>'Servicios profesionales','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60304000','nombre'=>'Combustibles y lubricantes','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60305000','nombre'=>'Costos comerciales / representacion','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60306000','nombre'=>'Arendamiento inmuebles','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60307000','nombre'=>'Telefono, internet','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60308000','nombre'=>'Agua','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60309000','nombre'=>'Energia electrica','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60310000','nombre'=>'Papeleria y articulos de oficina','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60311000','nombre'=>'Mantenimiento y conservacion','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60312000','nombre'=>'Cuotas y suscripciones','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60314000','nombre'=>'Capacitacion al personal','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60315000','nombre'=>'Fletes y acarreos','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60317000','nombre'=>'Uniformes','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60319000','nombre'=>'Depreciacion de edificios','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60320000','nombre'=>'Depreciacion de maquinaria y equipo','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60321000','nombre'=>'Depr de autom, autob, camiones, tractoc','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60322000','nombre'=>'Depreciacion de mobiliario y eq de ofic','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'60323000','nombre'=>'Depreciacion de equipo de computo','acumula'=>'60300000','tipo'=>'D','naturaleza'=>'A','csat'=>'501.08','team_id'=>$empresa],
            ['codigo'=>'61100000','nombre'=>'Impuesto Sobre la Renta','acumula'=>'60000000','tipo'=>'A','naturaleza'=>'A','csat'=>'611','team_id'=>$empresa],
            ['codigo'=>'70000000','nombre'=>'Resultado Integral de Financiamiento','acumula'=>'0','tipo'=>'A','naturaleza'=>'A','csat'=>'701','team_id'=>$empresa],
            ['codigo'=>'70100000','nombre'=>'Gastos Financieros','acumula'=>'70000000','tipo'=>'A','naturaleza'=>'A','csat'=>'701','team_id'=>$empresa],
            ['codigo'=>'70101000','nombre'=>'Perdida cambiaria','acumula'=>'70100000','tipo'=>'D','naturaleza'=>'A','csat'=>'701.01','team_id'=>$empresa],
            ['codigo'=>'70104000','nombre'=>'Intereses a cargo bancario nacional','acumula'=>'70100000','tipo'=>'A','naturaleza'=>'A','csat'=>'701.04','team_id'=>$empresa],
            ['codigo'=>'70110000','nombre'=>'Comisiones bancarias','acumula'=>'70100000','tipo'=>'A','naturaleza'=>'A','csat'=>'701.1','team_id'=>$empresa],
            ['codigo'=>'70200000','nombre'=>'Productos Financieros','acumula'=>'70000000','tipo'=>'A','naturaleza'=>'A','csat'=>'702','team_id'=>$empresa],
            ['codigo'=>'70201000','nombre'=>'Utilidad cambiaria','acumula'=>'70200000','tipo'=>'A','naturaleza'=>'A','csat'=>'702.01','team_id'=>$empresa],
            ['codigo'=>'70204000','nombre'=>'Intereses a favor bancarios nacional','acumula'=>'70200000','tipo'=>'A','naturaleza'=>'A','csat'=>'702.04','team_id'=>$empresa],
            ['codigo' => '21611000', 'nombre' => 'Retenciones de IMSS a los trabajadores', 'acumula' => '21600000', 'tipo' => 'D', 'naturaleza' => 'A', 'csat' => '216.11', 'team_id' => $empresa],
            ['codigo' => '21612000', 'nombre' => 'Descuento credito Infonavit', 'acumula' => '21600000', 'tipo' => 'D', 'naturaleza' => 'A', 'csat' => '216.11', 'team_id' => $empresa],
            ['codigo' => '21613000', 'nombre' => 'Descuentos prestamo empresa', 'acumula' => '21600000', 'tipo' => 'D', 'naturaleza' => 'A', 'csat' => '216.11', 'team_id' => $empresa]];
            CatCuentas::insert($data);
            //---------------------------------------------------------------------
            $data = [
                ['clave'=>'1','descripcion'=>'Compras','tipo'=>'Entrada','tercero'=>'P','signo'=>1,'team_id'=>1],
                ['clave'=>'2','descripcion'=>'Inventario Inicial','tipo'=>'Entrada','tercero'=>'N','signo'=>1,'team_id'=>1],
                ['clave'=>'3','descripcion'=>'Inventario Fisico','tipo'=>'Entrada','tercero'=>'N','signo'=>1,'team_id'=>1],
                ['clave'=>'4','descripcion'=>'Ajustes','tipo'=>'Entrada','tercero'=>'N','signo'=>1,'team_id'=>1],
                ['clave'=>'5','descripcion'=>'Devolucion','tipo'=>'Entrada','tercero'=>'C','signo'=>1,'team_id'=>1],
                ['clave'=>'6','descripcion'=>'Ventas','tipo'=>'Salida','tercero'=>'C','signo'=>-1,'team_id'=>1],
                ['clave'=>'7','descripcion'=>'Inventario Fisico','tipo'=>'Salida','tercero'=>'N','signo'=>-1,'team_id'=>1],
                ['clave'=>'8','descripcion'=>'Mermas','tipo'=>'Salida','tercero'=>'N','signo'=>-1,'team_id'=>1],
                ['clave'=>'9','descripcion'=>'Muestras','tipo'=>'Salida','tercero'=>'C','signo'=>-1,'team_id'=>1],
                ['clave'=>'10','descripcion'=>'Devolucion','tipo'=>'salida','tercero'=>'P','signo'=>-1,'team_id'=>1]
            ];
            DB::table('conceptosmis')->insert($data);
            DB::table('lineasprods')->insert(['clave'=>'1','descripcion'=>'General','team_id'=>1]);
            DB::table('esquemasimps')->insert(
                ['clave'=>'1','descripcion'=>'Iva General','iva'=>16,'retiva'=>0,'retisr'=>0,'ieps'=>0,
                'team_id'=>1],
                ['clave'=>'1','descripcion'=>'Iva Tasa 0','iva'=>0,'retiva'=>0,'retisr'=>0,'ieps'=>0,
                'team_id'=>1],
                ['clave'=>'1','descripcion'=>'Iva Fronterizo','iva'=>8,'retiva'=>0,'retisr'=>0,'ieps'=>0,
                'team_id'=>1]
            );
            DB::table('clientes')->insert([
                'clave'=>'1','nombre'=>'Publico en General','rfc'=>'XAXX010101000',
                'regimen'=>'616','codigo'=>'00000',
                'direccion'=>'','telefono'=>'','correo'=>'',
                'descuento'=>0,'lista'=>1,'contacto'=>'',
                'team_id'=>1
            ]);
            DB::table('datos_fiscales')->insert([
                'nombre'=>'Nieto Consulting',
                'rfc'=>'XAXX010101000',
                'regimen'=>'616',
                'codigo'=>'12345',
                'cer'=>'',
                'key'=>'',
                'csdpass'=>'',
                'logo'=>'',
                'logo64'=>'',
                'team_id'=>1
            ]);
            DB::table('listareportes')->insert([
                'nombre'=>'Balanza',
                'descripcion'=>'Balanza de Comprobacion',
                'tipo'=>'Contabilidad'
            ]);
            DB::table('listareportes')->insert([
                'nombre'=>'Balance',
                'descripcion'=>'Balance General',
                'tipo'=>'Contabilidad'
            ]);
            DB::table('listareportes')->insert([
                'nombre'=>'Estado',
                'descripcion'=>'Estado de Resultados',
                'tipo'=>'Contabilidad'
            ]);
            $date = new DateTime('now', new DateTimeZone('America/Mexico_City'));
            //DB::table('regimenes')->truncate();
            $csvFile = fopen(base_path('public/Fseeders/Regimenes.csv'), "r");
            $firstline = true;
            while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
            if (!$firstline) {

                DB::table('regimenes')->insert([
                    'clave' => $data['0'],
                    'descripcion' => $data['1'],
                    'mostrar' => $data['2'],
                    'created_at' => $date,
                    'updated_at' => $date
                ]);
            }
                $firstline = false;
            }
            fclose($csvFile);
            $csvFile = fopen(public_path('Fseeders/Formas.csv'), "r");
            $firstline = true;
            while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
            if (!$firstline) {

                DB::table('formas')->insert([
                    'clave' => $data['0'],
                    'descripcion' => $data['1'],
                    'mostrar' => $data['2'],
                    'created_at' => $date,
                    'updated_at' => $date
                ]);
            }
                $firstline = false;
            }
            fclose($csvFile);
            $csvFile = fopen(public_path('Fseeders/Metodos.csv'), "r");
            $firstline = true;
            while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
            if (!$firstline) {

                DB::table('metodos')->insert([
                    'clave' => $data['0'],
                    'descripcion' => $data['1'],
                    'mostrar' => $data['2'],
                    'created_at' => $date,
                    'updated_at' => $date
                ]);
            }
                $firstline = false;
            }
            fclose($csvFile);
            $csvFile = fopen(public_path('Fseeders/Usos.csv'), "r");
            $firstline = true;
            while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
            if (!$firstline) {

                DB::table('usos')->insert([
                    'clave' => $data['0'],
                    'descripcion' => $data['1'],
                    'mostrar' => $data['2'],
                    'created_at' => $date,
                    'updated_at' => $date
                ]);
            }
                $firstline = false;
            }
            fclose($csvFile);
            $csvFile = fopen(public_path('Fseeders/Unidades.csv'), "r");
            $firstline = true;
            while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
            if (!$firstline) {

                DB::table('unidades')->insert([
                    'clave' => $data['0'],
                    'descripcion' => $data['1'],
                    'mostrar' => $data['2'],
                    'created_at' => $date,
                    'updated_at' => $date
                ]);
            }
                $firstline = false;
            }
            fclose($csvFile);
            $csvFile = fopen(public_path('Fseeders/CveSAT.csv'), "r");
            $firstline = true;
            while (($data = fgetcsv($csvFile, 200000, ",")) !== FALSE) {
            if (!$firstline) {

                DB::table('claves')->insert([
                    'clave' => $data['0'],
                    'descripcion' => $data['1'],
                    'mostrar' => $data['2'],
                    'created_at' => $date,
                    'updated_at' => $date
                ]);
            }
                $firstline = false;
            }
            fclose($csvFile);
            DB::table('historico_tcs')->insert([
                'fecha'=>Carbon::now(),
                'tipo_cambio'=>1,
                'team_id'=>$empresa
            ]);
    }
}
