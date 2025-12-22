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
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_grade_levels_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `academic_grade_levels_education_system_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `prevent_grade_level_deletion` BEFORE DELETE ON `academic_grade_levels` FOR EACH ROW BEGIN 
                IF (SELECT COUNT(*) FROM student_profiles WHERE grade_level_id = OLD.id) > 0 THEN 
                    SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Cannot delete grade level: students are still assigned to it. Please reassign students first."; 
                END IF; 
            END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
DROP TABLE IF EXISTS `academic_homework`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_homework` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `academic_session_id` bigint unsigned NOT NULL,
  `academic_subscription_id` bigint unsigned DEFAULT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `learning_objectives` json DEFAULT NULL,
  `requirements` json DEFAULT NULL,
  `teacher_files` json DEFAULT NULL,
  `reference_links` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `submission_type` enum('text','file','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `allow_late_submissions` tinyint(1) NOT NULL DEFAULT '1',
  `max_files` int NOT NULL DEFAULT '5',
  `max_file_size_mb` int NOT NULL DEFAULT '10',
  `allowed_file_types` json DEFAULT NULL,
  `assigned_at` datetime NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `estimated_duration_minutes` int DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL DEFAULT '100.00',
  `grading_scale` enum('points','percentage','letter','pass_fail') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'points',
  `grading_criteria` json DEFAULT NULL,
  `auto_grade` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('draft','published','closed','archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'published',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '1',
  `priority` enum('low','medium','high','urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `difficulty_level` enum('beginner','intermediate','advanced','expert') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_students` int NOT NULL DEFAULT '0',
  `submitted_count` int NOT NULL DEFAULT '0',
  `graded_count` int NOT NULL DEFAULT '0',
  `late_count` int NOT NULL DEFAULT '0',
  `average_score` decimal(5,2) DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_homework_academic_subscription_id_foreign` (`academic_subscription_id`),
  KEY `academic_homework_created_by_foreign` (`created_by`),
  KEY `academic_homework_updated_by_foreign` (`updated_by`),
  KEY `academic_homework_academy_id_status_index` (`academy_id`,`status`),
  KEY `academic_homework_academic_session_id_index` (`academic_session_id`),
  KEY `academic_homework_teacher_id_status_index` (`teacher_id`,`status`),
  KEY `academic_homework_due_date_index` (`due_date`),
  KEY `academic_homework_status_is_active_index` (`status`,`is_active`),
  CONSTRAINT `academic_homework_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_homework_academic_subscription_id_foreign` FOREIGN KEY (`academic_subscription_id`) REFERENCES `academic_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_homework_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_homework_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_homework_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_homework_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_homework_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_homework_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `academic_homework_id` bigint unsigned NOT NULL,
  `academic_session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `submission_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `submission_files` json DEFAULT NULL,
  `submission_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `revision_history` json DEFAULT NULL,
  `submission_status` enum('not_submitted','draft','submitted','late','pending_review','under_review','graded','returned','revision_requested','resubmitted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_submitted',
  `submitted_at` datetime DEFAULT NULL,
  `is_late` tinyint(1) NOT NULL DEFAULT '0',
  `days_late` int NOT NULL DEFAULT '0',
  `submission_attempt` int NOT NULL DEFAULT '1',
  `revision_count` int NOT NULL DEFAULT '0',
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT NULL,
  `score_percentage` decimal(5,2) DEFAULT NULL,
  `grade_letter` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `grading_breakdown` json DEFAULT NULL,
  `late_penalty_applied` tinyint(1) NOT NULL DEFAULT '0',
  `late_penalty_amount` decimal(5,2) NOT NULL DEFAULT '0.00',
  `bonus_points` decimal(5,2) NOT NULL DEFAULT '0.00',
  `graded_by` bigint unsigned DEFAULT NULL,
  `graded_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `content_quality_score` decimal(5,2) DEFAULT NULL,
  `presentation_score` decimal(5,2) DEFAULT NULL,
  `effort_score` decimal(5,2) DEFAULT NULL,
  `creativity_score` decimal(5,2) DEFAULT NULL,
  `time_spent_minutes` int NOT NULL DEFAULT '0',
  `started_at` datetime DEFAULT NULL,
  `last_edited_at` datetime DEFAULT NULL,
  `student_reflection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `student_difficulty_rating` enum('very_easy','easy','moderate','difficult','very_difficult') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_time_estimate_minutes` int DEFAULT NULL,
  `student_questions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `requires_follow_up` tinyint(1) NOT NULL DEFAULT '0',
  `teacher_reviewed` tinyint(1) NOT NULL DEFAULT '0',
  `parent_notified` tinyint(1) NOT NULL DEFAULT '0',
  `flagged_for_review` tinyint(1) NOT NULL DEFAULT '0',
  `flag_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_viewed` tinyint(1) NOT NULL DEFAULT '0',
  `parent_viewed_at` datetime DEFAULT NULL,
  `parent_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_signature` tinyint(1) NOT NULL DEFAULT '0',
  `plagiarism_checked` tinyint(1) NOT NULL DEFAULT '0',
  `originality_score` decimal(5,2) DEFAULT NULL,
  `plagiarism_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_homework_student_attempt` (`academic_homework_id`,`student_id`,`submission_attempt`),
  KEY `academic_homework_submissions_student_id_foreign` (`student_id`),
  KEY `academic_homework_submissions_graded_by_foreign` (`graded_by`),
  KEY `academic_homework_submissions_created_by_foreign` (`created_by`),
  KEY `academic_homework_submissions_updated_by_foreign` (`updated_by`),
  KEY `idx_academy_student` (`academy_id`,`student_id`),
  KEY `idx_homework_student` (`academic_homework_id`,`student_id`),
  KEY `idx_session_student` (`academic_session_id`,`student_id`),
  KEY `idx_submission_status` (`submission_status`),
  KEY `idx_is_late` (`is_late`),
  KEY `idx_submitted_at` (`submitted_at`),
  KEY `idx_graded_at` (`graded_at`),
  CONSTRAINT `academic_homework_submissions_academic_homework_id_foreign` FOREIGN KEY (`academic_homework_id`) REFERENCES `academic_homework` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_homework_submissions_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_homework_submissions_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_homework_submissions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_homework_submissions_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_homework_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_homework_submissions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_individual_lessons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_individual_lessons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `academic_teacher_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `academic_subscription_id` bigint unsigned DEFAULT NULL,
  `lesson_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `academic_subject_id` bigint unsigned NOT NULL,
  `academic_grade_level_id` bigint unsigned NOT NULL,
  `total_sessions` int NOT NULL DEFAULT '0',
  `sessions_scheduled` int NOT NULL DEFAULT '0',
  `sessions_completed` int NOT NULL DEFAULT '0',
  `sessions_remaining` int NOT NULL DEFAULT '0',
  `lesson_topics_covered` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `current_topics` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `default_duration_minutes` int NOT NULL DEFAULT '60',
  `preferred_times` json DEFAULT NULL,
  `status` enum('pending','active','paused','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_session_at` timestamp NULL DEFAULT NULL,
  `meeting_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recording_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `materials_used` json DEFAULT NULL,
  `learning_objectives` json DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `preparation_minutes` int NOT NULL DEFAULT '5',
  `ending_buffer_minutes` int NOT NULL DEFAULT '5',
  `late_join_grace_period_minutes` int NOT NULL DEFAULT '10',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_individual_lessons_lesson_code_unique` (`lesson_code`),
  KEY `academic_individual_lessons_academic_subscription_id_foreign` (`academic_subscription_id`),
  KEY `academic_individual_lessons_academic_subject_id_foreign` (`academic_subject_id`),
  KEY `academic_individual_lessons_academic_grade_level_id_foreign` (`academic_grade_level_id`),
  KEY `academic_individual_lessons_created_by_foreign` (`created_by`),
  KEY `academic_individual_lessons_updated_by_foreign` (`updated_by`),
  KEY `academic_individual_lessons_academy_id_status_index` (`academy_id`,`status`),
  KEY `academic_individual_lessons_academic_teacher_id_status_index` (`academic_teacher_id`,`status`),
  KEY `academic_individual_lessons_student_id_status_index` (`student_id`,`status`),
  KEY `academic_individual_lessons_status_started_at_index` (`status`,`started_at`),
  CONSTRAINT `academic_individual_lessons_academic_grade_level_id_foreign` FOREIGN KEY (`academic_grade_level_id`) REFERENCES `academic_grade_levels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_individual_lessons_academic_subject_id_foreign` FOREIGN KEY (`academic_subject_id`) REFERENCES `academic_subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_individual_lessons_academic_subscription_id_foreign` FOREIGN KEY (`academic_subscription_id`) REFERENCES `academic_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_individual_lessons_academic_teacher_id_foreign` FOREIGN KEY (`academic_teacher_id`) REFERENCES `academic_teacher_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_individual_lessons_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_individual_lessons_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_individual_lessons_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_individual_lessons_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_packages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `name_ar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description_ar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `package_type` enum('individual','group') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `sessions_per_month` int NOT NULL DEFAULT '8',
  `session_duration_minutes` int NOT NULL DEFAULT '60',
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
  KEY `academic_packages_created_by_foreign` (`created_by`),
  KEY `academic_packages_updated_by_foreign` (`updated_by`),
  KEY `academic_packages_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `academic_packages_package_type_is_active_index` (`package_type`,`is_active`),
  KEY `academic_packages_sort_order_index` (`sort_order`),
  CONSTRAINT `academic_packages_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_packages_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_packages_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_session_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_session_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `attendance_status` enum('present','absent','late','partial','left_early') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'absent',
  `join_time` timestamp NULL DEFAULT NULL,
  `leave_time` timestamp NULL DEFAULT NULL,
  `auto_join_time` timestamp NULL DEFAULT NULL,
  `auto_leave_time` timestamp NULL DEFAULT NULL,
  `auto_duration_minutes` int DEFAULT NULL,
  `auto_tracked` tinyint(1) NOT NULL DEFAULT '0',
  `manually_overridden` tinyint(1) NOT NULL DEFAULT '0',
  `overridden_by` bigint unsigned DEFAULT NULL,
  `overridden_at` timestamp NULL DEFAULT NULL,
  `override_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meeting_events` json DEFAULT NULL COMMENT 'JSON log of join/leave events from LiveKit',
  `participation_score` decimal(3,1) DEFAULT NULL COMMENT 'Participation score 0-10',
  `lesson_understanding` decimal(3,1) DEFAULT NULL COMMENT 'Understanding level 0-10',
  `homework_completion` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Did student complete homework',
  `homework_quality` decimal(3,1) DEFAULT NULL COMMENT 'Homework quality 0-10',
  `questions_asked` int DEFAULT NULL COMMENT 'Number of questions asked',
  `concepts_mastered` int DEFAULT NULL COMMENT 'Number of concepts mastered',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Teacher notes and feedback',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_session_attendances_session_id_student_id_unique` (`session_id`,`student_id`),
  KEY `academic_session_attendances_student_id_foreign` (`student_id`),
  KEY `idx_acad_session_student` (`session_id`,`student_id`),
  KEY `idx_acad_attendance_status` (`attendance_status`),
  KEY `idx_acad_join_time` (`join_time`),
  KEY `idx_acad_tracking` (`auto_tracked`,`manually_overridden`),
  KEY `idx_acad_session_tracking` (`session_id`,`auto_tracked`),
  KEY `idx_acad_overridden_by` (`overridden_by`),
  CONSTRAINT `academic_session_attendances_overridden_by_foreign` FOREIGN KEY (`overridden_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_session_attendances_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_session_attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_session_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_session_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `academic_grade` decimal(3,1) DEFAULT NULL COMMENT 'الدرجة الأكاديمية العامة من 0 إلى 10',
  `lesson_understanding_degree` decimal(3,1) DEFAULT NULL COMMENT 'درجة فهم الدرس من 0 إلى 10',
  `homework_completion_degree` decimal(3,1) DEFAULT NULL COMMENT 'درجة أداء الواجب من 0 إلى 10',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'ملاحظات المعلم على أداء الطالب',
  `homework_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'وصف الواجب المطلوب',
  `homework_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ملف الواجب المرفوع',
  `homework_submitted_at` timestamp NULL DEFAULT NULL COMMENT 'وقت تسليم الواجب',
  `homework_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'ملاحظات المعلم على الواجب',
  `meeting_enter_time` timestamp NULL DEFAULT NULL COMMENT 'وقت دخول الطالب للاجتماع',
  `meeting_leave_time` timestamp NULL DEFAULT NULL COMMENT 'وقت خروج الطالب من الاجتماع',
  `actual_attendance_minutes` int NOT NULL DEFAULT '0' COMMENT 'عدد الدقائق الفعلية للحضور',
  `is_late` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'هل تأخر الطالب عن الموعد المحدد',
  `late_minutes` int NOT NULL DEFAULT '0' COMMENT 'عدد دقائق التأخير',
  `attendance_percentage` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'نسبة الحضور من إجمالي وقت الجلسة',
  `meeting_events` json DEFAULT NULL COMMENT 'أحداث الاجتماع (دخول، خروج، انقطاع)',
  `evaluated_at` timestamp NULL DEFAULT NULL COMMENT 'وقت تقييم المعلم',
  `is_calculated` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'هل تم حساب الحضور تلقائياً',
  `manually_evaluated` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'هل تم تعديل البيانات يدوياً',
  `override_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'سبب التعديل اليدوي',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `attendance_status` enum('attended','late','leaved','absent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'absent',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_academic_session_report` (`session_id`,`student_id`),
  KEY `academic_session_reports_student_id_foreign` (`student_id`),
  KEY `academic_session_reports_academy_id_foreign` (`academy_id`),
  KEY `academic_session_reports_session_id_student_id_index` (`session_id`,`student_id`),
  KEY `academic_session_reports_teacher_id_academy_id_index` (`teacher_id`,`academy_id`),
  KEY `academic_session_reports_evaluated_at_index` (`evaluated_at`),
  KEY `academic_session_reports_homework_submitted_at_index` (`homework_submitted_at`),
  CONSTRAINT `academic_session_reports_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_session_reports_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_session_reports_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_session_reports_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academic_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `academic_teacher_id` bigint unsigned NOT NULL,
  `academic_subscription_id` bigint unsigned DEFAULT NULL,
  `academic_individual_lesson_id` bigint unsigned DEFAULT NULL,
  `student_id` bigint unsigned DEFAULT NULL,
  `session_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_type` enum('individual','interactive_course') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `status` enum('unscheduled','scheduled','ready','ongoing','completed','cancelled','absent','missed','rescheduled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unscheduled',
  `teacher_scheduled_at` timestamp NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration_minutes` int NOT NULL DEFAULT '60',
  `actual_duration_minutes` int DEFAULT NULL,
  `meeting_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_platform` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_data` json DEFAULT NULL,
  `meeting_room_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_auto_generated` tinyint(1) NOT NULL DEFAULT '1',
  `meeting_expires_at` timestamp NULL DEFAULT NULL,
  `attendance_status` enum('scheduled','present','absent','late','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `participants_count` int NOT NULL DEFAULT '0',
  `lesson_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `homework_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `homework_file` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `homework_assigned` tinyint(1) NOT NULL DEFAULT '0',
  `recording_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'URL to session recording if available',
  `recording_enabled` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether recording is enabled for this session',
  `subscription_counted` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Track if this session was counted towards subscription',
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `reschedule_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rescheduled_from` timestamp NULL DEFAULT NULL,
  `rescheduled_to` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `scheduled_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_sessions_academy_id_session_code_unique` (`academy_id`,`session_code`),
  UNIQUE KEY `academic_sessions_session_code_unique` (`session_code`),
  KEY `academic_sessions_academic_subscription_id_foreign` (`academic_subscription_id`),
  KEY `academic_sessions_academic_individual_lesson_id_foreign` (`academic_individual_lesson_id`),
  KEY `academic_sessions_cancelled_by_foreign` (`cancelled_by`),
  KEY `academic_sessions_created_by_foreign` (`created_by`),
  KEY `academic_sessions_updated_by_foreign` (`updated_by`),
  KEY `academic_sessions_scheduled_by_foreign` (`scheduled_by`),
  KEY `academic_sessions_academy_id_scheduled_at_index` (`academy_id`,`scheduled_at`),
  KEY `academic_sessions_academy_id_status_index` (`academy_id`,`status`),
  KEY `academic_sessions_academic_teacher_id_scheduled_at_index` (`academic_teacher_id`,`scheduled_at`),
  KEY `academic_sessions_academic_teacher_id_status_index` (`academic_teacher_id`,`status`),
  KEY `academic_sessions_student_id_scheduled_at_index` (`student_id`,`scheduled_at`),
  KEY `academic_sessions_scheduled_at_status_index` (`scheduled_at`,`status`),
  KEY `academic_sessions_session_type_status_index` (`session_type`,`status`),
  KEY `academic_sessions_session_code_index` (`session_code`),
  KEY `academic_sessions_attendance_status_scheduled_at_index` (`attendance_status`,`scheduled_at`),
  KEY `academic_sessions_academy_status_scheduled_idx` (`academy_id`,`status`,`scheduled_at`),
  KEY `academic_sessions_teacher_scheduled_idx` (`academic_teacher_id`,`scheduled_at`),
  KEY `academic_sessions_student_scheduled_idx` (`student_id`,`scheduled_at`),
  KEY `academic_sessions_code_academy_idx` (`session_code`,`academy_id`),
  KEY `academic_sessions_sub_status_idx` (`academic_subscription_id`,`status`),
  CONSTRAINT `academic_sessions_academic_individual_lesson_id_foreign` FOREIGN KEY (`academic_individual_lesson_id`) REFERENCES `academic_individual_lessons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_sessions_academic_subscription_id_foreign` FOREIGN KEY (`academic_subscription_id`) REFERENCES `academic_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_sessions_academic_teacher_id_foreign` FOREIGN KEY (`academic_teacher_id`) REFERENCES `academic_teacher_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_sessions_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_sessions_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_sessions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_sessions_scheduled_by_foreign` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_sessions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_sessions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
  `default_package_ids` json DEFAULT NULL COMMENT 'JSON array of default academic package IDs for new teachers',
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
DROP TABLE IF EXISTS `academic_subject_grade_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_subject_grade_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject_id` bigint unsigned NOT NULL,
  `grade_level_id` bigint unsigned NOT NULL,
  `hours_per_week` int NOT NULL DEFAULT '3',
  `semester` enum('first','second','both','summer') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `is_mandatory` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_subject_grade_levels_subject_id_grade_level_id_unique` (`subject_id`,`grade_level_id`),
  KEY `academic_subject_grade_levels_grade_level_id_foreign` (`grade_level_id`),
  CONSTRAINT `academic_subject_grade_levels_grade_level_id_foreign` FOREIGN KEY (`grade_level_id`) REFERENCES `academic_grade_levels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_subject_grade_levels_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `academic_subjects` (`id`) ON DELETE CASCADE
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
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_subjects_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `academic_subjects_category_is_active_index` (`is_active`),
  KEY `academic_subjects_field_is_active_index` (`is_active`),
  KEY `academic_subjects_difficulty_level_is_active_index` (`is_active`)
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
  `subject_id` bigint unsigned DEFAULT NULL,
  `subject_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'اسم المادة الدراسية',
  `grade_level_id` bigint unsigned DEFAULT NULL,
  `grade_level_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'اسم المرحلة الدراسية',
  `session_request_id` bigint unsigned DEFAULT NULL,
  `academic_package_id` bigint unsigned DEFAULT NULL,
  `package_name_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_name_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subscription_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscription_type` enum('private','group') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `sessions_per_week` int NOT NULL DEFAULT '2',
  `session_duration_minutes` int NOT NULL DEFAULT '60',
  `hourly_rate` decimal(8,2) NOT NULL DEFAULT '0.00',
  `monthly_price` decimal(10,2) DEFAULT NULL,
  `quarterly_price` decimal(10,2) DEFAULT NULL,
  `yearly_price` decimal(10,2) DEFAULT NULL,
  `sessions_per_month` decimal(5,2) NOT NULL DEFAULT '8.00',
  `monthly_amount` decimal(8,2) NOT NULL,
  `discount_amount` decimal(8,2) NOT NULL DEFAULT '0.00',
  `final_monthly_amount` decimal(8,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `billing_cycle` enum('monthly','quarterly','yearly') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `next_billing_date` date DEFAULT NULL,
  `last_payment_date` date DEFAULT NULL,
  `last_payment_amount` decimal(8,2) DEFAULT NULL,
  `weekly_schedule` json DEFAULT NULL,
  `timezone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Riyadh',
  `auto_create_google_meet` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('active','paused','suspended','cancelled','expired','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `certificate_issued` tinyint(1) NOT NULL DEFAULT '0',
  `certificate_issued_at` timestamp NULL DEFAULT NULL,
  `preparation_minutes` int DEFAULT NULL COMMENT 'Minutes before session start when students can join',
  `buffer_minutes` int DEFAULT NULL COMMENT 'Buffer time after session ends',
  `late_tolerance_minutes` int DEFAULT NULL COMMENT 'How many minutes late before marked as late',
  `attendance_threshold_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Minimum attendance percentage required (e.g., 80.00)',
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
  `auto_renew` tinyint(1) NOT NULL DEFAULT '1',
  `renewal_reminder_days` int NOT NULL DEFAULT '7',
  `last_reminder_sent` timestamp NULL DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rating` tinyint unsigned DEFAULT NULL,
  `review_text` text COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `student_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_sessions_scheduled` int NOT NULL DEFAULT '0',
  `total_sessions_completed` int NOT NULL DEFAULT '0',
  `total_sessions_missed` int NOT NULL DEFAULT '0',
  `completion_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `last_session_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `renewal_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `final_price` decimal(10,2) DEFAULT NULL,
  `package_price_monthly` decimal(10,2) DEFAULT NULL,
  `package_price_quarterly` decimal(10,2) DEFAULT NULL,
  `package_price_yearly` decimal(10,2) DEFAULT NULL,
  `package_sessions_per_week` int DEFAULT NULL,
  `package_session_duration_minutes` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_subscriptions_subscription_code_unique` (`subscription_code`),
  KEY `academic_subscriptions_academy_id_status_index` (`academy_id`,`status`),
  KEY `academic_subscriptions_student_id_status_index` (`student_id`,`status`),
  KEY `academic_subscriptions_teacher_id_status_index` (`teacher_id`,`status`),
  KEY `academic_subscriptions_status_payment_status_index` (`status`,`payment_status`),
  KEY `academic_subscriptions_next_billing_date_status_index` (`next_billing_date`,`status`),
  KEY `academic_subscriptions_paused_at_resume_date_index` (`paused_at`,`resume_date`),
  KEY `academic_subscriptions_subscription_code_index` (`subscription_code`),
  KEY `academic_subscriptions_start_date_end_date_index` (`start_date`,`end_date`),
  KEY `academic_subscriptions_academic_package_id_foreign` (`academic_package_id`),
  KEY `academic_subscriptions_academy_status_idx` (`academy_id`,`status`),
  KEY `academic_subscriptions_student_status_idx` (`student_id`,`status`),
  KEY `academic_subscriptions_teacher_status_idx` (`teacher_id`,`status`),
  KEY `academic_subscriptions_certificate_issued_index` (`certificate_issued`),
  KEY `academic_sub_renewal_idx` (`status`,`auto_renew`,`next_billing_date`),
  KEY `academic_subscriptions_created_by_foreign` (`created_by`),
  KEY `academic_subscriptions_updated_by_foreign` (`updated_by`),
  CONSTRAINT `academic_subscriptions_academic_package_id_foreign` FOREIGN KEY (`academic_package_id`) REFERENCES `academic_packages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_subscriptions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `academic_subscriptions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
  `gender` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_country_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '+966' COMMENT 'Country calling code',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `education_level` enum('diploma','bachelor','master','phd','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bachelor',
  `university` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teaching_experience_years` int NOT NULL DEFAULT '0',
  `certifications` json DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `subject_ids` json DEFAULT NULL,
  `subjects_text` json DEFAULT NULL COMMENT 'مواد التدريس كنص حر',
  `grade_level_ids` json DEFAULT NULL,
  `grade_levels_text` json DEFAULT NULL COMMENT 'المراحل الدراسية كنص حر',
  `package_ids` json DEFAULT NULL COMMENT 'JSON array of academic package IDs that this teacher can offer',
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
  `total_reviews` int unsigned NOT NULL DEFAULT '0',
  `total_students` int NOT NULL DEFAULT '0',
  `total_sessions` int NOT NULL DEFAULT '0',
  `total_courses_created` int NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
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
  `favicon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#0ea5e9',
  `gradient_palette` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ocean_breeze',
  `country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SA',
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Riyadh',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `academic_settings` json DEFAULT NULL COMMENT 'JSON settings for academic configurations',
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
  `sections_order` json DEFAULT NULL,
  `hero_visible` tinyint(1) NOT NULL DEFAULT '1',
  `hero_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'template_1',
  `hero_heading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_subheading` text COLLATE utf8mb4_unicode_ci,
  `hero_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hero_show_in_nav` tinyint(1) NOT NULL DEFAULT '0',
  `stats_visible` tinyint(1) NOT NULL DEFAULT '1',
  `stats_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'template_1',
  `stats_heading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'إنجازاتنا بالأرقام',
  `stats_subheading` text COLLATE utf8mb4_unicode_ci,
  `stats_show_in_nav` tinyint(1) NOT NULL DEFAULT '1',
  `reviews_visible` tinyint(1) NOT NULL DEFAULT '1',
  `reviews_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'template_1',
  `reviews_heading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'آراء طلابنا',
  `reviews_subheading` text COLLATE utf8mb4_unicode_ci,
  `reviews_show_in_nav` tinyint(1) NOT NULL DEFAULT '1',
  `quran_visible` tinyint(1) NOT NULL DEFAULT '1',
  `quran_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'template_1',
  `quran_heading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'برامج القرآن الكريم',
  `quran_subheading` text COLLATE utf8mb4_unicode_ci,
  `quran_show_in_nav` tinyint(1) NOT NULL DEFAULT '1',
  `academic_visible` tinyint(1) NOT NULL DEFAULT '1',
  `academic_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'template_1',
  `academic_heading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'البرامج الأكاديمية',
  `academic_subheading` text COLLATE utf8mb4_unicode_ci,
  `academic_show_in_nav` tinyint(1) NOT NULL DEFAULT '1',
  `courses_visible` tinyint(1) NOT NULL DEFAULT '1',
  `courses_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'template_1',
  `courses_heading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'الدورات المسجلة',
  `courses_subheading` text COLLATE utf8mb4_unicode_ci,
  `courses_show_in_nav` tinyint(1) NOT NULL DEFAULT '1',
  `features_visible` tinyint(1) NOT NULL DEFAULT '1',
  `features_template` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'template_1',
  `features_heading` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'مميزات المنصة',
  `features_subheading` text COLLATE utf8mb4_unicode_ci,
  `features_show_in_nav` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `academies_subdomain_unique` (`subdomain`),
  KEY `academies_subdomain_index` (`subdomain`),
  KEY `academies_admin_id_foreign` (`admin_id`),
  KEY `academies_is_active_index` (`is_active`),
  CONSTRAINT `academies_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `academy_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academy_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Riyadh',
  `default_session_duration` int unsigned NOT NULL DEFAULT '60',
  `default_preparation_minutes` int unsigned NOT NULL DEFAULT '15',
  `default_buffer_minutes` int unsigned NOT NULL DEFAULT '5',
  `default_late_tolerance_minutes` int unsigned NOT NULL DEFAULT '10',
  `requires_session_approval` tinyint(1) NOT NULL DEFAULT '0',
  `allows_teacher_creation` tinyint(1) NOT NULL DEFAULT '1',
  `allows_student_enrollment` tinyint(1) NOT NULL DEFAULT '1',
  `default_attendance_threshold_percentage` decimal(5,2) NOT NULL DEFAULT '80.00',
  `trial_session_duration` int unsigned NOT NULL DEFAULT '30',
  `trial_expiration_days` int unsigned NOT NULL DEFAULT '7',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academy_settings_academy_id_unique` (`academy_id`),
  CONSTRAINT `academy_settings_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `business_service_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_service_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#3B82F6',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `business_service_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `business_service_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_category_id` bigint unsigned NOT NULL,
  `project_budget` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_deadline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','reviewed','approved','rejected','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `business_service_requests_service_category_id_foreign` (`service_category_id`),
  CONSTRAINT `business_service_requests_service_category_id_foreign` FOREIGN KEY (`service_category_id`) REFERENCES `business_service_categories` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `certificates` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned DEFAULT NULL,
  `certificateable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `certificateable_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `certificate_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `certificate_type` enum('recorded_course','interactive_course','quran_subscription','academic_subscription') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_style` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `certificate_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `issued_at` timestamp NOT NULL,
  `issued_by` bigint unsigned DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_manual` tinyint(1) NOT NULL DEFAULT '0',
  `custom_achievement_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificates_certificate_number_unique` (`certificate_number`),
  KEY `certificates_student_id_foreign` (`student_id`),
  KEY `certificates_teacher_id_foreign` (`teacher_id`),
  KEY `certificates_certificateable_type_certificateable_id_index` (`certificateable_type`,`certificateable_id`),
  KEY `certificates_issued_by_foreign` (`issued_by`),
  KEY `certificates_certificate_number_index` (`certificate_number`),
  KEY `certificates_academy_id_student_id_index` (`academy_id`,`student_id`),
  KEY `certificates_certificate_type_index` (`certificate_type`),
  KEY `certificates_issued_at_index` (`issued_at`),
  CONSTRAINT `certificates_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificates_issued_by_foreign` FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `certificates_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `certificates_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
  `to_id` bigint unsigned DEFAULT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `body` varchar(5000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seen` tinyint(1) NOT NULL DEFAULT '0',
  `is_edited` tinyint(1) NOT NULL DEFAULT '0',
  `edited_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reply_to` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `pinned_at` timestamp NULL DEFAULT NULL,
  `pinned_by` bigint unsigned DEFAULT NULL,
  `voice_duration` int DEFAULT NULL COMMENT 'Duration in seconds for voice messages',
  `forwarded_from` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ch_messages_group_id_index` (`group_id`),
  KEY `idx_messages_delivered` (`from_id`,`to_id`,`delivered_at`),
  KEY `ch_messages_reply_to_index` (`reply_to`),
  KEY `ch_messages_group_id_is_pinned_pinned_at_index` (`group_id`,`is_pinned`,`pinned_at`),
  KEY `ch_messages_forwarded_from_foreign` (`forwarded_from`),
  KEY `idx_messages_conversation` (`from_id`,`to_id`,`created_at`),
  KEY `idx_messages_unread` (`to_id`,`seen`,`created_at`),
  KEY `idx_messages_group` (`group_id`,`created_at`),
  CONSTRAINT `ch_messages_forwarded_from_foreign` FOREIGN KEY (`forwarded_from`) REFERENCES `ch_messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ch_messages_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ch_messages_reply_to_foreign` FOREIGN KEY (`reply_to`) REFERENCES `ch_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_blocked_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_blocked_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `blocked_user_id` bigint unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_blocked_users_user_id_blocked_user_id_unique` (`user_id`,`blocked_user_id`),
  KEY `chat_blocked_users_user_id_index` (`user_id`),
  KEY `chat_blocked_users_blocked_user_id_index` (`blocked_user_id`),
  CONSTRAINT `chat_blocked_users_blocked_user_id_foreign` FOREIGN KEY (`blocked_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_blocked_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_group_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_group_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `group_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` enum('admin','moderator','member','observer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'member',
  `can_send_messages` tinyint(1) NOT NULL DEFAULT '1',
  `is_muted` tinyint(1) NOT NULL DEFAULT '0',
  `joined_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `unread_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_group_members_group_id_user_id_unique` (`group_id`,`user_id`),
  KEY `chat_group_members_user_id_group_id_index` (`user_id`,`group_id`),
  KEY `chat_group_members_role_index` (`role`),
  CONSTRAINT `chat_group_members_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `chat_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_group_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` enum('quran_circle','individual_session','academic_session','interactive_course','recorded_course','academy_announcement') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settings` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `max_members` int DEFAULT NULL,
  `quran_circle_id` bigint unsigned DEFAULT NULL,
  `quran_session_id` bigint unsigned DEFAULT NULL,
  `academic_session_id` bigint unsigned DEFAULT NULL,
  `interactive_course_id` bigint unsigned DEFAULT NULL,
  `recorded_course_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_groups_quran_circle_id_foreign` (`quran_circle_id`),
  KEY `chat_groups_quran_session_id_foreign` (`quran_session_id`),
  KEY `chat_groups_academic_session_id_foreign` (`academic_session_id`),
  KEY `chat_groups_interactive_course_id_foreign` (`interactive_course_id`),
  KEY `chat_groups_recorded_course_id_foreign` (`recorded_course_id`),
  KEY `chat_groups_academy_id_type_index` (`academy_id`,`type`),
  KEY `chat_groups_owner_id_index` (`owner_id`),
  KEY `chat_groups_is_active_index` (`is_active`),
  CONSTRAINT `chat_groups_academic_session_id_foreign` FOREIGN KEY (`academic_session_id`) REFERENCES `academic_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_groups_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_groups_interactive_course_id_foreign` FOREIGN KEY (`interactive_course_id`) REFERENCES `interactive_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_groups_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_groups_quran_circle_id_foreign` FOREIGN KEY (`quran_circle_id`) REFERENCES `quran_circles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_groups_quran_session_id_foreign` FOREIGN KEY (`quran_session_id`) REFERENCES `quran_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_groups_recorded_course_id_foreign` FOREIGN KEY (`recorded_course_id`) REFERENCES `recorded_courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_message_edits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_message_edits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `edited_by` bigint unsigned NOT NULL,
  `original_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `edited_body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `edited_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_message_edits_edited_by_foreign` (`edited_by`),
  KEY `chat_message_edits_message_id_edited_at_index` (`message_id`,`edited_at`),
  CONSTRAINT `chat_message_edits_edited_by_foreign` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_message_edits_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `ch_messages` (`id`) ON DELETE CASCADE
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
  `section_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `duration_minutes` int NOT NULL DEFAULT '30',
  `passing_score` int NOT NULL DEFAULT '70',
  `max_attempts` int NOT NULL DEFAULT '3',
  `show_correct_answers` tinyint(1) NOT NULL DEFAULT '1',
  `randomize_questions` tinyint(1) NOT NULL DEFAULT '0',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `order` int NOT NULL DEFAULT '0',
  `available_from` timestamp NULL DEFAULT NULL,
  `available_until` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_quizzes_recorded_course_id_foreign` (`recorded_course_id`),
  KEY `course_quizzes_section_id_foreign` (`section_id`),
  CONSTRAINT `course_quizzes_recorded_course_id_foreign` FOREIGN KEY (`recorded_course_id`) REFERENCES `recorded_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_quizzes_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `course_sections` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_recordings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_recordings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `course_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `recording_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `meeting_room` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('recording','processing','completed','failed','deleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recording',
  `started_at` timestamp NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `file_format` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mp4',
  `metadata` json DEFAULT NULL,
  `processing_error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_recordings_recording_id_unique` (`recording_id`),
  KEY `course_recordings_session_id_status_index` (`session_id`,`status`),
  KEY `course_recordings_teacher_id_status_index` (`teacher_id`,`status`),
  KEY `course_recordings_course_id_created_at_index` (`course_id`,`created_at`),
  KEY `course_recordings_recording_id_index` (`recording_id`),
  CONSTRAINT `course_recordings_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `interactive_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_recordings_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `interactive_course_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_recordings_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `academic_teacher_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `course_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reviewable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AppModelsRecordedCourse',
  `academy_id` bigint unsigned DEFAULT NULL,
  `reviewable_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `review` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `review_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_approved` tinyint(1) NOT NULL DEFAULT '1',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_course_review` (`reviewable_type`,`reviewable_id`,`user_id`),
  KEY `course_reviews_user_id_foreign` (`user_id`),
  KEY `course_reviews_approved_by_foreign` (`approved_by`),
  KEY `course_reviews_reviewable_index` (`reviewable_type`,`reviewable_id`),
  CONSTRAINT `course_reviews_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `course_reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
  `interactive_course_id` bigint unsigned DEFAULT NULL,
  `subscription_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recorded',
  `enrollment_type` enum('free','paid','trial','gift') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid',
  `payment_type` enum('one_time','installment','subscription') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'one_time',
  `price_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `original_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `billing_cycle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'lifetime',
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
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
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
  `auto_renew` tinyint(1) NOT NULL DEFAULT '0',
  `next_billing_date` timestamp NULL DEFAULT NULL,
  `last_payment_date` timestamp NULL DEFAULT NULL,
  `final_price` decimal(10,2) DEFAULT NULL,
  `attendance_count` int NOT NULL DEFAULT '0',
  `total_possible_attendance` int NOT NULL DEFAULT '0',
  `package_name_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_name_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_subscriptions_student_id_recorded_course_id_unique` (`student_id`,`recorded_course_id`),
  UNIQUE KEY `course_subscriptions_subscription_code_unique` (`subscription_code`),
  KEY `course_subscriptions_academy_id_status_index` (`academy_id`,`status`),
  KEY `course_subscriptions_student_id_status_index` (`student_id`,`status`),
  KEY `course_subscriptions_recorded_course_id_status_index` (`recorded_course_id`,`status`),
  KEY `course_subscriptions_enrollment_type_status_index` (`enrollment_type`,`status`),
  KEY `course_subscriptions_expires_at_status_index` (`expires_at`,`status`),
  KEY `course_subscriptions_certificate_issued_index` (`certificate_issued`),
  KEY `course_sub_type_idx` (`course_type`),
  KEY `course_sub_interactive_idx` (`interactive_course_id`)
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
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `grade_levels_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `grade_levels_level_index` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `homework_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `homework_submissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `submitable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `submitable_id` bigint unsigned NOT NULL,
  `homework_type` enum('academic','interactive','quran') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_date` timestamp NULL DEFAULT NULL,
  `student_id` bigint unsigned NOT NULL,
  `submission_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `submission_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `submission_files` json DEFAULT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `is_late` tinyint(1) NOT NULL DEFAULT '0',
  `days_late` int NOT NULL DEFAULT '0',
  `graded_at` datetime DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `return_reason` text COLLATE utf8mb4_unicode_ci,
  `grade` decimal(3,1) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) NOT NULL DEFAULT '100.00',
  `score_percentage` decimal(5,2) DEFAULT NULL,
  `grade_letter` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `graded_by` bigint unsigned DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `submission_status` enum('not_started','draft','submitted','late','graded','returned','resubmitted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_started',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `last_auto_save_at` timestamp NULL DEFAULT NULL,
  `auto_save_content` longtext COLLATE utf8mb4_unicode_ci,
  `revision_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `homework_submissions_submission_code_unique` (`submission_code`),
  KEY `homework_submissions_academy_id_foreign` (`academy_id`),
  KEY `homework_submissions_submitable_type_submitable_id_index` (`submitable_type`,`submitable_id`),
  KEY `homework_submissions_graded_by_foreign` (`graded_by`),
  KEY `homework_submissions_student_id_status_index` (`student_id`,`status`),
  KEY `homework_submissions_academy_id_submission_status_index` (`academy_id`,`submission_status`),
  KEY `homework_submissions_student_id_homework_type_index` (`student_id`,`homework_type`),
  KEY `homework_submissions_due_date_submission_status_index` (`due_date`,`submission_status`),
  KEY `homework_submissions_is_late_submission_status_index` (`is_late`,`submission_status`),
  KEY `homework_submissions_student_status_idx` (`student_id`,`status`),
  CONSTRAINT `homework_submissions_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `homework_submissions_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`),
  CONSTRAINT `homework_submissions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `interactive_course_homework`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactive_course_homework` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `submission_status` enum('not_submitted','submitted','late','graded','returned') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'not_submitted' COMMENT 'Status of homework submission',
  `submission_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Text answer/response from student',
  `submission_files` json DEFAULT NULL COMMENT 'Array of file paths uploaded by student',
  `submitted_at` timestamp NULL DEFAULT NULL COMMENT 'When student submitted the homework',
  `is_late` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether submission was late',
  `score` decimal(5,2) DEFAULT NULL COMMENT 'Score given by teacher',
  `teacher_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Feedback from teacher',
  `graded_by` bigint unsigned DEFAULT NULL,
  `graded_at` timestamp NULL DEFAULT NULL COMMENT 'When homework was graded',
  `revision_count` int NOT NULL DEFAULT '0' COMMENT 'Number of times student revised/resubmitted',
  `revision_history` json DEFAULT NULL COMMENT 'History of all submissions/revisions',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interactive_course_homework_student_id_foreign` (`student_id`),
  KEY `interactive_course_homework_graded_by_foreign` (`graded_by`),
  KEY `interactive_course_homework_session_id_student_id_index` (`session_id`,`student_id`),
  KEY `interactive_course_homework_academy_id_submission_status_index` (`academy_id`,`submission_status`),
  KEY `interactive_course_homework_submitted_at_index` (`submitted_at`),
  KEY `interactive_course_homework_graded_at_index` (`graded_at`),
  CONSTRAINT `interactive_course_homework_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_course_homework_graded_by_foreign` FOREIGN KEY (`graded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interactive_course_homework_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `interactive_course_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_course_homework_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interactive_course_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactive_course_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `course_id` bigint unsigned NOT NULL,
  `academy_id` bigint unsigned DEFAULT NULL,
  `session_number` int NOT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `meeting_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_password` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_platform` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'livekit',
  `meeting_data` json DEFAULT NULL,
  `meeting_room_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_auto_generated` tinyint(1) NOT NULL DEFAULT '0',
  `meeting_expires_at` timestamp NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lesson_content` text COLLATE utf8mb4_unicode_ci COMMENT 'محتوى الدرس',
  `session_notes` text COLLATE utf8mb4_unicode_ci,
  `teacher_feedback` text COLLATE utf8mb4_unicode_ci,
  `cancellation_reason` text COLLATE utf8mb4_unicode_ci,
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `reschedule_reason` text COLLATE utf8mb4_unicode_ci,
  `rescheduled_from` timestamp NULL DEFAULT NULL,
  `rescheduled_to` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `scheduled_by` bigint unsigned DEFAULT NULL,
  `duration_minutes` int NOT NULL,
  `actual_duration_minutes` int DEFAULT NULL,
  `status` enum('unscheduled','scheduled','ready','ongoing','completed','cancelled','absent','missed','rescheduled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `attendance_count` int NOT NULL DEFAULT '0',
  `attendance_status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `participants_count` int NOT NULL DEFAULT '0',
  `homework_assigned` tinyint(1) NOT NULL DEFAULT '0',
  `homework_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Description/instructions for the homework',
  `homework_file` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interactive_course_sessions_course_id_session_number_unique` (`course_id`,`session_number`),
  KEY `ics_course_date_idx` (`course_id`),
  KEY `interactive_course_sessions_academy_id_foreign` (`academy_id`),
  KEY `interactive_course_sessions_cancelled_by_foreign` (`cancelled_by`),
  KEY `interactive_course_sessions_created_by_foreign` (`created_by`),
  KEY `interactive_course_sessions_updated_by_foreign` (`updated_by`),
  KEY `interactive_course_sessions_scheduled_by_foreign` (`scheduled_by`),
  CONSTRAINT `interactive_course_sessions_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_course_sessions_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interactive_course_sessions_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `interactive_courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_course_sessions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interactive_course_sessions_scheduled_by_foreign` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interactive_course_sessions_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
  `title_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'English title for the course',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'English description for the course',
  `subject_id` bigint unsigned NOT NULL,
  `grade_level_id` bigint unsigned NOT NULL,
  `course_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_type` enum('intensive','regular','exam_prep') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `difficulty_level` enum('beginner','intermediate','advanced') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner' COMMENT 'Course difficulty level',
  `max_students` int NOT NULL DEFAULT '20',
  `duration_weeks` int NOT NULL,
  `sessions_per_week` int NOT NULL,
  `session_duration_minutes` int NOT NULL DEFAULT '60',
  `total_sessions` int NOT NULL,
  `student_price` decimal(8,2) NOT NULL,
  `enrollment_fee` decimal(10,2) DEFAULT NULL COMMENT 'One-time enrollment fee for students',
  `is_enrollment_fee_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether enrollment fee is required',
  `teacher_payment` decimal(8,2) NOT NULL,
  `teacher_fixed_amount` decimal(10,2) DEFAULT NULL COMMENT 'Fixed amount for teacher (when payment_type = fixed)',
  `amount_per_student` decimal(10,2) DEFAULT NULL COMMENT 'Amount per enrolled student (when payment_type = per_student)',
  `amount_per_session` decimal(10,2) DEFAULT NULL COMMENT 'Amount per session conducted (when payment_type = per_session)',
  `payment_type` enum('fixed_amount','per_student','per_session') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fixed_amount',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `enrollment_deadline` date NOT NULL,
  `schedule` json NOT NULL,
  `learning_outcomes` json DEFAULT NULL COMMENT 'Learning outcomes - array of outcomes',
  `prerequisites` json DEFAULT NULL COMMENT 'Prerequisites - array of prerequisites',
  `course_outline` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Course outline and syllabus',
  `status` enum('draft','published','active','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `avg_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `total_reviews` int unsigned NOT NULL DEFAULT '0',
  `certificate_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `certificate_template_style` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'template_1',
  `recording_enabled` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Enable/disable recording for all course sessions',
  `preparation_minutes` int DEFAULT NULL COMMENT 'Minutes before session start when students can join',
  `buffer_minutes` int DEFAULT NULL COMMENT 'Buffer time after session ends',
  `late_tolerance_minutes` int DEFAULT NULL COMMENT 'How many minutes late before marked as late',
  `attendance_threshold_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Minimum attendance percentage required (e.g., 80.00)',
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `publication_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
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
  CONSTRAINT `interactive_courses_grade_level_id_foreign` FOREIGN KEY (`grade_level_id`) REFERENCES `academic_grade_levels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_courses_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `academic_subjects` (`id`) ON DELETE CASCADE,
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
  `attendance_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'absent',
  `join_time` timestamp NULL DEFAULT NULL,
  `leave_time` timestamp NULL DEFAULT NULL,
  `auto_join_time` timestamp NULL DEFAULT NULL,
  `auto_leave_time` timestamp NULL DEFAULT NULL,
  `auto_duration_minutes` int NOT NULL DEFAULT '0',
  `auto_tracked` tinyint(1) NOT NULL DEFAULT '0',
  `manually_overridden` tinyint(1) NOT NULL DEFAULT '0',
  `overridden_by` bigint unsigned DEFAULT NULL,
  `overridden_at` timestamp NULL DEFAULT NULL,
  `override_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `meeting_events` json DEFAULT NULL,
  `participation_score` decimal(3,1) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `video_completion_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `quiz_completion` tinyint(1) NOT NULL DEFAULT '0',
  `exercises_completed` int NOT NULL DEFAULT '0',
  `interaction_score` decimal(3,1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `interactive_session_attendances_student_id_foreign` (`student_id`),
  KEY `interactive_session_attendances_overridden_by_foreign` (`overridden_by`),
  KEY `interactive_session_attendances_session_id_student_id_index` (`session_id`,`student_id`),
  KEY `interactive_session_attendances_attendance_status_index` (`attendance_status`),
  KEY `interactive_session_attendances_auto_tracked_index` (`auto_tracked`),
  CONSTRAINT `interactive_session_attendances_overridden_by_foreign` FOREIGN KEY (`overridden_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interactive_session_attendances_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `interactive_course_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_session_attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `interactive_session_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactive_session_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned DEFAULT NULL,
  `academy_id` bigint unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `homework_degree` decimal(3,1) DEFAULT NULL COMMENT 'درجة أداء الواجب من 0 إلى 10',
  `meeting_enter_time` timestamp NULL DEFAULT NULL,
  `meeting_leave_time` timestamp NULL DEFAULT NULL,
  `actual_attendance_minutes` int NOT NULL DEFAULT '0',
  `is_late` tinyint(1) NOT NULL DEFAULT '0',
  `late_minutes` int NOT NULL DEFAULT '0',
  `attendance_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'absent',
  `attendance_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `meeting_events` json DEFAULT NULL,
  `evaluated_at` timestamp NULL DEFAULT NULL,
  `is_calculated` tinyint(1) NOT NULL DEFAULT '1',
  `manually_evaluated` tinyint(1) NOT NULL DEFAULT '0',
  `override_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `interactive_session_reports_session_id_student_id_unique` (`session_id`,`student_id`),
  KEY `interactive_session_reports_student_id_foreign` (`student_id`),
  KEY `interactive_session_reports_teacher_id_foreign` (`teacher_id`),
  KEY `interactive_session_reports_attendance_status_index` (`attendance_status`),
  KEY `interactive_session_reports_evaluated_at_index` (`evaluated_at`),
  KEY `interactive_session_reports_academy_id_index` (`academy_id`),
  CONSTRAINT `interactive_session_reports_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `interactive_session_reports_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `interactive_course_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_session_reports_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactive_session_reports_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
  `video_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `video_size_mb` decimal(10,2) NOT NULL DEFAULT '0.00',
  `video_quality` enum('480p','720p','1080p','4K') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '720p',
  `duration_minutes` int DEFAULT '0',
  `order` int DEFAULT '0',
  `transcript` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `attachments` json DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `is_free_preview` tinyint(1) NOT NULL DEFAULT '0',
  `is_downloadable` tinyint(1) NOT NULL DEFAULT '0',
  `quiz_id` bigint unsigned DEFAULT NULL,
  `assignment_requirements` json DEFAULT NULL,
  `learning_objectives` json DEFAULT NULL,
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
  KEY `lessons_recorded_course_id_order_index` (`recorded_course_id`),
  KEY `lessons_course_section_id_order_index` (`course_section_id`),
  KEY `lessons_is_published_lesson_type_index` (`is_published`),
  KEY `lessons_is_free_preview_is_published_index` (`is_free_preview`,`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` bigint unsigned NOT NULL,
  `manipulations` json NOT NULL,
  `custom_properties` json NOT NULL,
  `generated_conversions` json NOT NULL,
  `responsive_images` json NOT NULL,
  `order_column` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `media_uuid_unique` (`uuid`),
  KEY `media_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `media_order_column_index` (`order_column`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meeting_attendance_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_attendance_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'LiveKit webhook event UUID - for idempotency',
  `event_type` enum('join','leave','reconnect','aborted') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'join',
  `event_timestamp` timestamp NOT NULL COMMENT 'From LiveKit webhook - exact join/leave time',
  `session_id` bigint unsigned NOT NULL COMMENT 'Polymorphic session ID',
  `session_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'QuranSession or AcademicSession class name',
  `user_id` bigint unsigned NOT NULL,
  `academy_id` bigint unsigned DEFAULT NULL,
  `participant_sid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'LiveKit participant session ID - unique per connection',
  `participant_identity` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'User identity sent to LiveKit',
  `participant_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `left_at` timestamp NULL DEFAULT NULL COMMENT 'Populated by participant_left event',
  `duration_minutes` int DEFAULT NULL COMMENT 'Calculated: left_at - event_timestamp',
  `leave_event_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Event ID that closed this cycle',
  `raw_webhook_data` json DEFAULT NULL COMMENT 'Full webhook payload for debugging',
  `termination_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'normal, aborted, timeout, etc.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_attendance_events_event_id_unique` (`event_id`),
  KEY `meeting_attendance_events_academy_id_foreign` (`academy_id`),
  KEY `session_user_idx` (`session_id`,`session_type`,`user_id`),
  KEY `session_time_idx` (`session_id`,`event_timestamp`),
  KEY `participant_time_idx` (`participant_sid`,`event_timestamp`),
  KEY `user_time_idx` (`user_id`,`event_timestamp`),
  KEY `meeting_attendance_events_event_timestamp_index` (`event_timestamp`),
  CONSTRAINT `meeting_attendance_events_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_attendance_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meeting_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meeting_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `user_type` enum('student','teacher','supervisor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'student',
  `session_type` enum('individual','group','academic','interactive') COLLATE utf8mb4_unicode_ci DEFAULT 'individual',
  `first_join_time` timestamp NULL DEFAULT NULL COMMENT 'When user first joined the meeting',
  `last_leave_time` timestamp NULL DEFAULT NULL COMMENT 'When user last left the meeting',
  `total_duration_minutes` int NOT NULL DEFAULT '0' COMMENT 'Total time spent in meeting in minutes',
  `join_leave_cycles` json DEFAULT NULL COMMENT 'Array of all join/leave events',
  `attendance_calculated_at` timestamp NULL DEFAULT NULL COMMENT 'When final attendance was calculated',
  `attendance_percentage` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Percentage of session attended',
  `session_duration_minutes` int DEFAULT NULL COMMENT 'Total session duration for calculations',
  `session_start_time` timestamp NULL DEFAULT NULL COMMENT 'Actual session start time',
  `session_end_time` timestamp NULL DEFAULT NULL COMMENT 'Actual session end time',
  `join_count` int NOT NULL DEFAULT '0' COMMENT 'Number of times user joined',
  `leave_count` int NOT NULL DEFAULT '0' COMMENT 'Number of times user left',
  `is_calculated` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether final attendance has been calculated',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `attendance_status` enum('attended','late','leaved','absent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'absent',
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_attendance_session_user_unique` (`session_id`,`user_id`),
  KEY `meeting_attendance_session_status_idx` (`session_id`),
  KEY `meeting_attendance_user_type_idx` (`user_id`,`session_type`),
  KEY `meeting_attendance_calc_status_idx` (`attendance_calculated_at`,`is_calculated`),
  KEY `meeting_attendance_timing_idx` (`first_join_time`,`last_leave_time`),
  KEY `meeting_attendances_session_id_index` (`session_id`),
  CONSTRAINT `meeting_attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `message_reactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `message_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `reaction` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_reactions_message_id_user_id_reaction_unique` (`message_id`,`user_id`,`reaction`),
  KEY `message_reactions_user_id_foreign` (`user_id`),
  KEY `message_reactions_message_id_reaction_index` (`message_id`,`reaction`),
  CONSTRAINT `message_reactions_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `ch_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_reactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `tenant_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `is_important` tinyint(1) NOT NULL DEFAULT '0',
  `read_at` timestamp NULL DEFAULT NULL,
  `panel_opened_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`),
  KEY `notifications_notification_type_index` (`notification_type`),
  KEY `notifications_category_index` (`category`),
  KEY `notifications_tenant_id_index` (`tenant_id`),
  KEY `notifications_notifiable_type_notifiable_id_read_at_index` (`notifiable_type`,`notifiable_id`,`read_at`),
  KEY `notifications_created_at_index` (`created_at`),
  KEY `notifications_panel_opened_at_index` (`panel_opened_at`)
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
  `phone_country_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '+966' COMMENT 'Country calling code for primary phone',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `occupation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship_type` enum('father','mother','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `secondary_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_phone_country_code` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Country calling code for secondary phone',
  `preferred_contact_method` enum('phone','email','sms','whatsapp') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'phone',
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `parent_profiles_parent_code_unique` (`parent_code`),
  UNIQUE KEY `parent_profiles_email_academy_unique` (`email`,`academy_id`),
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
  `relationship_type` enum('father','mother','other') COLLATE utf8mb4_unicode_ci DEFAULT 'other',
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
DROP TABLE IF EXISTS `payment_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gateway` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_from` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_to` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount_cents` bigint unsigned DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_audit_logs_payment_id_action_index` (`payment_id`,`action`),
  KEY `payment_audit_logs_academy_id_created_at_index` (`academy_id`,`created_at`),
  KEY `payment_audit_logs_transaction_id_index` (`transaction_id`),
  KEY `payment_audit_logs_user_created_idx` (`user_id`,`created_at`),
  CONSTRAINT `payment_audit_logs_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_audit_logs_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_webhook_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned DEFAULT NULL,
  `payment_id` bigint unsigned DEFAULT NULL,
  `gateway` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique event ID for idempotency',
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_cents` bigint unsigned DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `is_processed` tinyint(1) NOT NULL DEFAULT '0',
  `processed_at` timestamp NULL DEFAULT NULL,
  `payload` json NOT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_webhook_events_event_id_unique` (`event_id`),
  KEY `payment_webhook_events_academy_id_foreign` (`academy_id`),
  KEY `payment_webhook_events_gateway_transaction_id_index` (`gateway`,`transaction_id`),
  KEY `payment_webhook_events_payment_id_status_index` (`payment_id`,`status`),
  KEY `payment_webhook_events_is_processed_index` (`is_processed`),
  CONSTRAINT `payment_webhook_events_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payment_webhook_events_payment_id_foreign` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
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
  `payment_method_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'e.g., card, wallet, bank_transfer',
  `card_brand` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `card_last_four` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gateway_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Gateway payment intent/session ID',
  `gateway_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Gateway order ID',
  `client_secret` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Client secret for frontend',
  `redirect_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iframe_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `callback_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intent_expires_at` timestamp NULL DEFAULT NULL,
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
  `paid_at` timestamp NULL DEFAULT NULL,
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
  KEY `payments_payment_code_index` (`payment_code`),
  KEY `payments_academy_status_idx` (`academy_id`,`status`),
  KEY `payments_user_created_idx` (`user_id`,`created_at`),
  KEY `payments_gateway_intent_id_index` (`gateway_intent_id`),
  KEY `payments_gateway_order_id_index` (`gateway_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `platform_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `platform_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `logo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `working_hours` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `social_links` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `portfolio_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `portfolio_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `project_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_category_id` bigint unsigned NOT NULL,
  `project_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `project_features` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `portfolio_items_service_category_id_foreign` (`service_category_id`),
  CONSTRAINT `portfolio_items_service_category_id_foreign` FOREIGN KEY (`service_category_id`) REFERENCES `business_service_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `endpoint` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auth_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_encoding` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_info` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_user_id_endpoint_unique` (`user_id`,`endpoint`),
  KEY `push_subscriptions_user_id_index` (`user_id`),
  CONSTRAINT `push_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quiz_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_assignments` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quiz_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assignable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assignable_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `available_from` timestamp NULL DEFAULT NULL,
  `available_until` timestamp NULL DEFAULT NULL,
  `max_attempts` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_assignments_quiz_id_foreign` (`quiz_id`),
  KEY `quiz_assignments_assignable_type_assignable_id_index` (`assignable_type`,`assignable_id`),
  KEY `quiz_assignments_assignable_visible_index` (`assignable_type`,`assignable_id`,`is_visible`),
  CONSTRAINT `quiz_assignments_quiz_id_foreign` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quiz_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_attempts` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quiz_assignment_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `answers` json DEFAULT NULL,
  `score` tinyint unsigned DEFAULT NULL,
  `passed` tinyint(1) NOT NULL DEFAULT '0',
  `started_at` timestamp NOT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_attempts_quiz_assignment_id_student_id_index` (`quiz_assignment_id`,`student_id`),
  KEY `quiz_attempts_student_id_passed_index` (`student_id`,`passed`),
  CONSTRAINT `quiz_attempts_quiz_assignment_id_foreign` FOREIGN KEY (`quiz_assignment_id`) REFERENCES `quiz_assignments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quiz_attempts_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quiz_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quiz_questions` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quiz_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` json NOT NULL,
  `correct_option` tinyint unsigned NOT NULL,
  `order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quiz_questions_quiz_id_order_index` (`quiz_id`,`order`),
  CONSTRAINT `quiz_questions_quiz_id_foreign` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quizzes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quizzes` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `duration_minutes` int unsigned DEFAULT NULL,
  `passing_score` tinyint unsigned NOT NULL DEFAULT '60',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quizzes_academy_id_is_active_index` (`academy_id`,`is_active`),
  CONSTRAINT `quizzes_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_circle_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_circle_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `circle_id` bigint unsigned NOT NULL,
  `quran_teacher_id` bigint unsigned NOT NULL,
  `weekly_schedule` json NOT NULL,
  `timezone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Asia/Riyadh',
  `default_duration_minutes` int NOT NULL DEFAULT '60',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `schedule_starts_at` datetime NOT NULL,
  `schedule_ends_at` datetime DEFAULT NULL,
  `last_generated_at` datetime DEFAULT NULL,
  `generate_ahead_days` int NOT NULL DEFAULT '30',
  `generate_before_hours` int NOT NULL DEFAULT '1',
  `session_title_template` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_description_template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `default_lesson_objectives` json DEFAULT NULL,
  `meeting_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recording_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quran_circle_schedules_created_by_foreign` (`created_by`),
  KEY `quran_circle_schedules_updated_by_foreign` (`updated_by`),
  KEY `quran_circle_schedules_circle_id_is_active_index` (`circle_id`,`is_active`),
  KEY `quran_circle_schedules_quran_teacher_id_is_active_index` (`quran_teacher_id`,`is_active`),
  KEY `quran_circle_schedules_academy_id_is_active_index` (`academy_id`,`is_active`),
  KEY `quran_circle_schedules_is_active_last_generated_at_index` (`is_active`,`last_generated_at`),
  KEY `quran_circle_schedules_schedule_starts_at_schedule_ends_at_index` (`schedule_starts_at`,`schedule_ends_at`),
  CONSTRAINT `quran_circle_schedules_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_circle_schedules_circle_id_foreign` FOREIGN KEY (`circle_id`) REFERENCES `quran_circles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_circle_schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_circle_schedules_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_circle_schedules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
  `monthly_sessions_count` int NOT NULL DEFAULT '8',
  `monthly_fee` decimal(8,2) NOT NULL DEFAULT '0.00',
  `teacher_monthly_revenue` decimal(8,2) DEFAULT NULL,
  `sessions_completed` int NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `schedule_configured` tinyint(1) NOT NULL DEFAULT '0',
  `schedule_configured_at` datetime DEFAULT NULL,
  `enrollment_status` enum('closed','open','full','waitlist') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'closed',
  `last_session_at` timestamp NULL DEFAULT NULL,
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
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `schedule_configured_by` bigint unsigned DEFAULT NULL,
  `schedule_days` json DEFAULT NULL COMMENT 'Basic weekdays for display - JSON array of weekday strings',
  `schedule_time` time DEFAULT NULL COMMENT 'Basic time for display - time format HH:MM',
  `attendance_threshold_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Minimum attendance percentage required (e.g., 80.00)',
  `learning_objectives` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_circles_academy_id_circle_code_unique` (`academy_id`,`circle_code`),
  UNIQUE KEY `quran_circles_circle_code_unique` (`circle_code`),
  KEY `quran_circles_created_by_foreign` (`created_by`),
  KEY `quran_circles_updated_by_foreign` (`updated_by`),
  KEY `quran_circles_academy_id_status_index` (`academy_id`),
  KEY `quran_circles_academy_id_enrollment_status_index` (`academy_id`,`enrollment_status`),
  KEY `quran_circles_quran_teacher_id_status_index` (`quran_teacher_id`),
  KEY `quran_circles_circle_type_specialization_index` (`circle_type`,`specialization`),
  KEY `quran_circles_enrollment_status_registration_deadline_index` (`enrollment_status`),
  KEY `quran_circles_memorization_level_status_index` (`memorization_level`),
  KEY `quran_circles_avg_rating_index` (`avg_rating`),
  KEY `quran_circles_circle_code_index` (`circle_code`),
  KEY `quran_circles_schedule_configured_by_foreign` (`schedule_configured_by`),
  KEY `quran_circles_academy_status_idx` (`academy_id`,`status`),
  KEY `quran_circles_teacher_status_idx` (`quran_teacher_id`,`status`),
  KEY `quran_circles_enrollment_status_idx` (`enrollment_status`,`status`),
  KEY `quran_circles_code_academy_idx` (`circle_code`,`academy_id`),
  CONSTRAINT `quran_circles_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_circles_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_circles_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_circles_schedule_configured_by_foreign` FOREIGN KEY (`schedule_configured_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_circles_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_individual_circles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_individual_circles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `quran_teacher_id` bigint unsigned DEFAULT NULL,
  `student_id` bigint unsigned NOT NULL,
  `subscription_id` bigint unsigned NOT NULL,
  `circle_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `specialization` enum('memorization','recitation','interpretation','arabic_language','complete') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'memorization',
  `memorization_level` enum('beginner','elementary','intermediate','advanced','expert') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `total_sessions` int NOT NULL,
  `sessions_scheduled` int NOT NULL DEFAULT '0',
  `sessions_completed` int NOT NULL DEFAULT '0',
  `lifetime_sessions_completed` int NOT NULL DEFAULT '0',
  `sessions_remaining` int NOT NULL DEFAULT '0',
  `current_surah` int DEFAULT NULL,
  `current_verse` int DEFAULT NULL,
  `current_page` int DEFAULT NULL COMMENT 'الصفحة الحالية في المصحف',
  `current_face` int DEFAULT NULL COMMENT 'الوجه الحالي (1 أو 2)',
  `papers_memorized` int NOT NULL DEFAULT '0' COMMENT 'عدد الأوجه المحفوظة',
  `papers_memorized_precise` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'عدد الأوجه المحفوظة بدقة (مع الكسور)',
  `lifetime_pages_memorized` decimal(10,2) NOT NULL DEFAULT '0.00',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `default_duration_minutes` int NOT NULL DEFAULT '45',
  `preferred_times` json DEFAULT NULL,
  `status` enum('pending','active','completed','suspended','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `subscription_renewal_count` int NOT NULL DEFAULT '0',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `last_session_at` datetime DEFAULT NULL,
  `meeting_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recording_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `materials_used` json DEFAULT NULL,
  `learning_objectives` json DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_individual_circles_circle_code_unique` (`circle_code`),
  KEY `quran_individual_circles_created_by_foreign` (`created_by`),
  KEY `quran_individual_circles_updated_by_foreign` (`updated_by`),
  KEY `quran_individual_circles_academy_id_status_index` (`academy_id`,`status`),
  KEY `quran_individual_circles_quran_teacher_id_status_index` (`quran_teacher_id`,`status`),
  KEY `quran_individual_circles_student_id_status_index` (`student_id`,`status`),
  KEY `quran_individual_circles_subscription_id_index` (`subscription_id`),
  KEY `quran_individual_circles_status_started_at_index` (`status`,`started_at`),
  KEY `quran_individual_circles_current_page_current_face_index` (`current_page`,`current_face`),
  KEY `quran_individual_circles_papers_memorized_index` (`papers_memorized`),
  KEY `quran_individual_circles_academy_status_idx` (`academy_id`,`status`),
  KEY `quran_individual_circles_teacher_status_idx` (`quran_teacher_id`,`status`),
  KEY `quran_individual_circles_student_status_idx` (`student_id`,`status`),
  KEY `quran_individual_circles_subscription_idx` (`subscription_id`),
  CONSTRAINT `quran_individual_circles_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_individual_circles_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_individual_circles_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_individual_circles_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_individual_circles_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `quran_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_individual_circles_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
DROP TABLE IF EXISTS `quran_session_attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_session_attendances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `attendance_status` enum('present','absent','late','partial','left_early') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'absent',
  `auto_tracked` tinyint(1) NOT NULL DEFAULT '0',
  `manually_overridden` tinyint(1) NOT NULL DEFAULT '0',
  `join_time` timestamp NULL DEFAULT NULL,
  `auto_join_time` timestamp NULL DEFAULT NULL,
  `leave_time` timestamp NULL DEFAULT NULL,
  `auto_leave_time` timestamp NULL DEFAULT NULL,
  `auto_duration_minutes` int DEFAULT NULL,
  `participation_score` decimal(3,1) DEFAULT NULL COMMENT 'درجة المشاركة من 0 إلى 10',
  `verses_reviewed` int DEFAULT NULL COMMENT 'عدد الآيات المراجعة',
  `homework_completion` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'إكمال الواجب المنزلي',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'ملاحظات المعلم',
  `meeting_events` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pages_reviewed_today` decimal(4,2) DEFAULT NULL,
  `overridden_by` bigint unsigned DEFAULT NULL,
  `overridden_at` timestamp NULL DEFAULT NULL,
  `override_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_session_attendances_session_id_student_id_unique` (`session_id`,`student_id`),
  KEY `quran_session_attendances_student_id_foreign` (`student_id`),
  KEY `quran_session_attendances_session_id_student_id_index` (`session_id`,`student_id`),
  KEY `quran_session_attendances_attendance_status_index` (`attendance_status`),
  KEY `quran_session_attendances_join_time_index` (`join_time`),
  KEY `quran_session_attendances_auto_tracked_manually_overridden_index` (`auto_tracked`,`manually_overridden`),
  KEY `quran_session_attendances_session_id_auto_tracked_index` (`session_id`,`auto_tracked`),
  KEY `quran_session_attendances_overridden_by_index` (`overridden_by`),
  CONSTRAINT `quran_session_attendances_overridden_by_foreign` FOREIGN KEY (`overridden_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_session_attendances_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `quran_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_session_attendances_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_session_homework`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_session_homework` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `has_new_memorization` tinyint(1) NOT NULL DEFAULT '0',
  `has_review` tinyint(1) NOT NULL DEFAULT '0',
  `has_comprehensive_review` tinyint(1) NOT NULL DEFAULT '0',
  `new_memorization_pages` decimal(5,2) DEFAULT NULL,
  `new_memorization_surah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_memorization_from_verse` int DEFAULT NULL,
  `new_memorization_to_verse` int DEFAULT NULL,
  `review_pages` decimal(5,2) DEFAULT NULL,
  `review_surah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `review_from_verse` int DEFAULT NULL,
  `review_to_verse` int DEFAULT NULL,
  `comprehensive_review_surahs` json DEFAULT NULL,
  `additional_instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `due_date` date DEFAULT NULL,
  `difficulty_level` enum('easy','medium','hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quran_session_homework_created_by_foreign` (`created_by`),
  KEY `quran_session_homework_session_id_is_active_index` (`session_id`,`is_active`),
  KEY `quran_session_homework_due_date_index` (`due_date`),
  CONSTRAINT `quran_session_homework_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_session_homework_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `quran_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quran_session_homeworks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_session_homeworks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `has_new_memorization` tinyint(1) NOT NULL DEFAULT '0',
  `has_review` tinyint(1) NOT NULL DEFAULT '0',
  `has_comprehensive_review` tinyint(1) NOT NULL DEFAULT '0',
  `new_memorization_pages` decimal(4,2) NOT NULL DEFAULT '0.00',
  `new_memorization_surah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_memorization_from_verse` int DEFAULT NULL,
  `new_memorization_to_verse` int DEFAULT NULL,
  `review_pages` decimal(4,2) NOT NULL DEFAULT '0.00',
  `review_surah` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `review_from_verse` int DEFAULT NULL,
  `review_to_verse` int DEFAULT NULL,
  `comprehensive_review_surahs` json DEFAULT NULL,
  `additional_instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `due_date` date DEFAULT NULL,
  `difficulty_level` enum('easy','medium','hard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quran_session_homeworks_session_id_is_active_index` (`session_id`,`is_active`),
  KEY `quran_session_homeworks_created_by_index` (`created_by`),
  KEY `homework_types_idx` (`has_new_memorization`,`has_review`,`has_comprehensive_review`),
  CONSTRAINT `quran_session_homeworks_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_session_homeworks_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `quran_sessions` (`id`) ON DELETE CASCADE
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
  `subscription_counted` tinyint(1) NOT NULL DEFAULT '0',
  `circle_id` bigint unsigned DEFAULT NULL,
  `session_schedule_id` bigint unsigned DEFAULT NULL,
  `student_id` bigint unsigned DEFAULT NULL,
  `trial_request_id` bigint unsigned DEFAULT NULL,
  `session_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_type` enum('individual','group','trial') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `is_generated` tinyint(1) NOT NULL DEFAULT '0',
  `monthly_session_number` int DEFAULT NULL COMMENT 'Session number within the month (1, 2, 3, etc.)',
  `session_month` date DEFAULT NULL COMMENT 'The month this session belongs to (YYYY-MM-01)',
  `status` enum('unscheduled','scheduled','ready','ongoing','completed','cancelled','absent','missed','rescheduled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unscheduled',
  `title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `lesson_content` text COLLATE utf8mb4_unicode_ci COMMENT 'محتوى الدرس',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `preparation_completed_at` timestamp NULL DEFAULT NULL,
  `meeting_created_at` timestamp NULL DEFAULT NULL,
  `duration_minutes` int NOT NULL DEFAULT '45',
  `actual_duration_minutes` int DEFAULT NULL,
  `meeting_link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_password` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_event_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_calendar_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_meet_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `google_meet_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `google_attendees` json DEFAULT NULL,
  `meeting_platform` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'jitsi',
  `meeting_data` json DEFAULT NULL,
  `meeting_room_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meeting_auto_generated` tinyint(1) NOT NULL DEFAULT '1',
  `meeting_expires_at` timestamp NULL DEFAULT NULL,
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `attendance_status` enum('attended','absent','late','left_early','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `participants_count` int NOT NULL DEFAULT '1',
  `attendance_marked_at` timestamp NULL DEFAULT NULL,
  `attendance_marked_by` bigint unsigned DEFAULT NULL,
  `current_surah` int DEFAULT NULL,
  `current_verse` int DEFAULT NULL,
  `current_page` int DEFAULT NULL COMMENT 'الصفحة الحالية',
  `verses_covered_start` int DEFAULT NULL,
  `verses_covered_end` int DEFAULT NULL,
  `verses_memorized_today` int NOT NULL DEFAULT '0',
  `homework_assigned` json DEFAULT NULL,
  `homework_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `session_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `teacher_feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_auto_generated` tinyint(1) NOT NULL DEFAULT '0',
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancellation_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'teacher_cancelled, student_cancelled, system_cancelled',
  `cancelled_by` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `reschedule_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `rescheduled_from` timestamp NULL DEFAULT NULL,
  `rescheduled_to` timestamp NULL DEFAULT NULL,
  `rescheduling_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reminder_sent_at` timestamp NULL DEFAULT NULL,
  `last_error_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `individual_circle_id` bigint unsigned DEFAULT NULL,
  `generated_from_schedule_id` bigint unsigned DEFAULT NULL,
  `scheduled_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_sessions_academy_id_session_code_unique` (`academy_id`,`session_code`),
  UNIQUE KEY `quran_sessions_session_code_unique` (`session_code`),
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
  KEY `quran_sessions_attendance_status_scheduled_at_index` (`attendance_status`,`scheduled_at`),
  KEY `quran_sessions_session_code_index` (`session_code`),
  KEY `quran_sessions_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `quran_sessions_attendance_marked_by_foreign` (`attendance_marked_by`),
  KEY `quran_sessions_session_schedule_id_status_index` (`session_schedule_id`,`status`),
  KEY `quran_sessions_google_event_id_index` (`google_event_id`),
  KEY `quran_sessions_is_auto_generated_scheduled_at_index` (`is_auto_generated`,`scheduled_at`),
  KEY `quran_sessions_preparation_completed_at_index` (`preparation_completed_at`),
  KEY `quran_sessions_meeting_source_academy_id_index` (`academy_id`),
  KEY `idx_session_month_number` (`session_month`,`monthly_session_number`),
  KEY `idx_session_type_circle` (`session_type`,`circle_id`),
  KEY `idx_circle_type_status` (`circle_id`,`session_type`,`status`),
  KEY `quran_sessions_generated_from_schedule_id_foreign` (`generated_from_schedule_id`),
  KEY `quran_sessions_scheduled_by_foreign` (`scheduled_by`),
  KEY `quran_sessions_individual_circle_id_status_index` (`individual_circle_id`,`status`),
  KEY `quran_sessions_is_template_quran_teacher_id_index` (`quran_teacher_id`),
  KEY `quran_sessions_is_generated_generated_from_schedule_id_index` (`is_generated`,`generated_from_schedule_id`),
  KEY `quran_sessions_is_scheduled_scheduled_at_index` (`scheduled_at`),
  KEY `quran_sessions_session_type_status_scheduled_at_index` (`session_type`,`status`,`scheduled_at`),
  KEY `quran_sessions_meeting_platform_meeting_source_index` (`meeting_platform`),
  KEY `quran_sessions_meeting_expires_at_index` (`meeting_expires_at`),
  KEY `quran_sessions_trial_request_id_foreign` (`trial_request_id`),
  KEY `quran_sessions_current_page_current_face_index` (`current_page`),
  KEY `quran_sessions_academy_status_scheduled_idx` (`academy_id`,`status`,`scheduled_at`),
  KEY `quran_sessions_teacher_scheduled_idx` (`quran_teacher_id`,`scheduled_at`),
  KEY `quran_sessions_student_scheduled_idx` (`student_id`,`scheduled_at`),
  KEY `quran_sessions_subscription_status_idx` (`quran_subscription_id`,`status`),
  KEY `quran_sessions_code_academy_idx` (`session_code`,`academy_id`),
  KEY `quran_sessions_subscription_counted_idx` (`subscription_counted`,`status`),
  KEY `quran_sessions_sub_status_idx` (`quran_subscription_id`,`status`),
  CONSTRAINT `quran_sessions_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_sessions_attendance_marked_by_foreign` FOREIGN KEY (`attendance_marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_cancelled_by_foreign` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_circle_id_foreign` FOREIGN KEY (`circle_id`) REFERENCES `quran_circles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_generated_from_schedule_id_foreign` FOREIGN KEY (`generated_from_schedule_id`) REFERENCES `quran_circle_schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_individual_circle_id_foreign` FOREIGN KEY (`individual_circle_id`) REFERENCES `quran_individual_circles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_sessions_quran_subscription_id_foreign` FOREIGN KEY (`quran_subscription_id`) REFERENCES `quran_subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_scheduled_by_foreign` FOREIGN KEY (`scheduled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_session_schedule_id_foreign` FOREIGN KEY (`session_schedule_id`) REFERENCES `session_schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_sessions_trial_request_id_foreign` FOREIGN KEY (`trial_request_id`) REFERENCES `quran_trial_requests` (`id`) ON DELETE SET NULL,
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
  `package_name_ar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `package_name_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `certificate_issued` tinyint(1) NOT NULL DEFAULT '0',
  `certificate_issued_at` timestamp NULL DEFAULT NULL,
  `trial_used` int NOT NULL DEFAULT '0',
  `is_trial_active` tinyint(1) NOT NULL DEFAULT '0',
  `current_surah` int DEFAULT NULL,
  `memorization_level` enum('beginner','elementary','intermediate','advanced','expert','hafiz') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beginner',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `last_session_at` timestamp NULL DEFAULT NULL,
  `starts_at` timestamp NOT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `pause_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancellation_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `auto_renew` tinyint(1) NOT NULL DEFAULT '1',
  `next_payment_at` timestamp NULL DEFAULT NULL,
  `next_billing_date` timestamp NULL DEFAULT NULL,
  `last_payment_at` timestamp NULL DEFAULT NULL,
  `last_payment_date` timestamp NULL DEFAULT NULL,
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
  `renewal_reminder_sent_at` timestamp NULL DEFAULT NULL,
  `package_price_monthly` decimal(10,2) DEFAULT NULL,
  `package_price_quarterly` decimal(10,2) DEFAULT NULL,
  `package_price_yearly` decimal(10,2) DEFAULT NULL,
  `package_sessions_per_week` int DEFAULT NULL,
  `package_session_duration_minutes` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_subscriptions_academy_id_subscription_code_unique` (`academy_id`,`subscription_code`),
  UNIQUE KEY `quran_subscriptions_subscription_code_unique` (`subscription_code`),
  KEY `quran_subscriptions_created_by_foreign` (`created_by`),
  KEY `quran_subscriptions_updated_by_foreign` (`updated_by`),
  KEY `quran_subscriptions_academy_id_subscription_status_index` (`academy_id`,`status`),
  KEY `quran_subscriptions_academy_id_payment_status_index` (`academy_id`,`payment_status`),
  KEY `quran_subscriptions_student_id_subscription_status_index` (`student_id`,`status`),
  KEY `quran_subscriptions_quran_teacher_id_subscription_status_index` (`quran_teacher_id`,`status`),
  KEY `quran_subscriptions_subscription_status_expires_at_index` (`status`),
  KEY `quran_subscriptions_is_trial_active_trial_used_index` (`is_trial_active`,`trial_used`),
  KEY `quran_subscriptions_auto_renew_next_payment_at_index` (`auto_renew`,`next_payment_at`),
  KEY `quran_subscriptions_subscription_code_index` (`subscription_code`),
  KEY `quran_subscriptions_package_id_foreign` (`package_id`),
  KEY `quran_subscriptions_academy_status_idx` (`academy_id`,`status`),
  KEY `quran_subscriptions_student_status_idx` (`student_id`,`status`),
  KEY `quran_subscriptions_teacher_status_idx` (`quran_teacher_id`,`status`),
  KEY `quran_subscriptions_code_academy_idx` (`subscription_code`,`academy_id`),
  KEY `quran_subscriptions_certificate_issued_index` (`certificate_issued`),
  KEY `quran_sub_renewal_idx` (`status`,`auto_renew`,`next_billing_date`),
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
  `phone_country_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '+966' COMMENT 'Country calling code',
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
  `offers_trial_sessions` tinyint(1) NOT NULL DEFAULT '1',
  `rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `total_reviews` int unsigned NOT NULL DEFAULT '0',
  `total_students` int NOT NULL DEFAULT '0',
  `total_sessions` int NOT NULL DEFAULT '0',
  `session_price_individual` decimal(8,2) DEFAULT NULL COMMENT 'سعر الحصة الفردية',
  `session_price_group` decimal(8,2) DEFAULT NULL COMMENT 'سعر الحصة الجماعية',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
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
DROP TABLE IF EXISTS `quran_trial_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quran_trial_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `request_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_age` int DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_level` enum('beginner','basic','intermediate','advanced','expert') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `learning_goals` json DEFAULT NULL,
  `preferred_time` enum('morning','afternoon','evening') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','approved','rejected','scheduled','completed','cancelled','no_show') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `trial_session_id` bigint unsigned DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `rating` int DEFAULT NULL,
  `feedback` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `quran_trial_requests_request_code_unique` (`request_code`),
  KEY `quran_trial_requests_trial_session_id_foreign` (`trial_session_id`),
  KEY `quran_trial_requests_created_by_foreign` (`created_by`),
  KEY `quran_trial_requests_updated_by_foreign` (`updated_by`),
  KEY `quran_trial_requests_academy_id_status_index` (`academy_id`,`status`),
  KEY `quran_trial_requests_teacher_id_status_index` (`teacher_id`,`status`),
  KEY `quran_trial_requests_student_id_status_index` (`student_id`,`status`),
  KEY `quran_trial_requests_created_at_index` (`created_at`),
  CONSTRAINT `quran_trial_requests_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_trial_requests_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_trial_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_trial_requests_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `quran_teacher_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `quran_trial_requests_trial_session_id_foreign` FOREIGN KEY (`trial_session_id`) REFERENCES `quran_sessions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `quran_trial_requests_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recorded_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `recorded_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `grade_level_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `certificate_template_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `certificate_template_style` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'template_1',
  `course_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trailer_video_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_hours` int NOT NULL DEFAULT '0',
  `language` enum('ar','en','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ar',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount_price` decimal(10,2) DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '0',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `enrollment_deadline` timestamp NULL DEFAULT NULL,
  `prerequisites` json DEFAULT NULL,
  `learning_outcomes` json DEFAULT NULL,
  `course_materials` json DEFAULT NULL,
  `materials` json DEFAULT NULL,
  `total_sections` int NOT NULL DEFAULT '0',
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
  KEY `recorded_courses_instructor_id_is_published_index` (`is_published`),
  KEY `recorded_courses_subject_id_grade_level_id_index` (`subject_id`,`grade_level_id`),
  KEY `recorded_courses_status_is_published_index` (`status`,`is_published`),
  KEY `recorded_courses_is_featured_is_published_index` (`is_featured`,`is_published`),
  KEY `recorded_courses_created_at_is_published_index` (`created_at`,`is_published`),
  CONSTRAINT `recorded_courses_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `academic_subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `session_recordings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_recordings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recordable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recordable_id` bigint unsigned NOT NULL,
  `recording_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meeting_room` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('recording','processing','completed','failed','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recording',
  `started_at` timestamp NOT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration` int DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `file_format` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mp4',
  `metadata` json DEFAULT NULL,
  `processing_error` text COLLATE utf8mb4_unicode_ci,
  `processed_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_recordings_recording_id_unique` (`recording_id`),
  KEY `session_recordings_recordable_type_recordable_id_index` (`recordable_type`,`recordable_id`),
  KEY `session_recordings_recordable_type_recordable_id_status_index` (`recordable_type`,`recordable_id`,`status`),
  KEY `session_recordings_status_created_at_index` (`status`,`created_at`),
  KEY `session_recordings_recording_id_index` (`recording_id`),
  KEY `session_recordings_meeting_room_index` (`meeting_room`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `session_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_schedules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `quran_teacher_id` bigint unsigned NOT NULL,
  `quran_subscription_id` bigint unsigned DEFAULT NULL,
  `quran_circle_id` bigint unsigned DEFAULT NULL,
  `schedule_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `schedule_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recurrence_pattern` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `schedule_data` json NOT NULL,
  `session_templates` json NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `max_sessions` int DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `auto_generate` tinyint(1) NOT NULL DEFAULT '1',
  `allow_rescheduling` tinyint(1) NOT NULL DEFAULT '1',
  `reschedule_hours_notice` int NOT NULL DEFAULT '24',
  `sessions_generated` int NOT NULL DEFAULT '0',
  `sessions_completed` int NOT NULL DEFAULT '0',
  `sessions_cancelled` int NOT NULL DEFAULT '0',
  `last_generated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_schedules_schedule_code_unique` (`schedule_code`),
  KEY `session_schedules_quran_subscription_id_foreign` (`quran_subscription_id`),
  KEY `session_schedules_quran_circle_id_foreign` (`quran_circle_id`),
  KEY `session_schedules_created_by_foreign` (`created_by`),
  KEY `session_schedules_updated_by_foreign` (`updated_by`),
  KEY `session_schedules_academy_id_schedule_type_status_index` (`academy_id`,`schedule_type`,`status`),
  KEY `session_schedules_quran_teacher_id_status_index` (`quran_teacher_id`,`status`),
  KEY `session_schedules_start_date_end_date_index` (`start_date`,`end_date`),
  KEY `session_schedules_auto_generate_status_index` (`auto_generate`,`status`),
  CONSTRAINT `session_schedules_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `session_schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `session_schedules_quran_circle_id_foreign` FOREIGN KEY (`quran_circle_id`) REFERENCES `quran_circles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `session_schedules_quran_subscription_id_foreign` FOREIGN KEY (`quran_subscription_id`) REFERENCES `quran_subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `session_schedules_quran_teacher_id_foreign` FOREIGN KEY (`quran_teacher_id`) REFERENCES `quran_teacher_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `session_schedules_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `phone_country_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '+966' COMMENT 'Country calling code for student phone',
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_level_id` bigint unsigned DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `gender` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nationality` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `emergency_contact` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_country_code` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Country calling code for emergency contact',
  `parent_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Parent phone number in E.164 format',
  `parent_phone_country_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '+966' COMMENT 'Country calling code (e.g., +966 for Saudi Arabia)',
  `parent_phone_country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SA' COMMENT 'ISO 3166-1 alpha-2 country code',
  `enrollment_date` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_profiles_student_code_unique` (`student_code`),
  UNIQUE KEY `student_profiles_email_unique` (`email`),
  KEY `student_profiles_grade_level_id_foreign` (`grade_level_id`),
  KEY `student_profiles_user_id_index` (`user_id`),
  KEY `idx_student_profiles_user_grade` (`user_id`,`grade_level_id`),
  KEY `idx_student_profiles_grade_level` (`grade_level_id`),
  CONSTRAINT `student_profiles_grade_level_id_foreign` FOREIGN KEY (`grade_level_id`) REFERENCES `academic_grade_levels` (`id`) ON DELETE SET NULL,
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
DROP TABLE IF EXISTS `student_session_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_session_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `new_memorization_degree` decimal(3,1) DEFAULT NULL COMMENT 'درجة الحفظ الجديد من 0 إلى 10',
  `reservation_degree` decimal(3,1) DEFAULT NULL COMMENT 'درجة المراجعة من 0 إلى 10',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'ملاحظات المعلم على أداء الطالب',
  `meeting_enter_time` timestamp NULL DEFAULT NULL COMMENT 'وقت دخول الطالب للاجتماع',
  `meeting_leave_time` timestamp NULL DEFAULT NULL COMMENT 'وقت خروج الطالب من الاجتماع',
  `actual_attendance_minutes` int NOT NULL DEFAULT '0' COMMENT 'عدد الدقائق الفعلية للحضور',
  `is_late` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'هل تأخر الطالب عن الموعد المحدد',
  `late_minutes` int NOT NULL DEFAULT '0' COMMENT 'عدد دقائق التأخير',
  `attendance_percentage` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'نسبة الحضور من إجمالي وقت الجلسة',
  `meeting_events` json DEFAULT NULL COMMENT 'أحداث الاجتماع (دخول، خروج، انقطاع)',
  `evaluated_at` timestamp NULL DEFAULT NULL COMMENT 'وقت تقييم المعلم',
  `is_calculated` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'هل تم حساب الحضور تلقائياً',
  `manually_evaluated` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'هل تم تعديل البيانات يدوياً',
  `override_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'سبب التعديل اليدوي',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `attendance_status` enum('attended','late','leaved','absent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'absent',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_session_report` (`session_id`,`student_id`),
  KEY `student_session_reports_student_id_foreign` (`student_id`),
  KEY `student_session_reports_academy_id_foreign` (`academy_id`),
  KEY `student_session_reports_session_id_student_id_index` (`session_id`,`student_id`),
  KEY `student_session_reports_teacher_id_academy_id_index` (`teacher_id`,`academy_id`),
  KEY `student_session_reports_evaluated_at_index` (`evaluated_at`),
  CONSTRAINT `student_session_reports_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_session_reports_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `quran_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_session_reports_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_session_reports_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `teacher_earnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_earnings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `teacher_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `session_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `session_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `calculation_method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_snapshot` decimal(10,2) DEFAULT NULL,
  `calculation_metadata` json DEFAULT NULL,
  `earning_month` date NOT NULL,
  `session_completed_at` datetime NOT NULL,
  `calculated_at` datetime NOT NULL,
  `payout_id` bigint unsigned DEFAULT NULL,
  `is_finalized` tinyint(1) NOT NULL DEFAULT '0',
  `is_disputed` tinyint(1) NOT NULL DEFAULT '0',
  `dispute_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session_earning` (`session_type`,`session_id`),
  KEY `idx_teacher_month` (`teacher_type`,`teacher_id`,`earning_month`),
  KEY `idx_session` (`session_type`,`session_id`),
  KEY `idx_academy_month` (`academy_id`,`earning_month`),
  KEY `idx_payout_status` (`is_finalized`,`payout_id`),
  KEY `teacher_earnings_tenant_id_index` (`tenant_id`),
  KEY `teacher_earnings_teacher_poly_idx` (`teacher_type`,`teacher_id`),
  CONSTRAINT `teacher_earnings_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teacher_payouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_payouts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `teacher_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `payout_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payout_month` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sessions_count` int NOT NULL,
  `breakdown` json DEFAULT NULL,
  `status` enum('pending','approved','paid','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_notes` text COLLATE utf8mb4_unicode_ci,
  `paid_by` bigint unsigned DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `payment_method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_notes` text COLLATE utf8mb4_unicode_ci,
  `rejected_by` bigint unsigned DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_monthly_payout` (`teacher_type`,`teacher_id`,`payout_month`,`academy_id`),
  UNIQUE KEY `teacher_payouts_payout_code_unique` (`payout_code`),
  KEY `teacher_payouts_approved_by_foreign` (`approved_by`),
  KEY `teacher_payouts_paid_by_foreign` (`paid_by`),
  KEY `teacher_payouts_rejected_by_foreign` (`rejected_by`),
  KEY `idx_teacher_payout_month` (`teacher_type`,`teacher_id`,`payout_month`),
  KEY `idx_academy_payout_month` (`academy_id`,`payout_month`),
  KEY `teacher_payouts_status_index` (`status`),
  KEY `teacher_payouts_tenant_id_index` (`tenant_id`),
  KEY `teacher_payouts_teacher_poly_idx` (`teacher_type`,`teacher_id`),
  CONSTRAINT `teacher_payouts_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_payouts_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `teacher_payouts_paid_by_foreign` FOREIGN KEY (`paid_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `teacher_payouts_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teacher_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `reviewable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviewable_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `rating` tinyint unsigned NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `is_approved` tinyint(1) NOT NULL DEFAULT '1',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_review` (`reviewable_type`,`reviewable_id`,`student_id`),
  KEY `teacher_reviews_academy_id_foreign` (`academy_id`),
  KEY `teacher_reviews_student_id_foreign` (`student_id`),
  KEY `teacher_reviews_approved_by_foreign` (`approved_by`),
  KEY `teacher_reviews_reviewable_index` (`reviewable_type`,`reviewable_id`),
  CONSTRAINT `teacher_reviews_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_reviews_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `teacher_reviews_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `teacher_video_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_video_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `academy_id` bigint unsigned NOT NULL,
  `preferred_max_participants` int DEFAULT NULL,
  `preferred_video_quality` enum('low','medium','high') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_audio_quality` enum('low','medium','high') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_video_resolution` enum('480p','720p','1080p') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auto_start_recording` tinyint(1) DEFAULT NULL,
  `preferred_recording_layout` enum('grid','speaker','custom') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mute_participants_on_join` tinyint(1) DEFAULT NULL,
  `disable_camera_on_join` tinyint(1) DEFAULT NULL,
  `enable_waiting_room` tinyint(1) DEFAULT NULL,
  `enable_screen_sharing` tinyint(1) DEFAULT NULL,
  `enable_chat` tinyint(1) DEFAULT NULL,
  `preferred_theme` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_background_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_participant_names` tinyint(1) NOT NULL DEFAULT '1',
  `show_time_remaining` tinyint(1) NOT NULL DEFAULT '1',
  `notify_before_session` tinyint(1) NOT NULL DEFAULT '1',
  `notification_minutes_before` int NOT NULL DEFAULT '15',
  `notify_on_late_student` tinyint(1) NOT NULL DEFAULT '1',
  `notify_on_session_end` tinyint(1) NOT NULL DEFAULT '0',
  `notification_methods` json DEFAULT NULL,
  `preferred_earliest_time` time DEFAULT NULL,
  `preferred_latest_time` time DEFAULT NULL,
  `unavailable_days` json DEFAULT NULL,
  `break_minutes_between_sessions` int NOT NULL DEFAULT '5',
  `allow_student_screen_sharing` tinyint(1) NOT NULL DEFAULT '0',
  `allow_student_unmute` tinyint(1) NOT NULL DEFAULT '1',
  `allow_student_camera` tinyint(1) NOT NULL DEFAULT '1',
  `auto_admit_known_students` tinyint(1) NOT NULL DEFAULT '1',
  `always_record_sessions` tinyint(1) NOT NULL DEFAULT '0',
  `save_recordings_locally` tinyint(1) NOT NULL DEFAULT '0',
  `recording_quality_preference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `include_chat_in_recording` tinyint(1) NOT NULL DEFAULT '1',
  `track_student_attendance` tinyint(1) NOT NULL DEFAULT '1',
  `track_session_engagement` tinyint(1) NOT NULL DEFAULT '1',
  `generate_session_reports` tinyint(1) NOT NULL DEFAULT '1',
  `share_reports_with_parents` tinyint(1) NOT NULL DEFAULT '0',
  `custom_meeting_templates` json DEFAULT NULL,
  `enable_breakout_rooms` tinyint(1) NOT NULL DEFAULT '0',
  `enable_whiteboard` tinyint(1) NOT NULL DEFAULT '0',
  `keyboard_shortcuts` json DEFAULT NULL,
  `adaptive_bitrate` tinyint(1) NOT NULL DEFAULT '1',
  `echo_cancellation` tinyint(1) NOT NULL DEFAULT '1',
  `noise_suppression` tinyint(1) NOT NULL DEFAULT '1',
  `max_video_participants` int NOT NULL DEFAULT '4',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_video_settings_user_id_academy_id_unique` (`user_id`,`academy_id`),
  KEY `teacher_video_settings_academy_id_foreign` (`academy_id`),
  KEY `teacher_video_settings_user_id_academy_id_index` (`user_id`,`academy_id`),
  CONSTRAINT `teacher_video_settings_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_video_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
DROP TABLE IF EXISTS `typing_indicators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `typing_indicators` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `conversation_id` bigint unsigned DEFAULT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `started_at` timestamp NOT NULL,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `typing_indicators_user_id_conversation_id_index` (`user_id`,`conversation_id`),
  KEY `typing_indicators_user_id_group_id_index` (`user_id`,`group_id`),
  KEY `typing_indicators_expires_at_index` (`expires_at`),
  CONSTRAINT `typing_indicators_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `device_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `gender` enum('male','female') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_auto_record` tinyint(1) NOT NULL DEFAULT '0',
  `teacher_default_duration` int NOT NULL DEFAULT '60',
  `teacher_meeting_prep_minutes` int NOT NULL DEFAULT '60',
  `teacher_send_reminders` tinyint(1) NOT NULL DEFAULT '1',
  `teacher_reminder_times` json DEFAULT NULL,
  `allow_calendar_conflicts` tinyint(1) NOT NULL DEFAULT '0',
  `calendar_visibility` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `notify_on_student_join` tinyint(1) NOT NULL DEFAULT '1',
  `notify_on_session_end` tinyint(1) NOT NULL DEFAULT '0',
  `notification_method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `meeting_preferences` json DEFAULT NULL,
  `auto_create_meetings` tinyint(1) NOT NULL DEFAULT '1',
  `meeting_prep_minutes` int NOT NULL DEFAULT '60',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_country_code` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '+966' COMMENT 'Country calling code (e.g., +966 for Saudi Arabia)',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_type` enum('student','quran_teacher','academic_teacher','parent','supervisor','admin','super_admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'student',
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
  `chat_settings` json DEFAULT NULL COMMENT 'Chat notification and preference settings',
  `last_typing_at` timestamp NULL DEFAULT NULL COMMENT 'Last time user was typing',
  `last_seen_at` timestamp NULL DEFAULT NULL COMMENT 'Last time user was seen online',
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_academy_unique` (`email`,`academy_id`),
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
  KEY `users_email_verified_idx` (`email`,`email_verified_at`),
  KEY `users_academy_type_idx` (`academy_id`,`user_type`),
  KEY `users_phone_verified_idx` (`phone`,`phone_verified_at`),
  KEY `users_last_seen_at_index` (`last_seen_at`),
  CONSTRAINT `users_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `users_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `video_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academy_id` bigint unsigned NOT NULL,
  `auto_create_meetings` tinyint(1) NOT NULL DEFAULT '1',
  `create_meetings_minutes_before` int NOT NULL DEFAULT '15',
  `auto_end_meetings` tinyint(1) NOT NULL DEFAULT '1',
  `auto_end_minutes_after` int NOT NULL DEFAULT '15',
  `default_max_participants` int NOT NULL DEFAULT '50',
  `default_video_quality` enum('low','medium','high') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'high',
  `default_audio_quality` enum('low','medium','high') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'high',
  `enable_recording_by_default` tinyint(1) NOT NULL DEFAULT '0',
  `enable_screen_sharing` tinyint(1) NOT NULL DEFAULT '1',
  `enable_chat` tinyint(1) NOT NULL DEFAULT '1',
  `enable_noise_cancellation` tinyint(1) NOT NULL DEFAULT '1',
  `default_recording_layout` enum('grid','speaker','custom') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'grid',
  `recording_storage_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'local',
  `recording_storage_config` json DEFAULT NULL,
  `auto_cleanup_recordings` tinyint(1) NOT NULL DEFAULT '0',
  `cleanup_recordings_after_days` int NOT NULL DEFAULT '30',
  `meeting_theme` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'light',
  `primary_color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#3B82F6',
  `logo_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_css` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `show_participant_count` tinyint(1) NOT NULL DEFAULT '1',
  `show_recording_indicator` tinyint(1) NOT NULL DEFAULT '1',
  `default_video_resolution` enum('480p','720p','1080p') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '720p',
  `default_video_fps` int NOT NULL DEFAULT '30',
  `default_audio_bitrate` int NOT NULL DEFAULT '64',
  `default_video_bitrate` int NOT NULL DEFAULT '1500',
  `notify_on_meeting_start` tinyint(1) NOT NULL DEFAULT '1',
  `notify_on_participant_join` tinyint(1) NOT NULL DEFAULT '0',
  `notify_on_recording_ready` tinyint(1) NOT NULL DEFAULT '1',
  `notification_channels` json DEFAULT NULL,
  `require_approval_to_join` tinyint(1) NOT NULL DEFAULT '0',
  `enable_waiting_room` tinyint(1) NOT NULL DEFAULT '0',
  `mute_participants_on_entry` tinyint(1) NOT NULL DEFAULT '0',
  `disable_camera_on_entry` tinyint(1) NOT NULL DEFAULT '0',
  `integration_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `webhook_endpoints` json DEFAULT NULL,
  `api_rate_limit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1000/hour',
  `earliest_meeting_time` time NOT NULL DEFAULT '06:00:00',
  `latest_meeting_time` time NOT NULL DEFAULT '23:00:00',
  `blocked_days` json DEFAULT NULL,
  `max_daily_meetings` int NOT NULL DEFAULT '20',
  `max_concurrent_meetings` int NOT NULL DEFAULT '5',
  `enable_analytics` tinyint(1) NOT NULL DEFAULT '1',
  `track_attendance` tinyint(1) NOT NULL DEFAULT '1',
  `generate_reports` tinyint(1) NOT NULL DEFAULT '1',
  `keep_analytics_days` int NOT NULL DEFAULT '365',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `video_settings_academy_id_index` (`academy_id`),
  CONSTRAINT `video_settings_academy_id_foreign` FOREIGN KEY (`academy_id`) REFERENCES `academies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wire_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wire_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actionable_id` bigint unsigned NOT NULL,
  `actionable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_id` bigint unsigned NOT NULL,
  `actor_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Some additional information about the action',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wire_actions_actionable_id_actionable_type_index` (`actionable_id`,`actionable_type`),
  KEY `wire_actions_actor_id_actor_type_index` (`actor_id`,`actor_type`),
  KEY `wire_actions_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wire_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wire_attachments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attachable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachable_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wire_attachments_attachable_type_attachable_id_index` (`attachable_type`,`attachable_id`),
  KEY `wire_attachments_attachable_id_attachable_type_index` (`attachable_id`,`attachable_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wire_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wire_conversations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Private is 1-1 , group or channel',
  `disappearing_started_at` timestamp NULL DEFAULT NULL,
  `disappearing_duration` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wire_conversations_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wire_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wire_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `avatar_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'private',
  `allow_members_to_send_messages` tinyint(1) NOT NULL DEFAULT '1',
  `allow_members_to_add_others` tinyint(1) NOT NULL DEFAULT '1',
  `allow_members_to_edit_group_info` tinyint(1) NOT NULL DEFAULT '0',
  `admins_must_approve_new_members` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'when turned on, admins must approve anyone who wants to join group',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wire_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wire_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `sendable_id` bigint unsigned NOT NULL,
  `sendable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reply_id` bigint unsigned DEFAULT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `kept_at` timestamp NULL DEFAULT NULL COMMENT 'filled when a message is kept from disappearing',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wire_messages_reply_id_foreign` (`reply_id`),
  KEY `wire_messages_conversation_id_index` (`conversation_id`),
  KEY `wire_messages_sendable_id_sendable_type_index` (`sendable_id`,`sendable_type`),
  CONSTRAINT `wire_messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `wire_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wire_messages_reply_id_foreign` FOREIGN KEY (`reply_id`) REFERENCES `wire_messages` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wire_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wire_participants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint unsigned NOT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `participantable_id` bigint unsigned NOT NULL,
  `participantable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exited_at` timestamp NULL DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `conversation_cleared_at` timestamp NULL DEFAULT NULL,
  `conversation_deleted_at` timestamp NULL DEFAULT NULL,
  `conversation_read_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conv_part_id_type_unique` (`conversation_id`,`participantable_id`,`participantable_type`),
  KEY `wire_participants_role_index` (`role`),
  KEY `wire_participants_exited_at_index` (`exited_at`),
  KEY `wire_participants_conversation_cleared_at_index` (`conversation_cleared_at`),
  KEY `wire_participants_conversation_deleted_at_index` (`conversation_deleted_at`),
  KEY `wire_participants_conversation_read_at_index` (`conversation_read_at`),
  CONSTRAINT `wire_participants_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `wire_conversations` (`id`) ON DELETE CASCADE
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
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2024_01_15_100000_add_google_oauth_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2024_01_15_110000_create_google_tokens_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2024_01_15_120000_create_session_schedules_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2024_01_15_130000_add_google_meet_fields_to_quran_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2024_01_15_140000_create_platform_google_accounts_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2024_01_15_150000_create_academy_google_settings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2024_01_15_160000_add_teacher_google_preferences_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2024_11_01_000001_create_wirechat_conversations_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2024_11_01_000002_create_wirechat_attachments_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2024_11_01_000003_create_wirechat_messages_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2024_11_01_000004_create_wirechat_participants_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2024_11_01_000006_create_wirechat_actions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2024_11_01_000007_create_wirechat_groups_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2024_11_11_000000_remove_package_type_from_academic_packages_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2024_12_20_000001_refactor_quran_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2024_12_20_000002_clear_existing_sessions',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2025_01_11_000000_remove_trial_sessions_and_expires_at_from_quran_subscriptions',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2025_01_13_123000_update_quran_trial_requests_current_level_enum',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2025_01_16_120000_fix_group_circle_sessions_issue',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2025_08_03_201546_improve_student_profile_data_integrity',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2025_08_03_213940_create_quran_trial_requests_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2025_08_04_160608_add_offers_trial_sessions_to_quran_teacher_profiles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2025_08_05_172818_create_jobs_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2025_08_05_232739_create_quran_individual_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2025_08_05_232745_create_quran_circle_schedules_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2025_08_05_232821_modify_quran_circles_remove_schedule_fields',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2025_08_05_232840_modify_quran_sessions_for_new_system',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2025_08_06_092633_make_scheduled_at_nullable_in_quran_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2025_08_06_144048_add_monthly_sessions_count_to_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2025_08_10_034731_alter_google_client_secret_column_size',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2025_08_10_104402_add_meeting_fields_to_sessions_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2025_08_10_114420_create_video_settings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2025_08_10_114449_create_teacher_video_settings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2025_08_10_173354_add_unscheduled_status_to_quran_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2025_08_11_115105_add_scheduling_columns_to_quran_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2025_08_11_120944_add_schedule_period_to_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2025_08_11_165733_add_trial_request_id_to_quran_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2025_08_11_231447_add_quran_paper_tracking_fields',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2025_08_13_111447_create_quran_session_attendances_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2025_08_13_112249_add_papers_memorized_today_to_quran_session_attendances_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2025_08_13_120000_add_subscription_counted_to_quran_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2025_08_13_130000_fix_session_statuses_remove_template',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2025_08_14_145726_fix_quran_sessions_status_enum_values',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2025_08_14_145907_make_scheduled_at_nullable_in_quran_sessions',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2025_08_19_200944_add_livekit_to_meeting_source_enum',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2025_08_25_215343_remove_instructor_and_lessons_fields_from_recorded_courses_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2025_08_25_220613_remove_order_field_from_lessons_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2025_08_26_164357_update_recorded_courses_table_optimize_fields',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2025_08_26_164429_fix_livewire_file_upload_configuration',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2025_08_26_203920_remove_duplicate_level_field_from_recorded_courses',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2025_08_26_204319_remove_status_and_is_free_fields_from_recorded_courses',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2025_08_26_205124_remove_completion_certificate_and_reorganize_course_fields',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2025_08_27_130620_create_media_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2025_08_27_133405_fix_media_table_charset',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2025_08_27_160723_remove_unused_lesson_fields_from_lessons_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2025_08_27_164719_make_video_url_nullable_in_lessons_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2025_08_27_170307_clean_up_lesson_video_urls_with_encoding_issues',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2025_08_27_234837_add_meeting_config_fields_to_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2025_08_27_234843_add_meeting_config_fields_to_quran_individual_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2025_08_27_235057_add_missing_index_to_quran_individual_circles',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2025_08_28_001220_create_meeting_attendances_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2025_08_28_205348_remove_educational_content_fields_from_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2025_08_28_210222_fix_status_column_in_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2025_08_28_211650_add_admin_notes_to_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2025_08_28_225300_add_learning_objectives_to_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2025_08_28_231413_add_teacher_monthly_revenue_and_cleanup_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2025_08_30_014343_add_schedule_fields_back_to_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2025_08_30_014618_add_teacher_monthly_revenue_field_to_quran_circles',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2025_08_30_033039_create_quran_session_homeworks_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2025_08_30_033058_create_quran_homework_assignments_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2025_08_30_034452_enhance_quran_session_attendances_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2025_08_30_042802_add_homework_toggle_fields_to_quran_session_homeworks_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2025_08_30_131942_drop_notes_columns_from_quran_session_homeworks_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2025_08_30_144954_create_student_session_reports_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2025_08_30_151106_create_quran_session_homework_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2025_09_01_150243_create_academic_individual_lessons_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2025_09_01_150246_create_academic_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2025_09_01_151428_create_course_recordings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2025_09_01_174345_remove_unnecessary_fields_from_academic_grade_levels_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2025_09_01_180137_create_academic_packages_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2025_09_01_182713_add_package_id_to_academic_subscriptions',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2025_09_01_183903_remove_teacher_qualifications_from_academic_packages',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2025_09_01_184835_create_academic_session_reports_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2025_09_01_194509_create_academic_teacher_pivot_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2025_09_01_195157_fix_student_profile_grade_level_foreign_key',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2025_09_01_201722_create_chat_groups_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2025_09_02_004933_remove_subjects_grades_from_academic_packages',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2025_09_02_005124_add_packages_field_to_academic_teacher_profiles',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2025_09_02_005755_add_default_packages_to_academic_settings',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2025_09_02_135431_add_session_prices_to_quran_teacher_profiles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2025_09_02_135602_remove_graduation_year_and_specialization_from_academic_teacher_profiles',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2025_09_02_140914_add_text_fields_to_academic_teacher_profiles',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2025_09_02_142101_add_text_fields_to_academic_subscriptions',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2025_09_02_143939_make_subject_and_grade_level_ids_nullable_in_academic_subscriptions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2025_09_02_164924_add_unscheduled_status_to_academic_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2025_09_02_185750_add_academic_session_type_to_meeting_attendances',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2025_09_03_173337_create_business_service_categories_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2025_09_03_173347_create_business_service_requests_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2025_09_03_173354_create_portfolio_items_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2025_09_04_204718_create_course_sections_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2025_09_04_210720_create_service_requests_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2025_09_05_000622_add_duration_minutes_to_lessons_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2025_09_06_010032_remove_counts_toward_subscription_from_quran_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2025_09_06_011855_create_test_livekit_session',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2025_09_08_134159_fix_interactive_courses_grade_level_foreign_key',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2025_09_08_185618_remove_teacher_response_fields_from_quran_trial_requests_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2025_11_10_000000_create_academy_settings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2025_11_10_000001_remove_academic_status_and_graduation_date_from_student_profiles',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2025_11_10_021356_add_foreign_key_constraints_for_data_integrity',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2025_11_10_021512_add_critical_database_indexes_for_performance',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2025_11_10_062604_create_academy_settings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2025_11_10_063351_create_academic_session_attendances_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2025_11_10_063633_enhance_interactive_session_attendances_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2025_11_10_063717_add_attendance_config_to_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2025_11_10_063824_add_attendance_config_to_academic_subscriptions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2025_11_10_063908_add_attendance_config_to_interactive_courses_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2025_11_10_072753_add_homework_fields_to_interactive_course_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2025_11_10_072807_add_payment_configuration_to_interactive_courses_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2025_11_10_072824_create_interactive_course_homework_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2025_11_10_074739_create_interactive_course_progress_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2025_11_10_115221_add_multilingual_fields_to_interactive_courses_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2025_11_10_122304_add_difficulty_and_content_fields_to_interactive_courses_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2025_11_10_130000_create_academic_homework_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2025_11_10_130000_remove_language_field_from_courses',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2025_11_10_130100_create_academic_homework_submissions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2025_11_10_133334_make_subject_id_nullable_in_academic_progress_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2025_11_10_140000_create_academic_progresses_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2025_11_10_add_country_to_academies_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2025_11_10_add_missing_academy_settings_columns',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2025_11_10_update_max_session_duration_defaults',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2025_11_11_000000_remove_meeting_config_fields_from_quran_circles_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2025_11_11_000000_update_academic_grade_levels_structure',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2025_11_11_120000_remove_subjects_fields_and_add_admin_notes',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2025_11_11_201626_phase1_critical_cleanup_unused_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2025_11_11_201745_remove_google_fields_from_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2025_11_11_202401_phase2_drop_duplicate_teacher_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2025_11_11_203221_phase3_drop_unused_model_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2025_11_11_220307_create_interactive_session_attendances_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2025_11_11_220308_create_interactive_session_reports_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2025_11_11_221457_create_homework_submissions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2025_11_11_233802_add_deleted_at_to_interactive_course_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2025_11_11_add_buffer_minutes_column',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2025_11_12_002502_update_quran_circles_teacher_id_to_user_id',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2025_11_12_141114_restore_session_duration_to_quran_circles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2025_11_12_add_academic_settings_to_academies',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2025_11_12_enhance_chat_system',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2025_11_12_remove_session_duration_from_circles',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2025_11_13_152322_fix_wire_attachments_utf8_encoding',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2025_11_13_200116_add_heartbeat_to_meeting_attendances',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2025_11_14_002020_convert_academy_colors_to_enum',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2025_11_14_151336_create_meeting_attendance_events_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2025_11_14_220111_cleanup_meeting_attendances_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2025_11_15_130326_update_meeting_attendance_status_enum_values',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2025_11_15_131810_update_student_session_reports_attendance_status_enum',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2025_11_15_132037_update_academic_interactive_reports_attendance_enum',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2025_11_15_132933_remove_connection_quality_score_from_all_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2025_11_15_143619_remove_template_columns_from_quran_sessions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2025_11_15_231518_remove_individual_quran_circles_system',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2025_11_16_000150_remove_duplicate_meeting_fields_from_quran_trial_requests_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2025_11_16_113628_enhance_notifications_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2025_11_17_173206_add_lifetime_columns_to_quran_individual_circles',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2025_11_17_173252_add_indexes_to_quran_progress',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2025_11_17_180148_drop_verse_columns_from_quran_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2025_11_17_190605_drop_quran_homework_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2025_11_17_204644_add_panel_opened_at_to_notifications_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2025_11_17_232454_add_gender_to_users_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2025_11_18_003906_create_course_reviews_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2025_11_18_004041_create_course_quizzes_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2025_11_18_004258_migrate_subjects_to_academic_subjects_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2025_11_18_121419_update_subject_foreign_keys_to_academic_subjects',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2025_11_18_150930_add_gender_to_academic_teacher_profiles_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2025_11_19_183923_remove_deprecated_fields_and_add_missing_fields_to_academic_sessions',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2025_11_20_140800_remove_participation_degree_from_academic_session_reports_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2025_11_20_234001_add_certificate_fields_to_courses_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2025_11_20_234001_add_certificate_fields_to_subscriptions_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2025_11_20_234001_create_certificates_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2025_11_21_160428_remove_unused_fields_from_base_session_tables',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2025_11_21_160453_remove_unused_fields_from_quran_sessions',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2025_11_21_160537_remove_quality_metrics_from_quran_session_attendances',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2025_11_21_160555_remove_unused_fields_from_academic_sessions_and_add_homework_assigned',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2025_11_21_160622_refactor_interactive_course_sessions_for_consistency',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2025_11_22_010613_add_recording_enabled_to_interactive_courses_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2025_11_22_012220_add_lesson_content_to_interactive_course_sessions_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2025_11_22_012220_add_lesson_content_to_quran_sessions_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2025_11_22_013500_remove_session_notes_and_teacher_feedback_from_academic_sessions',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2025_11_23_070747_simplify_interactive_session_reports_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2025_11_23_073620_drop_progress_tables',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2025_11_23_101910_create_quizzes_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2025_11_23_101911_create_quiz_questions_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2025_11_23_101912_create_quiz_assignments_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2025_11_23_101915_create_quiz_attempts_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2025_11_29_122520_create_permission_tables',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2025_11_29_133645_simplify_academic_subjects_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2025_11_29_134413_create_academic_subject_grade_levels_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2025_11_30_122946_create_session_recordings_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2025_11_30_132400_modify_academies_table_for_gradient_and_favicon',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2025_12_01_135639_convert_certificate_template_style_to_varchar',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2025_12_01_155343_add_base_subscription_fields_to_all_subscriptions',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2025_12_01_161306_add_soft_deletes_to_subscription_tables',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2025_12_01_195014_create_teacher_reviews_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2025_12_01_195053_add_review_stats_to_interactive_courses',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2025_12_01_195053_add_total_reviews_to_teacher_profiles',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2025_12_01_195053_make_course_reviews_polymorphic',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2025_12_01_200557_add_payment_intent_fields_to_payments_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2025_12_01_200557_create_payment_audit_logs_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2025_12_01_200557_create_payment_webhook_events_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2025_12_01_220858_add_missing_base_subscription_columns_to_academic_subscriptions',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2025_12_01_221424_add_base_pricing_columns_to_academic_subscriptions',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2025_12_01_224353_make_next_billing_date_nullable_in_academic_subscriptions',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2025_12_01_224620_make_optional_fields_nullable_in_academic_subscriptions',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (311,'2025_12_02_113407_fix_certificate_template_style_columns',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (312,'2025_12_03_004314_populate_standardized_date_fields_in_academic_subscriptions',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (313,'2025_12_03_163824_create_teacher_earnings_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (314,'2025_12_03_163858_create_teacher_payouts_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2025_12_03_170729_add_design_settings_to_academies_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2025_12_03_182631_add_subheadings_to_academies_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2025_12_04_000001_extend_homework_submissions_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (318,'2025_12_04_125148_remove_parent_pivot_permissions_from_parent_student_relationships',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2025_12_04_134227_cleanup_parent_profiles_table_and_add_relationship_type_enum',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2025_12_04_135817_add_relationship_type_back_to_parent_profiles',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (321,'2025_12_04_171204_add_parent_phone_fields_to_student_profiles_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (322,'2025_12_04_175034_add_phone_country_code_to_all_user_tables',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2025_12_05_155446_change_users_email_to_composite_unique',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (324,'2025_12_05_165230_change_parent_profiles_email_to_composite_unique',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (325,'2025_12_06_181801_add_base_session_fields_to_interactive_course_sessions_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (326,'2025_12_06_200000_add_tenant_id_and_constraints_to_teacher_tables',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (327,'2025_12_06_200001_add_missing_database_indexes',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (328,'2025_12_06_200002_add_soft_deletes_to_critical_models',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (329,'2025_12_08_182355_add_interactive_to_session_type_enum_in_meeting_attendances',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (330,'2025_12_14_153605_update_interactive_course_sessions_status_enum',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (331,'2025_12_15_134738_add_hero_image_to_academies_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2025_12_16_090253_create_platform_settings_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2025_12_16_173644_drop_max_students_per_session_from_academic_packages_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2025_12_16_180017_cleanup_education_fields_in_academic_teacher_profiles',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2025_12_21_000001_add_deleted_at_to_academies_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2025_12_21_110256_remove_unused_fields_from_supervisor_profiles_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2025_12_21_111359_add_deleted_at_to_users_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2025_12_21_161126_create_personal_access_tokens_table',25);
