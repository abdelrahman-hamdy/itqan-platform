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
        Schema::create('academies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('brand_color')->default('#0ea5e9');
            $table->enum('status', ['active', 'suspended', 'maintenance'])->default('active');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('monthly_revenue', 15, 2)->default(0);
            $table->decimal('pending_payments', 15, 2)->default(0);
            $table->integer('active_subscriptions')->default(0);
            $table->decimal('growth_rate', 5, 2)->default(0);
            $table->timestamps();
            
            $table->index(['status', 'is_active']);
            $table->index('subdomain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academies');
    }
};
