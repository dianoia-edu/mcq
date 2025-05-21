-- MySQL dump 10.13  Distrib 8.0.42, for Linux (x86_64)
--
-- Host: localhost    Database: mcq_test_system
-- ------------------------------------------------------
-- Server version	8.0.42-0ubuntu0.22.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `daily_attempts`
--

DROP TABLE IF EXISTS `daily_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `test_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_identifier` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempt_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attempt` (`test_id`,`student_identifier`,`attempt_date`),
  KEY `idx_attempt_date` (`attempt_date`),
  CONSTRAINT `daily_attempts_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `test_attempts`
--

DROP TABLE IF EXISTS `test_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `test_attempts_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`)
) ENGINE=InnoDB AUTO_INCREMENT=494 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `test_statistics`
--

DROP TABLE IF EXISTS `test_statistics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_statistics` (
  `test_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts_count` int DEFAULT '0',
  `average_percentage` decimal(5,2) DEFAULT NULL,
  `average_duration` int DEFAULT NULL,
  `last_attempt_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`test_id`),
  CONSTRAINT `test_statistics_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tests`
--

DROP TABLE IF EXISTS `tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'mcq_test_system'
--

--
-- Dumping routines for database 'mcq_test_system'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-21 18:05:11
