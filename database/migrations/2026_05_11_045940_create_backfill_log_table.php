<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backfill_log', function (Blueprint $table) {
            $table->id();
            $table->string('bug_id', 32);
            $table->string('table_name', 100);
            $table->unsignedBigInteger('row_id');
            $table->string('column_name', 100);
            $table->text('original_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('backfill_command', 255);
            $table->timestamp('ran_at')->useCurrent();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['bug_id', 'reversed_at']);
            $table->index(['table_name', 'row_id']);
            $table->index('backfill_command');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backfill_log');
    }
};
