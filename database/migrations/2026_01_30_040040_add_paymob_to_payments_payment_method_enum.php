<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'paymob' and 'card' to the payment_method ENUM
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('credit_card','debit_card','bank_transfer','wallet','cash','mada','visa','mastercard','apple_pay','stc_pay','urpay','fawry','aman','meeza','easykash','paymob','card','tapay','moyasar') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original ENUM values
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('credit_card','debit_card','bank_transfer','wallet','cash','mada','visa','mastercard','apple_pay','stc_pay','urpay','fawry','aman','meeza','easykash') NULL");
    }
};
