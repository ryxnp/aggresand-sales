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
INSERT INTO `admin` VALUES (1,'admin','admin@gmail.com','Admin','$2y$12$dfmh98n744XaTucecho/7OVIIRUKKunieWJvp/2L4QAXZP2m6d8ye','2025-12-29 13:26:30','Active','2025-11-26 14:04:46','2025-12-29 13:26:30',1,1),(2,'test','testuser@gmail.com','Encoder','$2y$12$E5hw4pxrq6fjRBzhcajCluVOpt6gXs0XffLtcabppfFln/yNHvs7G','2025-12-22 13:55:36','Active','2025-12-16 12:32:41','2025-12-22 13:55:36',1,2);
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
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
  `type` enum('sql','csv','excel') NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `type` (`type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_log`
--

LOCK TABLES `backup_log` WRITE;
/*!40000 ALTER TABLE `backup_log` DISABLE KEYS */;
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
  `assigned_to` varchar(100) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `company`
--

LOCK TABLES `company` WRITE;
/*!40000 ALTER TABLE `company` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contractor`
--

LOCK TABLES `contractor` WRITE;
/*!40000 ALTER TABLE `contractor` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer`
--

LOCK TABLES `customer` WRITE;
/*!40000 ALTER TABLE `customer` DISABLE KEYS */;
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
  `company_id` int DEFAULT NULL,
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
  UNIQUE KEY `uniq_dr_no` (`dr_no`),
  KEY `customer_id` (`customer_id`),
  KEY `truck_id` (`truck_id`),
  KEY `created_by` (`created_by`),
  KEY `edited_by` (`edited_by`),
  KEY `fk_delivery_soa` (`soa_id`),
  CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`truck_id`) REFERENCES `truck` (`truck_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `delivery_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `delivery_ibfk_4` FOREIGN KEY (`edited_by`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_delivery_soa` FOREIGN KEY (`soa_id`) REFERENCES `statement_of_account` (`soa_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_delivery_billing_date` CHECK ((`billing_date` >= `delivery_date`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery`
--

LOCK TABLES `delivery` WRITE;
/*!40000 ALTER TABLE `delivery` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site`
--

LOCK TABLES `site` WRITE;
/*!40000 ALTER TABLE `site` DISABLE KEYS */;
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
  `billing_date` date DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statement_of_account`
--

LOCK TABLES `statement_of_account` WRITE;
/*!40000 ALTER TABLE `statement_of_account` DISABLE KEYS */;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `truck`
--

LOCK TABLES `truck` WRITE;
/*!40000 ALTER TABLE `truck` DISABLE KEYS */;
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

-- Dump completed on 2025-12-29 20:27:12
