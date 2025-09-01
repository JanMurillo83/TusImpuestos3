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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->string('serie')->nullable();
            $table->integer('folio')->default(0);
            $table->string('clave_doc')->nullable();
            $table->string('cve_clie')->nullable();
            $table->dateTime('fecha_doc')->nullable();
            $table->dateTime('fecha_can')->nullable();
            $table->decimal('subtotal',18,8)->default(0);
            $table->decimal('iva',18,8)->default(0);
            $table->decimal('total',18,8)->default(0);
            $table->string('moneda',1000)->nullable();
            $table->integer('dat_fiscal')->default(0);
            $table->string('estado')->nullable();
            $table->string('timbrado')->nullable();
            $table->dateTime('fecha_tim')->nullable();
            $table->longText('xml')->nullable();
            $table->string('uuid')->nullable();
            $table->string('usocfdi')->nullable();
            $table->string('forma')->nullable();
            $table->date('fechapago')->nullable();
            $table->longText('pdf_file')->nullable();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
