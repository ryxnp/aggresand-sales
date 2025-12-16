-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: aggresand_db
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Temporary view structure for view `active_company`
--

DROP TABLE IF EXISTS `active_company`;
/*!50001 DROP VIEW IF EXISTS `active_company`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `active_company` AS SELECT 
 1 AS `company_id`,
 1 AS `company_name`,
 1 AS `address`,
 1 AS `contact_no`,
 1 AS `email`,
 1 AS `status`,
 1 AS `is_deleted`,
 1 AS `date_created`,
 1 AS `date_edited`,
 1 AS `created_by`,
 1 AS `edited_by`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `active_customer`
--

DROP TABLE IF EXISTS `active_customer`;
/*!50001 DROP VIEW IF EXISTS `active_customer`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `active_customer` AS SELECT 
 1 AS `customer_id`,
 1 AS `company_id`,
 1 AS `contractor_id`,
 1 AS `site_id`,
 1 AS `customer_name`,
 1 AS `contact_no`,
 1 AS `email`,
 1 AS `address`,
 1 AS `status`,
 1 AS `is_deleted`,
 1 AS `date_created`,
 1 AS `date_edited`,
 1 AS `created_by`,
 1 AS `edited_by`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `active_delivery`
--

DROP TABLE IF EXISTS `active_delivery`;
/*!50001 DROP VIEW IF EXISTS `active_delivery`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `active_delivery` AS SELECT 
 1 AS `del_id`,
 1 AS `customer_id`,
 1 AS `delivery_date`,
 1 AS `dr_no`,
 1 AS `truck_id`,
 1 AS `billing_date`,
 1 AS `material`,
 1 AS `quantity`,
 1 AS `unit_price`,
 1 AS `status`,
 1 AS `is_deleted`,
 1 AS `date_created`,
 1 AS `date_edited`,
 1 AS `created_by`,
 1 AS `edited_by`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `active_truck`
--

DROP TABLE IF EXISTS `active_truck`;
/*!50001 DROP VIEW IF EXISTS `active_truck`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `active_truck` AS SELECT 
 1 AS `truck_id`,
 1 AS `plate_no`,
 1 AS `capacity`,
 1 AS `truck_model`,
 1 AS `status`,
 1 AS `is_deleted`,
 1 AS `date_created`,
 1 AS `date_edited`,
 1 AS `created_by`,
 1 AS `edited_by`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `admin_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('Admin','Supervisor','Encoder') DEFAULT 'Encoder',
  `password` varchar(255) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `status` enum('Active','Disabled') DEFAULT 'Active',
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_edited` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `admin_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES (1,'Ryan Paul C. Rodanilla','admin@gmail.com','Admin','$2y$12$dfmh98n744XaTucecho/7OVIIRUKKunieWJvp/2L4QAXZP2m6d8ye','2025-12-16 12:49:28','Active','2025-11-26 14:04:46','2025-12-16 12:49:28',1,1),(2,'Test User','testuser@gmail.com','Admin','$2y$12$E5hw4pxrq6fjRBzhcajCluVOpt6gXs0XffLtcabppfFln/yNHvs7G',NULL,'Active','2025-12-16 12:32:41',NULL,1,NULL);
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_log` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `action` enum('CREATE','UPDATE','DELETE','FINALIZE','REOPEN','VOID','PRINT','EXPORT','APPROVE','REJECT') NOT NULL,
  `old_data` text,
  `new_data` text,
  `performed_by` int DEFAULT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `performed_by` (`performed_by`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`performed_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=67 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,'contractor',2,'CREATE',NULL,'{\"contractor_name\":\"TestName\",\"contact_person\":\"ContactPerson\",\"contact_no\":\"ContactNo\",\"email\":\"TestContractor@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 14:29:27'),(2,'contractor',1,'DELETE','{\"contractor_id\":1,\"contractor_name\":\"TestName\",\"contact_person\":\"ContactPerson\",\"contact_no\":\"ContactNo\",\"email\":\"TestContractor@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 14:26:09\",\"date_edited\":\"2025-11-26 14:26:09\",\"created_by\":1,\"edited_by\":1}','{\"is_deleted\":1}',1,'2025-11-26 14:29:32'),(3,'contractor',3,'CREATE',NULL,'{\"contractor_name\":\"asdkjaskdjh\",\"contact_person\":\"aksjdhakjshd\",\"contact_no\":\"akjshdkajhsd\",\"email\":\"akjshdkajshd@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 14:30:33'),(4,'contractor',3,'DELETE','{\"contractor_id\":3,\"contractor_name\":\"asdkjaskdjh\",\"contact_person\":\"aksjdhakjshd\",\"contact_no\":\"akjshdkajhsd\",\"email\":\"akjshdkajshd@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 14:30:33\",\"date_edited\":\"2025-11-26 14:30:33\",\"created_by\":1,\"edited_by\":1}','{\"is_deleted\":1}',1,'2025-11-26 14:30:49'),(5,'company',1,'CREATE',NULL,'{\"company_name\":\"TestCompany\",\"address\":\"TestAddress\",\"contact_no\":\"09123456789\",\"email\":\"TestCompany@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 14:31:46'),(6,'company',1,'UPDATE','{\"company_id\":1,\"company_name\":\"TestCompany\",\"address\":\"TestAddress\",\"contact_no\":\"09123456789\",\"email\":\"TestCompany@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 14:31:46\",\"date_edited\":\"2025-11-26 14:31:46\",\"created_by\":1,\"edited_by\":1}','{\"company_id\":\"1\",\"company_name\":\"TestCompany\",\"address\":\"TestAddress\",\"contact_no\":\"09123456789\",\"email\":\"TestCompany@gmail.com\",\"status\":\"inactive\"}',1,'2025-11-26 14:31:52'),(7,'company',1,'UPDATE','{\"company_id\":1,\"company_name\":\"TestCompany\",\"address\":\"TestAddress\",\"contact_no\":\"09123456789\",\"email\":\"TestCompany@gmail.com\",\"status\":\"Inactive\",\"is_deleted\":0,\"date_created\":\"2025-11-26 14:31:46\",\"date_edited\":\"2025-11-26 14:31:52\",\"created_by\":1,\"edited_by\":1}','{\"company_id\":\"1\",\"company_name\":\"TestCompany\",\"address\":\"TestAddress\",\"contact_no\":\"09123456789\",\"email\":\"TestCompany@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 14:31:55'),(8,'contractor',0,'UPDATE','false','{\"contractor_id\":\"\",\"contractor_name\":\"Lance Serrano\",\"contact_person\":\"Remi\",\"contact_no\":\"09676786767\",\"email\":\"Lanceserrano@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 14:40:28'),(9,'company',1,'UPDATE','{\"company_id\":1,\"company_name\":\"Remi\",\"address\":\"Motherss\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 14:31:46\",\"date_edited\":\"2025-11-26 16:56:27\",\"created_by\":1,\"edited_by\":1}','{\"action\":\"update\",\"company_id\":\"1\",\"company_name\":\"Remi\",\"address\":\"asdasdasd\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 16:59:57'),(10,'company',1,'UPDATE','{\"company_id\":1,\"company_name\":\"Remi\",\"address\":\"asdasdasd\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 14:31:46\",\"date_edited\":\"2025-11-26 16:59:57\",\"created_by\":1,\"edited_by\":1}','{\"action\":\"update\",\"company_id\":\"1\",\"company_name\":\"asdasdasdasd\",\"address\":\"asdasdasd\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 17:00:10'),(11,'company',2,'CREATE',NULL,'{\"company_id\":\"\",\"action\":\"create\",\"company_name\":\"Remi\",\"address\":\"asdasd\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 18:24:21'),(12,'company',1,'DELETE','{\"company_id\":1,\"company_name\":\"asdasdasdasd\",\"address\":\"asdasdasd\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 14:31:46\",\"date_edited\":\"2025-11-26 17:00:09\",\"created_by\":1,\"edited_by\":1}','{\"is_deleted\":1}',1,'2025-11-26 18:24:32'),(13,'company',3,'CREATE',NULL,'{\"company_id\":\"\",\"action\":\"create\",\"company_name\":\"Company 1\",\"address\":\"Company 1 Address\",\"contact_no\":\"09123456789\",\"email\":\"Company1@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 18:25:57'),(14,'company',3,'UPDATE','{\"company_id\":3,\"company_name\":\"Company 1\",\"address\":\"Company 1 Address\",\"contact_no\":\"09123456789\",\"email\":\"Company1@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 18:25:57\",\"date_edited\":\"2025-11-26 18:25:57\",\"created_by\":1,\"edited_by\":1}','{\"company_id\":\"3\",\"action\":\"update\",\"company_name\":\"Company 2\",\"address\":\"Company 1 Address\",\"contact_no\":\"09123456789\",\"email\":\"Company1@gmail.com\"}',1,'2025-11-26 20:33:58'),(15,'site',1,'CREATE',NULL,'{\"site_id\":\"\",\"action\":\"create\",\"site_name\":\"Marikina\",\"remarks\":\"TEST REMARK\",\"location\":\"Quezon City\",\"status\":\"active\"}',1,'2025-11-26 20:58:40'),(16,'site',1,'UPDATE','{\"site_id\":1,\"site_name\":\"Marikina\",\"remarks\":\"TEST REMARK\",\"location\":\"Quezon City\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 20:58:40\",\"date_edited\":\"2025-11-26 20:58:40\",\"created_by\":1,\"edited_by\":1}','{\"site_id\":\"1\",\"action\":\"update\",\"site_name\":\"Marikina\",\"remarks\":\"Test Remark\",\"location\":\"Quezon City\"}',1,'2025-11-26 20:58:50'),(17,'contractor',5,'UPDATE','{\"contractor_id\":5,\"contractor_name\":\"Lance Serrano\",\"contact_person\":\"asdasdasd\",\"contact_no\":\"09123456789\",\"email\":\"mark@mail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 20:18:28\",\"date_edited\":\"2025-11-26 20:18:28\",\"created_by\":1,\"edited_by\":1}','{\"contractor_id\":\"5\",\"action\":\"update\",\"contractor_name\":\"Lance Serrano\",\"contact_person\":\"Jarem\",\"contact_no\":\"\",\"email\":\"\",\"status\":\"active\"}',1,'2025-11-26 21:13:38'),(18,'contractor',5,'UPDATE','{\"contractor_id\":5,\"contractor_name\":\"Lance Serrano\",\"contact_person\":\"Jarem\",\"contact_no\":\"\",\"email\":\"\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 20:18:28\",\"date_edited\":\"2025-11-26 21:13:38\",\"created_by\":1,\"edited_by\":1}','{\"contractor_id\":\"5\",\"action\":\"update\",\"contractor_name\":\"Lance Serrano\",\"contact_person\":\"Jarem\",\"contact_no\":\"09123456789\",\"email\":\"Lanceserrano@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 21:22:49'),(19,'truck',1,'CREATE',NULL,'{\"truck_id\":\"\",\"action\":\"create\",\"plate_no\":\"ABF 7386\",\"capacity\":\"20\",\"truck_model\":\"Avanza\",\"status\":\"active\"}',1,'2025-11-26 21:27:38'),(20,'truck',1,'UPDATE','{\"truck_id\":1,\"plate_no\":\"ABF 7386\",\"capacity\":\"20.00\",\"truck_model\":\"Avanza\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 21:27:38\",\"date_edited\":\"2025-11-26 21:27:38\",\"created_by\":1,\"edited_by\":1}','{\"truck_id\":\"1\",\"action\":\"update\",\"plate_no\":\"ABF 7386\",\"capacity\":\"30.00\",\"truck_model\":\"Avanza\"}',1,'2025-11-26 21:29:08'),(21,'company',2,'UPDATE','{\"company_id\":2,\"company_name\":\"Remi\",\"address\":\"asdasd\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 18:24:21\",\"date_edited\":\"2025-11-26 18:24:21\",\"created_by\":1,\"edited_by\":1}','{\"company_id\":\"2\",\"action\":\"update\",\"company_name\":\"Remi\",\"address\":\"Marikina City\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 21:29:26'),(22,'materials',1,'CREATE',NULL,'{\"material_id\":\"\",\"action\":\"create\",\"material_name\":\"Glass\",\"unit_price\":\"100\",\"status\":\"active\"}',1,'2025-11-26 22:59:34'),(23,'materials',2,'CREATE',NULL,'{\"material_id\":\"\",\"action\":\"create\",\"material_name\":\"Sand\",\"unit_price\":\"250\",\"status\":\"active\"}',1,'2025-11-26 22:59:40'),(24,'materials',3,'CREATE',NULL,'{\"material_id\":\"\",\"action\":\"create\",\"material_name\":\"Dirt\",\"unit_price\":\"100\",\"status\":\"active\"}',1,'2025-11-26 22:59:49'),(25,'customer',1,'CREATE',NULL,'{\"form_type\":\"customer\",\"action\":\"create\",\"customer_id\":\"\",\"company_id\":\"3\",\"contractor_id\":\"5\",\"site_id\":\"1\",\"customer_name\":\"Ryan Paul\",\"contact_no\":\"09763303167\",\"email\":\"rodanillaryan@gmail.com\",\"address\":\"Quezon City\",\"status\":\"active\"}',1,'2025-11-26 23:00:33'),(26,'delivery',1,'CREATE',NULL,'{\"form_type\":\"delivery\",\"action\":\"create\",\"del_id\":\"\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-11-29\",\"billing_date\":\"2025-11-26\",\"dr_no\":\"000123\",\"truck_id\":\"1\",\"material_id\":\"1\",\"material_name\":\"Glass\",\"quantity\":\"25\",\"unit_price\":\"100.00\",\"status\":\"pending\"}',1,'2025-11-26 23:31:22'),(27,'delivery',1,'UPDATE','{\"del_id\":1,\"customer_id\":1,\"delivery_date\":\"2025-11-29\",\"dr_no\":\"000123\",\"truck_id\":1,\"billing_date\":\"2025-11-26\",\"material\":\"Glass\",\"quantity\":\"25.00\",\"unit_price\":\"100.00\",\"status\":\"Pending\",\"is_deleted\":0,\"date_created\":\"2025-11-26 23:31:22\",\"date_edited\":\"2025-11-26 23:31:22\",\"created_by\":1,\"edited_by\":1}','{\"form_type\":\"delivery\",\"action\":\"update\",\"del_id\":\"1\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-11-29\",\"billing_date\":\"2025-11-26\",\"dr_no\":\"000123\",\"truck_id\":\"1\",\"material_id\":\"1\",\"material_name\":\"Glass\",\"quantity\":\"25.00\",\"unit_price\":\"100.00\",\"status\":\"pending\"}',1,'2025-11-26 23:50:30'),(28,'delivery',1,'UPDATE','{\"del_id\":1,\"customer_id\":1,\"delivery_date\":\"2025-11-29\",\"dr_no\":\"000123\",\"truck_id\":1,\"billing_date\":\"2025-11-26\",\"material\":\"Glass\",\"quantity\":\"25.00\",\"unit_price\":\"100.00\",\"status\":\"Pending\",\"is_deleted\":0,\"date_created\":\"2025-11-26 23:31:22\",\"date_edited\":\"2025-11-26 23:50:30\",\"created_by\":1,\"edited_by\":1}','{\"form_type\":\"delivery\",\"action\":\"update\",\"del_id\":\"1\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-11-29\",\"billing_date\":\"2025-11-26\",\"dr_no\":\"000123\",\"truck_id\":\"1\",\"material_id\":\"1\",\"material_name\":\"Glass\",\"quantity\":\"25.00\",\"unit_price\":\"100.00\",\"status\":\"delivered\"}',1,'2025-11-26 23:52:13'),(29,'contractor',5,'UPDATE','{\"contractor_id\":5,\"contractor_name\":\"Lance Serrano\",\"contact_person\":\"Jarem\",\"contact_no\":\"09123456789\",\"email\":\"Lanceserrano@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 20:18:28\",\"date_edited\":\"2025-11-26 21:22:49\",\"created_by\":1,\"edited_by\":1}','{\"contractor_id\":\"5\",\"action\":\"update\",\"contractor_name\":\"Contractor#5\",\"contact_person\":\"Billy\",\"contact_no\":\"09769987612\",\"email\":\"Billy@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 23:56:12'),(30,'contractor',4,'UPDATE','{\"contractor_id\":4,\"contractor_name\":\"asdasdasdas\",\"contact_person\":\"asdasdasd\",\"contact_no\":\"asdasdasd\",\"email\":\"asdasdasdasd@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 20:18:11\",\"date_edited\":\"2025-11-26 20:18:11\",\"created_by\":1,\"edited_by\":1}','{\"contractor_id\":\"4\",\"action\":\"update\",\"contractor_name\":\"Contractor#4\",\"contact_person\":\"John\",\"contact_no\":\"09761238614\",\"email\":\"John@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 23:56:30'),(31,'contractor',2,'UPDATE','{\"contractor_id\":2,\"contractor_name\":\"TestName\",\"contact_person\":\"ContactPerson\",\"contact_no\":\"ContactNo\",\"email\":\"TestContractor@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 14:29:27\",\"date_edited\":\"2025-11-26 14:29:27\",\"created_by\":1,\"edited_by\":1}','{\"contractor_id\":\"2\",\"action\":\"update\",\"contractor_name\":\"Contractor#2\",\"contact_person\":\"Weng\",\"contact_no\":\"09871262782\",\"email\":\"Weng@gmail.com\",\"status\":\"active\"}',1,'2025-11-26 23:56:51'),(32,'site',1,'UPDATE','{\"site_id\":1,\"site_name\":\"Marikina\",\"remarks\":\"Test Remark\",\"location\":\"Quezon City\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 20:58:40\",\"date_edited\":\"2025-11-26 20:58:50\",\"created_by\":1,\"edited_by\":1}','{\"site_id\":\"1\",\"action\":\"update\",\"site_name\":\"Nangka\",\"remarks\":\"****\",\"location\":\"Marikina City\",\"status\":\"active\"}',1,'2025-11-26 23:57:51'),(33,'site',2,'CREATE',NULL,'{\"site_id\":\"\",\"action\":\"create\",\"site_name\":\"Nangka\",\"remarks\":\"***\",\"location\":\"Marikina City\",\"status\":\"active\"}',1,'2025-11-26 23:58:06'),(34,'site',3,'CREATE',NULL,'{\"site_id\":\"\",\"action\":\"create\",\"site_name\":\"City Hall\",\"remarks\":\"Small Shop\",\"location\":\"Quezon City\",\"status\":\"active\"}',1,'2025-11-26 23:58:33'),(35,'site',4,'CREATE',NULL,'{\"site_id\":\"\",\"action\":\"create\",\"site_name\":\"City Hall\",\"remarks\":\"Large Shop\",\"location\":\"Quezon City\",\"status\":\"active\"}',1,'2025-11-26 23:58:41'),(36,'site',5,'CREATE',NULL,'{\"site_id\":\"\",\"action\":\"create\",\"site_name\":\"Ever Gotesco\",\"remarks\":\"D.I.Y Store\",\"location\":\"Commonwealth\",\"status\":\"active\"}',1,'2025-11-26 23:59:09'),(37,'site',6,'CREATE',NULL,'{\"site_id\":\"\",\"action\":\"create\",\"site_name\":\"Ever Gotesco\",\"remarks\":\"Mercury Drug\",\"location\":\"Commonwealth\",\"status\":\"active\"}',1,'2025-11-26 23:59:22'),(38,'materials',4,'CREATE',NULL,'{\"material_id\":\"\",\"action\":\"create\",\"material_name\":\"Tiles\",\"unit_price\":\"162\",\"status\":\"active\"}',1,'2025-11-27 00:00:22'),(39,'materials',5,'CREATE',NULL,'{\"material_id\":\"\",\"action\":\"create\",\"material_name\":\"Bricks\",\"unit_price\":\"612\",\"status\":\"active\"}',1,'2025-11-27 00:00:29'),(40,'materials',6,'CREATE',NULL,'{\"material_id\":\"\",\"action\":\"create\",\"material_name\":\"Gravel\",\"unit_price\":\"215\",\"status\":\"active\"}',1,'2025-11-27 00:00:40'),(41,'company',3,'UPDATE','{\"company_id\":3,\"company_name\":\"Company 2\",\"address\":\"Company 1 Address\",\"contact_no\":\"09123456789\",\"email\":\"Company1@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 18:25:57\",\"date_edited\":\"2025-11-26 20:33:58\",\"created_by\":1,\"edited_by\":1}','{\"company_id\":\"3\",\"action\":\"update\",\"company_name\":\"Company 3\",\"address\":\"Company 3 Address\",\"contact_no\":\"09123456789\",\"email\":\"Company1@gmail.com\",\"status\":\"active\"}',1,'2025-11-27 00:02:33'),(42,'company',2,'UPDATE','{\"company_id\":2,\"company_name\":\"Remi\",\"address\":\"Marikina City\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 18:24:21\",\"date_edited\":\"2025-11-26 21:29:26\",\"created_by\":1,\"edited_by\":1}','{\"company_id\":\"2\",\"action\":\"update\",\"company_name\":\"Company 2\",\"address\":\"Company 2 Address\",\"contact_no\":\"09123456789\",\"email\":\"Company2@gmail.com\",\"status\":\"active\"}',1,'2025-11-27 00:02:50'),(43,'company',1,'UPDATE','{\"company_id\":1,\"company_name\":\"asdasdasdasd\",\"address\":\"asdasdasd\",\"contact_no\":\"09123456789\",\"email\":\"asdasdasd@gmail.com\",\"status\":\"Active\",\"is_deleted\":0,\"date_created\":\"2025-11-26 14:31:46\",\"date_edited\":\"2025-11-26 18:45:39\",\"created_by\":1,\"edited_by\":1}','{\"company_id\":\"1\",\"action\":\"update\",\"company_name\":\"Company 1\",\"address\":\"Company 1 Address\",\"contact_no\":\"09123456789\",\"email\":\"Company1@gmail.com\",\"status\":\"active\"}',1,'2025-11-27 00:03:05'),(44,'truck',2,'CREATE',NULL,'{\"truck_id\":\"\",\"action\":\"create\",\"plate_no\":\"HGD 1636\",\"capacity\":\"50\",\"truck_model\":\"L300\",\"status\":\"active\"}',1,'2025-11-27 00:03:29'),(45,'customer',2,'CREATE',NULL,'{\"form_type\":\"customer\",\"action\":\"create\",\"customer_id\":\"\",\"company_id\":\"1\",\"contractor_id\":\"4\",\"site_id\":\"3\",\"customer_name\":\"Test Customer\",\"contact_no\":\"09763303167\",\"email\":\"tetst@gmail.com\",\"address\":\"Quezon City\",\"status\":\"active\"}',1,'2025-11-27 00:33:29'),(46,'delivery',2,'CREATE',NULL,'{\"form_type\":\"delivery\",\"action\":\"create\",\"del_id\":\"\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-11-27\",\"billing_date\":\"2025-11-30\",\"dr_no\":\"000124\",\"truck_id\":\"2\",\"material_id\":\"5\",\"material_name\":\"Bricks\",\"quantity\":\"25\",\"unit_price\":\"612.00\",\"status\":\"pending\"}',1,'2025-11-27 01:36:20'),(47,'delivery',3,'CREATE',NULL,'{\"form_type\":\"delivery\",\"action\":\"create\",\"del_id\":\"\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-11-30\",\"billing_date\":\"2025-11-26\",\"dr_no\":\"000125\",\"truck_id\":\"1\",\"material_id\":\"6\",\"material_name\":\"Gravel\",\"quantity\":\"115\",\"unit_price\":\"215.00\",\"status\":\"pending\"}',1,'2025-11-27 01:36:44'),(48,'contractor',6,'CREATE',NULL,'{\"contractor_id\":\"\",\"action\":\"create\",\"contractor_name\":\"Contractor#6\",\"contact_person\":\"Ron Jacob\",\"contact_no\":\"096237431\",\"email\":\"Ron@gmail.com\",\"status\":\"active\"}',1,'2025-11-27 01:48:51'),(49,'delivery',4,'CREATE',NULL,'{\"form_type\":\"delivery\",\"action\":\"create\",\"del_id\":\"\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-12-04\",\"billing_date\":\"2025-12-01\",\"dr_no\":\"000127\",\"truck_id\":\"1\",\"material_id\":\"2\",\"material_name\":\"Sand\",\"quantity\":\"15\",\"unit_price\":\"250.00\",\"status\":\"pending\"}',1,'2025-12-01 10:16:59'),(50,'delivery',4,'UPDATE','{\"del_id\":4,\"customer_id\":1,\"delivery_date\":\"2025-12-04\",\"dr_no\":\"000127\",\"truck_id\":1,\"billing_date\":\"2025-12-01\",\"material\":\"Sand\",\"quantity\":\"15.00\",\"unit_price\":\"250.00\",\"status\":\"Pending\",\"is_deleted\":0,\"date_created\":\"2025-12-01 10:16:59\",\"date_edited\":\"2025-12-01 10:16:59\",\"created_by\":1,\"edited_by\":1}','{\"form_type\":\"delivery\",\"action\":\"update\",\"del_id\":\"4\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-12-04\",\"billing_date\":\"2025-12-01\",\"dr_no\":\"000127\",\"truck_id\":\"2\",\"material_id\":\"2\",\"material_name\":\"Sand\",\"quantity\":\"25.00\",\"unit_price\":\"250.00\",\"status\":\"pending\"}',1,'2025-12-01 10:17:34'),(51,'contractor',7,'CREATE',NULL,'{\"contractor_id\":\"\",\"action\":\"create\",\"contractor_name\":\"Test Contractor\",\"contact_person\":\"Test Contractor\",\"contact_no\":\"09763303167\",\"email\":\"TestContractor@gmail.com\",\"status\":\"active\"}',1,'2025-12-01 10:18:17'),(52,'materials',7,'CREATE',NULL,'{\"material_id\":\"\",\"action\":\"create\",\"material_name\":\"Sand A\",\"unit_price\":\"51\",\"status\":\"active\"}',1,'2025-12-01 10:21:37'),(53,'delivery',4,'UPDATE','{\"del_id\":4,\"customer_id\":1,\"delivery_date\":\"2025-12-04\",\"dr_no\":\"000127\",\"truck_id\":2,\"billing_date\":\"2025-12-01\",\"material\":\"Sand\",\"quantity\":\"25.00\",\"unit_price\":\"250.00\",\"status\":\"Pending\",\"is_deleted\":0,\"date_created\":\"2025-12-01 10:16:59\",\"date_edited\":\"2025-12-01 10:17:34\",\"created_by\":1,\"edited_by\":1,\"terms\":null,\"po_number\":null}','{\"form_type\":\"delivery\",\"action\":\"update\",\"del_id\":\"4\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-12-04\",\"billing_date\":\"2025-12-01\",\"dr_no\":\"000127\",\"po_number\":\"001\",\"terms\":\"15\",\"truck_id\":\"1\",\"material_id\":\"2\",\"material_name\":\"Sand\",\"quantity\":\"15.00\",\"unit_price\":\"250.00\",\"status\":\"pending\"}',1,'2025-12-09 17:10:01'),(54,'delivery',3,'UPDATE','{\"del_id\":3,\"customer_id\":1,\"delivery_date\":\"2025-11-30\",\"dr_no\":\"000125\",\"truck_id\":1,\"billing_date\":\"2025-11-26\",\"material\":\"Gravel\",\"quantity\":\"115.00\",\"unit_price\":\"215.00\",\"status\":\"Pending\",\"is_deleted\":0,\"date_created\":\"2025-11-27 01:36:44\",\"date_edited\":\"2025-11-27 01:36:44\",\"created_by\":1,\"edited_by\":1,\"terms\":null,\"po_number\":null}','{\"form_type\":\"delivery\",\"action\":\"update\",\"del_id\":\"3\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-11-30\",\"billing_date\":\"2025-11-26\",\"dr_no\":\"000125\",\"po_number\":\"002\",\"terms\":\"12\",\"truck_id\":\"\",\"material_id\":\"6\",\"material_name\":\"Gravel\",\"quantity\":\"115.00\",\"unit_price\":\"215.00\"}',1,'2025-12-09 18:05:00'),(55,'delivery',3,'UPDATE','{\"del_id\":3,\"customer_id\":1,\"delivery_date\":\"2025-11-30\",\"dr_no\":\"000125\",\"truck_id\":null,\"billing_date\":\"2025-11-26\",\"material\":\"Gravel\",\"quantity\":\"115.00\",\"unit_price\":\"215.00\",\"status\":\"Pending\",\"is_deleted\":0,\"date_created\":\"2025-11-27 01:36:44\",\"date_edited\":\"2025-12-09 18:05:00\",\"created_by\":1,\"edited_by\":1,\"terms\":\"12\",\"po_number\":\"002\"}','{\"form_type\":\"delivery\",\"action\":\"update\",\"del_id\":\"3\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-11-30\",\"billing_date\":\"2025-11-26\",\"dr_no\":\"000125\",\"po_number\":\"002\",\"terms\":\"12\",\"truck_id\":\"1\",\"material_id\":\"6\",\"material_name\":\"Gravel\",\"quantity\":\"115.00\",\"unit_price\":\"215.00\"}',1,'2025-12-09 18:05:06'),(56,'delivery',1,'UPDATE','{\"del_id\":1,\"customer_id\":1,\"delivery_date\":\"2025-11-29\",\"dr_no\":\"000123\",\"truck_id\":1,\"billing_date\":\"2025-11-26\",\"material\":\"Glass\",\"quantity\":\"25.00\",\"unit_price\":\"100.00\",\"status\":\"Delivered\",\"is_deleted\":0,\"date_created\":\"2025-11-26 23:31:22\",\"date_edited\":\"2025-11-26 23:52:13\",\"created_by\":1,\"edited_by\":1,\"terms\":null,\"po_number\":null}','{\"form_type\":\"delivery\",\"action\":\"update\",\"del_id\":\"1\",\"delivery_customer_id\":\"1\",\"delivery_date\":\"2025-11-29\",\"billing_date\":\"2025-11-26\",\"dr_no\":\"000123\",\"po_number\":\"003\",\"terms\":\"53\",\"truck_id\":\"2\",\"material_id\":\"1\",\"material_name\":\"Glass\",\"quantity\":\"25.00\",\"unit_price\":\"100.00\"}',1,'2025-12-09 18:05:34'),(57,'customer',13,'CREATE',NULL,'{\"form_type\":\"customer\",\"action\":\"create\",\"customer_id\":\"\",\"soa_id\":\"0\",\"company_id\":\"1\",\"contractor_id\":\"7\",\"site_id\":\"10\",\"customer_name\":\"New Customer\",\"contact_no\":\"09123456789\",\"email\":\"new@gmail.com\",\"address\":\"QC\",\"status\":\"active\"}',1,'2025-12-15 17:51:33'),(58,'delivery',5,'CREATE',NULL,'{\"form_type\":\"delivery\",\"action\":\"create\",\"del_id\":\"\",\"soa_id\":\"1\",\"delivery_customer_id\":\"10\",\"delivery_date\":\"2025-12-15\",\"billing_date\":\"2025-12-15\",\"dr_no\":\"000125\",\"po_number\":\"002\",\"terms\":\"30\",\"truck_id\":\"1\",\"material_id\":\"3\",\"material_name\":\"Dirt\",\"quantity\":\"25\",\"unit_price\":\"500\",\"status\":\"pending\"}',1,'2025-12-15 17:52:58'),(59,'delivery',6,'CREATE',NULL,'{\"form_type\":\"delivery\",\"action\":\"create\",\"del_id\":\"\",\"soa_id\":\"1\",\"delivery_customer_id\":\"10\",\"delivery_date\":\"2025-12-16\",\"billing_date\":\"2025-12-15\",\"dr_no\":\"000125\",\"po_number\":\"\",\"terms\":\"15\",\"truck_id\":\"2\",\"material_id\":\"3\",\"material_name\":\"Dirt\",\"quantity\":\"12\",\"unit_price\":\"51\",\"status\":\"pending\"}',1,'2025-12-15 17:54:15'),(60,'statement_of_account',3,'CREATE',NULL,'{\"soa_no\":\"SOA-2025-0003\",\"company_id\":4,\"site_id\":10,\"terms\":15,\"status\":\"draft\"}',1,'2025-12-15 19:48:07'),(61,'statement_of_account',1,'FINALIZE','{\"soa_id\":1,\"soa_no\":\"SOA-2025-0001\",\"company_id\":3,\"site_id\":1,\"terms\":\"30 Days\",\"status\":\"draft\",\"is_deleted\":0,\"created_by\":1,\"edited_by\":1,\"date_created\":\"2025-12-15 12:04:17\",\"date_edited\":\"2025-12-15 20:43:02\",\"date_finalized\":null}','{\"status\":\"finalized\"}',1,'2025-12-15 20:47:41'),(62,'customer',14,'CREATE',NULL,'{\"form_type\":\"customer\",\"action\":\"create\",\"customer_id\":\"\",\"soa_id\":\"3\",\"company_id\":\"4\",\"site_id\":\"10\",\"contractor_id\":\"11\",\"customer_name\":\"SOA 3 Customer\",\"contact_no\":\"09763303167\",\"email\":\"SOA3@gmail.com\",\"address\":\"QC\",\"status\":\"active\"}',1,'2025-12-15 21:21:54'),(63,'delivery',7,'CREATE',NULL,'{\"form_type\":\"delivery\",\"action\":\"create\",\"del_id\":\"\",\"soa_id\":\"3\",\"delivery_customer_id\":\"14\",\"delivery_date\":\"2025-12-15\",\"billing_date\":\"2025-12-20\",\"dr_no\":\"000123\",\"po_number\":\"002\",\"terms\":\"15\",\"truck_id\":\"1\",\"material_id\":\"5\",\"material_name\":\"Bricks\",\"quantity\":\"25\",\"unit_price\":\"15\",\"status\":\"pending\"}',1,'2025-12-15 21:22:18'),(64,'statement_of_account',3,'FINALIZE','{\"soa_id\":3,\"soa_no\":\"SOA-2025-0003\",\"company_id\":4,\"site_id\":10,\"terms\":\"15 Days\",\"status\":\"draft\",\"is_deleted\":0,\"created_by\":1,\"edited_by\":1,\"date_created\":\"2025-12-15 19:48:07\",\"date_edited\":\"2025-12-15 19:48:07\",\"date_finalized\":null}','{\"status\":\"finalized\"}',1,'2025-12-15 21:22:25'),(65,'statement_of_account',4,'CREATE',NULL,'{\"soa_no\":\"SOA-2025-0004\",\"company_id\":1,\"site_id\":3,\"terms\":15,\"status\":\"draft\"}',1,'2025-12-15 21:30:17'),(66,'materials',8,'CREATE',NULL,'{\"material_id\":\"\",\"action\":\"create\",\"material_name\":\"Remi\",\"status\":\"active\"}',1,'2025-12-15 21:32:33');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_log`
--

DROP TABLE IF EXISTS `backup_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `backup_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `type` enum('sql','csv') NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_log`
--

LOCK TABLES `backup_log` WRITE;
/*!40000 ALTER TABLE `backup_log` DISABLE KEYS */;
INSERT INTO `backup_log` VALUES (1,'backup_sql_20251216_130721.zip','sql',1,'2025-12-16 13:07:21'),(2,'backup_csv_20251216_131651.zip','csv',1,'2025-12-16 13:16:51');
/*!40000 ALTER TABLE `backup_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `company`
--

DROP TABLE IF EXISTS `company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company` (
  `company_id` int NOT NULL AUTO_INCREMENT,
  `company_name` varchar(100) NOT NULL,
  `address` text,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `is_deleted` tinyint(1) DEFAULT '0',
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_edited` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  PRIMARY KEY (`company_id`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  CONSTRAINT `company_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `company_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company`
--

LOCK TABLES `company` WRITE;
/*!40000 ALTER TABLE `company` DISABLE KEYS */;
INSERT INTO `company` VALUES (1,'Company 1','Company 1 Address','09123456789','Company1@gmail.com','Active',0,'2025-11-26 14:31:46','2025-11-27 00:03:05',1,1),(2,'Company 2','Company 2 Address','09123456789','Company2@gmail.com','Active',0,'2025-11-26 18:24:21','2025-11-27 00:02:50',1,1),(3,'Company 3','Company 3 Address','09123456789','Company1@gmail.com','Active',0,'2025-11-26 18:25:57','2025-11-27 00:02:33',1,1),(4,'Aggresand Quarrying Inc.','Bacolor, Pampanga','09171234567','info@aggresand.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(5,'Alphasand Aggregates Trading','Ortigas Center, Pasig','09181234567','office@alphasand.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(6,'Megawide Construction Corp','Quezon City','09172223333','megawide@gmail.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(7,'Unicon Ready Mix','Caloocan City','09179998888','unicon@gmail.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(8,'PrimeBuilders Corp','Makati City','09175556666','contact@primebuilders.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1);
/*!40000 ALTER TABLE `company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contractor`
--

DROP TABLE IF EXISTS `contractor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contractor` (
  `contractor_id` int NOT NULL AUTO_INCREMENT,
  `contractor_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `is_deleted` tinyint(1) DEFAULT '0',
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_edited` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  PRIMARY KEY (`contractor_id`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  CONSTRAINT `contractor_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `contractor_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor`
--

LOCK TABLES `contractor` WRITE;
/*!40000 ALTER TABLE `contractor` DISABLE KEYS */;
INSERT INTO `contractor` VALUES (1,'TestName','ContactPerson','ContactNo','TestContractor@gmail.com','Active',1,'2025-11-26 14:26:09','2025-11-26 14:29:32',1,1),(2,'Contractor#2','Weng','09871262782','Weng@gmail.com','Active',0,'2025-11-26 14:29:27','2025-11-26 23:56:51',1,1),(3,'asdkjaskdjh','aksjdhakjshd','akjshdkajhsd','akjshdkajshd@gmail.com','Active',1,'2025-11-26 14:30:33','2025-11-26 14:30:49',1,1),(4,'Contractor#4','John','09761238614','John@gmail.com','Active',0,'2025-11-26 20:18:11','2025-11-26 23:56:30',1,1),(5,'Contractor#5','Billy','09769987612','Billy@gmail.com','Active',0,'2025-11-26 20:18:28','2025-11-26 23:56:12',1,1),(6,'Contractor#6','Ron Jacob','096237431','Ron@gmail.com','Active',0,'2025-11-27 01:48:51','2025-11-27 01:48:51',1,1),(7,'Test Contractor','Test Contractor','09763303167','TestContractor@gmail.com','Active',0,'2025-12-01 10:18:17','2025-12-01 10:18:17',1,1),(8,'JRS Hauling Services','Juan Dela Cruz','09170000001','jrs@haul.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(9,'Triple A Trucking','Ana Santos','09170000002','aaa@truck.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(10,'Metro Haulers Inc','Pedro Gomez','09170000003','metro@haul.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(11,'Northside Aggregates','Mark Reyes','09170000004','northside@agg.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(12,'South Haulers Logistics','Lisa Cruz','09170000005','south@haul.com','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1);
/*!40000 ALTER TABLE `contractor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer`
--

DROP TABLE IF EXISTS `customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer` (
  `customer_id` int NOT NULL AUTO_INCREMENT,
  `company_id` int DEFAULT NULL,
  `contractor_id` int DEFAULT NULL,
  `site_id` int DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `is_deleted` tinyint(1) DEFAULT '0',
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_edited` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  PRIMARY KEY (`customer_id`),
  KEY `company_id` (`company_id`),
  KEY `contractor_id` (`contractor_id`),
  KEY `site_id` (`site_id`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  CONSTRAINT `customer_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `customer_ibfk_2` FOREIGN KEY (`contractor_id`) REFERENCES `contractor` (`contractor_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `customer_ibfk_3` FOREIGN KEY (`site_id`) REFERENCES `site` (`site_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `customer_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `customer_ibfk_5` FOREIGN KEY (`edited_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer`
--

LOCK TABLES `customer` WRITE;
/*!40000 ALTER TABLE `customer` DISABLE KEYS */;
INSERT INTO `customer` VALUES (1,3,5,1,'Ryan Paul','09763303167','rodanillaryan@gmail.com','Quezon City','Active',0,'2025-11-26 23:00:33','2025-11-26 23:00:33',1,1),(2,1,4,3,'Test Customer','09763303167','tetst@gmail.com','Quezon City','Active',0,'2025-11-27 00:33:29','2025-11-27 00:33:29',1,1),(3,1,1,1,'4GC-ARJILL CONCRETE BATCHING PLANT','09175551234','4gc@batch.com','Calamba, Laguna','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(4,2,2,2,'Nangka Ready Mix','09176661234','nangka@mix.com','Cainta, Rizal','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(5,3,3,3,'MetroMix Laguna','09178881234','metromix@laguna.com','Cabuyao, Laguna','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(6,4,4,4,'Unicon Project Pampanga','09179991234','unicon@pampanga.com','Porac, Pampanga','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(7,5,5,5,'PrimeBuilders South','09174441234','pbs@south.com','Calamba, Laguna','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(8,1,2,3,'Aggresand Major Client A','09175559999','clientA@agg.com','Quezon City','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(9,2,1,4,'Alphasand Bulk Buyer B','09176669999','buyerB@alpha.com','Mandaluyong','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(10,3,5,1,'Megawide Supplier Client C','09178889999','clientC@mega.com','Pasig','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(11,4,3,2,'Unicon Cement Buyer D','09179994444','buyerD@unicon.com','Caloocan','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(12,5,4,5,'PrimeBuilders Bulk Client E','09174445555','clientE@prime.com','Makati','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(13,1,7,10,'New Customer','09123456789','new@gmail.com','QC','Active',0,'2025-12-15 17:51:33','2025-12-15 17:51:33',1,1),(14,4,11,10,'SOA 3 Customer','09763303167','SOA3@gmail.com','QC','Active',0,'2025-12-15 21:21:54','2025-12-15 21:21:54',1,1);
/*!40000 ALTER TABLE `customer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery`
--

DROP TABLE IF EXISTS `delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery` (
  `del_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int DEFAULT NULL,
  `soa_id` int DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `dr_no` varchar(50) DEFAULT NULL,
  `truck_id` int DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `status` enum('Pending','Delivered','Cancelled') DEFAULT 'Pending',
  `is_deleted` tinyint(1) DEFAULT '0',
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_edited` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  `terms` text,
  `po_number` varchar(255) DEFAULT NULL,
  `billing_date` date DEFAULT NULL,
  PRIMARY KEY (`del_id`),
  KEY `customer_id` (`customer_id`),
  KEY `truck_id` (`truck_id`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  KEY `fk_delivery_soa` (`soa_id`),
  CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`truck_id`) REFERENCES `truck` (`truck_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `delivery_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `delivery_ibfk_4` FOREIGN KEY (`edited_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_soa` FOREIGN KEY (`soa_id`) REFERENCES `statement_of_account` (`soa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_delivery_billing_date` CHECK ((`billing_date` >= `delivery_date`))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery`
--

LOCK TABLES `delivery` WRITE;
/*!40000 ALTER TABLE `delivery` DISABLE KEYS */;
INSERT INTO `delivery` VALUES (1,1,NULL,'2025-11-29','000123',2,'Glass',25.00,100.00,'Pending',0,'2025-11-26 23:31:22','2025-12-15 19:46:43',1,1,'53','003','2025-12-20'),(2,1,NULL,'2025-11-27','000124',2,'Bricks',25.00,612.00,'Pending',0,'2025-11-27 01:36:20','2025-12-15 19:46:43',1,1,NULL,NULL,'2025-12-20'),(3,1,NULL,'2025-11-30','000125',1,'Gravel',115.00,215.00,'Pending',0,'2025-11-27 01:36:44','2025-12-15 19:46:43',1,1,'12','002','2025-12-20'),(4,1,NULL,'2025-12-04','000127',1,'Sand',15.00,250.00,'Pending',0,'2025-12-01 10:16:59','2025-12-15 19:46:43',1,1,'15','001','2025-12-20'),(5,10,1,'2025-12-15','000125',1,'Dirt',25.00,500.00,'Pending',0,'2025-12-15 17:52:58','2025-12-15 19:46:43',1,1,'30','002','2025-12-20'),(6,10,1,'2025-12-16','000125',2,'Dirt',12.00,51.00,'Pending',0,'2025-12-15 17:54:15','2025-12-15 19:46:43',1,1,'15',NULL,'2025-12-20'),(7,14,3,'2025-12-15','000123',1,'Bricks',25.00,15.00,'Pending',0,'2025-12-15 21:22:18','2025-12-15 21:22:18',1,1,'15','002','2025-12-20');
/*!40000 ALTER TABLE `delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materials`
--

DROP TABLE IF EXISTS `materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materials` (
  `material_id` int NOT NULL AUTO_INCREMENT,
  `material_name` varchar(100) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  `date_edited` datetime NOT NULL,
  `created_by` int DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  PRIMARY KEY (`material_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materials`
--

LOCK TABLES `materials` WRITE;
/*!40000 ALTER TABLE `materials` DISABLE KEYS */;
INSERT INTO `materials` VALUES (1,'Glass',100.00,'active',0,'2025-11-26 22:59:34','2025-11-26 22:59:34',1,1),(2,'Sand',250.00,'active',0,'2025-11-26 22:59:40','2025-11-26 22:59:40',1,1),(3,'Dirt',100.00,'active',0,'2025-11-26 22:59:49','2025-11-26 22:59:49',1,1),(4,'Tiles',162.00,'active',0,'2025-11-27 00:00:22','2025-11-27 00:00:22',1,1),(5,'Bricks',612.00,'active',0,'2025-11-27 00:00:29','2025-11-27 00:00:29',1,1),(6,'Gravel',215.00,'active',0,'2025-11-27 00:00:40','2025-11-27 00:00:40',1,1),(7,'Sand A',51.00,'active',0,'2025-12-01 10:21:37','2025-12-01 10:21:37',1,1),(8,'Remi',0.00,'active',0,'2025-12-15 21:32:33','2025-12-15 21:32:33',1,1);
/*!40000 ALTER TABLE `materials` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site`
--

DROP TABLE IF EXISTS `site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site` (
  `site_id` int NOT NULL AUTO_INCREMENT,
  `site_name` varchar(100) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `location` text,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `is_deleted` tinyint(1) DEFAULT '0',
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_edited` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  PRIMARY KEY (`site_id`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  CONSTRAINT `site_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `site_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site`
--

LOCK TABLES `site` WRITE;
/*!40000 ALTER TABLE `site` DISABLE KEYS */;
INSERT INTO `site` VALUES (1,'Nangka','****','Marikina City','Active',0,'2025-11-26 20:58:40','2025-11-26 23:57:51',1,1),(2,'Nangka','***','Marikina City','Active',0,'2025-11-26 23:58:06','2025-11-26 23:58:06',1,1),(3,'City Hall','Small Shop','Quezon City','Active',0,'2025-11-26 23:58:33','2025-11-26 23:58:33',1,1),(4,'City Hall','Large Shop','Quezon City','Active',0,'2025-11-26 23:58:41','2025-11-26 23:58:41',1,1),(5,'Ever Gotesco','D.I.Y Store','Commonwealth','Active',0,'2025-11-26 23:59:09','2025-11-26 23:59:09',1,1),(6,'Ever Gotesco','Mercury Drug','Commonwealth','Active',0,'2025-11-26 23:59:22','2025-11-26 23:59:22',1,1),(7,'Batulao Site','Nasugbu Project','Batangas','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(8,'Nangka Site','Cainta Project','Rizal','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(9,'Cabuyao Plant','Laguna Project','Laguna','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(10,'Porac Quarry','Main Extraction Area','Pampanga','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1),(11,'Calamba Site','Warehouse Build','Laguna','Active',0,'2025-12-09 18:55:35','2025-12-09 18:55:35',1,1);
/*!40000 ALTER TABLE `site` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statement_of_account`
--

DROP TABLE IF EXISTS `statement_of_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statement_of_account` (
  `soa_id` int NOT NULL AUTO_INCREMENT,
  `soa_no` varchar(30) NOT NULL,
  `company_id` int NOT NULL,
  `site_id` int DEFAULT NULL,
  `terms` varchar(255) NOT NULL,
  `status` enum('draft','finalized','paid') DEFAULT 'draft',
  `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` int NOT NULL,
  `edited_by` int DEFAULT NULL,
  `date_created` datetime NOT NULL,
  `date_edited` datetime DEFAULT NULL,
  `date_finalized` datetime DEFAULT NULL,
  PRIMARY KEY (`soa_id`),
  UNIQUE KEY `soa_no` (`soa_no`),
  KEY `fk_soa_company` (`company_id`),
  KEY `idx_soa_site` (`site_id`),
  CONSTRAINT `fk_soa_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`company_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statement_of_account`
--

LOCK TABLES `statement_of_account` WRITE;
/*!40000 ALTER TABLE `statement_of_account` DISABLE KEYS */;
INSERT INTO `statement_of_account` VALUES (1,'SOA-2025-0001',3,1,'30 Days','finalized',0,1,1,'2025-12-15 12:04:17','2025-12-15 20:47:41',NULL),(2,'SOA-2025-0002',2,7,'15 Days','draft',0,1,NULL,'2025-12-15 12:04:17',NULL,NULL),(3,'SOA-2025-0003',4,10,'15 Days','finalized',0,1,1,'2025-12-15 19:48:07','2025-12-15 21:22:25',NULL),(4,'SOA-2025-0004',1,3,'15','draft',0,1,1,'2025-12-15 21:30:17','2025-12-15 21:30:17',NULL);
/*!40000 ALTER TABLE `statement_of_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `truck`
--

DROP TABLE IF EXISTS `truck`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `truck` (
  `truck_id` int NOT NULL AUTO_INCREMENT,
  `plate_no` varchar(20) NOT NULL,
  `capacity` decimal(10,2) DEFAULT NULL,
  `truck_model` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive','Under Maintenance') DEFAULT 'Active',
  `is_deleted` tinyint(1) DEFAULT '0',
  `date_created` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_edited` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `edited_by` int DEFAULT NULL,
  PRIMARY KEY (`truck_id`),
  UNIQUE KEY `plate_no` (`plate_no`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  CONSTRAINT `truck_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `truck_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `truck`
--

LOCK TABLES `truck` WRITE;
/*!40000 ALTER TABLE `truck` DISABLE KEYS */;
INSERT INTO `truck` VALUES (1,'ABF 7386',30.00,'Avanza','Active',0,'2025-11-26 21:27:38','2025-11-26 21:29:08',1,1),(2,'HGD 1636',50.00,'L300','Active',0,'2025-11-27 00:03:29','2025-11-27 00:03:29',1,1);
/*!40000 ALTER TABLE `truck` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `active_company`
--

/*!50001 DROP VIEW IF EXISTS `active_company`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `active_company` AS select `company`.`company_id` AS `company_id`,`company`.`company_name` AS `company_name`,`company`.`address` AS `address`,`company`.`contact_no` AS `contact_no`,`company`.`email` AS `email`,`company`.`status` AS `status`,`company`.`is_deleted` AS `is_deleted`,`company`.`date_created` AS `date_created`,`company`.`date_edited` AS `date_edited`,`company`.`created_by` AS `created_by`,`company`.`edited_by` AS `edited_by` from `company` where (`company`.`is_deleted` = 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `active_customer`
--

/*!50001 DROP VIEW IF EXISTS `active_customer`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `active_customer` AS select `customer`.`customer_id` AS `customer_id`,`customer`.`company_id` AS `company_id`,`customer`.`contractor_id` AS `contractor_id`,`customer`.`site_id` AS `site_id`,`customer`.`customer_name` AS `customer_name`,`customer`.`contact_no` AS `contact_no`,`customer`.`email` AS `email`,`customer`.`address` AS `address`,`customer`.`status` AS `status`,`customer`.`is_deleted` AS `is_deleted`,`customer`.`date_created` AS `date_created`,`customer`.`date_edited` AS `date_edited`,`customer`.`created_by` AS `created_by`,`customer`.`edited_by` AS `edited_by` from `customer` where (`customer`.`is_deleted` = 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `active_delivery`
--

/*!50001 DROP VIEW IF EXISTS `active_delivery`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `active_delivery` AS select `delivery`.`del_id` AS `del_id`,`delivery`.`customer_id` AS `customer_id`,`delivery`.`delivery_date` AS `delivery_date`,`delivery`.`dr_no` AS `dr_no`,`delivery`.`truck_id` AS `truck_id`,`delivery`.`billing_date` AS `billing_date`,`delivery`.`material` AS `material`,`delivery`.`quantity` AS `quantity`,`delivery`.`unit_price` AS `unit_price`,`delivery`.`status` AS `status`,`delivery`.`is_deleted` AS `is_deleted`,`delivery`.`date_created` AS `date_created`,`delivery`.`date_edited` AS `date_edited`,`delivery`.`created_by` AS `created_by`,`delivery`.`edited_by` AS `edited_by` from `delivery` where (`delivery`.`is_deleted` = 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `active_truck`
--

/*!50001 DROP VIEW IF EXISTS `active_truck`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `active_truck` AS select `truck`.`truck_id` AS `truck_id`,`truck`.`plate_no` AS `plate_no`,`truck`.`capacity` AS `capacity`,`truck`.`truck_model` AS `truck_model`,`truck`.`status` AS `status`,`truck`.`is_deleted` AS `is_deleted`,`truck`.`date_created` AS `date_created`,`truck`.`date_edited` AS `date_edited`,`truck`.`created_by` AS `created_by`,`truck`.`edited_by` AS `edited_by` from `truck` where (`truck`.`is_deleted` = 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-16 13:21:38
