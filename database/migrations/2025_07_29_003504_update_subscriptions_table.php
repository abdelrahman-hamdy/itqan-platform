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
        Schema::table('subscriptions', function (Blueprint $table) {
            // Add all missing fields to the existing subscriptions table
            $table->unsignedBigInteger('academy_id')->after('id');
            $table->unsignedBigInteger('student_id')->after('academy_id');
            
            // Subscription Information
            $table->enum('subscription_type', ['quran', 'academic', 'recorded_course', 'general'])->default('general')->after('student_id');
            $table->string('subscription_code')->unique()->after('subscription_type');
            $table->string('plan_name')->after('subscription_code');
            $table->text('plan_description')->nullable()->after('plan_name');
            $table->enum('subscription_category', ['individual', 'group', 'course', 'package'])->default('individual')->after('plan_description');
            
            // Pricing
            $table->decimal('price', 10, 2)->default(0)->after('subscription_category');
            $table->string('currency', 3)->default('SAR')->after('price');
            $table->enum('billing_cycle', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'lifetime'])->default('monthly')->after('currency');
            
            // Status
            $table->enum('status', ['trial', 'active', 'expired', 'cancelled', 'suspended', 'pending'])->default('pending')->after('billing_cycle');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'overdue'])->default('pending')->after('status');
            
            // Trial
            $table->integer('trial_days')->default(0)->after('payment_status');
            $table->timestamp('trial_ends_at')->nullable()->after('trial_days');
            
            // Subscription Period
            $table->timestamp('starts_at')->nullable()->after('trial_ends_at');
            $table->timestamp('expires_at')->nullable()->after('starts_at');
            $table->timestamp('last_payment_at')->nullable()->after('expires_at');
            $table->timestamp('next_payment_at')->nullable()->after('last_payment_at');
            
            // Auto Renewal
            $table->boolean('auto_renew')->default(true)->after('next_payment_at');
            
            // Cancellation
            $table->text('cancellation_reason')->nullable()->after('auto_renew');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            
            // Suspension
            $table->timestamp('suspended_at')->nullable()->after('cancelled_at');
            $table->text('suspended_reason')->nullable()->after('suspended_at');
            
            // Additional Data
            $table->json('metadata')->nullable()->after('suspended_reason');
            $table->text('notes')->nullable()->after('metadata');
            
            // Audit
            $table->unsignedBigInteger('created_by')->nullable()->after('notes');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            
            // Add soft deletes
            $table->softDeletes()->after('updated_at');
            
            // Add indexes
            $table->index(['academy_id', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['subscription_type', 'status']);
            $table->index(['expires_at', 'status']);
            $table->index(['trial_ends_at', 'status']);
            $table->index(['billing_cycle', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['academy_id', 'status']);
            $table->dropIndex(['student_id', 'status']);
            $table->dropIndex(['subscription_type', 'status']);
            $table->dropIndex(['expires_at', 'status']);
            $table->dropIndex(['trial_ends_at', 'status']);
            $table->dropIndex(['billing_cycle', 'status']);
            
            $table->dropSoftDeletes();
            
            $table->dropColumn([
                'academy_id', 'student_id', 'subscription_type', 'subscription_code', 
                'plan_name', 'plan_description', 'subscription_category', 'price', 
                'currency', 'billing_cycle', 'status', 'payment_status', 'trial_days', 
                'trial_ends_at', 'starts_at', 'expires_at', 'last_payment_at', 
                'next_payment_at', 'auto_renew', 'cancellation_reason', 'cancelled_at', 
                'suspended_at', 'suspended_reason', 'metadata', 'notes', 
                'created_by', 'updated_by'
            ]);
        });
    }
};
