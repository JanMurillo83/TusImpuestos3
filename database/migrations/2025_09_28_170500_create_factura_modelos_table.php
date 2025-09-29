<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factura_modelos', function (Blueprint $table) {
            $table->id();
            $table->integer('team_id')->index();
            $table->string('nombre_modelo');
            $table->integer('clie'); // cliente id
            $table->string('cliente_nombre');
            $table->integer('esquema'); // esquema de impuestos id
            $table->integer('metodo'); // metodo de pago id
            $table->integer('forma'); // forma de pago id
            $table->integer('uso'); // uso CFDI id
            $table->string('moneda')->default('MXN');
            $table->decimal('tcambio', 18, 8)->default(1);
            $table->text('condiciones')->nullable();
            $table->text('observa')->nullable();
            $table->string('vendedor')->nullable();

            // Scheduling fields
            $table->string('periodicidad')->default('manual'); // manual,daily,weekly,monthly,custom
            $table->integer('cada_dias')->nullable(); // for custom
            $table->date('proxima_emision')->nullable();
            $table->date('ultima_emision')->nullable();
            $table->boolean('activa')->default(true);

            // Totals snapshot (optional) - can be recalculated on emission
            $table->decimal('subtotal', 18, 8)->default(0);
            $table->decimal('iva', 18, 8)->default(0);
            $table->decimal('retiva', 18, 8)->default(0);
            $table->decimal('retisr', 18, 8)->default(0);
            $table->decimal('ieps', 18, 8)->default(0);
            $table->decimal('total', 18, 8)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factura_modelos');
    }
};
