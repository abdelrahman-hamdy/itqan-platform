<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscription_admin_audit_decisions');
    }

    public function down(): void
    {
        // Re-creating the table on rollback isn't useful — the page that
        // wrote to it is gone. The migrations that originally built it
        // (2026_05_16_000002 + 2026_05_16_000003) are still in the
        // history if a fresh re-create is ever needed.
    }
};
