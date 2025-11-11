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
        Schema::create('business_service_requests', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('client_phone');
            $table->string('client_email');
            $table->foreignId('service_category_id')->constrained('business_service_categories')->onDelete('cascade');
            $table->string('project_budget')->nullable();
            $table->string('project_deadline')->nullable();
            $table->text('project_description');
            $table->enum('status', ['pending', 'reviewed', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_service_requests');
    }
};
