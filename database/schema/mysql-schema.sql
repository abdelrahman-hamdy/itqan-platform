/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `academic_grade_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_grade_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `level_number` int NOT NULL DEFAULT '1',
  `education_system` enum('primary','middle','secondary','university','vocational','international','special_needs') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'primary',
  `academic_year_start` date DEFAULT NULL,
  `academic_year_end` date DEFAULT NULL,
  `total_subjects` int NOT NULL DEFAULT '8',
  `core_subjects_count` int NOT NULL DEFAULT '6',
  `elective_subjects_count` int NOT NULL DEFAULT '2',
  `total_credit_hours` int NOT NULL DEFAULT '24',
  `min_credit_hours` int NOT NULL DEFAULT '18',
  `max_credit_hours` int NOT NULL DEFAULT '30',
  `graduation_requirements` json DEFAULT NULL,
  `assessment_system` enum('percentage','letter_grade','gpa','pass_fail','rubric') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'percentage',
  `grading_scale` json DEFAULT NULL,
  `pass_percentage` decimal(5,2) NOT NULL DEFAULT '60.00',
  `curriculum_framework` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `learning_outcomes` json DEFAULT NULL,
  `skill_requirements` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '1',
  `color_code` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#10B981',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_grade_levels_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `academic_grade_levels_education_system_is_active_index` (`education_system`,`is_active`),
  KEY `academic_grade_levels_level_number_display_order_index` (`level_number`,`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `subscription_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `progress_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `last_session_date` date DEFAULT NULL,
  `next_session_date` date DEFAULT NULL,
  `total_sessions_planned` int NOT NULL DEFAULT '0',
  `total_sessions_completed` int NOT NULL DEFAULT '0',
  `total_sessions_missed` int NOT NULL DEFAULT '0',
  `total_sessions_cancelled` int NOT NULL DEFAULT '0',
  `attendance_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `overall_grade` decimal(5,2) DEFAULT NULL,
  `participation_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `homework_completion_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `total_assignments_given` int NOT NULL DEFAULT '0',
  `total_assignments_completed` int NOT NULL DEFAULT '0',
  `total_quizzes_taken` int NOT NULL DEFAULT '0',
  `average_quiz_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `learning_objectives` json DEFAULT NULL,
  `completed_topics` json DEFAULT NULL,
  `current_topics` json DEFAULT NULL,
  `upcoming_topics` json DEFAULT NULL,
  `curriculum_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `strengths` json DEFAULT NULL,
  `weaknesses` json DEFAULT NULL,
  `improvement_areas` json DEFAULT NULL,
  `learning_style_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `student_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `monthly_reports` json DEFAULT NULL,
  `last_report_generated` timestamp NULL DEFAULT NULL,
  `consecutive_attended_sessions` int NOT NULL DEFAULT '0',
  `consecutive_missed_sessions` int NOT NULL DEFAULT '0',
  `last_attendance_update` timestamp NULL DEFAULT NULL,
  `pending_assignments` int NOT NULL DEFAULT '0',
  `overdue_assignments` int NOT NULL DEFAULT '0',
  `last_assignment_submitted` date DEFAULT NULL,
  `next_assignment_due` date DEFAULT NULL,
  `last_teacher_contact` timestamp NULL DEFAULT NULL,
  `last_student_contact` timestamp NULL DEFAULT NULL,
  `last_parent_contact` timestamp NULL DEFAULT NULL,
  `communication_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `engagement_level` enum('excellent','good','average','below_average','poor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motivation_level` enum('very_high','high','medium','low','very_low') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `behavioral_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `special_needs_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `short_term_goals` json DEFAULT NULL,
  `long_term_goals` json DEFAULT NULL,
  `achieved_milestones` json DEFAULT NULL,
  `upcoming_milestones` json DEFAULT NULL,
  `teacher_recommendations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recommended_resources` json DEFAULT NULL,
  `intervention_strategies` json DEFAULT NULL,
  `needs_additional_support` tinyint(1) NOT NULL DEFAULT '0',
  `progress_status` enum('excellent','good','satisfactory','needs_improvement','concerning') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'satisfactory',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_progress_progress_code_unique` (`progress_code`),
  KEY `academic_progress_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `academic_progress_subscription_id_index` (`subscription_id`),
  KEY `academic_progress_student_id_subject_id_index` (`student_id`,`subject_id`),
  KEY `academic_progress_teacher_id_progress_status_index` (`teacher_id`,`progress_status`),
  KEY `academic_progress_progress_status_is_active_index` (`progress_status`,`is_active`),
  KEY `academic_progress_last_session_date_next_session_date_index` (`last_session_date`,`next_session_date`),
  KEY `academic_progress_progress_code_index` (`progress_code`),
  KEY `academic_progress_attendance_rate_overall_grade_index` (`attendance_rate`,`overall_grade`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `sessions_per_week_options` json DEFAULT NULL,
  `default_session_duration_minutes` int NOT NULL DEFAULT '60',
  `default_booking_fee` decimal(8,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `enable_trial_sessions` tinyint(1) NOT NULL DEFAULT '1',
  `trial_session_duration_minutes` int NOT NULL DEFAULT '30',
  `trial_session_fee` decimal(8,2) NOT NULL DEFAULT '0.00',
  `subscription_pause_max_days` int NOT NULL DEFAULT '30',
  `auto_renewal_reminder_days` int NOT NULL DEFAULT '7',
  `allow_mid_month_cancellation` tinyint(1) NOT NULL DEFAULT '0',
  `enabled_payment_methods` json DEFAULT NULL,
  `late_payment_penalty_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `auto_create_google_meet_links` tinyint(1) NOT NULL DEFAULT '1',
  `google_meet_account_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `courses_start_on_schedule` tinyint(1) NOT NULL DEFAULT '1',
  `course_enrollment_deadline_days` int NOT NULL DEFAULT '3',
  `allow_late_enrollment` tinyint(1) NOT NULL DEFAULT '0',
  `available_languages` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_settings_academy_id_unique` (`academy_id`),
  KEY `academic_settings_created_by_foreign` (`created_by`),
  KEY `academic_settings_updated_by_foreign` (`updated_by`),
  KEY `academic_settings_academy_id_created_at_index` (`academy_id`,`created_at`),
  CONSTRAINT `academic_settings_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `category` enum('sciences','mathematics','languages','humanities','social_studies','arts','technology','physical_education','religious_studies','vocational') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sciences',
  `field` enum('natural_sciences','applied_sciences','formal_sciences','humanities','social_sciences','interdisciplinary') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'natural_sciences',
  `level_scope` json DEFAULT NULL,
  `prerequisites` json DEFAULT NULL,
  `color_code` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#3B82F6',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_core_subject` tinyint(1) NOT NULL DEFAULT '1',
  `is_elective` tinyint(1) NOT NULL DEFAULT '0',
  `credit_hours` int NOT NULL DEFAULT '3',
  `difficulty_level` int NOT NULL DEFAULT '1',
  `estimated_duration_weeks` int NOT NULL DEFAULT '16',
  `curriculum_framework` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `learning_objectives` json DEFAULT NULL,
  `assessment_methods` json DEFAULT NULL,
  `required_materials` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_subjects_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `academic_subjects_category_is_active_index` (`category`,`is_active`),
  KEY `academic_subjects_field_is_active_index` (`field`,`is_active`),
  KEY `academic_subjects_difficulty_level_is_active_index` (`difficulty_level`,`is_active`),
  KEY `academic_subjects_is_core_subject_is_elective_index` (`is_core_subject`,`is_elective`),
  KEY `academic_subjects_display_order_index` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `grade_level_id` bigint unsigned NOT NULL,
  `session_request_id` bigint unsigned DEFAULT NULL,
  `subscription_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscription_type` enum('private','group') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `sessions_per_week` int NOT NULL,
  `session_duration_minutes` int NOT NULL DEFAULT '60',
  `hourly_rate` decimal(8,2) NOT NULL,
  `sessions_per_month` decimal(5,2) NOT NULL,
  `monthly_amount` decimal(8,2) NOT NULL,
  `discount_amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `final_monthly_amount` decimal(8,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `billing_cycle` enum('monthly','quarterly','yearly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `next_billing_date` date NOT NULL,
  `last_payment_date` date DEFAULT NULL,
  `last_payment_amount` decimal(8,2) DEFAULT NULL,
  `weekly_schedule` json NOT NULL,
  `timezone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Riyadh',
  `auto_create_google_meet` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('active','paused','suspended','cancelled','expired','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `payment_status` enum('current','pending','overdue','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'current',
  `has_trial_session` tinyint(1) NOT NULL DEFAULT '0',
  `trial_session_used` tinyint(1) NOT NULL DEFAULT '0',
  `trial_session_date` timestamp NULL DEFAULT NULL,
  `trial_session_status` enum('scheduled','completed','missed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `resume_date` timestamp NULL DEFAULT NULL,
  `pause_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `pause_days_remaining` int NOT NULL DEFAULT '0',
  `auto_renewal` tinyint(1) NOT NULL DEFAULT '1',
  `renewal_reminder_days` int NOT NULL DEFAULT '7',
  `last_reminder_sent` timestamp NULL DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `student_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_sessions_scheduled` int NOT NULL DEFAULT '0',
  `total_sessions_completed` int NOT NULL DEFAULT '0',
  `total_sessions_missed` int NOT NULL DEFAULT '0',
  `completion_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_subscriptions_subscription_code_unique` (`subscription_code`),
  KEY `academic_subscriptions_academy_id_status_index` (`academy_id`,`status`),
  KEY `academic_subscriptions_student_id_status_index` (`student_id`,`status`),
  KEY `academic_subscriptions_teacher_id_status_index` (`teacher_id`,`status`),
  KEY `academic_subscriptions_status_payment_status_index` (`status`,`payment_status`),
  KEY `academic_subscriptions_next_billing_date_status_index` (`next_billing_date`,`status`),
  KEY `academic_subscriptions_paused_at_resume_date_index` (`paused_at`,`resume_date`),
  KEY `academic_subscriptions_subscription_code_index` (`subscription_code`),
  KEY `academic_subscriptions_start_date_end_date_index` (`start_date`,`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_teacher_grade_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_teacher_grade_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `grade_level_id` bigint unsigned NOT NULL,
  `years_experience` int NOT NULL DEFAULT '0',
  `specialization_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `at_grade_teacher_grade_unique` (`teacher_id`,`grade_level_id`),
  KEY `at_grade_teacher_grade_idx` (`teacher_id`,`grade_level_id`),
  KEY `at_grade_level_exp_idx` (`grade_level_id`,`years_experience`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_teacher_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_teacher_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `education_level` enum('diploma','bachelor','master','phd') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bachelor',
  `university` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_year` int DEFAULT NULL,
  `qualification_degree` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teaching_experience_years` int NOT NULL DEFAULT '0',
  `certifications` json DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `subject_ids` json DEFAULT NULL,
  `grade_level_ids` json DEFAULT NULL,
  `available_days` json DEFAULT NULL,
  `available_time_start` time NOT NULL DEFAULT '08:00:00',
  `available_time_end` time NOT NULL DEFAULT '18:00:00',
  `session_price_individual` decimal(8,2) NOT NULL DEFAULT '100.00',
  `min_session_duration` int NOT NULL DEFAULT '45',
  `max_session_duration` int NOT NULL DEFAULT '90',
  `max_students_per_group` int NOT NULL DEFAULT '10',
  `bio_arabic` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bio_english` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `approval_status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `total_students` int NOT NULL DEFAULT '0',
  `total_sessions` int NOT NULL DEFAULT '0',
  `total_courses_created` int NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_teacher_profiles_teacher_code_unique` (`teacher_code`),
  UNIQUE KEY `academic_teacher_profiles_email_unique` (`email`),
  KEY `academic_teacher_profiles_approved_by_foreign` (`approved_by`),
  KEY `academic_teacher_profiles_user_id_index` (`user_id`),
  KEY `academic_teacher_profiles_academy_id_approval_status_index` (`academy_id`,`approval_status`),
  CONSTRAINT `academic_teacher_profiles_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_teacher_profiles_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_teacher_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_teacher_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_teacher_students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('active','completed','suspended','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `current_subjects` json DEFAULT NULL,
  `performance_rating` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `at_stud_teacher_stud_unique` (`teacher_id`,`student_id`),
  KEY `at_stud_teacher_stud_idx` (`teacher_id`,`student_id`),
  KEY `at_stud_status_idx` (`student_id`,`status`),
  KEY `at_stud_status_start_idx` (`status`,`start_date`),
  KEY `at_stud_end_date_idx` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_teacher_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_teacher_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced','expert') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'intermediate',
  `years_experience` int NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `certification` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `at_subj_teacher_subj_unique` (`teacher_id`,`subject_id`),
  KEY `at_subj_teacher_subj_idx` (`teacher_id`,`subject_id`),
  KEY `at_subj_level_idx` (`subject_id`,`proficiency_level`),
  KEY `at_subj_primary_level_idx` (`is_primary`,`proficiency_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_teachers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `teacher_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `education_level` enum('diploma','bachelor','master','phd') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bachelor',
  `university` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_year` int DEFAULT NULL,
  `qualification_degree` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teaching_experience_years` int NOT NULL DEFAULT '0',
  `certifications` json DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `available_days` json DEFAULT NULL,
  `available_time_start` time NOT NULL DEFAULT '08:00:00',
  `available_time_end` time NOT NULL DEFAULT '18:00:00',
  `session_price_individual` decimal(8,2) NOT NULL DEFAULT '0.00',
  `min_session_duration` int NOT NULL DEFAULT '45',
  `max_session_duration` int NOT NULL DEFAULT '90',
  `max_students_per_group` int NOT NULL DEFAULT '6',
  `bio_arabic` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bio_english` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `approval_date` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `status` enum('pending','active','inactive','suspended','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `total_students` int NOT NULL DEFAULT '0',
  `total_sessions` int NOT NULL DEFAULT '0',
  `total_courses_created` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_teachers_teacher_code_unique` (`teacher_code`),
  KEY `academic_teachers_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `academic_teachers_specialization_field_is_approved_index` (`is_approved`),
  KEY `academic_teachers_status_is_active_index` (`status`,`is_active`),
  KEY `academic_teachers_education_level_is_active_index` (`education_level`,`is_active`),
  KEY `academic_teachers_teacher_code_index` (`teacher_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subdomain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#0ea5e9',
  `secondary_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#10B981',
  `theme` enum('light','dark','auto') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Riyadh',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `allow_registration` tinyint(1) NOT NULL DEFAULT '1',
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `admin_id` bigint unsigned DEFAULT NULL,
  `total_revenue` decimal(15,2) NOT NULL DEFAULT '0.00',
  `monthly_revenue` decimal(15,2) NOT NULL DEFAULT '0.00',
  `pending_payments` decimal(15,2) NOT NULL DEFAULT '0.00',
  `active_subscriptions` int NOT NULL DEFAULT '0',
  `growth_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academies_subdomain_unique` (`subdomain`),
  KEY `academies_subdomain_index` (`subdomain`),
  KEY `academies_admin_id_foreign` (`admin_id`),
  KEY `academies_is_active_index` (`is_active`),
  CONSTRAINT `academies_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ch_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ch_favorites` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint NOT NULL,
  `favorite_id` bigint NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ch_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ch_messages` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_id` bigint NOT NULL,
  `to_id` bigint NOT NULL,
  `body` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `enrolled_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `status` enum('enrolled','active','completed','dropped','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `final_grade` decimal(5,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_enrollments_user_id_course_id_unique` (`user_id`,`course_id`),
  KEY `course_enrollments_user_id_status_index` (`user_id`,`status`),
  KEY `course_enrollments_course_id_status_index` (`course_id`,`status`),
  KEY `course_enrollments_enrolled_at_index` (`enrolled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_quizzes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recorded_course_id` bigint unsigned NOT NULL,
  `course_section_id` bigint unsigned DEFAULT NULL,
  `lesson_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `quiz_type` enum('lesson','section','course','assignment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lesson',
  `time_limit_minutes` int DEFAULT NULL,
  `max_attempts` int NOT NULL DEFAULT '3',
  `pass_score_percentage` decimal(5,2) NOT NULL DEFAULT '70.00',
  `questions_count` int NOT NULL DEFAULT '0',
  `total_points` int NOT NULL DEFAULT '0',
  `is_randomized` tinyint(1) NOT NULL DEFAULT '0',
  `show_results_immediately` tinyint(1) NOT NULL DEFAULT '1',
  `allow_review` tinyint(1) NOT NULL DEFAULT '1',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `difficulty_level` enum('very_easy','easy','medium','hard','very_hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `completion_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `failure_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `attempts_count` int NOT NULL DEFAULT '0',
  `avg_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `pass_rate_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_quizzes_recorded_course_id_is_published_index` (`recorded_course_id`,`is_published`),
  KEY `course_quizzes_course_section_id_is_published_index` (`course_section_id`,`is_published`),
  KEY `course_quizzes_lesson_id_index` (`lesson_id`),
  KEY `course_quizzes_quiz_type_is_published_index` (`quiz_type`,`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `rating` int NOT NULL DEFAULT '5',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_helpful` tinyint(1) NOT NULL DEFAULT '0',
  `helpful_votes` int NOT NULL DEFAULT '0',
  `is_verified_purchase` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_course_review` (`student_id`,`course_id`),
  KEY `course_reviews_created_by_foreign` (`created_by`),
  KEY `course_reviews_updated_by_foreign` (`updated_by`),
  KEY `course_reviews_academy_id_status_index` (`academy_id`,`status`),
  KEY `course_reviews_course_id_status_index` (`course_id`,`status`),
  KEY `course_reviews_student_id_course_id_index` (`student_id`,`course_id`),
  KEY `course_reviews_rating_status_index` (`rating`,`status`),
  KEY `course_reviews_is_verified_purchase_status_index` (`is_verified_purchase`,`status`),
  KEY `course_reviews_created_at_status_index` (`created_at`,`status`),
  CONSTRAINT `course_reviews_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_reviews_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `recorded_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_reviews_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `course_reviews_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_reviews_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recorded_course_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `order` int NOT NULL DEFAULT '1',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `is_free_preview` tinyint(1) NOT NULL DEFAULT '0',
  `duration_minutes` int NOT NULL DEFAULT '0',
  `lessons_count` int NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_sections_recorded_course_id_order_index` (`recorded_course_id`,`order`),
  KEY `course_sections_recorded_course_id_is_published_index` (`recorded_course_id`,`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `recorded_course_id` bigint unsigned NOT NULL,
  `subscription_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `enrollment_type` enum('free','paid','trial','gift') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `payment_type` enum('one_time','installment','subscription') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time',
  `price_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `payment_status` enum('pending','paid','failed','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `access_type` enum('limited','lifetime') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'limited',
  `access_duration_months` int NOT NULL DEFAULT '12',
  `lifetime_access` tinyint(1) NOT NULL DEFAULT '0',
  `certificate_eligible` tinyint(1) NOT NULL DEFAULT '1',
  `certificate_issued` tinyint(1) NOT NULL DEFAULT '0',
  `certificate_issued_at` timestamp NULL DEFAULT NULL,
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `completed_lessons` int NOT NULL DEFAULT '0',
  `total_lessons` int NOT NULL DEFAULT '0',
  `watch_time_minutes` int NOT NULL DEFAULT '0',
  `total_duration_minutes` int NOT NULL DEFAULT '0',
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `completion_date` timestamp NULL DEFAULT NULL,
  `status` enum('active','completed','paused','expired','cancelled','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `enrolled_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `pause_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `refund_requested_at` timestamp NULL DEFAULT NULL,
  `refund_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `refund_processed_at` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `notes_count` int NOT NULL DEFAULT '0',
  `bookmarks_count` int NOT NULL DEFAULT '0',
  `quiz_attempts` int NOT NULL DEFAULT '0',
  `quiz_passed` tinyint(1) NOT NULL DEFAULT '0',
  `final_score` decimal(5,2) DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `review_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `completion_certificate_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_subscriptions_student_id_recorded_course_id_unique` (`student_id`,`recorded_course_id`),
  UNIQUE KEY `course_subscriptions_subscription_code_unique` (`subscription_code`),
  KEY `course_subscriptions_academy_id_status_index` (`academy_id`,`status`),
  KEY `course_subscriptions_student_id_status_index` (`student_id`,`status`),
  KEY `course_subscriptions_recorded_course_id_status_index` (`recorded_course_id`,`status`),
  KEY `course_subscriptions_enrollment_type_status_index` (`enrollment_type`,`status`),
  KEY `course_subscriptions_expires_at_status_index` (`expires_at`,`status`),
  KEY `course_subscriptions_certificate_issued_index` (`certificate_issued`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `grade_level_id` bigint unsigned DEFAULT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` enum('individual','group','recorded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'group',
  `level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `duration_weeks` int NOT NULL DEFAULT '8',
  `sessions_per_week` int NOT NULL DEFAULT '2',
  `session_duration_minutes` int NOT NULL DEFAULT '60',
  `max_students` int NOT NULL DEFAULT '10',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courses_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `courses_teacher_id_is_active_index` (`teacher_id`,`is_active`),
  KEY `courses_type_is_active_index` (`type`,`is_active`),
  KEY `courses_starts_at_ends_at_index` (`starts_at`,`ends_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `grade_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `level` int NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grade_levels_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `grade_levels_level_index` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interactive_course_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactive_course_enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `enrolled_by` bigint unsigned DEFAULT NULL,
  `enrollment_date` timestamp NOT NULL,
  `payment_status` enum('pending','paid','refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_amount` decimal(8,2) NOT NULL,
  `discount_applied` decimal(8,2) NOT NULL DEFAULT '0.00',
  `enrollment_status` enum('enrolled','dropped','completed','expelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `final_grade` decimal(5,2) DEFAULT NULL,
  `attendance_count` int NOT NULL DEFAULT '0',
  `total_possible_attendance` int NOT NULL DEFAULT '0',
  `certificate_issued` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interactive_course_enrollments_course_id_student_id_unique` (`course_id`,`student_id`),
  KEY `interactive_course_enrollments_student_id_foreign` (`student_id`),
  KEY `interactive_course_enrollments_enrolled_by_foreign` (`enrolled_by`),
  KEY `ice_academy_status_idx` (`academy_id`,`enrollment_status`),
  CONSTRAINT `interactive_course_enrollments_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_course_enrollments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `interactive_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_course_enrollments_enrolled_by_foreign` FOREIGN KEY (`enrolled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interactive_course_enrollments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interactive_course_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactive_course_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned NOT NULL,
  `session_number` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `duration_minutes` int NOT NULL,
  `google_meet_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `attendance_count` int NOT NULL DEFAULT '0',
  `materials_uploaded` tinyint(1) NOT NULL DEFAULT '0',
  `homework_assigned` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interactive_course_sessions_course_id_session_number_unique` (`course_id`,`session_number`),
  KEY `ics_course_date_idx` (`course_id`,`scheduled_date`),
  CONSTRAINT `interactive_course_sessions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `interactive_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interactive_course_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactive_course_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `default_teacher_payment_type` enum('fixed','per_student','per_session') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed',
  `min_teacher_payment` decimal(8,2) NOT NULL DEFAULT '100.00',
  `max_discount_percentage` decimal(5,2) NOT NULL DEFAULT '20.00',
  `min_course_duration_weeks` int NOT NULL DEFAULT '4',
  `max_students_per_course` int NOT NULL DEFAULT '30',
  `auto_create_sessions` tinyint(1) NOT NULL DEFAULT '1',
  `require_attendance_minimum` decimal(5,2) NOT NULL DEFAULT '75.00',
  `auto_create_google_meet` tinyint(1) NOT NULL DEFAULT '1',
  `send_reminder_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `certificate_auto_generation` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interactive_course_settings_academy_id_unique` (`academy_id`),
  KEY `interactive_course_settings_created_by_foreign` (`created_by`),
  KEY `interactive_course_settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `interactive_course_settings_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_course_settings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_course_settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interactive_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactive_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `assigned_teacher_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `grade_level_id` bigint unsigned NOT NULL,
  `course_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_type` enum('intensive','regular','exam_prep') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `max_students` int NOT NULL DEFAULT '20',
  `duration_weeks` int NOT NULL,
  `sessions_per_week` int NOT NULL,
  `session_duration_minutes` int NOT NULL DEFAULT '60',
  `total_sessions` int NOT NULL,
  `student_price` decimal(8,2) NOT NULL,
  `teacher_payment` decimal(8,2) NOT NULL,
  `payment_type` enum('fixed_amount','per_student','per_session') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed_amount',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `enrollment_deadline` date NOT NULL,
  `schedule` json NOT NULL,
  `status` enum('draft','published','active','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `publication_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interactive_courses_course_code_unique` (`course_code`),
  KEY `interactive_courses_created_by_foreign` (`created_by`),
  KEY `interactive_courses_updated_by_foreign` (`updated_by`),
  KEY `interactive_courses_grade_level_id_foreign` (`grade_level_id`),
  KEY `ic_academy_status_idx` (`academy_id`,`status`),
  KEY `ic_teacher_status_idx` (`assigned_teacher_id`,`status`),
  KEY `ic_subject_grade_idx` (`subject_id`,`grade_level_id`),
  CONSTRAINT `interactive_courses_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_courses_assigned_teacher_id_foreign` FOREIGN KEY (`assigned_teacher_id`) REFERENCES `academic_teacher_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_courses_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_courses_grade_level_id_foreign` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_courses_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_courses_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interactive_session_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactive_session_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `attendance_status` enum('present','absent','late') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'absent',
  `join_time` timestamp NULL DEFAULT NULL,
  `leave_time` timestamp NULL DEFAULT NULL,
  `participation_score` decimal(3,1) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interactive_session_attendances_session_id_student_id_unique` (`session_id`,`student_id`),
  KEY `interactive_session_attendances_student_id_foreign` (`student_id`),
  KEY `isa_session_status_idx` (`session_id`,`attendance_status`),
  CONSTRAINT `interactive_session_attendances_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `interactive_course_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_session_attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interactive_teacher_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactive_teacher_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_type` enum('fixed','per_student','per_session') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `students_enrolled` int NOT NULL DEFAULT '0',
  `amount_per_student` decimal(8,2) DEFAULT NULL,
  `bonus_amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `deductions` decimal(8,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('pending','partial','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `paid_by` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interactive_teacher_payments_course_id_teacher_id_unique` (`course_id`,`teacher_id`),
  KEY `interactive_teacher_payments_academy_id_foreign` (`academy_id`),
  KEY `interactive_teacher_payments_teacher_id_foreign` (`teacher_id`),
  KEY `interactive_teacher_payments_paid_by_foreign` (`paid_by`),
  CONSTRAINT `interactive_teacher_payments_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_teacher_payments_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `interactive_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_teacher_payments_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interactive_teacher_payments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `academic_teacher_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lessons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recorded_course_id` bigint unsigned NOT NULL,
  `course_section_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lesson_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_duration_seconds` int NOT NULL DEFAULT '0',
  `video_size_mb` decimal(10,2) NOT NULL DEFAULT '0.00',
  `video_quality` enum('480p','720p','1080p','4K') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '720p',
  `transcript` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `attachments` json DEFAULT NULL,
  `order` int NOT NULL DEFAULT '1',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `is_free_preview` tinyint(1) NOT NULL DEFAULT '0',
  `is_downloadable` tinyint(1) NOT NULL DEFAULT '0',
  `lesson_type` enum('video','quiz','assignment','reading') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'video',
  `quiz_id` bigint unsigned DEFAULT NULL,
  `assignment_requirements` json DEFAULT NULL,
  `learning_objectives` json DEFAULT NULL,
  `difficulty_level` enum('very_easy','easy','medium','hard','very_hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `estimated_study_time_minutes` int NOT NULL DEFAULT '0',
  `view_count` int NOT NULL DEFAULT '0',
  `avg_rating` decimal(3,1) NOT NULL DEFAULT '0.0',
  `total_comments` int NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lessons_lesson_code_unique` (`lesson_code`),
  KEY `lessons_recorded_course_id_order_index` (`recorded_course_id`,`order`),
  KEY `lessons_course_section_id_order_index` (`course_section_id`,`order`),
  KEY `lessons_is_published_lesson_type_index` (`is_published`,`lesson_type`),
  KEY `lessons_is_free_preview_is_published_index` (`is_free_preview`,`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parent_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parent_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship_type` enum('father','mother','guardian','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'father',
  `occupation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `workplace` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `secondary_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_contact_method` enum('phone','email','sms','whatsapp') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'phone',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_profiles_email_unique` (`email`),
  UNIQUE KEY `parent_profiles_parent_code_unique` (`parent_code`),
  KEY `parent_profiles_user_id_foreign` (`user_id`),
  KEY `parent_profiles_academy_id_index` (`academy_id`),
  CONSTRAINT `parent_profiles_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parent_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parent_student_relationships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parent_student_relationships` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `relationship_type` enum('father','mother','guardian','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'father',
  `is_primary_contact` tinyint(1) NOT NULL DEFAULT '0',
  `can_view_grades` tinyint(1) NOT NULL DEFAULT '1',
  `can_receive_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_student_relationships_parent_id_student_id_unique` (`parent_id`,`student_id`),
  KEY `parent_student_relationships_student_id_foreign` (`student_id`),
  CONSTRAINT `parent_student_relationships_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `parent_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `parent_student_relationships_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `subscription_id` bigint unsigned DEFAULT NULL,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `payment_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_method` enum('credit_card','debit_card','bank_transfer','wallet','cash','mada','visa','mastercard','apple_pay','stc_pay','urpay') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_gateway` enum('tap','moyasar','payfort','hyperpay','paytabs','manual') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_payment_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_type` enum('subscription','course','session','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'subscription',
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `exchange_rate` decimal(8,4) NOT NULL DEFAULT '1.0000',
  `amount_in_base_currency` decimal(10,2) DEFAULT NULL,
  `fees` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_amount` decimal(10,2) NOT NULL,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax_percentage` decimal(5,2) NOT NULL DEFAULT '15.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','processing','completed','failed','cancelled','refunded','partially_refunded') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','processing','paid','failed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `gateway_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failure_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gateway_response` json DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `refunded_at` timestamp NULL DEFAULT NULL,
  `refund_amount` decimal(10,2) DEFAULT NULL,
  `refund_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `refund_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_payment_code_unique` (`payment_code`),
  KEY `payments_academy_id_status_index` (`academy_id`,`status`),
  KEY `payments_user_id_status_index` (`user_id`,`status`),
  KEY `payments_subscription_id_index` (`subscription_id`),
  KEY `payments_payment_method_status_index` (`payment_method`,`status`),
  KEY `payments_payment_gateway_status_index` (`payment_gateway`,`status`),
  KEY `payments_payment_date_status_index` (`payment_date`,`status`),
  KEY `payments_gateway_transaction_id_index` (`gateway_transaction_id`),
  KEY `payments_payment_code_index` (`payment_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quizzes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_circle_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_circle_students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `circle_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `enrolled_at` timestamp NOT NULL,
  `status` enum('enrolled','completed','dropped','suspended','transferred') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `attendance_count` int NOT NULL DEFAULT '0',
  `missed_sessions` int NOT NULL DEFAULT '0',
  `makeup_sessions_used` int NOT NULL DEFAULT '0',
  `current_level` enum('beginner','elementary','intermediate','advanced','expert') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `progress_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_rating` int DEFAULT NULL COMMENT '1-5 rating from parent',
  `student_rating` int DEFAULT NULL COMMENT '1-5 rating from student',
  `completion_date` timestamp NULL DEFAULT NULL,
  `certificate_issued` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_circle_students_circle_id_student_id_unique` (`circle_id`,`student_id`),
  KEY `quran_circle_students_circle_id_status_index` (`circle_id`,`status`),
  KEY `quran_circle_students_student_id_status_index` (`student_id`,`status`),
  KEY `quran_circle_students_status_enrolled_at_index` (`status`,`enrolled_at`),
  KEY `quran_circle_students_certificate_issued_index` (`certificate_issued`),
  CONSTRAINT `quran_circle_students_circle_id_foreign` FOREIGN KEY (`circle_id`) REFERENCES `quran_circles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_circle_students_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_circles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_circles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `quran_teacher_id` bigint unsigned DEFAULT NULL,
  `supervisor_id` bigint unsigned DEFAULT NULL,
  `circle_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_ar` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description_ar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `circle_type` enum('memorization','recitation','mixed','advanced','beginners') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'memorization',
  `specialization` enum('memorization','recitation','interpretation','arabic_language','complete') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'memorization',
  `memorization_level` enum('beginner','elementary','intermediate','advanced','expert') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `age_group` enum('children','youth','adults','all_ages') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender_type` enum('male','female','mixed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `max_students` int NOT NULL DEFAULT '8',
  `enrolled_students` int NOT NULL DEFAULT '0',
  `min_students_to_start` int NOT NULL DEFAULT '3',
  `session_duration_minutes` int NOT NULL DEFAULT '60',
  `schedule_days` json DEFAULT NULL,
  `schedule_time` time DEFAULT NULL,
  `timezone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Riyadh',
  `monthly_fee` decimal(8,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `enrollment_fee` decimal(8,2) NOT NULL DEFAULT '0.00',
  `materials_fee` decimal(8,2) NOT NULL DEFAULT '0.00',
  `sessions_completed` int NOT NULL DEFAULT '0',
  `current_surah` int DEFAULT NULL,
  `current_verse` int DEFAULT NULL,
  `materials_used` json DEFAULT NULL,
  `requirements` json DEFAULT NULL,
  `learning_objectives` json DEFAULT NULL,
  `status` enum('planning','pending','active','ongoing','completed','cancelled','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planning',
  `enrollment_status` enum('open','closed','full','waitlist') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'closed',
  `actual_start_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `last_session_at` timestamp NULL DEFAULT NULL,
  `next_session_at` timestamp NULL DEFAULT NULL,
  `room_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recording_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `attendance_required` tinyint(1) NOT NULL DEFAULT '1',
  `makeup_sessions_allowed` tinyint(1) NOT NULL DEFAULT '1',
  `certificates_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `avg_rating` decimal(2,1) NOT NULL DEFAULT '0.0',
  `total_reviews` int NOT NULL DEFAULT '0',
  `completion_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `dropout_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `special_instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_circles_academy_id_circle_code_unique` (`academy_id`,`circle_code`),
  UNIQUE KEY `quran_circles_circle_code_unique` (`circle_code`),
  KEY `quran_circles_supervisor_id_foreign` (`supervisor_id`),
  KEY `quran_circles_created_by_foreign` (`created_by`),
  KEY `quran_circles_updated_by_foreign` (`updated_by`),
  KEY `quran_circles_academy_id_status_index` (`academy_id`,`status`),
  KEY `quran_circles_academy_id_enrollment_status_index` (`academy_id`,`enrollment_status`),
  KEY `quran_circles_quran_teacher_id_status_index` (`quran_teacher_id`,`status`),
  KEY `quran_circles_circle_type_specialization_index` (`circle_type`,`specialization`),
  KEY `quran_circles_status_start_date_index` (`status`),
  KEY `quran_circles_enrollment_status_registration_deadline_index` (`enrollment_status`),
  KEY `quran_circles_memorization_level_status_index` (`memorization_level`,`status`),
  KEY `quran_circles_avg_rating_index` (`avg_rating`),
  KEY `quran_circles_circle_code_index` (`circle_code`),
  CONSTRAINT `quran_circles_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_circles_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_circles_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_circles_supervisor_id_foreign` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_circles_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_homework`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_homework` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `quran_teacher_id` bigint unsigned DEFAULT NULL,
  `student_id` bigint unsigned NOT NULL,
  `subscription_id` bigint unsigned DEFAULT NULL,
  `circle_id` bigint unsigned DEFAULT NULL,
  `session_id` bigint unsigned DEFAULT NULL,
  `homework_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `homework_type` enum('memorization','recitation','review','research','writing','listening','practice') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'memorization',
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `difficulty_level` enum('very_easy','easy','medium','hard','very_hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `estimated_duration_minutes` int DEFAULT NULL,
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `requirements` json DEFAULT NULL,
  `learning_objectives` json DEFAULT NULL,
  `surah_assignment` int DEFAULT NULL,
  `verse_from` int DEFAULT NULL,
  `verse_to` int DEFAULT NULL,
  `total_verses` int NOT NULL DEFAULT '0',
  `memorization_required` tinyint(1) NOT NULL DEFAULT '0',
  `recitation_required` tinyint(1) NOT NULL DEFAULT '0',
  `tajweed_focus_areas` json DEFAULT NULL,
  `pronunciation_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `repetition_count_required` int NOT NULL DEFAULT '0',
  `audio_submission_required` tinyint(1) NOT NULL DEFAULT '0',
  `video_submission_required` tinyint(1) NOT NULL DEFAULT '0',
  `written_submission_required` tinyint(1) NOT NULL DEFAULT '0',
  `practice_materials` json DEFAULT NULL,
  `reference_materials` json DEFAULT NULL,
  `assigned_at` timestamp NOT NULL,
  `due_date` timestamp NOT NULL,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `submission_method` enum('audio','video','text','file','live','mixed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submission_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `submission_files` json DEFAULT NULL,
  `audio_recording_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_recording_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submission_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `submission_status` enum('not_submitted','partial','complete','late','resubmission') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_submitted',
  `evaluation_criteria` json DEFAULT NULL,
  `teacher_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `grade` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `quality_score` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `accuracy_score` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `effort_score` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `improvement_areas` json DEFAULT NULL,
  `strengths_noted` json DEFAULT NULL,
  `next_steps` json DEFAULT NULL,
  `follow_up_required` tinyint(1) NOT NULL DEFAULT '0',
  `follow_up_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `evaluated_at` timestamp NULL DEFAULT NULL,
  `status` enum('assigned','in_progress','submitted','evaluated','completed','overdue','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'assigned',
  `completion_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `time_spent_minutes` int NOT NULL DEFAULT '0',
  `attempts_count` int NOT NULL DEFAULT '0',
  `parent_reviewed` tinyint(1) NOT NULL DEFAULT '0',
  `parent_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_signature` tinyint(1) NOT NULL DEFAULT '0',
  `extension_requested` tinyint(1) NOT NULL DEFAULT '0',
  `extension_granted` tinyint(1) NOT NULL DEFAULT '0',
  `new_due_date` timestamp NULL DEFAULT NULL,
  `extension_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `late_submission` tinyint(1) NOT NULL DEFAULT '0',
  `late_penalty_applied` tinyint(1) NOT NULL DEFAULT '0',
  `bonus_points` decimal(3,1) NOT NULL DEFAULT '0.0',
  `total_score` decimal(3,1) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_homework_academy_id_homework_code_unique` (`academy_id`,`homework_code`),
  UNIQUE KEY `quran_homework_homework_code_unique` (`homework_code`),
  KEY `quran_homework_created_by_foreign` (`created_by`),
  KEY `quran_homework_updated_by_foreign` (`updated_by`),
  KEY `quran_homework_academy_id_due_date_index` (`academy_id`,`due_date`),
  KEY `quran_homework_academy_id_status_index` (`academy_id`,`status`),
  KEY `quran_homework_student_id_due_date_index` (`student_id`,`due_date`),
  KEY `quran_homework_student_id_status_index` (`student_id`,`status`),
  KEY `quran_homework_quran_teacher_id_due_date_index` (`quran_teacher_id`,`due_date`),
  KEY `quran_homework_quran_teacher_id_status_index` (`quran_teacher_id`,`status`),
  KEY `quran_homework_subscription_id_assigned_at_index` (`subscription_id`,`assigned_at`),
  KEY `quran_homework_circle_id_assigned_at_index` (`circle_id`,`assigned_at`),
  KEY `quran_homework_session_id_homework_type_index` (`session_id`,`homework_type`),
  KEY `quran_homework_homework_type_priority_index` (`homework_type`,`priority`),
  KEY `quran_homework_status_due_date_index` (`status`,`due_date`),
  KEY `quran_homework_submission_status_submitted_at_index` (`submission_status`,`submitted_at`),
  KEY `quran_homework_follow_up_required_evaluated_at_index` (`follow_up_required`,`evaluated_at`),
  KEY `quran_homework_parent_reviewed_status_index` (`parent_reviewed`,`status`),
  KEY `quran_homework_late_submission_due_date_index` (`late_submission`,`due_date`),
  KEY `quran_homework_homework_code_index` (`homework_code`),
  CONSTRAINT `quran_homework_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_homework_circle_id_foreign` FOREIGN KEY (`circle_id`) REFERENCES `quran_circles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_homework_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_homework_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_homework_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `quran_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_homework_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_homework_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `quran_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_homework_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `name_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description_ar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sessions_per_month` int NOT NULL,
  `session_duration_minutes` int NOT NULL,
  `monthly_price` decimal(10,2) NOT NULL,
  `quarterly_price` decimal(10,2) NOT NULL,
  `yearly_price` decimal(10,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `features` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quran_packages_created_by_foreign` (`created_by`),
  KEY `quran_packages_updated_by_foreign` (`updated_by`),
  KEY `quran_packages_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `quran_packages_sort_order_index` (`sort_order`),
  CONSTRAINT `quran_packages_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_packages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_packages_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `quran_teacher_id` bigint unsigned DEFAULT NULL,
  `quran_subscription_id` bigint unsigned DEFAULT NULL,
  `circle_id` bigint unsigned DEFAULT NULL,
  `session_id` bigint unsigned DEFAULT NULL,
  `progress_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `progress_date` date NOT NULL,
  `progress_type` enum('memorization','recitation','review','assessment','test','milestone') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'memorization',
  `current_surah` int DEFAULT NULL,
  `current_verse` int DEFAULT NULL,
  `target_surah` int DEFAULT NULL,
  `target_verse` int DEFAULT NULL,
  `verses_memorized` int NOT NULL DEFAULT '0',
  `verses_reviewed` int NOT NULL DEFAULT '0',
  `verses_perfect` int NOT NULL DEFAULT '0',
  `verses_need_work` int NOT NULL DEFAULT '0',
  `total_verses_memorized` int NOT NULL DEFAULT '0',
  `total_pages_memorized` int NOT NULL DEFAULT '0',
  `total_surahs_completed` int NOT NULL DEFAULT '0',
  `memorization_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `recitation_quality` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `tajweed_accuracy` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `fluency_level` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `confidence_level` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `retention_rate` decimal(5,1) DEFAULT NULL COMMENT 'Percentage',
  `common_mistakes` json DEFAULT NULL,
  `improvement_areas` json DEFAULT NULL,
  `strengths` json DEFAULT NULL,
  `weekly_goal` int DEFAULT NULL,
  `monthly_goal` int DEFAULT NULL,
  `goal_progress` decimal(5,2) NOT NULL DEFAULT '0.00',
  `difficulty_level` enum('very_easy','easy','moderate','challenging','very_challenging') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `study_hours_this_week` decimal(5,2) NOT NULL DEFAULT '0.00',
  `average_daily_study` decimal(4,2) NOT NULL DEFAULT '0.00',
  `last_review_date` date DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `repetition_count` int NOT NULL DEFAULT '0',
  `mastery_level` enum('beginner','developing','proficient','advanced','expert','master') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `certificate_eligible` tinyint(1) NOT NULL DEFAULT '0',
  `milestones_achieved` json DEFAULT NULL,
  `performance_trends` json DEFAULT NULL,
  `learning_pace` enum('very_slow','slow','normal','fast','very_fast') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `consistency_score` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `attendance_impact` decimal(3,1) DEFAULT NULL,
  `homework_completion_rate` decimal(5,1) DEFAULT NULL,
  `quiz_average_score` decimal(5,1) DEFAULT NULL,
  `parent_involvement_level` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `motivation_level` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `challenges_faced` json DEFAULT NULL,
  `support_needed` json DEFAULT NULL,
  `recommendations` json DEFAULT NULL,
  `next_steps` json DEFAULT NULL,
  `teacher_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `student_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `assessment_date` date DEFAULT NULL,
  `overall_rating` int DEFAULT NULL COMMENT '1-5 rating',
  `progress_status` enum('on_track','ahead','behind','needs_attention','excellent','struggling') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'on_track',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_progress_academy_id_progress_code_unique` (`academy_id`,`progress_code`),
  UNIQUE KEY `quran_progress_progress_code_unique` (`progress_code`),
  KEY `quran_progress_session_id_foreign` (`session_id`),
  KEY `quran_progress_created_by_foreign` (`created_by`),
  KEY `quran_progress_updated_by_foreign` (`updated_by`),
  KEY `quran_progress_academy_id_progress_date_index` (`academy_id`,`progress_date`),
  KEY `quran_progress_student_id_progress_date_index` (`student_id`,`progress_date`),
  KEY `quran_progress_quran_teacher_id_progress_date_index` (`quran_teacher_id`,`progress_date`),
  KEY `quran_progress_student_id_progress_type_index` (`student_id`,`progress_type`),
  KEY `quran_progress_quran_subscription_id_progress_date_index` (`quran_subscription_id`,`progress_date`),
  KEY `quran_progress_circle_id_progress_date_index` (`circle_id`,`progress_date`),
  KEY `quran_progress_progress_type_progress_date_index` (`progress_type`,`progress_date`),
  KEY `quran_progress_progress_status_progress_date_index` (`progress_status`,`progress_date`),
  KEY `quran_progress_mastery_level_certificate_eligible_index` (`mastery_level`,`certificate_eligible`),
  KEY `quran_progress_current_surah_current_verse_index` (`current_surah`,`current_verse`),
  KEY `quran_progress_progress_code_index` (`progress_code`),
  CONSTRAINT `quran_progress_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_progress_circle_id_foreign` FOREIGN KEY (`circle_id`) REFERENCES `quran_circles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_progress_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_progress_quran_subscription_id_foreign` FOREIGN KEY (`quran_subscription_id`) REFERENCES `quran_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_progress_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_progress_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `quran_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_progress_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_progress_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `quran_teacher_id` bigint unsigned DEFAULT NULL,
  `quran_subscription_id` bigint unsigned DEFAULT NULL,
  `circle_id` bigint unsigned DEFAULT NULL,
  `student_id` bigint unsigned DEFAULT NULL,
  `session_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_type` enum('individual','circle','makeup','trial','assessment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `status` enum('scheduled','ongoing','completed','cancelled','missed','rescheduled','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lesson_objectives` json DEFAULT NULL,
  `scheduled_at` timestamp NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration_minutes` int NOT NULL DEFAULT '45',
  `actual_duration_minutes` int DEFAULT NULL,
  `location_type` enum('online','physical','hybrid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'online',
  `location_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meeting_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_password` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recording_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recording_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `attendance_status` enum('attended','absent','late','left_early','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `participants_count` int NOT NULL DEFAULT '1',
  `attendance_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `current_surah` int DEFAULT NULL,
  `current_verse` int DEFAULT NULL,
  `verses_covered_start` int DEFAULT NULL,
  `verses_covered_end` int DEFAULT NULL,
  `verses_memorized_today` int NOT NULL DEFAULT '0',
  `recitation_quality` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `tajweed_accuracy` decimal(3,1) DEFAULT NULL COMMENT '1-10 scale',
  `mistakes_count` int NOT NULL DEFAULT '0',
  `common_mistakes` json DEFAULT NULL,
  `areas_for_improvement` json DEFAULT NULL,
  `homework_assigned` json DEFAULT NULL,
  `homework_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `next_session_plan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `session_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `student_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `overall_rating` int DEFAULT NULL COMMENT '1-5 rating',
  `technical_issues` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `makeup_session_for` bigint unsigned DEFAULT NULL,
  `is_makeup_session` tinyint(1) NOT NULL DEFAULT '0',
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `reschedule_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rescheduled_from` timestamp NULL DEFAULT NULL,
  `rescheduled_to` timestamp NULL DEFAULT NULL,
  `materials_used` json DEFAULT NULL,
  `learning_outcomes` json DEFAULT NULL,
  `assessment_results` json DEFAULT NULL,
  `follow_up_required` tinyint(1) NOT NULL DEFAULT '0',
  `follow_up_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_sessions_academy_id_session_code_unique` (`academy_id`,`session_code`),
  UNIQUE KEY `quran_sessions_session_code_unique` (`session_code`),
  KEY `quran_sessions_makeup_session_for_foreign` (`makeup_session_for`),
  KEY `quran_sessions_cancelled_by_foreign` (`cancelled_by`),
  KEY `quran_sessions_created_by_foreign` (`created_by`),
  KEY `quran_sessions_updated_by_foreign` (`updated_by`),
  KEY `quran_sessions_academy_id_scheduled_at_index` (`academy_id`,`scheduled_at`),
  KEY `quran_sessions_academy_id_status_index` (`academy_id`,`status`),
  KEY `quran_sessions_quran_teacher_id_scheduled_at_index` (`quran_teacher_id`,`scheduled_at`),
  KEY `quran_sessions_quran_teacher_id_status_index` (`quran_teacher_id`,`status`),
  KEY `quran_sessions_student_id_scheduled_at_index` (`student_id`,`scheduled_at`),
  KEY `quran_sessions_quran_subscription_id_status_index` (`quran_subscription_id`,`status`),
  KEY `quran_sessions_circle_id_scheduled_at_index` (`circle_id`,`scheduled_at`),
  KEY `quran_sessions_session_type_status_index` (`session_type`,`status`),
  KEY `quran_sessions_scheduled_at_status_index` (`scheduled_at`,`status`),
  KEY `quran_sessions_is_makeup_session_makeup_session_for_index` (`is_makeup_session`,`makeup_session_for`),
  KEY `quran_sessions_attendance_status_scheduled_at_index` (`attendance_status`,`scheduled_at`),
  KEY `quran_sessions_session_code_index` (`session_code`),
  CONSTRAINT `quran_sessions_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_sessions_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_circle_id_foreign` FOREIGN KEY (`circle_id`) REFERENCES `quran_circles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_makeup_session_for_foreign` FOREIGN KEY (`makeup_session_for`) REFERENCES `quran_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_quran_subscription_id_foreign` FOREIGN KEY (`quran_subscription_id`) REFERENCES `quran_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `quran_teacher_id` bigint unsigned DEFAULT NULL,
  `package_id` bigint unsigned DEFAULT NULL,
  `subscription_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscription_type` enum('individual','group') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_sessions` int NOT NULL,
  `sessions_used` int NOT NULL DEFAULT '0',
  `sessions_remaining` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `final_price` decimal(10,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `billing_cycle` enum('monthly','quarterly','yearly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_status` enum('paid','pending','failed','refunded','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `subscription_status` enum('active','expired','paused','cancelled','pending','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `trial_sessions` int NOT NULL DEFAULT '0',
  `trial_used` int NOT NULL DEFAULT '0',
  `is_trial_active` tinyint(1) NOT NULL DEFAULT '0',
  `current_surah` int DEFAULT NULL,
  `current_verse` int DEFAULT NULL,
  `verses_memorized` int NOT NULL DEFAULT '0',
  `memorization_level` enum('beginner','elementary','intermediate','advanced','expert','hafiz') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `last_session_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `pause_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '1',
  `next_payment_at` timestamp NULL DEFAULT NULL,
  `last_payment_at` timestamp NULL DEFAULT NULL,
  `rating` int DEFAULT NULL COMMENT '1-5 rating',
  `review_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_subscriptions_academy_id_subscription_code_unique` (`academy_id`,`subscription_code`),
  UNIQUE KEY `quran_subscriptions_subscription_code_unique` (`subscription_code`),
  KEY `quran_subscriptions_created_by_foreign` (`created_by`),
  KEY `quran_subscriptions_updated_by_foreign` (`updated_by`),
  KEY `quran_subscriptions_academy_id_subscription_status_index` (`academy_id`,`subscription_status`),
  KEY `quran_subscriptions_academy_id_payment_status_index` (`academy_id`,`payment_status`),
  KEY `quran_subscriptions_student_id_subscription_status_index` (`student_id`,`subscription_status`),
  KEY `quran_subscriptions_quran_teacher_id_subscription_status_index` (`quran_teacher_id`,`subscription_status`),
  KEY `quran_subscriptions_subscription_status_expires_at_index` (`subscription_status`,`expires_at`),
  KEY `quran_subscriptions_is_trial_active_trial_used_index` (`is_trial_active`,`trial_used`),
  KEY `quran_subscriptions_auto_renew_next_payment_at_index` (`auto_renew`,`next_payment_at`),
  KEY `quran_subscriptions_subscription_code_index` (`subscription_code`),
  KEY `quran_subscriptions_package_id_foreign` (`package_id`),
  CONSTRAINT `quran_subscriptions_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_subscriptions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_subscriptions_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `quran_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_subscriptions_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_subscriptions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_subscriptions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_teacher_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_teacher_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `educational_qualification` enum('bachelor','master','phd','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bachelor',
  `certifications` json DEFAULT NULL,
  `teaching_experience_years` int NOT NULL DEFAULT '0',
  `available_time_start` time NOT NULL DEFAULT '08:00:00',
  `available_time_end` time NOT NULL DEFAULT '18:00:00',
  `available_days` json DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `bio_arabic` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `bio_english` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `approval_status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `total_students` int NOT NULL DEFAULT '0',
  `total_sessions` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_teacher_profiles_teacher_code_unique` (`teacher_code`),
  UNIQUE KEY `quran_teacher_profiles_email_unique` (`email`),
  KEY `quran_teacher_profiles_approved_by_foreign` (`approved_by`),
  KEY `quran_teacher_profiles_user_id_index` (`user_id`),
  KEY `quran_teacher_profiles_academy_id_approval_status_index` (`academy_id`,`approval_status`),
  CONSTRAINT `quran_teacher_profiles_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_teacher_profiles_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_teacher_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recorded_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recorded_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `instructor_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `grade_level_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `course_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trailer_video_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `level` enum('beginner','intermediate','advanced') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `duration_hours` int NOT NULL DEFAULT '0',
  `language` enum('ar','en','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ar',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_price` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `is_free` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `enrollment_deadline` timestamp NULL DEFAULT NULL,
  `completion_certificate` tinyint(1) NOT NULL DEFAULT '1',
  `prerequisites` json DEFAULT NULL,
  `learning_outcomes` json DEFAULT NULL,
  `course_materials` json DEFAULT NULL,
  `total_sections` int NOT NULL DEFAULT '0',
  `total_lessons` int NOT NULL DEFAULT '0',
  `total_duration_minutes` int NOT NULL DEFAULT '0',
  `avg_rating` decimal(3,1) NOT NULL DEFAULT '0.0',
  `total_reviews` int NOT NULL DEFAULT '0',
  `total_enrollments` int NOT NULL DEFAULT '0',
  `difficulty_level` enum('very_easy','easy','medium','hard','very_hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `meta_keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','review','published','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recorded_courses_course_code_unique` (`course_code`),
  KEY `recorded_courses_academy_id_is_published_index` (`academy_id`,`is_published`),
  KEY `recorded_courses_instructor_id_is_published_index` (`instructor_id`,`is_published`),
  KEY `recorded_courses_subject_id_grade_level_id_index` (`subject_id`,`grade_level_id`),
  KEY `recorded_courses_status_is_published_index` (`status`,`is_published`),
  KEY `recorded_courses_category_level_index` (`category`,`level`),
  KEY `recorded_courses_is_featured_is_published_index` (`is_featured`,`is_published`),
  KEY `recorded_courses_created_at_is_published_index` (`created_at`,`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `session_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `grade_level_id` bigint unsigned NOT NULL,
  `request_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sessions_per_week` int NOT NULL,
  `hourly_rate` decimal(8,2) NOT NULL,
  `total_monthly_cost` decimal(8,2) DEFAULT NULL,
  `is_trial_request` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('pending','teacher_proposed','student_negotiating','teacher_revising','agreed','paid','rejected','cancelled','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `proposed_schedule` json DEFAULT NULL,
  `current_proposal` json DEFAULT NULL,
  `initial_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `latest_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `trial_session_completed` tinyint(1) NOT NULL DEFAULT '0',
  `trial_session_date` timestamp NULL DEFAULT NULL,
  `trial_session_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_responded_at` timestamp NULL DEFAULT NULL,
  `agreed_at` timestamp NULL DEFAULT NULL,
  `payment_completed_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_subscription_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_requests_request_code_unique` (`request_code`),
  KEY `session_requests_academy_id_status_index` (`academy_id`,`status`),
  KEY `session_requests_student_id_status_index` (`student_id`,`status`),
  KEY `session_requests_teacher_id_status_index` (`teacher_id`,`status`),
  KEY `session_requests_status_expires_at_index` (`status`,`expires_at`),
  KEY `session_requests_request_code_index` (`request_code`),
  KEY `session_requests_last_activity_at_index` (`last_activity_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT '0',
  `payload` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_group_name_unique` (`group`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_level_id` bigint unsigned DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `emergency_contact` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `academic_status` enum('enrolled','graduated','dropped','transferred') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_profiles_student_code_unique` (`student_code`),
  UNIQUE KEY `student_profiles_email_unique` (`email`),
  KEY `student_profiles_grade_level_id_foreign` (`grade_level_id`),
  KEY `student_profiles_user_id_index` (`user_id`),
  CONSTRAINT `student_profiles_grade_level_id_foreign` FOREIGN KEY (`grade_level_id`) REFERENCES `grade_levels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `student_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `student_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `recorded_course_id` bigint unsigned NOT NULL,
  `course_section_id` bigint unsigned DEFAULT NULL,
  `lesson_id` bigint unsigned DEFAULT NULL,
  `progress_type` enum('course','section','lesson') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lesson',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `watch_time_seconds` int NOT NULL DEFAULT '0',
  `total_time_seconds` int NOT NULL DEFAULT '0',
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_accessed_at` timestamp NULL DEFAULT NULL,
  `current_position_seconds` int NOT NULL DEFAULT '0',
  `quiz_score` decimal(5,2) DEFAULT NULL,
  `quiz_attempts` int NOT NULL DEFAULT '0',
  `notes` json DEFAULT NULL,
  `bookmarked_at` timestamp NULL DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `review_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_progress_user_id_recorded_course_id_lesson_id_unique` (`user_id`,`recorded_course_id`,`lesson_id`),
  KEY `student_progress_user_id_recorded_course_id_index` (`user_id`,`recorded_course_id`),
  KEY `student_progress_user_id_lesson_id_index` (`user_id`,`lesson_id`),
  KEY `student_progress_recorded_course_id_is_completed_index` (`recorded_course_id`,`is_completed`),
  KEY `student_progress_progress_type_is_completed_index` (`progress_type`,`is_completed`),
  KEY `student_progress_last_accessed_at_index` (`last_accessed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subject_grade_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subject_grade_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `prerequisites` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `hours_per_week` int NOT NULL DEFAULT '2',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_subject_code_unique` (`subject_code`),
  KEY `subjects_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `subjects_is_academic_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `subscription_type` enum('quran','academic','recorded_course','general') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `subscription_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `subscription_category` enum('individual','group','course','package') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `billing_cycle` enum('daily','weekly','monthly','quarterly','yearly','lifetime') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `status` enum('trial','active','expired','cancelled','suspended','pending') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed','overdue') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `trial_days` int NOT NULL DEFAULT '0',
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_payment_at` timestamp NULL DEFAULT NULL,
  `next_payment_at` timestamp NULL DEFAULT NULL,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '1',
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `suspended_at` timestamp NULL DEFAULT NULL,
  `suspended_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_subscription_code_unique` (`subscription_code`),
  KEY `subscriptions_academy_id_status_index` (`academy_id`,`status`),
  KEY `subscriptions_student_id_status_index` (`student_id`,`status`),
  KEY `subscriptions_subscription_type_status_index` (`subscription_type`,`status`),
  KEY `subscriptions_expires_at_status_index` (`expires_at`,`status`),
  KEY `subscriptions_trial_ends_at_status_index` (`trial_ends_at`,`status`),
  KEY `subscriptions_billing_cycle_status_index` (`billing_cycle`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supervisor_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supervisor_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `supervisor_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_teachers` json DEFAULT NULL,
  `hired_date` date NOT NULL,
  `salary` decimal(10,2) DEFAULT NULL,
  `performance_rating` decimal(3,2) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `supervisor_profiles_email_unique` (`email`),
  UNIQUE KEY `supervisor_profiles_supervisor_code_unique` (`supervisor_code`),
  KEY `supervisor_profiles_user_id_foreign` (`user_id`),
  KEY `supervisor_profiles_academy_id_index` (`academy_id`),
  CONSTRAINT `supervisor_profiles_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supervisor_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teacher_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `proficiency_level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'intermediate',
  `is_certified` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_subjects_user_id_subject_id_unique` (`user_id`,`subject_id`),
  KEY `teacher_subjects_user_id_index` (`user_id`),
  KEY `teacher_subjects_subject_id_index` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teaching_session_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teaching_session_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `teaching_session_id` bigint unsigned NOT NULL,
  `status` enum('present','absent','late','excused') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `joined_at` datetime DEFAULT NULL,
  `left_at` datetime DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teaching_session_attendances_user_id_teaching_session_id_unique` (`user_id`,`teaching_session_id`),
  KEY `teaching_session_attendances_user_id_index` (`user_id`),
  KEY `teaching_session_attendances_teaching_session_id_status_index` (`teaching_session_id`,`status`),
  KEY `teaching_session_attendances_joined_at_index` (`joined_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teaching_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teaching_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned DEFAULT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` enum('individual','group','assessment') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'group',
  `status` enum('scheduled','in_progress','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `scheduled_at` datetime NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `ended_at` datetime DEFAULT NULL,
  `duration_minutes` int NOT NULL DEFAULT '60',
  `google_event_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_meet_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `attendance_taken` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teaching_sessions_academy_id_status_index` (`academy_id`,`status`),
  KEY `teaching_sessions_teacher_id_scheduled_at_index` (`teacher_id`,`scheduled_at`),
  KEY `teaching_sessions_course_id_scheduled_at_index` (`course_id`,`scheduled_at`),
  KEY `teaching_sessions_scheduled_at_index` (`scheduled_at`),
  KEY `teaching_sessions_google_event_id_index` (`google_event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `session_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `device_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_at` timestamp NOT NULL,
  `logout_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_sessions_session_id_unique` (`session_id`),
  KEY `user_sessions_user_id_is_active_index` (`user_id`,`is_active`),
  KEY `user_sessions_session_id_index` (`session_id`),
  KEY `user_sessions_login_at_index` (`login_at`),
  KEY `user_sessions_last_activity_at_index` (`last_activity_at`),
  CONSTRAINT `user_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS (concat(`first_name`,_utf8mb4' ',`last_name`)) VIRTUAL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` enum('student','quran_teacher','academic_teacher','parent','supervisor','admin','super_admin') COLLATE utf8mb4_unicode_ci DEFAULT 'student',
  `status` enum('pending','active','inactive','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `bio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_type` enum('quran','academic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualification_degree` enum('bachelor','master','phd','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qualification_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `university` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `years_experience` int DEFAULT NULL,
  `has_ijazah` tinyint(1) NOT NULL DEFAULT '0',
  `student_session_price` decimal(8,2) DEFAULT NULL,
  `teacher_session_price` decimal(8,2) DEFAULT NULL,
  `parent_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `active_status` tinyint(1) NOT NULL DEFAULT '0',
  `dark_mode` tinyint(1) NOT NULL DEFAULT '0',
  `messenger_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_academy_id_role_index` (`academy_id`),
  KEY `users_academy_id_status_index` (`academy_id`,`status`),
  KEY `users_teacher_type_index` (`teacher_type`),
  KEY `users_email_index` (`email`),
  KEY `users_parent_id_foreign` (`parent_id`),
  KEY `users_phone_index` (`phone`),
  KEY `users_academy_id_user_type_index` (`academy_id`,`user_type`),
  KEY `users_email_academy_id_index` (`email`,`academy_id`),
  KEY `users_user_type_academy_id_index` (`user_type`,`academy_id`),
  KEY `users_status_active_status_index` (`status`,`active_status`),
  KEY `users_parent_id_index` (`parent_id`),
  CONSTRAINT `users_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_academies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000001_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_07_28_193554_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_07_28_194003_add_foreign_keys_to_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_07_28_225513_create_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_07_28_225520_create_grade_levels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_07_28_225527_create_courses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_07_28_225535_create_teaching_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_07_28_225544_create_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_07_28_225551_create_quizzes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_07_28_225558_create_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_07_28_225830_create_teacher_subjects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_07_28_225837_create_subject_grade_levels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_07_28_225844_create_course_enrollments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_07_28_225852_create_teaching_session_attendances_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_07_28_999999_add_active_status_to_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_07_28_999999_add_avatar_to_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_07_28_999999_add_dark_mode_to_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_07_28_999999_add_messenger_color_to_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_07_28_999999_create_chatify_favorites_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_07_28_999999_create_chatify_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_07_29_000358_create_quran_teachers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_07_29_000405_create_academic_teachers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_07_29_000406_create_academic_grade_levels_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_07_29_000406_create_academic_subjects_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_07_29_003421_create_recorded_courses_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_07_29_003453_create_course_sections_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_07_29_003503_create_course_quizzes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_07_29_003503_create_lessons_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_07_29_003503_create_student_progress_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_07_29_003504_create_course_subscriptions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_07_29_003504_create_payments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_07_29_003504_create_quran_subscriptions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_07_29_003504_update_subscriptions_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_07_29_083855_create_quran_teachers_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_07_29_083906_create_quran_subscriptions_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_07_29_083907_create_quran_circles_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_07_29_083908_create_quran_sessions_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_07_29_083909_create_quran_progress_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_07_29_083910_create_quran_homework_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_07_29_084221_create_quran_circle_students_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_07_29_101509_create_quran_packages_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_07_29_101532_update_quran_tables_structure',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_07_29_104726_fix_quran_teachers_unique_constraint',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_07_29_105836_update_subjects_table_for_academic_section',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_07_29_125154_remove_difficulty_level_from_subjects_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_07_29_125411_create_academic_settings_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_07_29_133023_create_session_requests_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_07_29_133030_create_academic_subscriptions_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_07_29_133038_create_academic_progress_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_07_29_134700_create_academic_teacher_subjects_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_07_29_134706_create_academic_teacher_grade_levels_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_07_29_134712_create_academic_teacher_students_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_07_29_144923_update_academic_teachers_remove_fields_and_fix_available_times',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_07_29_152917_remove_age_fields_from_grade_levels_and_create_courses_field_from_academic_teachers',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_07_29_152940_update_users_table_add_user_type_system',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_07_29_153006_create_user_profiles_tables',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_07_29_154255_create_interactive_courses_system_v2',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_07_29_164954_add_available_languages_to_academic_settings_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_07_29_173023_add_avatar_to_profile_tables',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_07_29_174708_create_supervisor_profiles_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_07_29_174730_create_parent_profiles_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_07_29_174757_create_parent_student_relationships_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_07_29_215216_create_course_reviews_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_07_30_175355_add_missing_fields_to_academies_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_07_30_180619_remove_status_column_from_academies_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2022_12_14_083707_create_settings_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_07_31_011635_enhance_users_table_for_authentication_system',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_07_31_011652_create_user_sessions_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_07_31_120000_remove_role_field_from_users',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_08_01_185325_add_super_admin_user_type',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_08_03_065729_fix_multi_tenant_database_structure',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_08_03_104936_remove_age_fields_from_academic_grade_levels_table',23);
