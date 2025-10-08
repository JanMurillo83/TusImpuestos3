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
        \Illuminate\Support\Facades\DB::statement("alter table par_pagos modify imppagado decimal(18, 8) default 0.00 null");
        \Illuminate\Support\Facades\DB::statement("alter table par_pagos modify insoluto decimal(18, 8) default 0.00 null");
        \Illuminate\Support\Facades\DB::statement("alter table par_pagos modify baseiva decimal(18, 8) default 0.00 null");
        \Illuminate\Support\Facades\DB::statement("alter table par_pagos modify montoiva decimal(18, 8) default 0.00 null");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
