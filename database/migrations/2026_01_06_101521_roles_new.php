<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        DB::table('roles')
        ->insert([
            ['id' => 1, 'name' => 'administrador', 'description' => 'Administrador del sistema'],
            ['id' => 2, 'name' => 'contador', 'description' => 'Contador'],
            ['id' => 3, 'name' => 'ventas', 'description' => 'Modulo de Ventas'],
            ['id' => 4, 'name' => 'compras', 'description' => 'Modulo de Compras'],
            ['id' => 5, 'name' => 'facturista', 'description' => 'Facturista']
        ]);
        DB::table('users')->where('id',1)->update(['role' => 'administrador']);
        DB::table('users')->where('id','>',1)->update(['role' => 'contador']);
        DB::table('role_user')->truncate();
        DB::table('role_user')->insert([
            'user_id'=>1,
            'role_id'=>1
        ]);
        $usuars = \App\Models\User::where('id','>',1)->get();
        foreach ($usuars as $usu){
            DB::table('role_user')->insert([
                'user_id'=>$usu->id,
                'role_id'=>2
            ]);
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
