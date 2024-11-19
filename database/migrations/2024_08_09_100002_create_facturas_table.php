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
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->string('serie')->nullable();
            $table->integer('folio')->default(0);
            $table->string('clave_doc')->nullable();
            $table->string('cve_clie')->nullable();
            $table->string('cve_vend')->nullable();
            $table->dateTime('fecha_doc')->nullable();
            $table->dateTime('fecha_can')->nullable();
            $table->decimal('subtotal',18,8)->default(0);
            $table->decimal('impuesto1',18,8)->default(0);
            $table->decimal('impuesto2',18,8)->default(0);
            $table->decimal('impuesto3',18,8)->default(0);
            $table->decimal('impuesto4',18,8)->default(0);
            $table->decimal('descuento',18,8)->default(0);
            $table->decimal('total',18,8)->default(0);
            $table->decimal('por_im1',18,8)->default(0);
            $table->decimal('por_im2',18,8)->default(0);
            $table->decimal('por_im3',18,8)->default(0);
            $table->decimal('por_im4',18,8)->default(0);
            $table->decimal('por_des',18,8)->default(0);
            $table->string('condiciones',1000)->nullable();
            $table->string('observaciones',1000)->nullable();
            $table->integer('dir_entrega')->default(0);
            $table->integer('dat_fiscal')->default(0);
            $table->string('estado')->nullable();
            $table->string('timbrado')->nullable();
            $table->dateTime('fecha_tim')->nullable();
            $table->longText('xml')->nullable();
            $table->string('metodo')->nullable();
            $table->string('forma')->nullable();
            $table->string('uuid')->nullable();
            $table->string('usocfdi')->nullable();
            $table->decimal('traslados',18,8)->default(0);
            $table->decimal('retenciones',18,8)->default(0);
            $table->longText('pdf_file')->nullable();
            $table->integer('emisor')->default(0);
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
        Schema::create('facturas_team', function (Blueprint $table) {
            $table->integer('facturas_id');
            $table->integer('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};
