<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notade_creditos', function (Blueprint $table) {
            $table->id();
            $table->string('serie');
            $table->integer('folio');
            $table->string('docto');
            $table->date('fecha');
            $table->integer('clie');
            $table->string('nombre')->nullable();
            $table->integer('esquema');
            $table->decimal('subtotal',18,8)->default(0);
            $table->decimal('iva',18,8)->default(0);
            $table->decimal('retiva',18,8)->default(0);
            $table->decimal('retisr',18,8)->default(0);
            $table->decimal('ieps',18,8)->default(0);
            $table->decimal('total',18,8)->default(0);
            $table->text('observa')->nullable();
            $table->string('estado');
            $table->string('metodo')->nullable();
            $table->string('forma')->nullable();
            $table->string('uso')->nullable();
            $table->string('uuid')->nullable();
            $table->string('condiciones')->nullable();
            $table->integer('vendedor')->default(0);
            $table->integer('anterior')->nullable();
            $table->string('timbrado')->nullable();
            $table->longText('xml')->nullable();
            $table->string('fecha_tim')->nullable();
            $table->string('moneda')->default('MXN');
            $table->decimal('tcambio',18,8)->default(1);
            $table->date('fecha_cancela')->nullable();
            $table->string('motivo')->nullable();
            $table->string('sustituye')->nullable();
            $table->text('xml_cancela')->nullable();
            $table->decimal('pendiente_pago',18,8)->default(0);
            $table->integer('team_id');
            $table->decimal('error_timbrado')->nullable();
            $table->string('docto_rela')->nullable();
            $table->string('tipo_rela')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notade_creditos');
    }
};
