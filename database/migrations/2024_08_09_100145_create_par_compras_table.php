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
        Schema::create('par_compras', function (Blueprint $table) {
            $table->id();
            $table->string('compras_id')->nullable();
            $table->string('serie')->nullable();
            $table->string('folio')->nullable();
            $table->string('clave_doc')->nullable();
            $table->string('cve_clie')->nullable();
            $table->string('cve_vend')->nullable();
            $table->dateTime('fecha_doc')->nullable();
            $table->decimal('cant',18,8)->default(0);
            $table->integer('id_prod')->nullable();
            $table->string('cve_prod')->nullable();
            $table->string('descripcion')->nullable();
            $table->string('unidad')->nullable();
            $table->decimal('precio',18,8)->default(0);
            $table->decimal('subtotal',18,8)->default(0);
            $table->decimal('impuesto1',18,8)->default(0);
            $table->decimal('impuesto2',18,8)->default(0);
            $table->decimal('impuesto3',18,8)->default(0);
            $table->decimal('impuesto4',18,8)->default(0);
            $table->decimal('descuento',18,8)->default(0);
            $table->decimal('por_im1',18,8)->default(0);
            $table->decimal('por_im2',18,8)->default(0);
            $table->decimal('por_im3',18,8)->default(0);
            $table->decimal('por_im4',18,8)->default(0);
            $table->decimal('por_des',18,8)->default(0);
            $table->decimal('total',18,8)->default(0);
            $table->string('cvesat')->nullable();
            $table->string('unisat')->nullable();
            $table->foreignId('team_id')->constrained();
            $table->timestamps();
        });
        Schema::create('par_compras_team', function (Blueprint $table) {
            $table->integer('par_compras_id');
            $table->integer('team _id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('par_compras');
    }
};
