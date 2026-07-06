-- MySQL dump 10.13  Distrib 8.4.8, for Linux (x86_64)
--
-- Host: scholarpoint-db.cha6geg2ilp0.ap-south-1.rds.amazonaws.com    Database: school_portal
-- ------------------------------------------------------
-- Server version	8.0.40

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
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '';

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int DEFAULT NULL,
  `teacher_name` varchar(100) DEFAULT NULL,
  `announcement_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,2,'Aditya','Hii','2026-05-21 07:54:01'),(2,3,'soma','hello student','2026-05-23 11:19:40'),(3,8,'Khot','hello','2026-05-26 07:07:10');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assignments`
--

DROP TABLE IF EXISTS `assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assignments`
--

LOCK TABLES `assignments` WRITE;
/*!40000 ALTER TABLE `assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `class_date` date NOT NULL,
  `class_label` varchar(150) NOT NULL DEFAULT 'Class',
  `status` enum('present','absent') NOT NULL DEFAULT 'present',
  `marked_by` varchar(100) NOT NULL,
  `marked_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `subject_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_att` (`student_id`,`class_date`,`class_label`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,4,'Rahul Patil','2026-05-26','SE','present','Khot','2026-05-26 06:43:29',NULL),(2,1,'sandip raut','2026-05-26','SE','present','Khot','2026-05-26 06:43:29',NULL),(3,1,'sandip raut','2026-05-26','Lecture','present','Khot','2026-05-26 10:02:55',2),(4,4,'Rahul Patil','2026-05-26','Lecture','present','Khot','2026-05-26 10:02:55',2),(5,9,'sanket rajinj','2026-05-26','Lecture','absent','Khot','2026-05-26 10:02:55',2),(6,1,'sandip raut','2026-05-28','Lecture','present','Khot','2026-05-28 06:54:41',2),(7,4,'Rahul Patil','2026-05-28','Lecture','present','Khot','2026-05-28 06:54:41',2),(8,9,'sanket rajinj','2026-05-28','Lecture','present','Khot','2026-05-28 06:54:41',2);
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class_name` varchar(50) NOT NULL,
  `description` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'SE','Second Year Electronics Engineering','2026-05-25 11:27:08'),(2,'TE','Third Year Electronics Engineering','2026-05-25 11:27:08'),(3,'BE','Final Year Electronics Engineering','2026-05-25 11:27:08');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_marks`
--

DROP TABLE IF EXISTS `exam_marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_marks` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `subject` varchar(100) NOT NULL,
  `marks` int DEFAULT NULL,
  `total_marks` int DEFAULT '100',
  `exam_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_marks`
--

LOCK TABLES `exam_marks` WRITE;
/*!40000 ALTER TABLE `exam_marks` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_marks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_by` varchar(100) DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meetings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `teacher_name` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `meet_date` date NOT NULL,
  `meet_time` time NOT NULL,
  `room_name` varchar(200) DEFAULT NULL,
  `is_live` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `subject_id` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meetings`
--

LOCK TABLES `meetings` WRITE;
/*!40000 ALTER TABLE `meetings` DISABLE KEYS */;
INSERT INTO `meetings` VALUES (1,2,'Aditya','Data structure','Hsg','2026-05-21','13:24:00','eduportal-data-structure-7183d885',1,'2026-05-21 07:54:47',NULL),(2,8,'Khot','chap 3','session start','2026-05-28','12:19:00','SP-D7ACFF3C',0,'2026-05-28 06:47:56',2);
/*!40000 ALTER TABLE `meetings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject_announcements`
--

DROP TABLE IF EXISTS `subject_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subject_announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `announcement_text` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `subject_announcements_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject_announcements`
--

LOCK TABLES `subject_announcements` WRITE;
/*!40000 ALTER TABLE `subject_announcements` DISABLE KEYS */;
INSERT INTO `subject_announcements` VALUES (1,3,6,'come to class room 45','2026-05-26 06:05:26');
/*!40000 ALTER TABLE `subject_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject_homework`
--

DROP TABLE IF EXISTS `subject_homework`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subject_homework` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `student_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `subject_homework_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `subject_homework_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject_homework`
--

LOCK TABLES `subject_homework` WRITE;
/*!40000 ALTER TABLE `subject_homework` DISABLE KEYS */;
/*!40000 ALTER TABLE `subject_homework` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject_materials`
--

DROP TABLE IF EXISTS `subject_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subject_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `uploaded_by` int NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `subject_materials_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject_materials`
--

LOCK TABLES `subject_materials` WRITE;
/*!40000 ALTER TABLE `subject_materials` DISABLE KEYS */;
/*!40000 ALTER TABLE `subject_materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `subject_name` varchar(100) NOT NULL,
  `subject_code` varchar(20) DEFAULT NULL,
  `class_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `class_id` (`class_id`),
  CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,'Digital Circuits','DEC01',1,'2026-05-26 04:36:23'),(2,'Signal and System','SE02',1,'2026-05-26 05:21:24'),(3,'Communication System','SE03',1,'2026-05-26 05:22:05'),(4,'Object Oriented Programming','EC04',1,'2026-05-26 05:22:54'),(5,'ADBMS','TE01',2,'2026-05-26 06:35:59'),(6,'Embedded Processors and Applications','TE02',2,'2026-05-26 10:07:42'),(7,'Software Engineering and Project Management','TE03',2,'2026-05-26 10:08:40'),(8,'Computer Network and Security','TE04',2,'2026-05-26 10:09:27'),(9,'VLSI','BE01',3,'2026-05-26 10:10:19'),(10,'AIML','BE02',3,'2026-05-26 10:10:35'),(11,'STQA','BE03',3,'2026-05-26 10:11:05'),(12,'Wireless Sensor Networks','BE04',3,'2026-05-26 10:12:08');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_subjects`
--

DROP TABLE IF EXISTS `teacher_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `assigned_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_assignment` (`teacher_id`,`subject_id`),
  KEY `subject_id` (`subject_id`),
  CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`),
  CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_subjects`
--

LOCK TABLES `teacher_subjects` WRITE;
/*!40000 ALTER TABLE `teacher_subjects` DISABLE KEYS */;
INSERT INTO `teacher_subjects` VALUES (1,2,1,'2026-05-26 04:36:31'),(2,7,4,'2026-05-26 05:26:48'),(3,8,2,'2026-05-26 05:27:03'),(4,6,3,'2026-05-26 05:27:16'),(5,7,5,'2026-05-26 06:36:15'),(7,8,6,'2026-05-26 10:13:15'),(8,6,8,'2026-05-26 10:13:35'),(9,2,7,'2026-05-26 10:13:55'),(10,7,10,'2026-05-26 10:14:13'),(11,8,9,'2026-05-26 10:14:22'),(12,6,11,'2026-05-26 10:14:41'),(13,2,12,'2026-05-26 10:14:55');
/*!40000 ALTER TABLE `teacher_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','teacher') DEFAULT 'student',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `class_id` int DEFAULT NULL,
  `status` enum('pending','approved') DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'sandip','raut','sandipraut3652@gmail.com','$2y$12$LVzuG9R4fkgq9PoKs8Fi9O3diDtBdor4mhE.GREi/TY9OKNC5e./u','student','2026-05-21 07:34:53',1,'approved'),(2,'Aditya','Vitkar','adityavitkar77@gmail.com','$2y$12$WOaprKawmnAcd9k/d7lLt.C4HPuocPgQMAbwM0pu7TjBDnfwya8lu','teacher','2026-05-21 07:52:34',NULL,'approved'),(4,'Rahul','Patil','rahulpatil12@gmail.com','$2y$12$jKcok02nHWT5wEPprUcEie6kOXiNJ9u/8YgENV60qdR4V31BMveh.','student','2026-05-26 04:29:31',1,'approved'),(6,'satish','shelke','satishraje@gmail.com','$2y$12$/7YybBkUMZpv0N1dzAGNsuP.bK5nB//HPfD9n4DVwmS6iq2D9JBMq','teacher','2026-05-26 05:11:02',NULL,'approved'),(7,'Date','mam','date@gmail.com','$2y$12$ibquFMBwZBwZtkqYtk5oQuETj6IQw00RWimpdnONR0UQwdICzHITG','teacher','2026-05-26 05:24:53',NULL,'approved'),(8,'Khot','mam','khot@gmail.com','$2y$12$scwaIlW2x9xi60IWlUSI6OaORWAUhqiZB7MnVoe64feYQcDMwH4xG','teacher','2026-05-26 05:25:24',NULL,'approved'),(9,'sanket','rajinj','sanku1@gmail.com','$2y$12$c0EwO8pve1xB9X19q8Unlu/Atc4r0E9AMbViQ03BMdli9SB5uDy7O','student','2026-05-26 07:05:25',1,'approved'),(10,'Amar','Vitkar','amar22@gmail.com','$2y$12$QLNomuTDEGRuDl.lFiTbyOwbz6bdrvy/1CYHfAylok5UWIi2Lqjf.','student','2026-05-26 10:24:17',3,'approved'),(11,'XYZ','WWW','abcd@gmail.com','$2y$12$FLu9dFTVqqMmtfwyXQ/T6u7v1fXd4QzbFIUgWqjC4zO1is15XBBZa','student','2026-05-29 09:47:28',2,'pending');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-05 10:41:40
