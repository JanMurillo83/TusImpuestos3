<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $email = 'janmurillo@tusimpuestos.com';

        DB::table('users')->updateOrInsert(
            ['email' => $email],
            [
                'name' => 'Antonio Nieto',
                'email' => $email,
                'password' => Hash::make('110881'),
                'is_admin' => 'SI',
                'role' => 'administrador',
                'team_id' => null,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $userId = DB::table('users')->where('email', $email)->value('id');
        if (! $userId) {
            return;
        }

        $roleId = DB::table('roles')->where('name', 'administrador')->value('id');
        if (! $roleId) {
            DB::table('roles')->insert([
                'name' => 'administrador',
                'description' => 'Administrador del sistema',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $roleId = DB::table('roles')->where('name', 'administrador')->value('id');
        }

        if ($roleId && ! DB::table('role_user')->where('user_id', $userId)->where('role_id', $roleId)->exists()) {
            DB::table('role_user')->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
        }

        $teamIds = DB::table('teams')->pluck('id');
        foreach ($teamIds as $teamId) {
            if (! DB::table('team_user')->where('user_id', $userId)->where('team_id', $teamId)->exists()) {
                DB::table('team_user')->insert([
                    'user_id' => $userId,
                    'team_id' => $teamId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $email = 'janmurillo@tusimpuestos.com';
        $userId = DB::table('users')->where('email', $email)->value('id');
        if (! $userId) {
            return;
        }

        DB::table('team_user')->where('user_id', $userId)->delete();
        DB::table('role_user')->where('user_id', $userId)->delete();
        DB::table('users')->where('id', $userId)->delete();
    }
};
