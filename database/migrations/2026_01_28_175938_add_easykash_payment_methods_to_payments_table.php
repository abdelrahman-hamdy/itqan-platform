<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add EasyKash payment methods to the payment_method enum.
     */
    public function up(): void
    {
        // Add new payment methods to enum
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('credit_card','debit_card','bank_transfer','wallet','cash','mada','visa','mastercard','apple_pay','stc_pay','urpay','fawry','aman','meeza','easykash') NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE payments MODIFY COLUMN payment_method ENUM('credit_card','debit_card','bank_transfer','wallet','cash','mada','visa','mastercard','apple_pay','stc_pay','urpay') NULL");
    }
};
