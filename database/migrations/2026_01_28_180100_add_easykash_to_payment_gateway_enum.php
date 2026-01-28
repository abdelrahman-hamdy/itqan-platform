<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_gateway ENUM('tap','moyasar','payfort','hyperpay','paytabs','manual','easykash','paymob') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_gateway ENUM('tap','moyasar','payfort','hyperpay','paytabs','manual') NULL");
    }
};
