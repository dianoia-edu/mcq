-- Sauberes DB-Schema OHNE Daten für neue Instanzen
-- Erstellt am: 2025-01-09

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabelle: tests
DROP TABLE IF EXISTS `tests`;
CREATE TABLE `tests` (
  `test_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `access_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_count` int NOT NULL,
  `answer_count` int NOT NULL,
  `answer_type` enum('single','multiple','mixed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`test_id`),
  KEY `idx_access_code` (`access_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle: test_attempts  
DROP TABLE IF EXISTS `test_attempts`;
CREATE TABLE `test_attempts` (
  `attempt_id` int NOT NULL AUTO_INCREMENT,
  `test_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `xml_file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `points_achieved` int NOT NULL,
  `points_maximum` int NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `grade` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`attempt_id`),
  KEY `idx_test_student` (`test_id`,`student_name`),
  CONSTRAINT `test_attempts_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle: test_statistics
DROP TABLE IF EXISTS `test_statistics`;
CREATE TABLE `test_statistics` (
  `test_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts_count` int DEFAULT '0',
  `average_percentage` decimal(5,2) DEFAULT NULL,
  `average_duration` int DEFAULT NULL,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`test_id`),
  CONSTRAINT `test_statistics_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle: daily_attempts
DROP TABLE IF EXISTS `daily_attempts`;
CREATE TABLE `daily_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_identifier` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempt_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attempt` (`test_id`,`student_identifier`,`attempt_date`),
  KEY `idx_attempt_date` (`attempt_date`),
  CONSTRAINT `daily_attempts_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
