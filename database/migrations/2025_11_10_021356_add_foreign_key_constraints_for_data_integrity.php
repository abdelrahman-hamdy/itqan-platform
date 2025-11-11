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
        // Note: We're adding foreign keys with ON DELETE CASCADE or ON DELETE SET NULL
        // based on business logic requirements

        // QuranSessions foreign keys
        if (!$this->foreignKeyExists('quran_sessions', 'quran_sessions_academy_id_foreign')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->foreign('academy_id')
                    ->references('id')
                    ->on('academies')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_sessions', 'quran_sessions_quran_teacher_id_foreign')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->foreign('quran_teacher_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_sessions', 'quran_sessions_student_id_foreign')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->foreign('student_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_sessions', 'quran_sessions_subscription_id_foreign')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->foreign('quran_subscription_id')
                    ->references('id')
                    ->on('quran_subscriptions')
                    ->onDelete('set null');
            });
        }

        if (!$this->foreignKeyExists('quran_sessions', 'quran_sessions_circle_id_foreign')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->foreign('circle_id')
                    ->references('id')
                    ->on('quran_circles')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_sessions', 'quran_sessions_individual_circle_id_foreign')) {
            Schema::table('quran_sessions', function (Blueprint $table) {
                $table->foreign('individual_circle_id')
                    ->references('id')
                    ->on('quran_individual_circles')
                    ->onDelete('cascade');
            });
        }

        // QuranSubscriptions foreign keys
        if (!$this->foreignKeyExists('quran_subscriptions', 'quran_subscriptions_academy_id_foreign')) {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                $table->foreign('academy_id')
                    ->references('id')
                    ->on('academies')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_subscriptions', 'quran_subscriptions_student_id_foreign')) {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                $table->foreign('student_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_subscriptions', 'quran_subscriptions_teacher_id_foreign')) {
            Schema::table('quran_subscriptions', function (Blueprint $table) {
                $table->foreign('quran_teacher_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        // QuranCircles foreign keys
        if (!$this->foreignKeyExists('quran_circles', 'quran_circles_academy_id_foreign')) {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->foreign('academy_id')
                    ->references('id')
                    ->on('academies')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_circles', 'quran_circles_teacher_id_foreign')) {
            Schema::table('quran_circles', function (Blueprint $table) {
                $table->foreign('quran_teacher_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        // QuranIndividualCircles foreign keys
        if (!$this->foreignKeyExists('quran_individual_circles', 'quran_individual_circles_academy_id_foreign')) {
            Schema::table('quran_individual_circles', function (Blueprint $table) {
                $table->foreign('academy_id')
                    ->references('id')
                    ->on('academies')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_individual_circles', 'quran_individual_circles_teacher_id_foreign')) {
            Schema::table('quran_individual_circles', function (Blueprint $table) {
                $table->foreign('quran_teacher_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_individual_circles', 'quran_individual_circles_student_id_foreign')) {
            Schema::table('quran_individual_circles', function (Blueprint $table) {
                $table->foreign('student_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        if (!$this->foreignKeyExists('quran_individual_circles', 'quran_individual_circles_subscription_id_foreign')) {
            Schema::table('quran_individual_circles', function (Blueprint $table) {
                $table->foreign('subscription_id')
                    ->references('id')
                    ->on('quran_subscriptions')
                    ->onDelete('cascade');
            });
        }

        // AcademicSessions foreign keys
        if (Schema::hasTable('academic_sessions')) {
            if (!$this->foreignKeyExists('academic_sessions', 'academic_sessions_academy_id_foreign')) {
                Schema::table('academic_sessions', function (Blueprint $table) {
                    $table->foreign('academy_id')
                        ->references('id')
                        ->on('academies')
                        ->onDelete('cascade');
                });
            }

            if (!$this->foreignKeyExists('academic_sessions', 'academic_sessions_teacher_id_foreign')) {
                Schema::table('academic_sessions', function (Blueprint $table) {
                    $table->foreign('academic_teacher_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                });
            }

            if (!$this->foreignKeyExists('academic_sessions', 'academic_sessions_student_id_foreign')) {
                Schema::table('academic_sessions', function (Blueprint $table) {
                    $table->foreign('student_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                });
            }

            if (!$this->foreignKeyExists('academic_sessions', 'academic_sessions_subscription_id_foreign')) {
                Schema::table('academic_sessions', function (Blueprint $table) {
                    $table->foreign('academic_subscription_id')
                        ->references('id')
                        ->on('academic_subscriptions')
                        ->onDelete('set null');
                });
            }
        }

        // AcademicSubscriptions foreign keys
        if (Schema::hasTable('academic_subscriptions')) {
            if (!$this->foreignKeyExists('academic_subscriptions', 'academic_subscriptions_academy_id_foreign')) {
                Schema::table('academic_subscriptions', function (Blueprint $table) {
                    $table->foreign('academy_id')
                        ->references('id')
                        ->on('academies')
                        ->onDelete('cascade');
                });
            }

            if (!$this->foreignKeyExists('academic_subscriptions', 'academic_subscriptions_student_id_foreign')) {
                Schema::table('academic_subscriptions', function (Blueprint $table) {
                    $table->foreign('student_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                });
            }

            if (!$this->foreignKeyExists('academic_subscriptions', 'academic_subscriptions_teacher_id_foreign')) {
                Schema::table('academic_subscriptions', function (Blueprint $table) {
                    $table->foreign('teacher_id')
                        ->references('id')
                        ->on('users')
                        ->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys in reverse order
        if (Schema::hasTable('academic_subscriptions')) {
            Schema::table('academic_subscriptions', function (Blueprint $table) {
                $table->dropForeign(['academy_id']);
                $table->dropForeign(['student_id']);
                $table->dropForeign(['teacher_id']);
            });
        }

        if (Schema::hasTable('academic_sessions')) {
            Schema::table('academic_sessions', function (Blueprint $table) {
                $table->dropForeign(['academy_id']);
                $table->dropForeign(['academic_teacher_id']);
                $table->dropForeign(['student_id']);
                $table->dropForeign(['academic_subscription_id']);
            });
        }

        Schema::table('quran_individual_circles', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropForeign(['quran_teacher_id']);
            $table->dropForeign(['student_id']);
            $table->dropForeign(['subscription_id']);
        });

        Schema::table('quran_circles', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropForeign(['quran_teacher_id']);
        });

        Schema::table('quran_subscriptions', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropForeign(['student_id']);
            $table->dropForeign(['quran_teacher_id']);
        });

        Schema::table('quran_sessions', function (Blueprint $table) {
            $table->dropForeign(['academy_id']);
            $table->dropForeign(['quran_teacher_id']);
            $table->dropForeign(['student_id']);
            $table->dropForeign(['quran_subscription_id']);
            $table->dropForeign(['circle_id']);
            $table->dropForeign(['individual_circle_id']);
        });
    }

    /**
     * Check if a foreign key exists
     */
    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $schema = Schema::getConnection()->getDoctrineSchemaManager();
        $foreignKeys = $schema->listTableForeignKeys($table);

        foreach ($foreignKeys as $key) {
            if ($key->getName() === $foreignKey) {
                return true;
            }
        }

        return false;
    }
};
