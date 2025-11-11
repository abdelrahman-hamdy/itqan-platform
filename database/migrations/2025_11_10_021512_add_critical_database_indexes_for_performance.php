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
        // QuranSessions indexes for common queries
        Schema::table('quran_sessions', function (Blueprint $table) {
            if (!$this->indexExists('quran_sessions', 'quran_sessions_academy_status_scheduled_idx')) {
                $table->index(['academy_id', 'status', 'scheduled_at'], 'quran_sessions_academy_status_scheduled_idx');
            }

            if (!$this->indexExists('quran_sessions', 'quran_sessions_teacher_scheduled_idx')) {
                $table->index(['quran_teacher_id', 'scheduled_at'], 'quran_sessions_teacher_scheduled_idx');
            }

            if (!$this->indexExists('quran_sessions', 'quran_sessions_student_scheduled_idx')) {
                $table->index(['student_id', 'scheduled_at'], 'quran_sessions_student_scheduled_idx');
            }

            if (!$this->indexExists('quran_sessions', 'quran_sessions_subscription_status_idx')) {
                $table->index(['quran_subscription_id', 'status'], 'quran_sessions_subscription_status_idx');
            }

            if (!$this->indexExists('quran_sessions', 'quran_sessions_code_academy_idx')) {
                $table->index(['session_code', 'academy_id'], 'quran_sessions_code_academy_idx');
            }

            if (!$this->indexExists('quran_sessions', 'quran_sessions_subscription_counted_idx')) {
                $table->index(['subscription_counted', 'status'], 'quran_sessions_subscription_counted_idx');
            }
        });

        // QuranSubscriptions indexes
        Schema::table('quran_subscriptions', function (Blueprint $table) {
            if (!$this->indexExists('quran_subscriptions', 'quran_subscriptions_academy_status_idx')) {
                $table->index(['academy_id', 'subscription_status'], 'quran_subscriptions_academy_status_idx');
            }

            if (!$this->indexExists('quran_subscriptions', 'quran_subscriptions_student_status_idx')) {
                $table->index(['student_id', 'subscription_status'], 'quran_subscriptions_student_status_idx');
            }

            if (!$this->indexExists('quran_subscriptions', 'quran_subscriptions_teacher_status_idx')) {
                $table->index(['quran_teacher_id', 'subscription_status'], 'quran_subscriptions_teacher_status_idx');
            }

            if (!$this->indexExists('quran_subscriptions', 'quran_subscriptions_expires_at_idx')) {
                $table->index(['expires_at', 'subscription_status'], 'quran_subscriptions_expires_at_idx');
            }

            if (!$this->indexExists('quran_subscriptions', 'quran_subscriptions_code_academy_idx')) {
                $table->index(['subscription_code', 'academy_id'], 'quran_subscriptions_code_academy_idx');
            }
        });

        // QuranCircles indexes
        Schema::table('quran_circles', function (Blueprint $table) {
            if (!$this->indexExists('quran_circles', 'quran_circles_academy_status_idx')) {
                $table->index(['academy_id', 'status'], 'quran_circles_academy_status_idx');
            }

            if (!$this->indexExists('quran_circles', 'quran_circles_teacher_status_idx')) {
                $table->index(['quran_teacher_id', 'status'], 'quran_circles_teacher_status_idx');
            }

            if (!$this->indexExists('quran_circles', 'quran_circles_enrollment_status_idx')) {
                $table->index(['enrollment_status', 'status'], 'quran_circles_enrollment_status_idx');
            }

            if (!$this->indexExists('quran_circles', 'quran_circles_code_academy_idx')) {
                $table->index(['circle_code', 'academy_id'], 'quran_circles_code_academy_idx');
            }
        });

        // QuranIndividualCircles indexes
        Schema::table('quran_individual_circles', function (Blueprint $table) {
            if (!$this->indexExists('quran_individual_circles', 'quran_individual_circles_academy_status_idx')) {
                $table->index(['academy_id', 'status'], 'quran_individual_circles_academy_status_idx');
            }

            if (!$this->indexExists('quran_individual_circles', 'quran_individual_circles_teacher_status_idx')) {
                $table->index(['quran_teacher_id', 'status'], 'quran_individual_circles_teacher_status_idx');
            }

            if (!$this->indexExists('quran_individual_circles', 'quran_individual_circles_student_status_idx')) {
                $table->index(['student_id', 'status'], 'quran_individual_circles_student_status_idx');
            }

            if (!$this->indexExists('quran_individual_circles', 'quran_individual_circles_subscription_idx')) {
                $table->index(['subscription_id'], 'quran_individual_circles_subscription_idx');
            }
        });

        // Users indexes for authentication and lookups
        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_email_verified_idx')) {
                $table->index(['email', 'email_verified_at'], 'users_email_verified_idx');
            }

            if (!$this->indexExists('users', 'users_academy_type_idx')) {
                $table->index(['academy_id', 'user_type'], 'users_academy_type_idx');
            }

            if (!$this->indexExists('users', 'users_phone_verified_idx')) {
                $table->index(['phone', 'phone_verified_at'], 'users_phone_verified_idx');
            }
        });

        // AcademicSessions indexes (if table exists)
        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                if (!$this->indexExists('academic_sessions', 'academic_sessions_academy_status_scheduled_idx')) {
                    $table->index(['academy_id', 'status', 'scheduled_at'], 'academic_sessions_academy_status_scheduled_idx');
                }

                if (!$this->indexExists('academic_sessions', 'academic_sessions_teacher_scheduled_idx')) {
                    $table->index(['academic_teacher_id', 'scheduled_at'], 'academic_sessions_teacher_scheduled_idx');
                }

                if (!$this->indexExists('academic_sessions', 'academic_sessions_student_scheduled_idx')) {
                    $table->index(['student_id', 'scheduled_at'], 'academic_sessions_student_scheduled_idx');
                }

                if (!$this->indexExists('academic_sessions', 'academic_sessions_code_academy_idx')) {
                    $table->index(['session_code', 'academy_id'], 'academic_sessions_code_academy_idx');
                }
            });
        }

        // AcademicSubscriptions indexes (if table exists)
        if (Schema::hasTable('academic_subscriptions')) {
            Schema::table('academic_subscriptions', function (Blueprint $table) {
                if (!$this->indexExists('academic_subscriptions', 'academic_subscriptions_academy_status_idx')) {
                    $table->index(['academy_id', 'status'], 'academic_subscriptions_academy_status_idx');
                }

                if (!$this->indexExists('academic_subscriptions', 'academic_subscriptions_student_status_idx')) {
                    $table->index(['student_id', 'status'], 'academic_subscriptions_student_status_idx');
                }

                if (!$this->indexExists('academic_subscriptions', 'academic_subscriptions_teacher_status_idx')) {
                    $table->index(['teacher_id', 'status'], 'academic_subscriptions_teacher_status_idx');
                }
            });
        }

        // Payments indexes (if table exists)
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!$this->indexExists('payments', 'payments_academy_status_idx')) {
                    $table->index(['academy_id', 'status'], 'payments_academy_status_idx');
                }

                if (!$this->indexExists('payments', 'payments_user_created_idx')) {
                    $table->index(['user_id', 'created_at'], 'payments_user_created_idx');
                }

                if (!$this->indexExists('payments', 'payments_subscription_idx')) {
                    $table->index(['subscription_id', 'subscription_type'], 'payments_subscription_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('payments_academy_status_idx');
                $table->dropIndex('payments_user_created_idx');
                $table->dropIndex('payments_subscription_idx');
            });
        }

        if (Schema::hasTable('academic_subscriptions')) {
            Schema::table('academic_subscriptions', function (Blueprint $table) {
                $table->dropIndex('academic_subscriptions_academy_status_idx');
                $table->dropIndex('academic_subscriptions_student_status_idx');
                $table->dropIndex('academic_subscriptions_teacher_status_idx');
            });
        }

        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                $table->dropIndex('academic_sessions_academy_status_scheduled_idx');
                $table->dropIndex('academic_sessions_teacher_scheduled_idx');
                $table->dropIndex('academic_sessions_student_scheduled_idx');
                $table->dropIndex('academic_sessions_code_academy_idx');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_verified_idx');
            $table->dropIndex('users_academy_type_idx');
            $table->dropIndex('users_phone_verified_idx');
        });

        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->dropIndex('quran_individual_circles_academy_status_idx');
            $table->dropIndex('quran_individual_circles_teacher_status_idx');
            $table->dropIndex('quran_individual_circles_student_status_idx');
            $table->dropIndex('quran_individual_circles_subscription_idx');
        });

        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropIndex('quran_circles_academy_status_idx');
            $table->dropIndex('quran_circles_teacher_status_idx');
            $table->dropIndex('quran_circles_enrollment_status_idx');
            $table->dropIndex('quran_circles_code_academy_idx');
        });

        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropIndex('quran_subscriptions_academy_status_idx');
            $table->dropIndex('quran_subscriptions_student_status_idx');
            $table->dropIndex('quran_subscriptions_teacher_status_idx');
            $table->dropIndex('quran_subscriptions_expires_at_idx');
            $table->dropIndex('quran_subscriptions_code_academy_idx');
        });

        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropIndex('quran_sessions_academy_status_scheduled_idx');
            $table->dropIndex('quran_sessions_teacher_scheduled_idx');
            $table->dropIndex('quran_sessions_student_scheduled_idx');
            $table->dropIndex('quran_sessions_subscription_status_idx');
            $table->dropIndex('quran_sessions_code_academy_idx');
            $table->dropIndex('quran_sessions_subscription_counted_idx');
        });
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $index): bool
    {
        $schema = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $schema->listTableIndexes($table);

        return array_key_exists($index, $indexes);
    }
};
