<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change payment_settings from JSON to TEXT to support Laravel's encrypted:array cast.
     *
     * MySQL JSON columns reject non-JSON strings, but the encrypted:array cast
     * stores base64-encoded ciphertext. Converting to TEXT allows encrypted storage.
     */
    public function up(): void
    {
        // 1. Read existing plaintext JSON values before column change
        $academies = DB::table('academies')
            ->whereNotNull('payment_settings')
            ->pluck('payment_settings', 'id');

        // 2. Change column type from JSON to TEXT
        Schema::table('academies', function (Blueprint $table) {
            $table->text('payment_settings')->nullable()->change();
        });

        // 3. Re-encrypt existing plaintext values
        foreach ($academies as $id => $raw) {
            $decoded = json_decode($raw, true);
            if ($decoded !== null) {
                // It's valid plaintext JSON — encrypt it
                $encrypted = Crypt::encryptString(json_encode($decoded));
                DB::table('academies')
                    ->where('id', $id)
                    ->update(['payment_settings' => $encrypted]);
            }
        }
    }

    /**
     * Reverse: decrypt and convert back to JSON.
     */
    public function down(): void
    {
        // 1. Decrypt existing values
        $academies = DB::table('academies')
            ->whereNotNull('payment_settings')
            ->pluck('payment_settings', 'id');

        // 2. Change column type back to JSON
        Schema::table('academies', function (Blueprint $table) {
            $table->json('payment_settings')->nullable()->change();
        });

        // 3. Write back decrypted plaintext JSON
        foreach ($academies as $id => $raw) {
            try {
                $decrypted = Crypt::decryptString($raw);
                DB::table('academies')
                    ->where('id', $id)
                    ->update(['payment_settings' => $decrypted]);
            } catch (\Exception $e) {
                // Already plaintext or invalid — leave as-is
            }
        }
    }
};
