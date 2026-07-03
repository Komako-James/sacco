/*
SQLyog Ultimate v12.09 (32 bit)
MySQL - 8.0.31 : Database - sacco_system
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`sacco_system` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `sacco_system`;

/*Table structure for table `audit_logs` */

DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `status` enum('success','failure') DEFAULT 'success',
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `old_data` text,
  `new_data` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `error_message` text,
  `timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_audit_user_timestamp` (`user_id`,`timestamp`),
  KEY `idx_audit_action_type` (`action`,`entity_type`)
) ENGINE=InnoDB AUTO_INCREMENT=199 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `audit_logs` */

insert  into `audit_logs`(`log_id`,`user_id`,`status`,`action`,`entity_type`,`entity_id`,`table_name`,`record_id`,`old_values`,`new_values`,`old_data`,`new_data`,`ip_address`,`user_agent`,`error_message`,`timestamp`,`created_at`) values (1,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-15 14:01:57'),(2,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0',NULL,'2026-05-29 17:31:02','2026-05-15 14:06:01'),(3,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.120.0 Chrome/142.0.7444.265 Electron/39.8.8 Safari/537.36',NULL,'2026-05-29 17:31:02','2026-05-15 15:06:34'),(4,1,'success','Create',NULL,NULL,'members',1,NULL,NULL,NULL,'{\"full_name\":\"James Komako\",\"national_id\":\"CM790102CF\",\"phone\":\"+256752965680\",\"email\":\"komakoj22@gmail.com\",\"gender\":\"Male\",\"date_of_birth\":\"1979-02-01\",\"occupation\":\"IT\",\"employer\":\"\",\"address\":\"Uganda\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-15 17:43:01'),(5,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0',NULL,'2026-05-29 17:31:02','2026-05-18 11:46:25'),(6,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-18 11:58:34'),(7,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0',NULL,'2026-05-29 17:31:02','2026-05-18 12:58:04'),(8,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0',NULL,'2026-05-29 17:31:02','2026-05-19 14:38:44'),(9,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0',NULL,'2026-05-29 17:31:02','2026-05-19 14:44:01'),(10,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0',NULL,'2026-05-29 17:31:02','2026-05-19 14:44:30'),(11,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0',NULL,'2026-05-29 17:31:02','2026-05-19 14:56:04'),(12,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0',NULL,'2026-05-29 17:31:02','2026-05-19 15:41:13'),(13,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0',NULL,'2026-05-29 17:31:02','2026-05-19 16:28:36'),(14,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0',NULL,'2026-05-29 17:31:02','2026-05-19 20:55:47'),(15,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-26 04:53:34'),(16,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-26 05:37:34'),(17,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-26 07:31:15'),(18,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-26 14:10:11'),(19,1,'success','Create',NULL,NULL,'members',2,NULL,NULL,NULL,'{\"full_name\":\"Joseph Kamya\",\"national_id\":\"CM800162CF\",\"phone\":\"+256782880410\",\"email\":\"joseph@gmail.com\",\"gender\":\"Male\",\"date_of_birth\":\"2026-05-26\",\"occupation\":\"Teacher\",\"employer\":\"Government\",\"address\":\"Wandegeya\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-26 14:42:44'),(20,1,'success','Create',NULL,NULL,'loans',1,NULL,NULL,NULL,'{\"member_id\":\"2\",\"product_id\":\"1\",\"amount\":\"500000\",\"period\":\"4\",\"purpose\":\"Sick child\",\"guarantors\":[\"1\"]}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-26 14:48:58'),(21,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-26 19:06:13'),(22,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-26 19:42:08'),(23,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-05-29 17:31:02','2026-05-27 14:31:17'),(24,1,'success','Create',NULL,NULL,'members',3,NULL,NULL,NULL,'{\"full_name\":\"Musoke Richard\",\"national_id\":\"CM8709890FM\",\"phone\":\"+256781236358\",\"email\":\"\",\"gender\":\"Male\",\"date_of_birth\":\"1984-05-11\",\"occupation\":\"IT\",\"employer\":\"Government\",\"address\":\"KAWEMPE\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-27 18:16:53'),(25,1,'success','Create',NULL,NULL,'loans',2,NULL,NULL,NULL,'{\"member_id\":\"1\",\"product_id\":\"1\",\"amount\":\"450000\",\"period\":\"1\",\"purpose\":\"Personal\",\"guarantors\":[\"3\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-27 18:37:41'),(26,1,'success','Create',NULL,NULL,'loans',3,NULL,NULL,NULL,'{\"member_id\":\"3\",\"product_id\":\"1\",\"amount\":\"150000\",\"period\":\"2\",\"purpose\":\"Personal\",\"guarantors\":[\"2\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-27 19:17:24'),(27,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-27 20:04:32'),(28,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-27 20:10:13'),(29,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-27 20:52:15'),(30,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-28 12:01:27'),(31,1,'success','Create',NULL,NULL,'members',4,NULL,NULL,NULL,'{\"full_name\":\"Hellen Ekanu\",\"national_id\":\"CF8709860FM\",\"phone\":\"+256771458963\",\"email\":\"testing@test.com\",\"gender\":\"Female\",\"date_of_birth\":\"1987-08-19\",\"occupation\":\"Farmer\",\"employer\":\"Self Employed\",\"address\":\"Kyengera\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-28 12:26:26'),(32,1,'success','Create',NULL,NULL,'loans',4,NULL,NULL,NULL,'{\"member_id\":\"4\",\"product_id\":\"4\",\"amount\":\"500000\",\"period\":\"12\",\"purpose\":\"personal development\",\"guarantors\":[\"3\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-28 12:39:46'),(33,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-28 14:24:41'),(34,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-28 14:59:34'),(35,1,'success','Create',NULL,NULL,'members',5,NULL,NULL,NULL,'{\"full_name\":\"Mugumya John\",\"national_id\":\"CM6709890FM\",\"phone\":\"+256789236354\",\"email\":\"mugumya@gmail.com\",\"gender\":\"Male\",\"date_of_birth\":\"1976-05-25\",\"occupation\":\"DFO\",\"employer\":\"Government\",\"address\":\"Rakai\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-28 15:09:26'),(36,1,'success','Create',NULL,NULL,'loans',5,NULL,NULL,NULL,'{\"member_id\":\"5\",\"product_id\":\"1\",\"amount\":\"60000\",\"period\":\"2\",\"purpose\":\"Personal\",\"guarantors\":[\"2\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-28 15:27:23'),(37,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-28 17:09:22'),(38,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 17:31:02','2026-05-28 22:48:21'),(39,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 19:48:38','2026-05-29 19:48:38'),(40,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 19:56:34','2026-05-29 19:56:34'),(41,1,'success','share_purchase',NULL,NULL,'member_share_transactions',0,NULL,NULL,NULL,'{\"member_id\":3,\"shares\":2,\"amount\":20000,\"savings_account_id\":4}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 20:19:21','2026-05-29 20:19:21'),(42,1,'success','share_purchase',NULL,NULL,'member_share_transactions',0,NULL,NULL,NULL,'{\"member_id\":3,\"shares\":2,\"amount\":20000,\"savings_account_id\":4}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 20:24:20','2026-05-29 20:24:20'),(43,1,'success','share_purchase',NULL,NULL,'member_share_transactions',0,NULL,NULL,NULL,'{\"member_id\":3,\"shares\":2,\"amount\":20000,\"savings_account_id\":4}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 20:24:55','2026-05-29 20:24:55'),(44,1,'success','share_purchase',NULL,NULL,'member_share_transactions',0,NULL,NULL,NULL,'{\"member_id\":2,\"shares\":2,\"amount\":20000,\"savings_account_id\":3}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 20:25:34','2026-05-29 20:25:34'),(45,1,'success','share_purchase',NULL,NULL,'member_share_transactions',0,NULL,NULL,NULL,'{\"member_id\":4,\"shares\":2,\"amount\":20000,\"savings_account_id\":6}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 20:26:03','2026-05-29 20:26:03'),(46,1,'success','share_purchase',NULL,NULL,'member_share_transactions',2,NULL,NULL,NULL,'{\"member_id\":3,\"shares\":2,\"amount\":20000,\"savings_account_id\":5}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 20:34:13','2026-05-29 20:34:13'),(47,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 21:09:44','2026-05-29 21:09:44'),(48,1,'success','share_transfer',NULL,NULL,'member_share_transfers',3,NULL,NULL,NULL,'{\"source_member_id\":3,\"destination_member_id\":4,\"shares\":2,\"amount\":20000}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-05-29 21:13:52','2026-05-29 21:13:52'),(49,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-06-05 10:23:14','2026-06-05 10:23:14'),(50,1,'success','Create',NULL,NULL,'loans',6,NULL,NULL,NULL,'{\"member_id\":\"5\",\"product_id\":\"1\",\"amount\":\"70000\",\"period\":\"2\",\"purpose\":\"School fees\",\"guarantors\":[\"3\"]}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-06-05 10:41:41','2026-06-05 10:41:41'),(51,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-06-05 15:25:33','2026-06-05 15:25:33'),(52,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-06-05 15:25:36','2026-06-05 15:25:36'),(53,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-06-11 10:31:25','2026-06-11 10:31:25'),(54,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0',NULL,'2026-06-11 15:32:26','2026-06-11 15:32:26'),(55,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-06-12 14:07:34','2026-06-12 14:07:34'),(56,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-06-12 15:43:12','2026-06-12 15:43:12'),(57,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-06-12 15:43:23','2026-06-12 15:43:23'),(58,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-06-12 17:07:20','2026-06-12 17:07:20'),(59,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-06-12 17:08:09','2026-06-12 17:08:09'),(60,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-06-12 23:52:22','2026-06-12 23:52:22'),(61,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-06-13 13:00:02','2026-06-13 13:00:02'),(62,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-06-14 08:39:11','2026-06-14 08:39:11'),(63,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0',NULL,'2026-06-15 17:41:29','2026-06-15 17:41:29'),(64,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 10:09:58','2026-06-17 10:09:58'),(65,1,'success','Create',NULL,NULL,'members',6,NULL,NULL,NULL,'{\"full_name\":\"Robin Mukasa\",\"national_id\":\"CF8599840FM\",\"phone\":\"+256771458963\",\"email\":\"test@gmail.com\",\"gender\":\"Male\",\"date_of_birth\":\"1980-08-02\",\"occupation\":\"Secretary\",\"employer\":\"Self Employed\",\"address\":\"Rakai\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 10:12:03','2026-06-17 10:12:03'),(66,1,'success','Create',NULL,NULL,'members',7,NULL,NULL,NULL,'{\"full_name\":\"Buyego Fred\",\"national_id\":\"CM6709822FM\",\"phone\":\"+256771458963\",\"email\":\"testing@test.com\",\"gender\":\"Female\",\"date_of_birth\":\"1975-09-18\",\"occupation\":\"DFO\",\"employer\":\"Government\",\"address\":\"London\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 10:13:00','2026-06-17 10:13:00'),(67,1,'success','Create',NULL,NULL,'loans',7,NULL,NULL,NULL,'{\"member_id\":\"7\",\"product_id\":\"3\",\"amount\":\"1500000\",\"period\":\"3\",\"purpose\":\"Personal\",\"guarantors\":[\"1\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 10:15:51','2026-06-17 10:15:51'),(68,1,'success','Create',NULL,NULL,'loans',8,NULL,NULL,NULL,'{\"member_id\":\"6\",\"product_id\":\"4\",\"amount\":\"700000\",\"period\":\"13\",\"purpose\":\"Business\",\"guarantors\":[\"2\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 10:17:10','2026-06-17 10:17:10'),(69,1,'success','share_purchase',NULL,NULL,'member_share_transactions',6,NULL,NULL,NULL,'{\"member_id\":7,\"shares\":4,\"amount\":40000,\"savings_account_id\":8}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 10:17:53','2026-06-17 10:17:53'),(70,1,'success','share_purchase',NULL,NULL,'member_share_transactions',8,NULL,NULL,NULL,'{\"member_id\":6,\"shares\":3,\"amount\":30000,\"savings_account_id\":9}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 10:18:15','2026-06-17 10:18:15'),(71,1,'success','share_transfer',NULL,NULL,'member_share_transfers',4,NULL,NULL,NULL,'{\"source_member_id\":4,\"destination_member_id\":7,\"shares\":4,\"amount\":40000}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 10:19:52','2026-06-17 10:19:52'),(72,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 12:15:31','2026-06-17 12:15:31'),(73,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 12:16:06','2026-06-17 12:16:06'),(74,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 14:10:29','2026-06-17 14:10:29'),(75,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 14:11:09','2026-06-17 14:11:09'),(76,1,'success','Create',NULL,NULL,'members',8,NULL,NULL,NULL,'{\"full_name\":\"Lamech Katamba\",\"national_id\":\"CM65698822FM\",\"phone\":\"+256781236358\",\"email\":\"test@gmail.com\",\"gender\":\"Male\",\"date_of_birth\":\"1975-05-11\",\"occupation\":\"IT\",\"employer\":\"ABF\",\"address\":\"Kampala\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 14:12:58','2026-06-17 14:12:58'),(77,1,'success','Create',NULL,NULL,'members',9,NULL,NULL,NULL,'{\"full_name\":\"Agnes Musitwa\",\"national_id\":\"CM6772390FM\",\"phone\":\"+256789236354\",\"email\":\"test@gmail.com\",\"gender\":\"Female\",\"date_of_birth\":\"2009-02-02\",\"occupation\":\"Student\",\"employer\":\"School\",\"address\":\"\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 14:14:10','2026-06-17 14:14:10'),(78,1,'success','Create',NULL,NULL,'loans',15,NULL,NULL,NULL,'{\"member_id\":\"8\",\"product_id\":\"4\",\"amount\":\"3000000\",\"period\":\"12\",\"purpose\":\"Personal\",\"guarantors\":[\"9\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 14:19:24','2026-06-17 14:19:24'),(79,1,'success','Create',NULL,NULL,'loans',17,NULL,NULL,NULL,'{\"member_id\":\"2\",\"product_id\":\"1\",\"amount\":\"60000\",\"period\":\"3\",\"purpose\":\"Personal\",\"guarantors\":[\"8\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 14:44:00','2026-06-17 14:44:00'),(80,1,'success','LOAN_DISBURSED','loans',17,NULL,NULL,'{\"status\": \"approved\"}','{\"status\": \"disbursed\", \"disbursement_amount\": 60000}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 14:55:14','2026-06-17 14:55:14'),(81,1,'success','LOAN_DISBURSED','loans',15,NULL,NULL,'{\"status\": \"approved\"}','{\"status\": \"disbursed\", \"disbursement_amount\": 3000000}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 14:56:25','2026-06-17 14:56:25'),(82,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 18:44:51','2026-06-17 18:44:51'),(83,1,'success','LOAN_REPAYMENT','loan_repayments',9,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1203.98\", \"total_payment\": 50000, \"principal_paid\": 48796.02, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 18:51:09','2026-06-17 18:51:09'),(84,1,'success','LOAN_REPAYMENT','loan_repayments',10,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1203.98\", \"total_payment\": 11203.98, \"principal_paid\": 10000, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 18:52:39','2026-06-17 18:52:39'),(85,1,'success','share_purchase',NULL,NULL,'member_share_transactions',44,NULL,NULL,NULL,'{\"member_id\":8,\"shares\":12,\"amount\":120000,\"savings_account_id\":10}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 18:54:07','2026-06-17 18:54:07'),(86,1,'success','share_transfer',NULL,NULL,'member_share_transfers',5,NULL,NULL,NULL,'{\"source_member_id\":4,\"destination_member_id\":8,\"shares\":4,\"amount\":40000}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-17 18:54:50','2026-06-17 18:54:50'),(87,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 12:58:13','2026-06-18 12:58:13'),(88,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 13:28:48','2026-06-18 13:28:48'),(89,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 15:42:22','2026-06-18 15:42:22'),(90,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 15:43:17','2026-06-18 15:43:17'),(91,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 16:27:32','2026-06-18 16:27:32'),(92,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 16:28:00','2026-06-18 16:28:00'),(93,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 19:07:57','2026-06-18 19:07:57'),(94,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 19:08:19','2026-06-18 19:08:19'),(95,1,'success','Create',NULL,NULL,'members',10,NULL,NULL,NULL,'{\"full_name\":\"Nambwayo Ritah\",\"national_id\":\"CM8111190FM\",\"phone\":\"+256771458963\",\"email\":\"testing@test.com\",\"gender\":\"Female\",\"date_of_birth\":\"1997-10-05\",\"occupation\":\"\",\"employer\":\"Self\",\"address\":\"\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 19:13:31','2026-06-18 19:13:31'),(96,1,'success','share_purchase',NULL,NULL,'member_share_transactions',54,NULL,NULL,NULL,'{\"member_id\":10,\"shares\":6,\"amount\":60000,\"savings_account_id\":12}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 19:14:53','2026-06-18 19:14:53'),(97,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 20:17:12','2026-06-18 20:17:12'),(98,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 20:17:36','2026-06-18 20:17:36'),(99,1,'success','LOAN_DISBURSED','loans',12,NULL,NULL,'{\"status\": \"approved\"}','{\"status\": \"disbursed\", \"disbursement_amount\": 10000}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 20:19:25','2026-06-18 20:19:25'),(100,1,'success','LOAN_REPAYMENT','loan_repayments',11,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 1203.98, \"total_payment\": 1203.98, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 20:20:29','2026-06-18 20:20:29'),(101,1,'success','LOAN_REPAYMENT','loan_repayments',16,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 10000, \"principal_paid\": 10000, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 20:41:44','2026-06-18 20:41:44'),(102,1,'success','LOAN_REPAYMENT','loan_repayments',17,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 1203.98, \"total_payment\": 1203.98, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 20:42:32','2026-06-18 20:42:32'),(103,1,'success','LOAN_REPAYMENT','loan_repayments',18,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 1203, \"total_payment\": 1203, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-18 20:43:19','2026-06-18 20:43:19'),(104,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 09:11:45','2026-06-21 09:11:45'),(105,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 13:10:29','2026-06-21 13:10:29'),(106,1,'success','share_purchase',NULL,NULL,'member_share_transactions',82,NULL,NULL,NULL,'{\"member_id\":4,\"shares\":4,\"amount\":40000,\"savings_account_id\":6}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 13:24:33','2026-06-21 13:24:33'),(107,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 14:30:26','2026-06-21 14:30:26'),(108,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 14:30:29','2026-06-21 14:30:29'),(109,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 16:44:28','2026-06-21 16:44:28'),(110,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 16:44:30','2026-06-21 16:44:30'),(111,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 16:45:23','2026-06-21 16:45:23'),(112,1,'success','Create',NULL,NULL,'members',11,NULL,NULL,NULL,'{\"full_name\":\"Paul Adrole\",\"national_id\":\"CM8702220FM\",\"phone\":\"+256781236358\",\"email\":\"adrole@mtn.com\",\"gender\":\"Male\",\"date_of_birth\":\"1979-08-08\",\"occupation\":\"Team Leader\",\"employer\":\"MTN\",\"address\":\"Ntinda\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 16:48:59','2026-06-21 16:48:59'),(113,1,'success','Create',NULL,NULL,'loans',18,NULL,NULL,NULL,'{\"member_id\":\"11\",\"product_id\":\"3\",\"amount\":\"100000\",\"period\":\"5\",\"purpose\":\"personal\",\"guarantors\":[\"7\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:17:01','2026-06-21 17:17:01'),(114,1,'success','LOAN_DISBURSED','loans',18,NULL,NULL,'{\"status\": \"approved\"}','{\"status\": \"disbursed\", \"disbursement_amount\": 100000}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:19:49','2026-06-21 17:19:49'),(115,1,'success','LOAN_DISBURSED','loans',10,NULL,NULL,'{\"status\": \"approved\"}','{\"status\": \"disbursed\", \"disbursement_amount\": 10000}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:20:22','2026-06-21 17:20:22'),(116,1,'success','LOAN_REPAYMENT','loan_repayments',19,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"300479.74\", \"total_payment\": 1000000, \"principal_paid\": 699520.26, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:21:37','2026-06-21 17:21:37'),(117,1,'success','LOAN_REPAYMENT','loan_repayments',20,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 100000, \"principal_paid\": 100000, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:23:09','2026-06-21 17:23:09'),(118,1,'success','LOAN_REPAYMENT','loan_repayments',21,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"2513.82\", \"total_payment\": 50000, \"principal_paid\": 47486.18, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:24:19','2026-06-21 17:24:19'),(119,1,'success','LOAN_REPAYMENT','loan_repayments',22,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 1203.98, \"total_payment\": 1203.98, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:32:01','2026-06-21 17:32:01'),(120,1,'success','LOAN_REPAYMENT','loan_repayments',23,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"2513.82\", \"total_payment\": 52513.82, \"principal_paid\": 50000, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:32:52','2026-06-21 17:32:52'),(121,1,'success','LOAN_REPAYMENT','loan_repayments',24,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 2513, \"total_payment\": 2513, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:33:52','2026-06-21 17:33:52'),(122,1,'success','Create',NULL,NULL,'members',12,NULL,NULL,NULL,'{\"full_name\":\"Kagimu Henry\",\"national_id\":\"CM8700090FM\",\"phone\":\"+256771458963\",\"email\":\"mine@test.com\",\"gender\":\"Male\",\"date_of_birth\":\"1976-05-01\",\"occupation\":\"Farmer\",\"employer\":\"Self Employed\",\"address\":\"Katosi\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:35:41','2026-06-21 17:35:41'),(123,1,'success','Create',NULL,NULL,'loans',19,NULL,NULL,NULL,'{\"member_id\":\"12\",\"product_id\":\"2\",\"amount\":\"150000\",\"period\":\"6\",\"purpose\":\"Fees\",\"guarantors\":[\"10\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:38:04','2026-06-21 17:38:04'),(124,1,'success','LOAN_DISBURSED','loans',19,NULL,NULL,'{\"status\": \"approved\"}','{\"status\": \"disbursed\", \"disbursement_amount\": 150000}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 17:38:42','2026-06-21 17:38:42'),(125,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:44:47','2026-06-21 18:44:47'),(126,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:44:58','2026-06-21 18:44:58'),(127,1,'success','LOAN_REPAYMENT','loan_repayments',25,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 2513.82, \"total_payment\": 2513.82, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:46:30','2026-06-21 18:46:30'),(128,1,'success','LOAN_DISBURSED','loans',6,NULL,NULL,'{\"status\": \"approved\"}','{\"status\": \"disbursed\", \"disbursement_amount\": 70000}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:50:46','2026-06-21 18:50:46'),(129,1,'success','LOAN_REPAYMENT','loan_repayments',26,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1051.74\", \"total_payment\": 50000, \"principal_paid\": 48948.26, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:51:51','2026-06-21 18:51:51'),(130,1,'success','LOAN_REPAYMENT','loan_repayments',27,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1051.74\", \"total_payment\": 10000, \"principal_paid\": 8948.26, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:52:21','2026-06-21 18:52:21'),(131,1,'success','LOAN_REPAYMENT','loan_repayments',28,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1051.74\", \"total_payment\": 8000, \"principal_paid\": 6948.26, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:52:41','2026-06-21 18:52:41'),(132,1,'success','LOAN_REPAYMENT','loan_repayments',29,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1051.74\", \"total_payment\": 5155.22, \"principal_paid\": 4103.4800000000005, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:52:59','2026-06-21 18:52:59'),(133,1,'success','LOAN_REPAYMENT','loan_repayments',30,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 1051.74, \"total_payment\": 1051.74, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:53:25','2026-06-21 18:53:25'),(134,1,'success','LOAN_REPAYMENT','loan_repayments',31,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 500, \"total_payment\": 500, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:53:43','2026-06-21 18:53:43'),(135,1,'success','LOAN_REPAYMENT','loan_repayments',32,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 1051, \"total_payment\": 1051, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:55:11','2026-06-21 18:55:11'),(136,1,'success','LOAN_REPAYMENT','loan_repayments',33,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 1500000, \"principal_paid\": 1500000, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:56:47','2026-06-21 18:56:47'),(137,1,'success','Create',NULL,NULL,'loans',20,NULL,NULL,NULL,'{\"member_id\":\"7\",\"product_id\":\"1\",\"amount\":\"50000\",\"period\":\"3\",\"purpose\":\"self\",\"guarantors\":[\"11\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 18:59:57','2026-06-21 18:59:57'),(138,1,'success','LOAN_DISBURSED','loans',20,NULL,NULL,'{\"status\": \"approved\"}','{\"status\": \"disbursed\", \"disbursement_amount\": 50000}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 19:00:50','2026-06-21 19:00:50'),(139,1,'success','LOAN_REPAYMENT','loan_repayments',34,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1003.32\", \"total_payment\": 30000, \"principal_paid\": 28996.68, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 19:04:20','2026-06-21 19:04:20'),(140,1,'success','LOAN_REPAYMENT','loan_repayments',35,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1003.32\", \"total_payment\": 15000, \"principal_paid\": 13996.68, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 19:07:40','2026-06-21 19:07:40'),(141,1,'success','LOAN_REPAYMENT','loan_repayments',36,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1003.32\", \"total_payment\": 5000, \"principal_paid\": 3996.68, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 19:08:40','2026-06-21 19:08:40'),(142,1,'success','LOAN_REPAYMENT','loan_repayments',37,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1003.32\", \"total_payment\": 1500, \"principal_paid\": 496.6799999999999, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 19:09:44','2026-06-21 19:09:44'),(143,1,'success','LOAN_REPAYMENT','loan_repayments',38,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1003.32\", \"total_payment\": 1500, \"principal_paid\": 496.6799999999999, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 19:11:16','2026-06-21 19:11:16'),(144,1,'success','LOAN_REPAYMENT','loan_repayments',39,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1003.32\", \"total_payment\": 1500, \"principal_paid\": 496.6799999999999, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 19:12:19','2026-06-21 19:12:19'),(145,1,'success','LOAN_REPAYMENT','loan_repayments',40,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": \"1003.32\", \"total_payment\": 1500, \"principal_paid\": 496.6799999999999, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 19:13:23','2026-06-21 19:13:23'),(146,1,'success','LOAN_REPAYMENT','loan_repayments',41,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 1000, \"total_payment\": 1000, \"principal_paid\": 0, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-21 19:15:19','2026-06-21 19:15:19'),(147,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-22 12:29:45','2026-06-22 12:29:45'),(148,1,'success','share_adjustment',NULL,NULL,'member_share_transactions',166,NULL,NULL,NULL,'{\"member_id\":3,\"shares\":4,\"amount\":40000,\"increase\":true,\"reason\":\"Min\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-22 12:40:32','2026-06-22 12:40:32'),(149,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-22 14:34:19','2026-06-22 14:34:19'),(150,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-22 14:34:22','2026-06-22 14:34:22'),(151,1,'success','LOAN_REPAYMENT','loan_repayments',42,NULL,NULL,NULL,'{\"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 10000, \"principal_paid\": 10000, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-22 14:37:38','2026-06-22 14:37:38'),(152,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 17:27:30','2026-06-23 17:27:30'),(153,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 18:50:02','2026-06-23 18:50:02'),(154,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 18:50:30','2026-06-23 18:50:30'),(155,1,'success','LOAN_REPAYMENT','loan_repayments',46,NULL,NULL,NULL,'{\"schedule_id\": 28, \"penalty_paid\": 0, \"interest_paid\": 833.33, \"total_payment\": 2513.82, \"principal_paid\": 1680.4900000000002, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:04:04','2026-06-23 19:04:04'),(156,1,'success','LOAN_REPAYMENT','loan_repayments',47,NULL,NULL,NULL,'{\"schedule_id\": 28, \"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 833.33, \"principal_paid\": 833.33, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:04:30','2026-06-23 19:04:30'),(157,1,'success','LOAN_REPAYMENT','loan_repayments',48,NULL,NULL,NULL,'{\"schedule_id\": 1, \"penalty_paid\": 0, \"interest_paid\": 600, \"total_payment\": 1203.98, \"principal_paid\": 603.98, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:05:14','2026-06-23 19:05:14'),(158,1,'success','LOAN_REPAYMENT','loan_repayments',49,NULL,NULL,NULL,'{\"schedule_id\": 1, \"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 600, \"principal_paid\": 600, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:05:36','2026-06-23 19:05:36'),(159,1,'success','LOAN_REPAYMENT','loan_repayments',50,NULL,NULL,NULL,'{\"schedule_id\": 56, \"penalty_paid\": 0, \"interest_paid\": 834.99, \"total_payment\": 30000, \"principal_paid\": 29165.01, \"balance_remaining\": 0}',NULL,NULL,'0.0.0.0','',NULL,'2026-06-23 19:29:27','2026-06-23 19:29:27'),(160,1,'success','LOAN_REPAYMENT','loan_repayments',51,NULL,NULL,NULL,'{\"schedule_id\": 57, \"penalty_paid\": 0, \"interest_paid\": 168.33, \"total_payment\": 15000, \"principal_paid\": 14831.67, \"balance_remaining\": 0}',NULL,NULL,'0.0.0.0','',NULL,'2026-06-23 19:29:28','2026-06-23 19:29:28'),(161,1,'success','LOAN_REPAYMENT','loan_repayments',52,NULL,NULL,NULL,'{\"schedule_id\": 58, \"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 5000, \"principal_paid\": 5000, \"balance_remaining\": 0}',NULL,NULL,'0.0.0.0','',NULL,'2026-06-23 19:29:29','2026-06-23 19:29:29'),(162,1,'success','LOAN_REPAYMENT','loan_repayments',53,NULL,NULL,NULL,'{\"schedule_id\": 58, \"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 1003.32, \"principal_paid\": 1003.3199999999996, \"balance_remaining\": 0.0000000000003410605131648481}',NULL,NULL,'0.0.0.0','',NULL,'2026-06-23 19:29:30','2026-06-23 19:29:30'),(163,1,'success','LOAN_REPAYMENT','loan_repayments',54,NULL,NULL,NULL,'{\"schedule_id\": 59, \"penalty_paid\": 0, \"interest_paid\": 834.99, \"total_payment\": 30000, \"principal_paid\": 29165.01, \"balance_remaining\": 0}',NULL,NULL,'0.0.0.0','',NULL,'2026-06-23 19:29:56','2026-06-23 19:29:56'),(164,1,'success','LOAN_REPAYMENT','loan_repayments',55,NULL,NULL,NULL,'{\"schedule_id\": 60, \"penalty_paid\": 0, \"interest_paid\": 168.33, \"total_payment\": 15000, \"principal_paid\": 14831.67, \"balance_remaining\": 0}',NULL,NULL,'0.0.0.0','',NULL,'2026-06-23 19:29:57','2026-06-23 19:29:57'),(165,1,'success','LOAN_REPAYMENT','loan_repayments',56,NULL,NULL,NULL,'{\"schedule_id\": 61, \"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 5000, \"principal_paid\": 5000, \"balance_remaining\": 0}',NULL,NULL,'0.0.0.0','',NULL,'2026-06-23 19:29:57','2026-06-23 19:29:57'),(166,1,'success','LOAN_REPAYMENT','loan_repayments',57,NULL,NULL,NULL,'{\"schedule_id\": 61, \"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 1003.32, \"principal_paid\": 1003.3199999999996, \"balance_remaining\": 0.0000000000003410605131648481}',NULL,NULL,'0.0.0.0','',NULL,'2026-06-23 19:29:57','2026-06-23 19:29:57'),(167,1,'success','Create',NULL,NULL,'loans',23,NULL,NULL,NULL,'{\"member_id\":\"2\",\"product_id\":\"2\",\"amount\":\"180000\",\"period\":\"6\",\"purpose\":\"Personal\",\"guarantors\":[\"5\"]}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:40:23','2026-06-23 19:40:23'),(168,1,'success','LOAN_DISBURSED','loans',23,NULL,NULL,'{\"status\": \"approved\"}','{\"status\": \"disbursed\", \"disbursement_amount\": 180000}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:41:11','2026-06-23 19:41:11'),(169,1,'success','LOAN_REPAYMENT','loan_repayments',58,NULL,NULL,NULL,'{\"schedule_id\": 62, \"penalty_paid\": 0, \"interest_paid\": 2250, \"total_payment\": 30000, \"principal_paid\": 27750, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:46:37','2026-06-23 19:46:37'),(170,1,'success','LOAN_REPAYMENT','loan_repayments',59,NULL,NULL,NULL,'{\"schedule_id\": 62, \"penalty_paid\": 0, \"interest_paid\": 3405.1, \"total_payment\": 50000, \"principal_paid\": 46594.9, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:47:17','2026-06-23 19:47:17'),(171,1,'success','LOAN_REPAYMENT','loan_repayments',60,NULL,NULL,NULL,'{\"schedule_id\": 64, \"penalty_paid\": 0, \"interest_paid\": 1914.67, \"total_payment\": 50000, \"principal_paid\": 48085.329999999994, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:47:58','2026-06-23 19:47:58'),(172,1,'success','LOAN_REPAYMENT','loan_repayments',61,NULL,NULL,NULL,'{\"schedule_id\": 66, \"penalty_paid\": 0, \"interest_paid\": 386.74, \"total_payment\": 40000, \"principal_paid\": 39613.26, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:52:09','2026-06-23 19:52:09'),(173,1,'success','LOAN_REPAYMENT','loan_repayments',62,NULL,NULL,NULL,'{\"schedule_id\": 67, \"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 16000, \"principal_paid\": 16000, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:53:37','2026-06-23 19:53:37'),(174,1,'success','LOAN_REPAYMENT','loan_repayments',63,NULL,NULL,NULL,'{\"schedule_id\": 67, \"penalty_paid\": 0, \"interest_paid\": 0, \"total_payment\": 1956.51, \"principal_paid\": 1956.51, \"balance_remaining\": 0}',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 19:56:25','2026-06-23 19:56:25'),(175,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 21:35:53','2026-06-23 21:35:53'),(176,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 21:37:30','2026-06-23 21:37:30'),(177,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 22:49:59','2026-06-23 22:49:59'),(178,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 22:50:08','2026-06-23 22:50:08'),(179,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:07:36','2026-06-23 23:07:36'),(180,5,'success','Login',NULL,NULL,'users',5,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:07:49','2026-06-23 23:07:49'),(181,5,'success','Logout',NULL,NULL,'users',5,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:08:28','2026-06-23 23:08:28'),(182,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:08:32','2026-06-23 23:08:32'),(183,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:10:21','2026-06-23 23:10:21'),(184,5,'success','Login',NULL,NULL,'users',5,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:10:30','2026-06-23 23:10:30'),(185,5,'success','Logout',NULL,NULL,'users',5,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:11:31','2026-06-23 23:11:31'),(186,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:11:34','2026-06-23 23:11:34'),(187,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:12:43','2026-06-23 23:12:43'),(188,5,'success','Login',NULL,NULL,'users',5,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:12:53','2026-06-23 23:12:53'),(189,5,'success','Logout',NULL,NULL,'users',5,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:14:18','2026-06-23 23:14:18'),(190,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:14:21','2026-06-23 23:14:21'),(191,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:15:28','2026-06-23 23:15:28'),(192,5,'success','Login',NULL,NULL,'users',5,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:15:38','2026-06-23 23:15:38'),(193,5,'success','share_purchase',NULL,NULL,'member_share_transactions',220,NULL,NULL,NULL,'{\"member_id\":7,\"shares\":1,\"amount\":10000,\"savings_account_id\":8}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-23 23:24:49','2026-06-23 23:24:49'),(194,5,'success','Logout',NULL,NULL,'users',5,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-24 10:18:01','2026-06-24 10:18:01'),(195,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-24 10:18:27','2026-06-24 10:18:27'),(196,1,'success','Logout',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-24 11:45:39','2026-06-24 11:45:39'),(197,1,'success','Login',NULL,NULL,'users',1,NULL,NULL,NULL,NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-24 11:45:59','2026-06-24 11:45:59'),(198,1,'success','Create',NULL,NULL,'members',13,NULL,NULL,NULL,'{\"full_name\":\"Esther Akiiki\",\"national_id\":\"CM878820FM\",\"phone\":\"+256789236354\",\"email\":\"mine@test.com\",\"gender\":\"Female\",\"date_of_birth\":\"1998-02-01\",\"occupation\":\"Teacher\",\"employer\":\"Government \",\"address\":\"\"}','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0',NULL,'2026-06-24 12:10:24','2026-06-24 12:10:24');

/*Table structure for table `bank_accounts` */

DROP TABLE IF EXISTS `bank_accounts`;

CREATE TABLE `bank_accounts` (
  `bank_account_id` bigint NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(150) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `currency` varchar(20) NOT NULL DEFAULT 'UGX',
  `account_type` varchar(50) NOT NULL DEFAULT 'current',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`bank_account_id`),
  UNIQUE KEY `idx_bank_account_number` (`account_number`),
  KEY `idx_bank_name` (`bank_name`),
  KEY `idx_bank_active` (`is_active`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `bank_accounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `bank_accounts_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `bank_accounts` */

/*Table structure for table `branches` */

DROP TABLE IF EXISTS `branches`;

CREATE TABLE `branches` (
  `branch_id` int NOT NULL AUTO_INCREMENT,
  `branch_code` varchar(10) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `manager_id` int DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`branch_id`),
  UNIQUE KEY `branch_code` (`branch_code`),
  KEY `manager_id` (`manager_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `branches` */

insert  into `branches`(`branch_id`,`branch_code`,`branch_name`,`location`,`phone`,`email`,`manager_id`,`status`) values (1,'HQ','Head Office','Kampala',NULL,NULL,NULL,'active');

/*Table structure for table `cash_book` */

DROP TABLE IF EXISTS `cash_book`;

CREATE TABLE `cash_book` (
  `cash_book_id` bigint NOT NULL AUTO_INCREMENT,
  `cash_book_date` date NOT NULL,
  `description` text,
  `amount` decimal(15,2) NOT NULL,
  `entry_type` enum('receipt','payment') NOT NULL,
  `bank_account_id` bigint DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `posted_by` int DEFAULT NULL,
  `status` enum('pending','posted','reconciled') NOT NULL DEFAULT 'posted',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cash_book_id`),
  KEY `idx_cash_book_date` (`cash_book_date`),
  KEY `idx_cash_book_entry_type` (`entry_type`),
  KEY `idx_cash_book_status` (`status`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `posted_by` (`posted_by`),
  CONSTRAINT `cash_book_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`bank_account_id`) ON DELETE SET NULL,
  CONSTRAINT `cash_book_ibfk_2` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `cash_book` */

/*Table structure for table `chart_of_accounts` */

DROP TABLE IF EXISTS `chart_of_accounts`;

CREATE TABLE `chart_of_accounts` (
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `account_type` enum('asset','liability','equity','income','expense') NOT NULL,
  `account_category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_code`),
  KEY `idx_coa_type` (`account_type`),
  KEY `idx_coa_active` (`is_active`),
  KEY `idx_coa_category` (`account_category`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `chart_of_accounts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `chart_of_accounts_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `chart_of_accounts` */

insert  into `chart_of_accounts`(`account_code`,`account_name`,`account_type`,`account_category`,`is_active`,`created_by`,`updated_by`,`created_at`,`updated_at`) values ('1010','Cash','asset','Current Asset',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('1020','Main Bank Account','asset','Current Asset',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('1030','Loans Receivable','asset','Current Asset',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('2010','Member Savings','liability','Member Liability',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('2020','Member Deposits','liability','Member Liability',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('3010','Share Capital','equity','Owner Equity',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('3020','Retained Earnings','equity','Owner Equity',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('4010','Loan Interest Income','income','Operating Income',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('4020','Processing Fee Income','income','Operating Income',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('4030','Penalty Income','income','Operating Income',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('4040','Other Income','income','Operating Income',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('5010','Staff Costs','expense','Operating Expense',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('5020','Administrative Expenses','expense','Operating Expense',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('5030','Utilities','expense','Operating Expense',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('5040','Rent','expense','Operating Expense',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('5050','Fuel','expense','Operating Expense',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07'),('5060','Internet','expense','Operating Expense',1,NULL,NULL,'2026-06-12 15:40:07','2026-06-12 15:40:07');

/*Table structure for table `journal_entries` */

DROP TABLE IF EXISTS `journal_entries`;

CREATE TABLE `journal_entries` (
  `journal_entry_id` bigint NOT NULL AUTO_INCREMENT,
  `entry_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reference_number` varchar(100) NOT NULL,
  `description` text,
  `posted_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `status` enum('draft','posted','reversed') NOT NULL DEFAULT 'posted',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`journal_entry_id`),
  KEY `idx_journal_reference` (`reference_number`),
  KEY `idx_journal_status` (`status`),
  KEY `posted_by` (`posted_by`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`posted_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `journal_entries_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `journal_entries` */

/*Table structure for table `journal_entry_lines` */

DROP TABLE IF EXISTS `journal_entry_lines`;

CREATE TABLE `journal_entry_lines` (
  `journal_entry_line_id` bigint NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(150) NOT NULL,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `description` text,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `account_type` varchar(50) DEFAULT NULL,
  `member_id` int DEFAULT NULL,
  `related_member_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`journal_entry_line_id`),
  KEY `idx_journal_entry_id` (`journal_entry_id`),
  KEY `idx_journal_account_code` (`account_code`),
  CONSTRAINT `journal_entry_lines_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`journal_entry_id`) ON DELETE CASCADE,
  CONSTRAINT `journal_entry_lines_ibfk_2` FOREIGN KEY (`account_code`) REFERENCES `chart_of_accounts` (`account_code`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `journal_entry_lines` */

/*Table structure for table `ledger_entries` */

DROP TABLE IF EXISTS `ledger_entries`;

CREATE TABLE `ledger_entries` (
  `entry_id` bigint NOT NULL AUTO_INCREMENT,
  `ledger_code` varchar(20) NOT NULL,
  `ledger_name` varchar(100) NOT NULL,
  `entry_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `receipt_number` varchar(100) DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `description` text,
  `payment_method` varchar(50) DEFAULT NULL,
  `posted_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `member_id` int DEFAULT NULL,
  `related_member_id` int DEFAULT NULL,
  `account_type` varchar(50) DEFAULT NULL,
  `status` enum('pending','posted','reversed') DEFAULT 'posted',
  `reversal_of_id` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`entry_id`),
  KEY `posted_by` (`posted_by`),
  KEY `approved_by` (`approved_by`),
  KEY `related_member_id` (`related_member_id`),
  KEY `idx_ledger_code` (`ledger_code`),
  KEY `idx_entry_date` (`entry_date`),
  KEY `idx_member` (`member_id`),
  KEY `idx_receipt` (`receipt_number`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `ledger_entries` */

insert  into `ledger_entries`(`entry_id`,`ledger_code`,`ledger_name`,`entry_date`,`receipt_number`,`transaction_reference`,`transaction_type`,`debit`,`credit`,`description`,`payment_method`,`posted_by`,`approved_by`,`member_id`,`related_member_id`,`account_type`,`status`,`reversal_of_id`,`created_at`) values (1,'2010','Member Savings','2026-05-29 20:34:12','SHR-3-1780076050','SHR-3-1780076050','SHARE_PURCHASE','20000.00','0.00','Transfer from savings to share capital','internal',1,NULL,3,NULL,'savings','posted',NULL,'2026-05-29 20:34:12'),(2,'3010','Share Capital','2026-05-29 20:34:13','SHR-3-1780076050','SHR-3-1780076050','SHARE_PURCHASE','0.00','20000.00','Member share capital increase','internal',1,NULL,3,NULL,'shares','posted',NULL,'2026-05-29 20:34:13'),(3,'3010','Share Capital','2026-05-29 21:13:51','STR-3-4-1780078429','STR-3-4-1780078429','SHARE_TRANSFER_OUT','20000.00','0.00','Share transfer out to member 4',NULL,1,NULL,3,4,'shares','posted',NULL,'2026-05-29 21:13:51'),(4,'3010','Share Capital','2026-05-29 21:13:51','STR-3-4-1780078429','STR-3-4-1780078429','SHARE_TRANSFER_IN','0.00','20000.00','Share transfer in from member 3',NULL,1,NULL,4,3,'shares','posted',NULL,'2026-05-29 21:13:51'),(5,'2020','Member Deposits','2026-06-17 10:17:53','SHR-7-1781680671','SHR-7-1781680671','SHARE_PURCHASE','40000.00','0.00','Transfer from savings to share capital','internal',1,NULL,7,NULL,'savings','posted',NULL,'2026-06-17 10:17:53'),(6,'2010','Member Savings','2026-06-17 10:17:53','SHR-7-1781680671','SHR-7-1781680671','SHARE_PURCHASE','0.00','40000.00','Member share capital increase','internal',1,NULL,7,NULL,'shares','posted',NULL,'2026-06-17 10:17:53'),(7,'2020','Member Deposits','2026-06-17 10:18:15','SHR-6-1781680695','SHR-6-1781680695','SHARE_PURCHASE','30000.00','0.00','Transfer from savings to share capital','internal',1,NULL,6,NULL,'savings','posted',NULL,'2026-06-17 10:18:15'),(8,'2010','Member Savings','2026-06-17 10:18:15','SHR-6-1781680695','SHR-6-1781680695','SHARE_PURCHASE','0.00','30000.00','Member share capital increase','internal',1,NULL,6,NULL,'shares','posted',NULL,'2026-06-17 10:18:15'),(9,'2010','Member Savings','2026-06-17 10:19:52','STR-4-7-1781680792','STR-4-7-1781680792','SHARE_TRANSFER_OUT','40000.00','0.00','Share transfer out to member 7',NULL,1,NULL,4,7,'shares','posted',NULL,'2026-06-17 10:19:52'),(10,'2010','Member Savings','2026-06-17 10:19:52','STR-4-7-1781680792','STR-4-7-1781680792','SHARE_TRANSFER_IN','0.00','40000.00','Share transfer in from member 4',NULL,1,NULL,7,4,'shares','posted',NULL,'2026-06-17 10:19:52'),(13,'1030','Loans Receivable','2026-06-17 14:07:57','LD20260617110756670','LD20260617110756670','general_entry','10000.00','0.00','Loan disbursement for loan #13',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:07:57'),(14,'1020','Main Bank Account','2026-06-17 14:07:58','LD20260617110756670','LD20260617110756670','general_entry','0.00','10000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:07:58'),(15,'1030','Loans Receivable','2026-06-17 14:08:18','LD20260617110817214','LD20260617110817214','general_entry','10000.00','0.00','Loan disbursement for loan #14',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:08:18'),(16,'1020','Main Bank Account','2026-06-17 14:08:18','LD20260617110817214','LD20260617110817214','general_entry','0.00','10000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:08:18'),(17,'1010','Cash','2026-06-17 14:08:18','RP20260617110818890','RP20260617110818890','general_entry','1000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:08:18'),(18,'1030','Loans Receivable','2026-06-17 14:08:18','RP20260617110818890','RP20260617110818890','general_entry','0.00','1000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:08:18'),(19,'1030','Loans Receivable','2026-06-17 14:28:17','LD20260617112815121','LD20260617112815121','general_entry','10000.00','0.00','Loan disbursement for loan #16',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:28:17'),(20,'1020','Main Bank Account','2026-06-17 14:28:17','LD20260617112815121','LD20260617112815121','general_entry','0.00','10000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:28:17'),(21,'1010','Cash','2026-06-17 14:28:17','RP20260617112817416','RP20260617112817416','general_entry','1000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:28:17'),(22,'1030','Loans Receivable','2026-06-17 14:28:17','RP20260617112817416','RP20260617112817416','general_entry','0.00','1000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:28:17'),(27,'1030','Loans Receivable','2026-06-17 14:55:13','LD20260617145511343','LD20260617145511343','general_entry','60000.00','0.00','Loan disbursement for loan #17',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:55:13'),(28,'1020','Main Bank Account','2026-06-17 14:55:13','LD20260617145511343','LD20260617145511343','general_entry','0.00','60000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:55:13'),(29,'1030','Loans Receivable','2026-06-17 14:56:25','LD20260617145624487','LD20260617145624487','general_entry','3000000.00','0.00','Loan disbursement for loan #15',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:56:25'),(30,'1020','Main Bank Account','2026-06-17 14:56:25','LD20260617145624487','LD20260617145624487','general_entry','0.00','3000000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 14:56:25'),(37,'1010','Cash','2026-06-17 18:51:09','RP20260617185109210','RP20260617185109210','general_entry','50000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 18:51:09'),(38,'1030','Loans Receivable','2026-06-17 18:51:09','RP20260617185109210','RP20260617185109210','general_entry','0.00','48796.02','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 18:51:09'),(39,'4010','Loan Interest Income','2026-06-17 18:51:09','RP20260617185109210','RP20260617185109210','general_entry','0.00','1203.98','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 18:51:09'),(40,'1010','Cash','2026-06-17 18:52:39','RP20260617185239943','RP20260617185239943','general_entry','11203.98','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 18:52:39'),(41,'1030','Loans Receivable','2026-06-17 18:52:39','RP20260617185239943','RP20260617185239943','general_entry','0.00','10000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 18:52:39'),(42,'4010','Loan Interest Income','2026-06-17 18:52:39','RP20260617185239943','RP20260617185239943','general_entry','0.00','1203.98','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-17 18:52:39'),(43,'2020','Member Deposits','2026-06-17 18:54:07','SHR-8-1781711646','SHR-8-1781711646','SHARE_PURCHASE','120000.00','0.00','Transfer from savings to share capital','internal',1,NULL,8,NULL,'savings','posted',NULL,'2026-06-17 18:54:07'),(44,'2010','Member Savings','2026-06-17 18:54:07','SHR-8-1781711646','SHR-8-1781711646','SHARE_PURCHASE','0.00','120000.00','Member share capital increase','internal',1,NULL,8,NULL,'shares','posted',NULL,'2026-06-17 18:54:07'),(45,'2010','Member Savings','2026-06-17 18:54:50','STR-4-8-1781711690','STR-4-8-1781711690','SHARE_TRANSFER_OUT','40000.00','0.00','Share transfer out to member 8',NULL,1,NULL,4,8,'shares','posted',NULL,'2026-06-17 18:54:50'),(46,'2010','Member Savings','2026-06-17 18:54:50','STR-4-8-1781711690','STR-4-8-1781711690','SHARE_TRANSFER_IN','0.00','40000.00','Share transfer in from member 4',NULL,1,NULL,8,4,'shares','posted',NULL,'2026-06-17 18:54:50'),(47,'1010','Cash','2026-06-18 19:09:13','SD20260618190913943','SD20260618190913943','general_entry','250000.00','0.00','Savings deposit received (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 19:09:13'),(48,'2010','Member Savings','2026-06-18 19:09:14','SD20260618190913943','SD20260618190913943','general_entry','0.00','250000.00','Member savings deposit',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 19:09:14'),(49,'2010','Member Savings','2026-06-18 19:10:36','SW20260618191036934','SW20260618191036934','general_entry','150000.00','0.00','Savings withdrawal',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 19:10:36'),(50,'1010','Cash','2026-06-18 19:10:36','SW20260618191036934','SW20260618191036934','general_entry','0.00','150000.00','Savings paid out (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 19:10:36'),(51,'1010','Cash','2026-06-18 19:14:14','SD20260618191414832','SD20260618191414832','general_entry','180000.00','0.00','Savings deposit received (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 19:14:14'),(52,'2010','Member Savings','2026-06-18 19:14:14','SD20260618191414832','SD20260618191414832','general_entry','0.00','180000.00','Member savings deposit',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 19:14:14'),(53,'2010','Member Savings','2026-06-18 19:14:52','SHR-10-1781799290','SHR-10-1781799290','SHARE_PURCHASE','60000.00','0.00','Transfer from savings to share capital','internal',1,NULL,10,NULL,'savings','posted',NULL,'2026-06-18 19:14:52'),(54,'3010','Share Capital','2026-06-18 19:14:52','SHR-10-1781799290','SHR-10-1781799290','SHARE_PURCHASE','0.00','60000.00','Member share capital increase','internal',1,NULL,10,NULL,'shares','posted',NULL,'2026-06-18 19:14:52'),(55,'1010','Cash','2026-06-18 20:18:14','SD20260618201811633','SD20260618201811633','general_entry','2000.00','0.00','Savings deposit received (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:18:14'),(56,'2010','Member Savings','2026-06-18 20:18:14','SD20260618201811633','SD20260618201811633','general_entry','0.00','2000.00','Member savings deposit',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:18:14'),(57,'2010','Member Savings','2026-06-18 20:18:41','SW20260618201840847','SW20260618201840847','general_entry','4000.00','0.00','Savings withdrawal',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:18:41'),(58,'1010','Cash','2026-06-18 20:18:41','SW20260618201840847','SW20260618201840847','general_entry','0.00','4000.00','Savings paid out (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:18:41'),(59,'1030','Loans Receivable','2026-06-18 20:19:22','LD20260618201922446','LD20260618201922446','general_entry','10000.00','0.00','Loan disbursement for loan #12',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:19:22'),(60,'1020','Main Bank Account','2026-06-18 20:19:23','LD20260618201922446','LD20260618201922446','general_entry','0.00','10000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:19:23'),(61,'1010','Cash','2026-06-18 20:20:28','RP20260618202028761','RP20260618202028761','general_entry','1203.98','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:20:28'),(62,'4010','Loan Interest Income','2026-06-18 20:20:28','RP20260618202028761','RP20260618202028761','general_entry','0.00','1203.98','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:20:28'),(71,'1010','Cash','2026-06-18 20:41:44','RP20260618204143328','RP20260618204143328','general_entry','10000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:41:44'),(72,'1030','Loans Receivable','2026-06-18 20:41:44','RP20260618204143328','RP20260618204143328','general_entry','0.00','10000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:41:44'),(73,'1010','Cash','2026-06-18 20:42:32','RP20260618204231389','RP20260618204231389','general_entry','1203.98','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:42:32'),(74,'4010','Loan Interest Income','2026-06-18 20:42:32','RP20260618204231389','RP20260618204231389','general_entry','0.00','1203.98','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:42:32'),(75,'1010','Cash','2026-06-18 20:43:18','RP20260618204318252','RP20260618204318252','general_entry','1203.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:43:18'),(76,'4010','Loan Interest Income','2026-06-18 20:43:18','RP20260618204318252','RP20260618204318252','general_entry','0.00','1203.00','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-18 20:43:18'),(77,'1020','Main Bank Account','2026-06-21 13:22:01','SD20260621132200639','SD20260621132200639','general_entry','250000.00','0.00','Savings deposit received (cheque)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 13:22:01'),(78,'2010','Member Savings','2026-06-21 13:22:01','SD20260621132200639','SD20260621132200639','general_entry','0.00','250000.00','Member savings deposit',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 13:22:01'),(79,'2010','Member Savings','2026-06-21 13:23:39','SW20260621132339996','SW20260621132339996','general_entry','30000.00','0.00','Savings withdrawal',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 13:23:39'),(80,'1010','Cash','2026-06-21 13:23:39','SW20260621132339996','SW20260621132339996','general_entry','0.00','30000.00','Savings paid out (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 13:23:39'),(81,'2010','Member Savings','2026-06-21 13:24:33','SHR-4-1782037472','SHR-4-1782037472','SHARE_PURCHASE','40000.00','0.00','Transfer from savings to share capital','internal',1,NULL,4,NULL,'savings','posted',NULL,'2026-06-21 13:24:33'),(82,'3010','Share Capital','2026-06-21 13:24:33','SHR-4-1782037472','SHR-4-1782037472','SHARE_PURCHASE','0.00','40000.00','Member share capital increase','internal',1,NULL,4,NULL,'shares','posted',NULL,'2026-06-21 13:24:33'),(83,'1020','Main Bank Account','2026-06-21 16:50:51','SD20260621165049806','SD20260621165049806','general_entry','100000.00','0.00','Savings deposit received (mobile_money)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 16:50:51'),(84,'2010','Member Savings','2026-06-21 16:50:51','SD20260621165049806','SD20260621165049806','general_entry','0.00','100000.00','Member savings deposit',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 16:50:51'),(85,'1010','Cash','2026-06-21 16:51:28','SD20260621165127521','SD20260621165127521','general_entry','500000.00','0.00','Savings deposit received (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 16:51:28'),(86,'2010','Member Savings','2026-06-21 16:51:28','SD20260621165127521','SD20260621165127521','general_entry','0.00','500000.00','Member savings deposit',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 16:51:28'),(87,'2010','Member Savings','2026-06-21 16:52:05','SW20260621165204197','SW20260621165204197','general_entry','300000.00','0.00','Savings withdrawal',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 16:52:05'),(88,'1010','Cash','2026-06-21 16:52:05','SW20260621165204197','SW20260621165204197','general_entry','0.00','300000.00','Savings paid out (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 16:52:05'),(89,'2010','Member Savings','2026-06-21 17:15:24','SW20260621171523919','SW20260621171523919','general_entry','20000.00','0.00','Savings withdrawal',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:15:24'),(90,'1010','Cash','2026-06-21 17:15:24','SW20260621171523919','SW20260621171523919','general_entry','0.00','20000.00','Savings paid out (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:15:24'),(91,'1030','Loans Receivable','2026-06-21 17:19:49','LD20260621171949498','LD20260621171949498','general_entry','100000.00','0.00','Loan disbursement for loan #18',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:19:49'),(92,'1020','Main Bank Account','2026-06-21 17:19:49','LD20260621171949498','LD20260621171949498','general_entry','0.00','100000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:19:49'),(93,'1030','Loans Receivable','2026-06-21 17:20:22','LD20260621172022248','LD20260621172022248','general_entry','10000.00','0.00','Loan disbursement for loan #10',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:20:22'),(94,'1020','Main Bank Account','2026-06-21 17:20:22','LD20260621172022248','LD20260621172022248','general_entry','0.00','10000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:20:22'),(95,'1010','Cash','2026-06-21 17:21:36','RP20260621172136918','RP20260621172136918','general_entry','1000000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:21:36'),(96,'1030','Loans Receivable','2026-06-21 17:21:36','RP20260621172136918','RP20260621172136918','general_entry','0.00','699520.26','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:21:36'),(97,'4010','Loan Interest Income','2026-06-21 17:21:36','RP20260621172136918','RP20260621172136918','general_entry','0.00','300479.74','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:21:36'),(98,'1010','Cash','2026-06-21 17:23:09','RP20260621172309138','RP20260621172309138','general_entry','100000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:23:09'),(99,'1030','Loans Receivable','2026-06-21 17:23:09','RP20260621172309138','RP20260621172309138','general_entry','0.00','100000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:23:09'),(100,'1010','Cash','2026-06-21 17:24:19','RP20260621172418529','RP20260621172418529','general_entry','50000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:24:19'),(101,'1030','Loans Receivable','2026-06-21 17:24:19','RP20260621172418529','RP20260621172418529','general_entry','0.00','47486.18','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:24:19'),(102,'4010','Loan Interest Income','2026-06-21 17:24:19','RP20260621172418529','RP20260621172418529','general_entry','0.00','2513.82','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:24:19'),(103,'1010','Cash','2026-06-21 17:32:00','RP20260621173200938','RP20260621173200938','general_entry','1203.98','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:32:00'),(104,'4010','Loan Interest Income','2026-06-21 17:32:00','RP20260621173200938','RP20260621173200938','general_entry','0.00','1203.98','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:32:00'),(105,'1010','Cash','2026-06-21 17:32:52','RP20260621173252323','RP20260621173252323','general_entry','52513.82','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:32:52'),(106,'1030','Loans Receivable','2026-06-21 17:32:52','RP20260621173252323','RP20260621173252323','general_entry','0.00','50000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:32:52'),(107,'4010','Loan Interest Income','2026-06-21 17:32:52','RP20260621173252323','RP20260621173252323','general_entry','0.00','2513.82','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:32:52'),(108,'1010','Cash','2026-06-21 17:33:52','RP20260621173352764','RP20260621173352764','general_entry','2513.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:33:52'),(109,'4010','Loan Interest Income','2026-06-21 17:33:52','RP20260621173352764','RP20260621173352764','general_entry','0.00','2513.00','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:33:52'),(110,'1010','Cash','2026-06-21 17:36:51','SD20260621173650746','SD20260621173650746','general_entry','50000.00','0.00','Savings deposit received (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:36:51'),(111,'2010','Member Savings','2026-06-21 17:36:51','SD20260621173650746','SD20260621173650746','general_entry','0.00','50000.00','Member savings deposit',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:36:51'),(112,'1010','Cash','2026-06-21 17:37:22','SD20260621173722790','SD20260621173722790','general_entry','60000.00','0.00','Savings deposit received (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:37:22'),(113,'2010','Member Savings','2026-06-21 17:37:22','SD20260621173722790','SD20260621173722790','general_entry','0.00','60000.00','Member savings deposit',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:37:22'),(114,'1030','Loans Receivable','2026-06-21 17:38:42','LD20260621173842854','LD20260621173842854','general_entry','150000.00','0.00','Loan disbursement for loan #19',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:38:42'),(115,'1020','Main Bank Account','2026-06-21 17:38:42','LD20260621173842854','LD20260621173842854','general_entry','0.00','150000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 17:38:42'),(116,'1010','Cash','2026-06-21 18:46:29','RP20260621184628637','RP20260621184628637','general_entry','2513.82','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:46:29'),(117,'4010','Loan Interest Income','2026-06-21 18:46:30','RP20260621184628637','RP20260621184628637','general_entry','0.00','2513.82','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:46:30'),(118,'1030','Loans Receivable','2026-06-21 18:50:45','LD20260621185045161','LD20260621185045161','general_entry','70000.00','0.00','Loan disbursement for loan #6',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:50:45'),(119,'1020','Main Bank Account','2026-06-21 18:50:45','LD20260621185045161','LD20260621185045161','general_entry','0.00','70000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:50:45'),(120,'1010','Cash','2026-06-21 18:51:50','RP20260621185150170','RP20260621185150170','general_entry','50000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:51:50'),(121,'1030','Loans Receivable','2026-06-21 18:51:50','RP20260621185150170','RP20260621185150170','general_entry','0.00','48948.26','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:51:50'),(122,'4010','Loan Interest Income','2026-06-21 18:51:50','RP20260621185150170','RP20260621185150170','general_entry','0.00','1051.74','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:51:50'),(123,'1010','Cash','2026-06-21 18:52:21','RP20260621185220640','RP20260621185220640','general_entry','10000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:52:21'),(124,'1030','Loans Receivable','2026-06-21 18:52:21','RP20260621185220640','RP20260621185220640','general_entry','0.00','8948.26','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:52:21'),(125,'4010','Loan Interest Income','2026-06-21 18:52:21','RP20260621185220640','RP20260621185220640','general_entry','0.00','1051.74','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:52:21'),(126,'1010','Cash','2026-06-21 18:52:41','RP20260621185241637','RP20260621185241637','general_entry','8000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:52:41'),(127,'1030','Loans Receivable','2026-06-21 18:52:41','RP20260621185241637','RP20260621185241637','general_entry','0.00','6948.26','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:52:41'),(128,'4010','Loan Interest Income','2026-06-21 18:52:41','RP20260621185241637','RP20260621185241637','general_entry','0.00','1051.74','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:52:41'),(129,'1010','Cash','2026-06-21 18:52:59','RP20260621185259297','RP20260621185259297','general_entry','5155.22','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:52:59'),(130,'1030','Loans Receivable','2026-06-21 18:52:59','RP20260621185259297','RP20260621185259297','general_entry','0.00','4103.48','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:52:59'),(131,'4010','Loan Interest Income','2026-06-21 18:52:59','RP20260621185259297','RP20260621185259297','general_entry','0.00','1051.74','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:52:59'),(132,'1010','Cash','2026-06-21 18:53:25','RP20260621185324420','RP20260621185324420','general_entry','1051.74','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:53:25'),(133,'4010','Loan Interest Income','2026-06-21 18:53:25','RP20260621185324420','RP20260621185324420','general_entry','0.00','1051.74','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:53:25'),(134,'1010','Cash','2026-06-21 18:53:42','RP20260621185342410','RP20260621185342410','general_entry','500.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:53:42'),(135,'4010','Loan Interest Income','2026-06-21 18:53:42','RP20260621185342410','RP20260621185342410','general_entry','0.00','500.00','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:53:42'),(136,'1010','Cash','2026-06-21 18:55:11','RP20260621185511142','RP20260621185511142','general_entry','1051.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:55:11'),(137,'4010','Loan Interest Income','2026-06-21 18:55:11','RP20260621185511142','RP20260621185511142','general_entry','0.00','1051.00','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:55:11'),(138,'1010','Cash','2026-06-21 18:56:47','RP20260621185647483','RP20260621185647483','general_entry','1500000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:56:47'),(139,'1030','Loans Receivable','2026-06-21 18:56:47','RP20260621185647483','RP20260621185647483','general_entry','0.00','1500000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 18:56:47'),(140,'1030','Loans Receivable','2026-06-21 19:00:50','LD20260621190050458','LD20260621190050458','general_entry','50000.00','0.00','Loan disbursement for loan #20',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:00:50'),(141,'1020','Main Bank Account','2026-06-21 19:00:50','LD20260621190050458','LD20260621190050458','general_entry','0.00','50000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:00:50'),(142,'1010','Cash','2026-06-21 19:04:20','RP20260621190419147','RP20260621190419147','general_entry','30000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:04:20'),(143,'1030','Loans Receivable','2026-06-21 19:04:20','RP20260621190419147','RP20260621190419147','general_entry','0.00','28996.68','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:04:20'),(144,'4010','Loan Interest Income','2026-06-21 19:04:20','RP20260621190419147','RP20260621190419147','general_entry','0.00','1003.32','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:04:20'),(145,'1010','Cash','2026-06-21 19:07:40','RP20260621190740259','RP20260621190740259','general_entry','15000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:07:40'),(146,'1030','Loans Receivable','2026-06-21 19:07:40','RP20260621190740259','RP20260621190740259','general_entry','0.00','13996.68','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:07:40'),(147,'4010','Loan Interest Income','2026-06-21 19:07:40','RP20260621190740259','RP20260621190740259','general_entry','0.00','1003.32','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:07:40'),(148,'1010','Cash','2026-06-21 19:08:40','RP20260621190840358','RP20260621190840358','general_entry','5000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:08:40'),(149,'1030','Loans Receivable','2026-06-21 19:08:40','RP20260621190840358','RP20260621190840358','general_entry','0.00','3996.68','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:08:40'),(150,'4010','Loan Interest Income','2026-06-21 19:08:40','RP20260621190840358','RP20260621190840358','general_entry','0.00','1003.32','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:08:40'),(151,'1010','Cash','2026-06-21 19:09:44','RP20260621190943461','RP20260621190943461','general_entry','1500.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:09:44'),(152,'1030','Loans Receivable','2026-06-21 19:09:44','RP20260621190943461','RP20260621190943461','general_entry','0.00','496.68','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:09:44'),(153,'4010','Loan Interest Income','2026-06-21 19:09:44','RP20260621190943461','RP20260621190943461','general_entry','0.00','1003.32','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:09:44'),(154,'1010','Cash','2026-06-21 19:11:16','RP20260621191116658','RP20260621191116658','general_entry','1500.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:11:16'),(155,'1030','Loans Receivable','2026-06-21 19:11:16','RP20260621191116658','RP20260621191116658','general_entry','0.00','496.68','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:11:16'),(156,'4010','Loan Interest Income','2026-06-21 19:11:16','RP20260621191116658','RP20260621191116658','general_entry','0.00','1003.32','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:11:16'),(157,'1010','Cash','2026-06-21 19:12:19','RP20260621191218618','RP20260621191218618','general_entry','1500.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:12:19'),(158,'1030','Loans Receivable','2026-06-21 19:12:19','RP20260621191218618','RP20260621191218618','general_entry','0.00','496.68','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:12:19'),(159,'4010','Loan Interest Income','2026-06-21 19:12:19','RP20260621191218618','RP20260621191218618','general_entry','0.00','1003.32','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:12:19'),(160,'1010','Cash','2026-06-21 19:13:23','RP20260621191322646','RP20260621191322646','general_entry','1500.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:13:23'),(161,'1030','Loans Receivable','2026-06-21 19:13:23','RP20260621191322646','RP20260621191322646','general_entry','0.00','496.68','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:13:23'),(162,'4010','Loan Interest Income','2026-06-21 19:13:23','RP20260621191322646','RP20260621191322646','general_entry','0.00','1003.32','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:13:23'),(163,'1010','Cash','2026-06-21 19:15:19','RP20260621191519120','RP20260621191519120','general_entry','1000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:15:19'),(164,'4010','Loan Interest Income','2026-06-21 19:15:19','RP20260621191519120','RP20260621191519120','general_entry','0.00','1000.00','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-21 19:15:19'),(165,'3020','Retained Earnings','2026-06-22 12:40:32','SADJ-3-1782121232','SADJ-3-1782121232','SHARE_ADJUSTMENT','40000.00','0.00','Manual share adjustment increase for member 3',NULL,1,NULL,3,NULL,'adjustment','posted',NULL,'2026-06-22 12:40:32'),(166,'3010','Share Capital','2026-06-22 12:40:32','SADJ-3-1782121232','SADJ-3-1782121232','SHARE_ADJUSTMENT','0.00','40000.00','Manual share adjustment increase for member 3',NULL,1,NULL,3,NULL,'shares','posted',NULL,'2026-06-22 12:40:32'),(167,'1010','Cash','2026-06-22 14:37:38','RP20260622143738713','RP20260622143738713','general_entry','10000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-22 14:37:38'),(168,'1030','Loans Receivable','2026-06-22 14:37:38','RP20260622143738713','RP20260622143738713','general_entry','0.00','10000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-22 14:37:38'),(169,'1010','Cash','2026-06-23 19:04:03','RP20260623190400640','RP20260623190400640','general_entry','2513.82','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:04:03'),(170,'1030','Loans Receivable','2026-06-23 19:04:04','RP20260623190400640','RP20260623190400640','general_entry','0.00','1680.49','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:04:04'),(171,'4010','Loan Interest Income','2026-06-23 19:04:04','RP20260623190400640','RP20260623190400640','general_entry','0.00','833.33','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:04:04'),(172,'1010','Cash','2026-06-23 19:04:30','RP20260623190430496','RP20260623190430496','general_entry','833.33','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:04:30'),(173,'1030','Loans Receivable','2026-06-23 19:04:30','RP20260623190430496','RP20260623190430496','general_entry','0.00','833.33','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:04:30'),(174,'1010','Cash','2026-06-23 19:05:14','RP20260623190514184','RP20260623190514184','general_entry','1203.98','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:05:14'),(175,'1030','Loans Receivable','2026-06-23 19:05:14','RP20260623190514184','RP20260623190514184','general_entry','0.00','603.98','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:05:14'),(176,'4010','Loan Interest Income','2026-06-23 19:05:14','RP20260623190514184','RP20260623190514184','general_entry','0.00','600.00','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:05:14'),(177,'1010','Cash','2026-06-23 19:05:36','RP20260623190536386','RP20260623190536386','general_entry','600.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:05:36'),(178,'1030','Loans Receivable','2026-06-23 19:05:36','RP20260623190536386','RP20260623190536386','general_entry','0.00','600.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:05:36'),(179,'1010','Cash','2026-06-23 19:29:26','RP20260623162925971','RP20260623162925971','general_entry','30000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:26'),(180,'1030','Loans Receivable','2026-06-23 19:29:27','RP20260623162925971','RP20260623162925971','general_entry','0.00','29165.01','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:27'),(181,'4010','Loan Interest Income','2026-06-23 19:29:27','RP20260623162925971','RP20260623162925971','general_entry','0.00','834.99','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:27'),(182,'1010','Cash','2026-06-23 19:29:28','RP20260623162928922','RP20260623162928922','general_entry','15000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:28'),(183,'1030','Loans Receivable','2026-06-23 19:29:28','RP20260623162928922','RP20260623162928922','general_entry','0.00','14831.67','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:28'),(184,'4010','Loan Interest Income','2026-06-23 19:29:28','RP20260623162928922','RP20260623162928922','general_entry','0.00','168.33','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:28'),(185,'1010','Cash','2026-06-23 19:29:29','RP20260623162929603','RP20260623162929603','general_entry','5000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:29'),(186,'1030','Loans Receivable','2026-06-23 19:29:29','RP20260623162929603','RP20260623162929603','general_entry','0.00','5000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:29'),(187,'1010','Cash','2026-06-23 19:29:30','RP20260623162930513','RP20260623162930513','general_entry','1003.32','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:30'),(188,'1030','Loans Receivable','2026-06-23 19:29:30','RP20260623162930513','RP20260623162930513','general_entry','0.00','1003.32','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:30'),(189,'1010','Cash','2026-06-23 19:29:56','RP20260623162956388','RP20260623162956388','general_entry','30000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:56'),(190,'1030','Loans Receivable','2026-06-23 19:29:56','RP20260623162956388','RP20260623162956388','general_entry','0.00','29165.01','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:56'),(191,'4010','Loan Interest Income','2026-06-23 19:29:56','RP20260623162956388','RP20260623162956388','general_entry','0.00','834.99','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:56'),(192,'1010','Cash','2026-06-23 19:29:57','RP20260623162957176','RP20260623162957176','general_entry','15000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:57'),(193,'1030','Loans Receivable','2026-06-23 19:29:57','RP20260623162957176','RP20260623162957176','general_entry','0.00','14831.67','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:57'),(194,'4010','Loan Interest Income','2026-06-23 19:29:57','RP20260623162957176','RP20260623162957176','general_entry','0.00','168.33','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:57'),(195,'1010','Cash','2026-06-23 19:29:57','RP20260623162957945','RP20260623162957945','general_entry','5000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:57'),(196,'1030','Loans Receivable','2026-06-23 19:29:57','RP20260623162957945','RP20260623162957945','general_entry','0.00','5000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:57'),(197,'1010','Cash','2026-06-23 19:29:57','RP20260623162957187','RP20260623162957187','general_entry','1003.32','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:57'),(198,'1030','Loans Receivable','2026-06-23 19:29:57','RP20260623162957187','RP20260623162957187','general_entry','0.00','1003.32','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:29:57'),(199,'1030','Loans Receivable','2026-06-23 19:41:10','LD20260623194109781','LD20260623194109781','general_entry','180000.00','0.00','Loan disbursement for loan #23',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:41:10'),(200,'1020','Main Bank Account','2026-06-23 19:41:10','LD20260623194109781','LD20260623194109781','general_entry','0.00','180000.00','Loan disbursement payment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:41:10'),(201,'1010','Cash','2026-06-23 19:46:37','RP20260623194636741','RP20260623194636741','general_entry','30000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:46:37'),(202,'1030','Loans Receivable','2026-06-23 19:46:37','RP20260623194636741','RP20260623194636741','general_entry','0.00','27750.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:46:37'),(203,'4010','Loan Interest Income','2026-06-23 19:46:37','RP20260623194636741','RP20260623194636741','general_entry','0.00','2250.00','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:46:37'),(204,'1010','Cash','2026-06-23 19:47:17','RP20260623194717760','RP20260623194717760','general_entry','50000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:47:17'),(205,'1030','Loans Receivable','2026-06-23 19:47:17','RP20260623194717760','RP20260623194717760','general_entry','0.00','46594.90','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:47:17'),(206,'4010','Loan Interest Income','2026-06-23 19:47:17','RP20260623194717760','RP20260623194717760','general_entry','0.00','3405.10','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:47:17'),(207,'1010','Cash','2026-06-23 19:47:57','RP20260623194757988','RP20260623194757988','general_entry','50000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:47:57'),(208,'1030','Loans Receivable','2026-06-23 19:47:57','RP20260623194757988','RP20260623194757988','general_entry','0.00','48085.33','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:47:57'),(209,'4010','Loan Interest Income','2026-06-23 19:47:58','RP20260623194757988','RP20260623194757988','general_entry','0.00','1914.67','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:47:58'),(210,'1010','Cash','2026-06-23 19:52:09','RP20260623195209638','RP20260623195209638','general_entry','40000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:52:09'),(211,'1030','Loans Receivable','2026-06-23 19:52:09','RP20260623195209638','RP20260623195209638','general_entry','0.00','39613.26','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:52:09'),(212,'4010','Loan Interest Income','2026-06-23 19:52:09','RP20260623195209638','RP20260623195209638','general_entry','0.00','386.74','Interest received (not previously accrued)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:52:09'),(213,'1010','Cash','2026-06-23 19:53:37','RP20260623195337545','RP20260623195337545','general_entry','16000.00','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:53:37'),(214,'1030','Loans Receivable','2026-06-23 19:53:37','RP20260623195337545','RP20260623195337545','general_entry','0.00','16000.00','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:53:37'),(215,'1010','Cash','2026-06-23 19:56:25','RP20260623195624760','RP20260623195624760','general_entry','1956.51','0.00','Loan repayment received',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:56:25'),(216,'1030','Loans Receivable','2026-06-23 19:56:25','RP20260623195624760','RP20260623195624760','general_entry','0.00','1956.51','Principal repayment',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 19:56:25'),(217,'2010','Member Savings','2026-06-23 20:23:21','SW20260623202320291','SW20260623202320291','general_entry','270000.00','0.00','Savings withdrawal',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 20:23:21'),(218,'1010','Cash','2026-06-23 20:23:21','SW20260623202320291','SW20260623202320291','general_entry','0.00','270000.00','Savings paid out (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-23 20:23:21'),(219,'2010','Member Savings','2026-06-23 23:24:49','SHR-7-1782246282','SHR-7-1782246282','SHARE_PURCHASE','10000.00','0.00','Transfer from savings to share capital','internal',5,NULL,7,NULL,'savings','posted',NULL,'2026-06-23 23:24:49'),(220,'3010','Share Capital','2026-06-23 23:24:49','SHR-7-1782246282','SHR-7-1782246282','SHARE_PURCHASE','0.00','10000.00','Member share capital increase','internal',5,NULL,7,NULL,'shares','posted',NULL,'2026-06-23 23:24:49'),(221,'1010','Cash','2026-06-24 12:11:13','SD20260624121110758','SD20260624121110758','general_entry','90000.00','0.00','Savings deposit received (cash)',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-24 12:11:13'),(222,'2010','Member Savings','2026-06-24 12:11:13','SD20260624121110758','SD20260624121110758','general_entry','0.00','90000.00','Member savings deposit',NULL,1,NULL,NULL,NULL,NULL,'posted',NULL,'2026-06-24 12:11:13');

/*Table structure for table `loan_guarantors` */

DROP TABLE IF EXISTS `loan_guarantors`;

CREATE TABLE `loan_guarantors` (
  `guarantor_id` int NOT NULL AUTO_INCREMENT,
  `loan_id` int NOT NULL,
  `guarantor_member_id` int NOT NULL,
  `amount_guaranteed` decimal(12,2) NOT NULL,
  `percentage_guarantee` decimal(5,2) NOT NULL,
  `status` enum('active','released','called','defaulted') DEFAULT 'active',
  `release_date` date DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`guarantor_id`),
  UNIQUE KEY `unique_guarantor_loan` (`loan_id`,`guarantor_member_id`),
  KEY `idx_loan` (`loan_id`),
  KEY `idx_guarantor` (`guarantor_member_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `loan_guarantors` */

insert  into `loan_guarantors`(`guarantor_id`,`loan_id`,`guarantor_member_id`,`amount_guaranteed`,`percentage_guarantee`,`status`,`release_date`,`notes`) values (1,1,1,'160000.00','32.00','active',NULL,NULL),(2,2,3,'50000.00','11.11','active',NULL,NULL),(3,3,2,'150000.00','100.00','active',NULL,NULL),(4,4,3,'500000.00','100.00','active',NULL,NULL),(5,5,2,'60000.00','100.00','active',NULL,NULL),(6,6,3,'70000.00','100.00','active',NULL,NULL),(7,7,1,'160000.00','10.67','active',NULL,NULL),(8,8,2,'700000.00','100.00','active',NULL,NULL),(9,15,9,'200000.00','6.67','active',NULL,NULL),(10,17,8,'60000.00','100.00','active',NULL,NULL),(11,18,7,'100000.00','100.00','active',NULL,NULL),(12,19,10,'150000.00','100.00','active',NULL,NULL),(13,20,11,'50000.00','100.00','active',NULL,NULL),(14,23,5,'120000.00','66.67','active',NULL,NULL);

/*Table structure for table `loan_products` */

DROP TABLE IF EXISTS `loan_products`;

CREATE TABLE `loan_products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `description` text,
  `min_amount` decimal(12,2) NOT NULL,
  `max_amount` decimal(12,2) NOT NULL,
  `default_interest_rate` decimal(5,2) NOT NULL,
  `min_repayment_months` int NOT NULL,
  `max_repayment_months` int NOT NULL,
  `processing_fee` decimal(12,2) DEFAULT '0.00',
  `late_penalty_rate` decimal(5,2) DEFAULT '0.00',
  `requires_guarantors` tinyint(1) DEFAULT '1',
  `min_guarantors` int DEFAULT '2',
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `loan_products` */

insert  into `loan_products`(`product_id`,`product_name`,`description`,`min_amount`,`max_amount`,`default_interest_rate`,`min_repayment_months`,`max_repayment_months`,`processing_fee`,`late_penalty_rate`,`requires_guarantors`,`min_guarantors`,`status`) values (1,'Emergency Loan',NULL,'50000.00','500000.00','12.00',1,6,'0.00','0.00',1,1,'active'),(2,'Development Loan',NULL,'100000.00','5000000.00','15.00',6,24,'0.00','0.00',1,2,'active'),(3,'School Fees Loan',NULL,'50000.00','2000000.00','10.00',3,12,'0.00','0.00',1,1,'active'),(4,'Business Loan',NULL,'200000.00','10000000.00','18.00',12,36,'0.00','0.00',1,2,'active');

/*Table structure for table `loan_repayment_schedule` */

DROP TABLE IF EXISTS `loan_repayment_schedule`;

CREATE TABLE `loan_repayment_schedule` (
  `schedule_id` int NOT NULL AUTO_INCREMENT,
  `loan_id` int NOT NULL,
  `installment_no` int NOT NULL,
  `due_date` date NOT NULL,
  `principal_amount` decimal(12,2) NOT NULL,
  `interest_amount` decimal(12,2) NOT NULL,
  `total_due` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) DEFAULT '0.00',
  `principal_balance` decimal(12,2) DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('pending','paid','partial','overdue') DEFAULT 'pending',
  `late_penalty` decimal(12,2) DEFAULT '0.00',
  PRIMARY KEY (`schedule_id`),
  KEY `idx_loan` (`loan_id`),
  KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `loan_repayment_schedule` */

insert  into `loan_repayment_schedule`(`schedule_id`,`loan_id`,`installment_no`,`due_date`,`principal_amount`,`interest_amount`,`total_due`,`paid_amount`,`principal_balance`,`paid_date`,`status`,`late_penalty`) values (1,17,1,'2026-08-17','19801.33','600.00','20401.33','1803.98','40198.67','2026-06-23','partial','0.00'),(2,17,2,'2026-09-17','19999.34','401.99','20401.33','0.00','20199.33',NULL,'pending','0.00'),(3,17,3,'2026-10-17','20199.33','201.99','20401.32','0.00','0.00',NULL,'pending','0.00'),(4,15,1,'2026-08-17','230039.98','45000.00','275039.98','0.00','2769960.02',NULL,'pending','0.00'),(5,15,2,'2026-09-17','233490.58','41549.40','275039.98','0.00','2536469.44',NULL,'pending','0.00'),(6,15,3,'2026-10-17','236992.94','38047.04','275039.98','0.00','2299476.50',NULL,'pending','0.00'),(7,15,4,'2026-11-17','240547.83','34492.15','275039.98','0.00','2058928.67',NULL,'pending','0.00'),(8,15,5,'2026-12-17','244156.05','30883.93','275039.98','0.00','1814772.62',NULL,'pending','0.00'),(9,15,6,'2027-01-17','247818.39','27221.59','275039.98','0.00','1566954.23',NULL,'pending','0.00'),(10,15,7,'2027-02-17','251535.67','23504.31','275039.98','0.00','1315418.56',NULL,'pending','0.00'),(11,15,8,'2027-03-17','255308.70','19731.28','275039.98','0.00','1060109.86',NULL,'pending','0.00'),(12,15,9,'2027-04-17','259138.33','15901.65','275039.98','0.00','800971.53',NULL,'pending','0.00'),(13,15,10,'2027-05-17','263025.41','12014.57','275039.98','0.00','537946.12',NULL,'pending','0.00'),(14,15,11,'2027-06-17','266970.79','8069.19','275039.98','0.00','270975.33',NULL,'pending','0.00'),(15,15,12,'2027-07-17','270975.33','4064.63','275039.96','0.00','0.00',NULL,'pending','0.00'),(16,12,1,'2026-08-18','795.83','83.33','879.16','0.00','9204.17',NULL,'pending','0.00'),(17,12,2,'2026-09-18','802.46','76.70','879.16','0.00','8401.71',NULL,'pending','0.00'),(18,12,3,'2026-10-18','809.15','70.01','879.16','0.00','7592.56',NULL,'pending','0.00'),(19,12,4,'2026-11-18','815.89','63.27','879.16','0.00','6776.67',NULL,'pending','0.00'),(20,12,5,'2026-12-18','822.69','56.47','879.16','0.00','5953.98',NULL,'pending','0.00'),(21,12,6,'2027-01-18','829.54','49.62','879.16','0.00','5124.44',NULL,'pending','0.00'),(22,12,7,'2027-02-18','836.46','42.70','879.16','0.00','4287.98',NULL,'pending','0.00'),(23,12,8,'2027-03-18','843.43','35.73','879.16','0.00','3444.55',NULL,'pending','0.00'),(24,12,9,'2027-04-18','850.46','28.70','879.16','0.00','2594.09',NULL,'pending','0.00'),(25,12,10,'2027-05-18','857.54','21.62','879.16','0.00','1736.55',NULL,'pending','0.00'),(26,12,11,'2027-06-18','864.69','14.47','879.16','0.00','871.86',NULL,'pending','0.00'),(27,12,12,'2027-07-18','871.86','7.27','879.13','0.00','0.00',NULL,'pending','0.00'),(28,18,1,'2026-08-21','19669.44','833.33','20502.77','3347.15','80330.56','2026-06-23','partial','0.00'),(29,18,2,'2026-09-21','19833.35','669.42','20502.77','0.00','60497.21',NULL,'pending','0.00'),(30,18,3,'2026-10-21','19998.63','504.14','20502.77','0.00','40498.58',NULL,'pending','0.00'),(31,18,4,'2026-11-21','20165.28','337.49','20502.77','0.00','20333.30',NULL,'pending','0.00'),(32,18,5,'2026-12-21','20333.30','169.44','20502.74','0.00','0.00',NULL,'pending','0.00'),(33,10,1,'2026-08-21','795.83','83.33','879.16','0.00','9204.17',NULL,'pending','0.00'),(34,10,2,'2026-09-21','802.46','76.70','879.16','0.00','8401.71',NULL,'pending','0.00'),(35,10,3,'2026-10-21','809.15','70.01','879.16','0.00','7592.56',NULL,'pending','0.00'),(36,10,4,'2026-11-21','815.89','63.27','879.16','0.00','6776.67',NULL,'pending','0.00'),(37,10,5,'2026-12-21','822.69','56.47','879.16','0.00','5953.98',NULL,'pending','0.00'),(38,10,6,'2027-01-21','829.54','49.62','879.16','0.00','5124.44',NULL,'pending','0.00'),(39,10,7,'2027-02-21','836.46','42.70','879.16','0.00','4287.98',NULL,'pending','0.00'),(40,10,8,'2027-03-21','843.43','35.73','879.16','0.00','3444.55',NULL,'pending','0.00'),(41,10,9,'2027-04-21','850.46','28.70','879.16','0.00','2594.09',NULL,'pending','0.00'),(42,10,10,'2027-05-21','857.54','21.62','879.16','0.00','1736.55',NULL,'pending','0.00'),(43,10,11,'2027-06-21','864.69','14.47','879.16','0.00','871.86',NULL,'pending','0.00'),(44,10,12,'2027-07-21','871.86','7.27','879.13','0.00','0.00',NULL,'pending','0.00'),(45,19,1,'2026-08-21','24230.07','1875.00','26105.07','0.00','125769.93',NULL,'pending','0.00'),(46,19,2,'2026-09-21','24532.95','1572.12','26105.07','0.00','101236.98',NULL,'pending','0.00'),(47,19,3,'2026-10-21','24839.61','1265.46','26105.07','0.00','76397.37',NULL,'pending','0.00'),(48,19,4,'2026-11-21','25150.10','954.97','26105.07','0.00','51247.27',NULL,'pending','0.00'),(49,19,5,'2026-12-21','25464.48','640.59','26105.07','0.00','25782.79',NULL,'pending','0.00'),(50,19,6,'2027-01-21','25782.79','322.28','26105.07','0.00','0.00',NULL,'pending','0.00'),(51,6,1,'2026-08-21','34825.87','700.00','35525.87','0.00','35174.13',NULL,'pending','0.00'),(52,6,2,'2026-09-21','35174.13','351.74','35525.87','0.00','0.00',NULL,'pending','0.00'),(53,20,1,'2026-08-21','16501.11','500.00','17001.11','0.00','33498.89',NULL,'pending','0.00'),(54,20,2,'2026-09-21','16666.12','334.99','17001.11','0.00','16832.77',NULL,'pending','0.00'),(55,20,3,'2026-10-21','16832.77','168.33','17001.10','0.00','0.00',NULL,'pending','0.00'),(56,21,1,'2026-08-23','16501.11','500.00','17001.11','17001.11',NULL,'2026-06-23','paid','0.00'),(57,21,2,'2026-09-23','16666.12','334.99','17001.11','17001.11',NULL,'2026-06-23','paid','0.00'),(58,21,3,'2026-10-23','16832.77','168.33','17001.10','17001.10',NULL,'2026-06-23','paid','0.00'),(59,22,1,'2026-08-23','16501.11','500.00','17001.11','17001.11',NULL,'2026-06-23','paid','0.00'),(60,22,2,'2026-09-23','16666.12','334.99','17001.11','17001.11',NULL,'2026-06-23','paid','0.00'),(61,22,3,'2026-10-23','16832.77','168.33','17001.10','17001.10',NULL,'2026-06-23','paid','0.00'),(62,23,1,'2026-08-23','29076.09','2250.00','31326.09','31326.09',NULL,'2026-06-23','paid','0.00'),(63,23,2,'2026-09-23','29439.54','1886.55','31326.09','31326.09',NULL,'2026-06-23','paid','0.00'),(64,23,3,'2026-10-23','29807.54','1518.55','31326.09','31326.09',NULL,'2026-06-23','paid','0.00'),(65,23,4,'2026-11-23','30180.13','1145.96','31326.09','31326.09',NULL,'2026-06-23','paid','0.00'),(66,23,5,'2026-12-23','30557.38','768.71','31326.09','31326.09',NULL,'2026-06-23','paid','0.00'),(67,23,6,'2027-01-23','30939.32','386.74','31326.06','31326.06',NULL,'2026-06-23','paid','0.00');

/*Table structure for table `loan_repayments` */

DROP TABLE IF EXISTS `loan_repayments`;

CREATE TABLE `loan_repayments` (
  `repayment_id` int NOT NULL AUTO_INCREMENT,
  `loan_id` int NOT NULL,
  `schedule_id` int DEFAULT NULL,
  `amount_paid` decimal(12,2) NOT NULL,
  `principal_paid` decimal(12,2) DEFAULT NULL,
  `interest_paid` decimal(12,2) DEFAULT NULL,
  `penalty_paid` decimal(12,2) DEFAULT '0.00',
  `payment_method` enum('cash','mobile_money','bank_transfer','salary_deduction') NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `posted_by` int DEFAULT NULL,
  `notes` text,
  `last_transaction_date` datetime DEFAULT NULL,
  PRIMARY KEY (`repayment_id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `schedule_id` (`schedule_id`),
  KEY `idx_loan` (`loan_id`),
  KEY `idx_receipt` (`receipt_no`)
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `loan_repayments` */

insert  into `loan_repayments`(`repayment_id`,`loan_id`,`schedule_id`,`amount_paid`,`principal_paid`,`interest_paid`,`penalty_paid`,`payment_method`,`reference_no`,`receipt_no`,`payment_date`,`posted_by`,`notes`,`last_transaction_date`) values (1,3,NULL,'50000.00',NULL,NULL,'0.00','mobile_money','','LRP20260527200624592','2026-05-27 20:06:24',1,NULL,NULL),(2,2,NULL,'450000.00',NULL,NULL,'0.00','mobile_money','','LRP20260528124409772','2026-05-28 12:44:09',1,NULL,NULL),(3,5,NULL,'25000.00',NULL,NULL,'0.00','bank_transfer','','LRP20260528153346226','2026-05-28 15:33:46',1,NULL,NULL),(4,5,NULL,'35000.00',NULL,NULL,'0.00','bank_transfer','','LRP20260528153747156','2026-05-28 15:37:47',1,NULL,NULL),(5,4,NULL,'25000.00',NULL,NULL,'0.00','cash','','LRP20260611103853405','2026-06-11 10:38:53',1,NULL,NULL),(6,8,NULL,'700000.00',NULL,NULL,'0.00','cash','','LRP20260617122117581','2026-06-17 12:21:17',1,NULL,NULL),(9,17,NULL,'50000.00','48796.02','1203.98','0.00','cash','','RP20260617185109817','2026-06-17 18:51:09',1,NULL,NULL),(10,17,NULL,'11203.98','10000.00','1203.98','0.00','mobile_money','','RP20260617185239904','2026-06-17 18:52:39',1,NULL,NULL),(11,17,NULL,'1203.98','0.00','1203.98','0.00','cash','','RP20260618202028777','2026-06-18 20:20:28',1,NULL,NULL),(16,16,NULL,'10000.00','10000.00','0.00','0.00','cash','','RP20260618204143317','2026-06-18 20:41:43',1,NULL,NULL),(17,17,NULL,'1203.98','0.00','1203.98','0.00','cash','','RP20260618204231993','2026-06-18 20:42:31',1,NULL,NULL),(18,17,NULL,'1203.00','0.00','1203.00','0.00','cash','','RP20260618204318677','2026-06-18 20:43:18',1,NULL,NULL),(19,15,NULL,'1000000.00','699520.26','300479.74','0.00','cash','','RP20260621172136842','2026-06-21 17:21:36',1,NULL,NULL),(20,3,NULL,'100000.00','100000.00','0.00','0.00','bank_transfer','','RP20260621172309996','2026-06-21 17:23:09',1,NULL,NULL),(21,18,NULL,'50000.00','47486.18','2513.82','0.00','cash','','RP20260621172418262','2026-06-21 17:24:18',1,NULL,NULL),(22,17,NULL,'1203.98','0.00','1203.98','0.00','cash','','RP20260621173200241','2026-06-21 17:32:00',1,NULL,NULL),(23,18,NULL,'52513.82','50000.00','2513.82','0.00','cash','','RP20260621173252282','2026-06-21 17:32:52',1,NULL,NULL),(24,18,NULL,'2513.00','0.00','2513.00','0.00','cash','','RP20260621173352878','2026-06-21 17:33:52',1,NULL,NULL),(25,18,NULL,'2513.82','0.00','2513.82','0.00','cash','','RP20260621184627405','2026-06-21 18:46:28',1,NULL,NULL),(26,6,NULL,'50000.00','48948.26','1051.74','0.00','cash','','RP20260621185150836','2026-06-21 18:51:50',1,NULL,NULL),(27,6,NULL,'10000.00','8948.26','1051.74','0.00','cash','','RP20260621185220775','2026-06-21 18:52:20',1,NULL,NULL),(28,6,NULL,'8000.00','6948.26','1051.74','0.00','cash','','RP20260621185240188','2026-06-21 18:52:40',1,NULL,NULL),(29,6,NULL,'5155.22','4103.48','1051.74','0.00','cash','','RP20260621185259560','2026-06-21 18:52:59',1,NULL,NULL),(30,6,NULL,'1051.74','0.00','1051.74','0.00','cash','','RP20260621185324240','2026-06-21 18:53:24',1,NULL,NULL),(31,6,NULL,'500.00','0.00','500.00','0.00','cash','','RP20260621185342331','2026-06-21 18:53:42',1,NULL,NULL),(32,6,NULL,'1051.00','0.00','1051.00','0.00','cash','','RP20260621185511204','2026-06-21 18:55:11',1,NULL,NULL),(33,7,NULL,'1500000.00','1500000.00','0.00','0.00','cash','','RP20260621185647729','2026-06-21 18:56:47',1,NULL,NULL),(34,20,NULL,'30000.00','28996.68','1003.32','0.00','cash','','RP20260621190419759','2026-06-21 19:04:19',1,NULL,NULL),(35,20,NULL,'15000.00','13996.68','1003.32','0.00','cash','','RP20260621190740577','2026-06-21 19:07:40',1,NULL,NULL),(36,20,NULL,'5000.00','3996.68','1003.32','0.00','cash','','RP20260621190840398','2026-06-21 19:08:40',1,NULL,NULL),(37,20,NULL,'1500.00','496.68','1003.32','0.00','cash','','RP20260621190943890','2026-06-21 19:09:43',1,NULL,NULL),(38,20,NULL,'1500.00','496.68','1003.32','0.00','cash','','RP20260621191116560','2026-06-21 19:11:16',1,NULL,NULL),(39,20,NULL,'1500.00','496.68','1003.32','0.00','cash','','RP20260621191218996','2026-06-21 19:12:18',1,NULL,NULL),(40,20,NULL,'1500.00','496.68','1003.32','0.00','cash','','RP20260621191322637','2026-06-21 19:13:22',1,NULL,NULL),(41,20,NULL,'1000.00','0.00','1000.00','0.00','cash','','RP20260621191519191','2026-06-21 19:15:19',1,NULL,NULL),(42,14,NULL,'10000.00','10000.00','0.00','0.00','cash','','RP20260622143738910','2026-06-22 14:37:38',1,NULL,NULL),(46,18,28,'2513.82','1680.49','833.33','0.00','cash','','RP20260623190400437','2026-06-23 19:04:00',1,NULL,NULL),(47,18,28,'833.33','833.33','0.00','0.00','cash','','RP20260623190430458','2026-06-23 19:04:30',1,NULL,NULL),(48,17,1,'1203.98','603.98','600.00','0.00','cash','','RP20260623190514190','2026-06-23 19:05:14',1,NULL,NULL),(49,17,1,'600.00','600.00','0.00','0.00','cash','','RP20260623190536161','2026-06-23 19:05:36',1,NULL,NULL),(50,21,56,'30000.00','29165.01','834.99','0.00','cash','TESTPAY-1','RP20260623162924283','2026-06-23 19:29:25',1,NULL,NULL),(51,21,57,'15000.00','14831.67','168.33','0.00','cash','TESTPAY-2','RP20260623162928402','2026-06-23 19:29:28',1,NULL,NULL),(52,21,58,'5000.00','5000.00','0.00','0.00','cash','TESTPAY-3','RP20260623162928776','2026-06-23 19:29:28',1,NULL,NULL),(53,21,58,'1003.32','1003.32','0.00','0.00','cash','TESTPAY-FINAL','RP20260623162930766','2026-06-23 19:29:30',1,NULL,NULL),(54,22,59,'30000.00','29165.01','834.99','0.00','cash','TESTPAY-1','RP20260623162956520','2026-06-23 19:29:56',1,NULL,NULL),(55,22,60,'15000.00','14831.67','168.33','0.00','cash','TESTPAY-2','RP20260623162957145','2026-06-23 19:29:57',1,NULL,NULL),(56,22,61,'5000.00','5000.00','0.00','0.00','cash','TESTPAY-3','RP20260623162957609','2026-06-23 19:29:57',1,NULL,NULL),(57,22,61,'1003.32','1003.32','0.00','0.00','cash','TESTPAY-FINAL','RP20260623162957710','2026-06-23 19:29:57',1,NULL,NULL),(58,23,62,'30000.00','27750.00','2250.00','0.00','cash','','RP20260623194636823','2026-06-23 19:46:36',1,NULL,NULL),(59,23,62,'50000.00','46594.90','3405.10','0.00','cash','','RP20260623194717831','2026-06-23 19:47:17',1,NULL,NULL),(60,23,64,'50000.00','48085.33','1914.67','0.00','cash','','RP20260623194757744','2026-06-23 19:47:57',1,NULL,NULL),(61,23,66,'40000.00','39613.26','386.74','0.00','cash','','RP20260623195209777','2026-06-23 19:52:09',1,NULL,NULL),(62,23,67,'16000.00','16000.00','0.00','0.00','cash','','RP20260623195337754','2026-06-23 19:53:37',1,NULL,NULL),(63,23,67,'1956.51','1956.51','0.00','0.00','cash','','RP20260623195624127','2026-06-23 19:56:24',1,NULL,NULL);

/*Table structure for table `loans` */

DROP TABLE IF EXISTS `loans`;

CREATE TABLE `loans` (
  `loan_id` int NOT NULL AUTO_INCREMENT,
  `loan_ref_no` varchar(20) NOT NULL,
  `member_id` int NOT NULL,
  `product_id` int NOT NULL,
  `amount_requested` decimal(12,2) NOT NULL,
  `amount_approved` decimal(12,2) DEFAULT NULL,
  `interest_rate` decimal(5,2) NOT NULL,
  `repayment_period_months` int NOT NULL,
  `processing_fee` decimal(12,2) DEFAULT '0.00',
  `purpose` text,
  `application_date` date NOT NULL,
  `approval_date` date DEFAULT NULL,
  `disbursement_date` date DEFAULT NULL,
  `first_payment_date` date DEFAULT NULL,
  `status` enum('applied','reviewed','approved','rejected','disbursed','completed','defaulted') DEFAULT 'applied',
  `outstanding_balance` decimal(12,2) DEFAULT '0.00',
  `total_paid` decimal(12,2) DEFAULT '0.00',
  `applied_by` int DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `disbursed_by` int DEFAULT NULL,
  `rejection_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_payment_date` datetime DEFAULT NULL,
  PRIMARY KEY (`loan_id`),
  UNIQUE KEY `loan_ref_no` (`loan_ref_no`),
  KEY `product_id` (`product_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_status` (`status`),
  KEY `idx_ref_no` (`loan_ref_no`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `loans` */

insert  into `loans`(`loan_id`,`loan_ref_no`,`member_id`,`product_id`,`amount_requested`,`amount_approved`,`interest_rate`,`repayment_period_months`,`processing_fee`,`purpose`,`application_date`,`approval_date`,`disbursement_date`,`first_payment_date`,`status`,`outstanding_balance`,`total_paid`,`applied_by`,`reviewed_by`,`approved_by`,`disbursed_by`,`rejection_reason`,`created_at`,`updated_at`,`last_payment_date`) values (1,'LN202605266542',2,1,'500000.00',NULL,'12.00',4,'0.00','Sick child','2026-05-26','2026-05-27',NULL,NULL,'rejected','0.00','0.00',1,NULL,1,NULL,'Low savings','2026-05-26 14:48:58','2026-05-27 18:57:57',NULL),(2,'LN202605279169',1,1,'450000.00',NULL,'12.00',1,'0.00','Personal','2026-05-27','2026-05-27','2026-05-28','2026-06-28','completed','0.00','0.00',1,NULL,1,1,NULL,'2026-05-27 18:37:40','2026-05-28 12:44:09',NULL),(3,'LN202605275603',3,1,'150000.00',NULL,'12.00',2,'0.00','Personal','2026-05-27','2026-05-27','2026-05-27','2026-06-27','completed','0.00','100000.00',1,NULL,1,1,NULL,'2026-05-27 19:17:24','2026-06-21 17:23:09','2026-06-21 17:23:09'),(4,'LN202605284210',4,4,'500000.00',NULL,'18.00',12,'0.00','personal development','2026-05-28','2026-05-28','2026-05-28','2026-06-28','disbursed','475000.00','0.00',1,NULL,1,1,NULL,'2026-05-28 12:39:46','2026-06-11 10:38:53',NULL),(5,'LN202605285431',5,1,'60000.00',NULL,'12.00',2,'0.00','Personal','2026-05-28','2026-05-28','2026-05-28','2026-06-28','completed','0.00','0.00',1,NULL,1,1,NULL,'2026-05-28 15:27:23','2026-05-28 15:37:47',NULL),(6,'LN202606050702',5,1,'70000.00','70000.00','12.00',2,'0.00','School fees','2026-06-05','2026-06-21','2026-06-21','2026-07-21','disbursed','1051.74','75757.96',1,NULL,1,1,NULL,'2026-06-05 10:41:41','2026-06-21 18:55:11',NULL),(7,'LN202606176561',7,3,'1500000.00',NULL,'10.00',3,'0.00','Personal','2026-06-17','2026-06-17','2026-06-17','2026-07-17','completed','0.00','1500000.00',1,NULL,1,1,NULL,'2026-06-17 10:15:51','2026-06-21 18:56:47','2026-06-21 18:56:47'),(8,'LN202606174618',6,4,'700000.00',NULL,'18.00',13,'0.00','Business','2026-06-17','2026-06-17','2026-06-17','2026-07-17','completed','0.00','0.00',1,NULL,1,1,NULL,'2026-06-17 10:17:10','2026-06-17 12:21:17',NULL),(9,'LN20260617110411657',1,3,'10000.00',NULL,'10.00',12,'0.00','Remediation test loan','2026-06-17','2026-06-17',NULL,NULL,'approved','10000.00','0.00',1,NULL,1,NULL,NULL,'2026-06-17 14:04:12','2026-06-17 14:25:06',NULL),(10,'LN20260617110441450',1,3,'10000.00','10000.00','10.00',12,'0.00','Remediation test loan','2026-06-17','2026-06-17','2026-06-21','2026-07-21','disbursed','10000.00','0.00',1,NULL,1,1,NULL,'2026-06-17 14:04:42','2026-06-21 17:20:22',NULL),(11,'LN20260617110606915',1,3,'10000.00','10000.00','10.00',12,'0.00','Remediation test loan','2026-06-17','2026-06-17',NULL,NULL,'approved','10000.00','0.00',1,NULL,1,NULL,NULL,'2026-06-17 14:06:07','2026-06-17 14:06:07',NULL),(12,'LN20260617110712934',1,3,'10000.00','10000.00','10.00',12,'0.00','Remediation test loan','2026-06-17','2026-06-17','2026-06-18','2026-07-18','disbursed','10000.00','0.00',1,NULL,1,1,NULL,'2026-06-17 14:07:12','2026-06-18 20:19:21',NULL),(13,'LN20260617110754952',1,3,'10000.00','10000.00','10.00',12,'0.00','Remediation test loan','2026-06-17','2026-06-17','2026-06-17',NULL,'disbursed','10000.00','0.00',1,NULL,1,1,NULL,'2026-06-17 14:07:54','2026-06-17 14:07:56',NULL),(14,'LN20260617110816474',1,3,'10000.00','10000.00','10.00',12,'0.00','Remediation test loan','2026-06-17','2026-06-17','2026-06-17',NULL,'completed','0.00','10000.00',1,NULL,1,1,NULL,'2026-06-17 14:08:16','2026-06-22 14:37:38','2026-06-22 14:37:38'),(15,'LN202606176245',8,4,'3000000.00','3000000.00','18.00',12,'0.00','Personal','2026-06-17','2026-06-17','2026-06-17','2026-07-17','disbursed','2300479.74','1000000.00',1,NULL,1,1,NULL,'2026-06-17 14:19:23','2026-06-21 17:21:36',NULL),(16,'LN20260617112814390',1,3,'10000.00','10000.00','10.00',12,'0.00','Remediation test loan','2026-06-17','2026-06-17','2026-06-17',NULL,'completed','0.00','10000.00',1,NULL,1,1,NULL,'2026-06-17 14:28:14','2026-06-18 20:41:44','2026-06-18 20:41:44'),(17,'LN202606173945',2,1,'60000.00','60000.00','12.00',3,'0.00','Personal','2026-06-17','2026-06-17','2026-06-17','2026-07-17','completed','0.00','67822.90',1,NULL,1,1,NULL,'2026-06-17 14:43:59','2026-06-23 19:05:36','2026-06-23 19:05:36'),(18,'LN202606210397',11,3,'100000.00','100000.00','10.00',5,'0.00','personal','2026-06-21','2026-06-21','2026-06-21','2026-07-21','completed','0.00','110887.79',1,NULL,1,1,NULL,'2026-06-21 17:17:00','2026-06-23 19:04:30','2026-06-23 19:04:30'),(19,'LN202606214731',12,2,'150000.00','150000.00','15.00',6,'0.00','Fees','2026-06-21','2026-06-21','2026-06-21','2026-07-21','disbursed','150000.00','0.00',1,NULL,1,1,NULL,'2026-06-21 17:38:03','2026-06-21 17:38:42',NULL),(20,'LN202606212107',7,1,'50000.00','50000.00','12.00',3,'0.00','self','2026-06-21','2026-06-21','2026-06-21','2026-07-21','disbursed','1023.24','57000.00',1,NULL,1,1,NULL,'2026-06-21 18:59:56','2026-06-21 19:15:19',NULL),(21,'TESTLOAN-1782232162',2,1,'50000.00','50000.00','12.00',3,'0.00','Test loan for repayment engine validation','2026-06-23','2026-06-23','2026-06-23','2026-07-23','completed','0.00','51003.32',NULL,NULL,1,1,NULL,'2026-06-23 19:29:22','2026-06-23 19:29:30','2026-06-23 19:29:30'),(22,'TESTLOAN-1782232196',2,1,'50000.00','50000.00','12.00',3,'0.00','Test loan for repayment engine validation','2026-06-23','2026-06-23','2026-06-23','2026-07-23','completed','0.00','51003.32',NULL,NULL,1,1,NULL,'2026-06-23 19:29:56','2026-06-23 19:29:57','2026-06-23 19:29:57'),(23,'LN202606235065',2,2,'180000.00','180000.00','15.00',6,'0.00','Personal','2026-06-23','2026-06-23','2026-06-23','2026-07-23','completed','0.00','187956.51',1,NULL,1,1,NULL,'2026-06-23 19:40:21','2026-06-23 19:56:25','2026-06-23 19:56:25');

/*Table structure for table `member_devices` */

DROP TABLE IF EXISTS `member_devices`;

CREATE TABLE `member_devices` (
  `device_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `member_id` int NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_type` varchar(20) DEFAULT NULL,
  `device_fingerprint` varchar(191) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_trusted` tinyint(1) DEFAULT '0',
  `is_blocked` tinyint(1) DEFAULT '0',
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`device_id`),
  UNIQUE KEY `device_fingerprint` (`device_fingerprint`),
  KEY `user_id` (`user_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_fingerprint` (`device_fingerprint`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_devices` */

/*Table structure for table `member_documents` */

DROP TABLE IF EXISTS `member_documents`;

CREATE TABLE `member_documents` (
  `doc_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `document_type` enum('id_copy','passport_photo','membership_form','employment_letter','other') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_id`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_documents` */

/*Table structure for table `member_login_audit` */

DROP TABLE IF EXISTS `member_login_audit`;

CREATE TABLE `member_login_audit` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `member_id` int DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `status` enum('success','failed_password','failed_username','locked','suspicious') DEFAULT 'failed_password',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `login_timestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `mfa_verified` tinyint(1) DEFAULT '0',
  `mfa_method` varchar(20) DEFAULT NULL,
  `geographic_anomaly` tinyint(1) DEFAULT '0',
  `device_fingerprint` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_timestamp` (`login_timestamp`),
  KEY `idx_status` (`status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_login_audit` */

/*Table structure for table `member_login_credentials_history` */

DROP TABLE IF EXISTS `member_login_credentials_history`;

CREATE TABLE `member_login_credentials_history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `member_id` int NOT NULL,
  `old_username` varchar(50) DEFAULT NULL,
  `new_username` varchar(50) DEFAULT NULL,
  `old_password_hash` varchar(255) DEFAULT NULL,
  `new_password_hash` varchar(255) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `changed_by` int DEFAULT NULL,
  `change_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `changed_by` (`changed_by`),
  KEY `idx_member` (`member_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_login_credentials_history` */

/*Table structure for table `member_otp_tokens` */

DROP TABLE IF EXISTS `member_otp_tokens`;

CREATE TABLE `member_otp_tokens` (
  `otp_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `member_id` int NOT NULL,
  `otp_code` varchar(10) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `purpose` enum('login_verification','password_reset','phone_change','device_change') DEFAULT 'login_verification',
  `is_used` tinyint(1) DEFAULT '0',
  `used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`otp_id`),
  KEY `member_id` (`member_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_unused` (`is_used`,`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_otp_tokens` */

/*Table structure for table `member_security_preferences` */

DROP TABLE IF EXISTS `member_security_preferences`;

CREATE TABLE `member_security_preferences` (
  `preference_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `member_id` int NOT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `two_factor_method` enum('sms','email','authenticator_app') DEFAULT 'sms',
  `trusted_devices_only` tinyint(1) DEFAULT '0',
  `notification_on_login` tinyint(1) DEFAULT '1',
  `notification_on_transaction` tinyint(1) DEFAULT '1',
  `session_timeout_minutes` int DEFAULT '30',
  `allowed_login_hours` varchar(50) DEFAULT NULL,
  `require_password_change_days` int DEFAULT '90',
  `failed_login_threshold` int DEFAULT '5',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`preference_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `member_id` (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_security_preferences` */

/*Table structure for table `member_sessions` */

DROP TABLE IF EXISTS `member_sessions`;

CREATE TABLE `member_sessions` (
  `session_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `member_id` int NOT NULL,
  `session_token` varchar(191) NOT NULL,
  `device_id` int DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `location` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `login_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `logout_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  UNIQUE KEY `session_token` (`session_token`),
  KEY `user_id` (`user_id`),
  KEY `device_id` (`device_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_token` (`session_token`),
  KEY `idx_active` (`is_active`,`expires_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_sessions` */

/*Table structure for table `member_share_holdings` */

DROP TABLE IF EXISTS `member_share_holdings`;

CREATE TABLE `member_share_holdings` (
  `share_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `shares_owned` int NOT NULL DEFAULT '0',
  `share_price` decimal(12,2) NOT NULL DEFAULT '10000.00',
  `total_invested` decimal(15,2) NOT NULL DEFAULT '0.00',
  `last_purchase_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`share_id`),
  UNIQUE KEY `idx_member` (`member_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_share_holdings` */

insert  into `member_share_holdings`(`share_id`,`member_id`,`shares_owned`,`share_price`,`total_invested`,`last_purchase_date`,`created_at`,`updated_at`) values (1,3,5,'10000.00','50000.00','2026-05-29 20:34:10','2026-05-29 20:19:19','2026-06-22 12:40:32'),(2,2,2,'10000.00','20000.00','2026-05-29 20:25:34','2026-05-29 20:25:34','2026-05-29 20:25:34'),(3,4,5,'10000.00','50000.00','2026-06-21 13:24:33','2026-05-29 20:26:03','2026-06-21 13:24:33'),(4,7,9,'10000.00','90000.00','2026-06-23 23:24:46','2026-06-17 10:17:51','2026-06-23 23:24:46'),(5,6,3,'10000.00','30000.00','2026-06-17 10:18:15','2026-06-17 10:18:15','2026-06-17 10:18:15'),(6,8,16,'10000.00','160000.00','2026-06-17 18:54:07','2026-06-17 18:54:07','2026-06-17 18:54:50'),(7,10,6,'10000.00','60000.00','2026-06-18 19:14:51','2026-06-18 19:14:51','2026-06-18 19:14:51');

/*Table structure for table `member_share_transactions` */

DROP TABLE IF EXISTS `member_share_transactions`;

CREATE TABLE `member_share_transactions` (
  `transaction_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `related_member_id` int DEFAULT NULL,
  `transfer_id` int DEFAULT NULL,
  `share_id` int NOT NULL,
  `account_id` int DEFAULT NULL,
  `transaction_type` enum('purchase','sell','transfer_in','transfer_out','adjustment','reversal') NOT NULL DEFAULT 'purchase',
  `shares` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `transaction_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `description` text,
  `status` enum('pending','completed','rejected','reversed') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_share` (`share_id`),
  KEY `idx_account` (`account_id`),
  KEY `idx_member_status` (`member_id`,`status`),
  KEY `idx_related_member` (`related_member_id`),
  KEY `idx_transfer` (`transfer_id`)
) ENGINE=MyISAM AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_share_transactions` */

insert  into `member_share_transactions`(`transaction_id`,`member_id`,`related_member_id`,`transfer_id`,`share_id`,`account_id`,`transaction_type`,`shares`,`amount`,`reference_number`,`transaction_date`,`created_by`,`description`,`status`,`created_at`,`updated_at`) values (1,3,NULL,NULL,1,4,'purchase',2,'20000.00','SHR-3-1780075157','2026-05-29 20:19:20',1,'Share purchase from savings account','completed','2026-05-29 20:19:20','2026-05-29 20:19:20'),(2,3,NULL,NULL,1,4,'purchase',2,'20000.00','SHR-3-1780075459','2026-05-29 20:24:20',1,'Share purchase from savings account','completed','2026-05-29 20:24:20','2026-05-29 20:24:20'),(3,3,NULL,NULL,1,4,'purchase',2,'20000.00','SHR-3-1780075495','2026-05-29 20:24:55',1,'Share purchase from savings account','completed','2026-05-29 20:24:55','2026-05-29 20:24:55'),(4,2,NULL,NULL,2,3,'purchase',2,'20000.00','SHR-2-1780075534','2026-05-29 20:25:34',1,'Share purchase from savings account','completed','2026-05-29 20:25:34','2026-05-29 20:25:34'),(5,4,NULL,NULL,3,6,'purchase',2,'20000.00','SHR-4-1780075563','2026-05-29 20:26:03',1,'Share purchase from savings account','completed','2026-05-29 20:26:03','2026-05-29 20:26:03'),(6,3,NULL,NULL,1,5,'purchase',2,'20000.00','SHR-3-1780076050','2026-05-29 20:34:10',1,'Share purchase from savings account','completed','2026-05-29 20:34:10','2026-05-29 20:34:10'),(7,3,4,1,1,NULL,'transfer_out',3,'30000.00','STR-3-4-1780077070','2026-05-29 20:51:15',1,'Share transfer to Hellen Ekanu','completed','2026-05-29 20:51:15','2026-05-29 20:51:15'),(8,4,3,1,3,NULL,'transfer_in',3,'30000.00','STR-3-4-1780077070','2026-05-29 20:51:15',1,'Share transfer received from ','completed','2026-05-29 20:51:15','2026-05-29 20:51:15'),(9,3,4,2,1,NULL,'transfer_out',2,'20000.00','STR-3-4-1780077242','2026-05-29 20:54:02',1,'Share transfer to Hellen Ekanu','completed','2026-05-29 20:54:02','2026-05-29 20:54:02'),(10,4,3,2,3,NULL,'transfer_in',2,'20000.00','STR-3-4-1780077242','2026-05-29 20:54:02',1,'Share transfer received from ','completed','2026-05-29 20:54:02','2026-05-29 20:54:02'),(11,3,4,3,1,NULL,'transfer_out',2,'20000.00','STR-3-4-1780078429','2026-05-29 21:13:51',1,'Share transfer to Hellen Ekanu','completed','2026-05-29 21:13:51','2026-05-29 21:13:51'),(12,4,3,3,3,NULL,'transfer_in',2,'20000.00','STR-3-4-1780078429','2026-05-29 21:13:51',1,'Share transfer received from Musoke Richard','completed','2026-05-29 21:13:51','2026-05-29 21:13:51'),(13,7,NULL,NULL,4,8,'purchase',4,'40000.00','SHR-7-1781680671','2026-06-17 10:17:52',1,'Share purchase from savings account','completed','2026-06-17 10:17:52','2026-06-17 10:17:52'),(14,6,NULL,NULL,5,9,'purchase',3,'30000.00','SHR-6-1781680695','2026-06-17 10:18:15',1,'Share purchase from savings account','completed','2026-06-17 10:18:15','2026-06-17 10:18:15'),(15,4,7,4,3,NULL,'transfer_out',4,'40000.00','STR-4-7-1781680792','2026-06-17 10:19:52',1,'Share transfer to Buyego Fred','completed','2026-06-17 10:19:52','2026-06-17 10:19:52'),(16,7,4,4,4,NULL,'transfer_in',4,'40000.00','STR-4-7-1781680792','2026-06-17 10:19:52',1,'Share transfer received from Hellen Ekanu','completed','2026-06-17 10:19:52','2026-06-17 10:19:52'),(17,8,NULL,NULL,6,10,'purchase',12,'120000.00','SHR-8-1781711646','2026-06-17 18:54:07',1,'Share purchase from savings account','completed','2026-06-17 18:54:07','2026-06-17 18:54:07'),(18,4,8,5,3,NULL,'transfer_out',4,'40000.00','STR-4-8-1781711690','2026-06-17 18:54:50',1,'Share transfer to Lamech Katamba','completed','2026-06-17 18:54:50','2026-06-17 18:54:50'),(19,8,4,5,6,NULL,'transfer_in',4,'40000.00','STR-4-8-1781711690','2026-06-17 18:54:50',1,'Share transfer received from Hellen Ekanu','completed','2026-06-17 18:54:50','2026-06-17 18:54:50'),(20,10,NULL,NULL,7,12,'purchase',6,'60000.00','SHR-10-1781799290','2026-06-18 19:14:52',1,'Share purchase from savings account','completed','2026-06-18 19:14:52','2026-06-18 19:14:52'),(21,4,NULL,NULL,3,6,'purchase',4,'40000.00','SHR-4-1782037472','2026-06-21 13:24:33',1,'Share purchase from savings account','completed','2026-06-21 13:24:33','2026-06-21 13:24:33'),(22,3,NULL,NULL,1,NULL,'adjustment',4,'40000.00','SADJ-3-1782121232','2026-06-22 12:40:32',1,'Min','completed','2026-06-22 12:40:32','2026-06-22 12:40:32'),(23,7,NULL,NULL,4,8,'purchase',1,'10000.00','SHR-7-1782246282','2026-06-23 23:24:47',5,'Share purchase from savings account','completed','2026-06-23 23:24:47','2026-06-23 23:24:47');

/*Table structure for table `member_share_transfers` */

DROP TABLE IF EXISTS `member_share_transfers`;

CREATE TABLE `member_share_transfers` (
  `transfer_id` int NOT NULL AUTO_INCREMENT,
  `source_member_id` int NOT NULL,
  `destination_member_id` int NOT NULL,
  `source_share_id` int NOT NULL,
  `destination_share_id` int DEFAULT NULL,
  `shares_transferred` int NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_number` varchar(100) NOT NULL,
  `status` enum('pending','approved','completed','rejected','reversed') DEFAULT 'completed',
  `posted_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `reversed_by` int DEFAULT NULL,
  `notes` text,
  `transfer_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transfer_id`),
  KEY `source_share_id` (`source_share_id`),
  KEY `destination_share_id` (`destination_share_id`),
  KEY `posted_by` (`posted_by`),
  KEY `approved_by` (`approved_by`),
  KEY `reversed_by` (`reversed_by`),
  KEY `idx_source_member` (`source_member_id`),
  KEY `idx_destination_member` (`destination_member_id`),
  KEY `idx_reference` (`reference_number`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `member_share_transfers` */

insert  into `member_share_transfers`(`transfer_id`,`source_member_id`,`destination_member_id`,`source_share_id`,`destination_share_id`,`shares_transferred`,`amount`,`reference_number`,`status`,`posted_by`,`approved_by`,`reversed_by`,`notes`,`transfer_date`) values (1,3,4,1,3,3,'30000.00','STR-3-4-1780077070','completed',1,NULL,NULL,'','2026-05-29 20:51:15'),(2,3,4,1,3,2,'20000.00','STR-3-4-1780077242','completed',1,NULL,NULL,'','2026-05-29 20:54:02'),(3,3,4,1,3,2,'20000.00','STR-3-4-1780078429','completed',1,NULL,NULL,'','2026-05-29 21:13:50'),(4,4,7,3,4,4,'40000.00','STR-4-7-1781680792','completed',1,NULL,NULL,'','2026-06-17 10:19:52'),(5,4,8,3,6,4,'40000.00','STR-4-8-1781711690','completed',1,NULL,NULL,'','2026-06-17 18:54:50');

/*Table structure for table `members` */

DROP TABLE IF EXISTS `members`;

CREATE TABLE `members` (
  `member_id` int NOT NULL AUTO_INCREMENT,
  `membership_no` varchar(20) NOT NULL,
  `national_id` varchar(20) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `employer` varchar(100) DEFAULT NULL,
  `join_date` date NOT NULL,
  `status` enum('active','inactive','deceased','suspended') DEFAULT 'active',
  `photo_path` varchar(255) DEFAULT NULL,
  `signature_path` varchar(255) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `membership_no` (`membership_no`),
  UNIQUE KEY `national_id` (`national_id`),
  KEY `idx_membership_no` (`membership_no`),
  KEY `idx_phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `members_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `members_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `members_ibfk_5` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `members_ibfk_6` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `members_ibfk_7` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `members` */

insert  into `members`(`member_id`,`membership_no`,`national_id`,`full_name`,`phone`,`email`,`address`,`date_of_birth`,`gender`,`occupation`,`employer`,`join_date`,`status`,`photo_path`,`signature_path`,`created_by`,`user_id`,`created_at`,`updated_at`) values (1,'SAC202663690','CM790102CF','James Komako','+256752965680','komakoj22@gmail.com',NULL,'1979-02-01','Male','IT',NULL,'2026-05-15','active',NULL,NULL,1,NULL,'2026-05-15 17:43:00','2026-05-15 17:43:00'),(2,'001','CM800162CF','Joseph Kamya','+256782880410','joseph@gmail.com',NULL,'2026-05-26','Male','Teacher',NULL,'2026-05-26','active',NULL,NULL,1,NULL,'2026-05-26 14:42:44','2026-05-26 14:42:44'),(3,'002','CM8709890FM','Musoke Richard','+256781236358',NULL,NULL,'1984-05-11','Male','IT',NULL,'2026-05-27','active',NULL,NULL,1,NULL,'2026-05-27 18:16:52','2026-05-27 18:16:52'),(4,'003','CF8709860FM','Hellen Ekanu','+256771458963','testing@test.com',NULL,'1987-08-19','Female','Farmer',NULL,'2026-05-28','active',NULL,NULL,1,NULL,'2026-05-28 12:26:26','2026-05-28 12:26:26'),(5,'004','CM6709890FM','Mugumya John','+256789236354','mugumya@gmail.com',NULL,'1976-05-25','Male','DFO',NULL,'2026-05-28','active',NULL,NULL,1,NULL,'2026-05-28 15:09:26','2026-05-28 15:09:26'),(6,'005','CF8599840FM','Robin Mukasa','+256771458963','test@gmail.com',NULL,'1980-08-02','Male','Secretary',NULL,'2026-06-17','active',NULL,NULL,1,NULL,'2026-06-17 10:12:02','2026-06-17 10:12:02'),(7,'006','CM6709822FM','Buyego Fred','+256771458963','testing@test.com',NULL,'1975-09-18','Female','DFO',NULL,'2026-06-17','active',NULL,NULL,1,NULL,'2026-06-17 10:12:59','2026-06-17 10:12:59'),(8,'007','CM65698822FM','Lamech Katamba','+256781236358','test@gmail.com',NULL,'1975-05-11','Male','IT',NULL,'2026-06-17','active',NULL,NULL,1,NULL,'2026-06-17 14:12:58','2026-06-17 14:12:58'),(9,'008','CM6772390FM','Agnes Musitwa','+256789236354','test@gmail.com',NULL,'2009-02-02','Female','Student',NULL,'2026-06-17','active',NULL,NULL,1,NULL,'2026-06-17 14:14:10','2026-06-17 14:14:10'),(10,'009','CM8111190FM','Nambwayo Ritah','+256771458963','testing@test.com',NULL,'1997-10-05','Female','',NULL,'2026-06-18','active',NULL,NULL,1,NULL,'2026-06-18 19:13:31','2026-06-18 19:13:31'),(11,'010','CM8702220FM','Paul Adrole','+256781236358','adrole@mtn.com',NULL,'1979-08-08','Male','Team Leader',NULL,'2026-06-21','active',NULL,NULL,1,NULL,'2026-06-21 16:48:58','2026-06-21 16:48:58'),(12,'011','CM8700090FM','Kagimu Henry','+256771458963','mine@test.com',NULL,'1976-05-01','Male','Farmer',NULL,'2026-06-21','active',NULL,NULL,1,NULL,'2026-06-21 17:35:40','2026-06-21 17:35:40'),(13,'012','CM878820FM','Esther Akiiki','+256789236354','mine@test.com',NULL,'1998-02-01','Female','Teacher',NULL,'2026-06-24','active',NULL,NULL,1,NULL,'2026-06-24 12:10:22','2026-06-24 12:10:22');

/*Table structure for table `next_of_kin` */

DROP TABLE IF EXISTS `next_of_kin`;

CREATE TABLE `next_of_kin` (
  `kin_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text,
  PRIMARY KEY (`kin_id`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `next_of_kin` */

/*Table structure for table `notifications` */

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `notification_type` enum('sms','email','in_app') NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text,
  `is_sent` tinyint(1) DEFAULT '0',
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_sent` (`is_sent`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `notifications` */

/*Table structure for table `password_reset_tokens` */

DROP TABLE IF EXISTS `password_reset_tokens`;

CREATE TABLE `password_reset_tokens` (
  `token_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(191) NOT NULL,
  `token_hash` varchar(255) NOT NULL,
  `purpose` enum('reset','first_login','change') DEFAULT 'reset',
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_token` (`token_hash`(250)),
  KEY `idx_user_expires` (`user_id`,`expires_at`),
  KEY `idx_unused` (`used_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `password_reset_tokens` */

/*Table structure for table `password_resets` */

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `reset_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `password_resets` */

/*Table structure for table `permissions` */

DROP TABLE IF EXISTS `permissions`;

CREATE TABLE `permissions` (
  `permission_id` int NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `permissions` */

insert  into `permissions`(`permission_id`,`permission_key`,`label`,`description`,`created_at`) values (1,'user.manage','Manage users','Create, update and deactivate system users','2026-05-29 17:07:00'),(2,'loan.approve','Approve loans','Approve loan applications','2026-05-29 17:07:00'),(3,'settings.manage','Manage settings','Create and edit system settings','2026-05-29 17:07:00'),(4,'report.view','View reports','Access reports and dashboards','2026-05-29 17:07:00'),(5,'audit.view','View audits','Access audit logs','2026-05-29 17:07:00');

/*Table structure for table `role_permissions` */

DROP TABLE IF EXISTS `role_permissions`;

CREATE TABLE `role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `role_permissions` */

/*Table structure for table `roles` */

DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `roles` */

insert  into `roles`(`role_id`,`role_name`,`label`,`description`,`created_at`) values (1,'admin','Super Admin','Full access to all system functions','2026-05-29 17:06:59'),(2,'manager','Manager','Manage business operations and approvals','2026-05-29 17:06:59'),(3,'accountant','Accountant','Manage finance, reports and ledger entries','2026-05-29 17:06:59'),(4,'loan_officer','Loan Officer','Manage loan applications and approvals','2026-05-29 17:06:59'),(5,'cashier','Teller','Process savings deposits and withdrawals','2026-05-29 17:06:59'),(6,'audit','Auditor','View audit logs and compliance reports','2026-05-29 17:06:59'),(7,'viewer','Viewer','Read-only access','2026-05-29 17:06:59');

/*Table structure for table `savings_accounts` */

DROP TABLE IF EXISTS `savings_accounts`;

CREATE TABLE `savings_accounts` (
  `account_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `account_type` enum('monthly_savings','share_capital','voluntary','fixed_deposit') NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `balance` decimal(12,2) DEFAULT '0.00',
  `interest_rate` decimal(5,2) DEFAULT '0.00',
  `opening_balance` decimal(12,2) DEFAULT '0.00',
  `status` enum('active','dormant','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_transaction_date` datetime DEFAULT NULL,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `account_number` (`account_number`),
  KEY `idx_member` (`member_id`),
  KEY `idx_account_number` (`account_number`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `savings_accounts` */

insert  into `savings_accounts`(`account_id`,`member_id`,`account_type`,`account_number`,`balance`,`interest_rate`,`opening_balance`,`status`,`created_at`,`last_transaction_date`) values (1,1,'fixed_deposit','SV000001','160000.00','0.00','100000.00','active','2026-05-26 04:09:24',NULL),(2,2,'monthly_savings','SV000002','630000.00','0.00','250000.00','active','2026-05-26 14:45:46','2026-06-21 17:15:23'),(3,2,'monthly_savings','SV000003','850000.00','0.00','900000.00','active','2026-05-26 14:55:30',NULL),(4,3,'monthly_savings','SV000004','800000.00','0.00','50000.00','active','2026-05-27 18:17:58','2026-06-18 19:09:12'),(5,3,'monthly_savings','SV000005','830000.00','0.00','850000.00','active','2026-05-27 19:01:40',NULL),(6,4,'voluntary','SV000006','55000.00','0.00','20000.00','active','2026-05-28 12:27:42',NULL),(7,5,'monthly_savings','SV000007','120000.00','0.00','20000.00','active','2026-05-28 15:12:27',NULL),(8,7,'monthly_savings','SV000008','9816000.00','0.00','50000.00','active','2026-06-17 10:14:00','2026-06-18 20:18:40'),(9,6,'monthly_savings','SV000009','0.00','0.00','50000.00','active','2026-06-17 10:14:21','2026-06-23 20:23:20'),(10,8,'voluntary','SV000010','7280000.00','0.00','200000.00','active','2026-06-17 14:14:45',NULL),(11,9,'monthly_savings','SV000011','170000.00','0.00','200000.00','active','2026-06-17 14:15:35','2026-06-21 13:23:39'),(12,10,'monthly_savings','SV000012','552000.00','0.00','180000.00','active','2026-06-18 19:14:14','2026-06-21 13:22:00'),(13,11,'fixed_deposit','SV000013','400000.00','0.00','100000.00','active','2026-06-21 16:50:49','2026-06-21 16:52:04'),(14,12,'monthly_savings','SV000014','160000.00','0.00','50000.00','active','2026-06-21 17:36:50','2026-06-21 17:37:22'),(15,13,'monthly_savings','SV000015','180000.00','0.00','90000.00','active','2026-06-24 12:11:09','2026-06-24 12:11:10');

/*Table structure for table `savings_transactions` */

DROP TABLE IF EXISTS `savings_transactions`;

CREATE TABLE `savings_transactions` (
  `trans_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `transaction_type` enum('deposit','withdrawal','interest','transfer_in','transfer_out') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','mobile_money','bank_transfer','cheque','internal') NOT NULL DEFAULT 'internal',
  `reference_no` varchar(50) DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `description` text,
  `posted_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'completed',
  `transaction_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`trans_id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `idx_account` (`account_id`),
  KEY `idx_receipt` (`receipt_no`),
  KEY `idx_date` (`transaction_date`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `savings_transactions` */

insert  into `savings_transactions`(`trans_id`,`account_id`,`transaction_type`,`amount`,`balance_after`,`payment_method`,`reference_no`,`receipt_no`,`description`,`posted_by`,`approved_by`,`status`,`transaction_date`) values (1,1,'deposit','100000.00','100000.00','cash','','DEP20260526040924791','Initial savings account opening deposit',1,NULL,'completed','2026-05-26 04:09:24'),(2,1,'deposit','60000.00','160000.00','cash','try','DEP20260526041016733','',1,NULL,'completed','2026-05-26 04:10:16'),(3,2,'deposit','250000.00','250000.00','cash','','DEP20260526144546841','Initial savings account opening deposit',1,NULL,'completed','2026-05-26 14:45:46'),(4,3,'deposit','900000.00','900000.00','cash','','DEP20260526145530917','Initial savings account opening deposit',1,NULL,'completed','2026-05-26 14:55:30'),(5,3,'withdrawal','50000.00','850000.00','mobile_money','made','WTH20260526150039742',NULL,1,NULL,'completed','2026-05-26 15:00:39'),(6,4,'deposit','50000.00','50000.00','cash','','DEP20260527181758971','Initial savings account opening deposit',1,NULL,'completed','2026-05-27 18:17:58'),(7,5,'deposit','850000.00','850000.00','cash','','DEP20260527190140541','Initial savings account opening deposit',1,NULL,'completed','2026-05-27 19:01:40'),(8,4,'deposit','750000.00','800000.00','cash','','DEP20260527191549509','',1,NULL,'completed','2026-05-27 19:15:49'),(9,4,'withdrawal','200000.00','600000.00','cash','','WTH20260527191623632',NULL,1,NULL,'completed','2026-05-27 19:16:24'),(10,6,'deposit','20000.00','20000.00','cash','','DEP20260528122742133','Initial savings account opening deposit',1,NULL,'completed','2026-05-28 12:27:43'),(11,6,'deposit','100000.00','120000.00','bank_transfer','','DEP20260528123157977','',1,NULL,'completed','2026-05-28 12:31:57'),(12,6,'withdrawal','25000.00','95000.00','bank_transfer','','WTH20260528123525973',NULL,1,NULL,'completed','2026-05-28 12:35:25'),(13,7,'deposit','20000.00','20000.00','bank_transfer','','DEP20260528151227929','Initial savings account opening deposit',1,NULL,'completed','2026-05-28 15:12:27'),(14,7,'deposit','150000.00','170000.00','bank_transfer','','DEP20260528151824626','',1,NULL,'completed','2026-05-28 15:18:24'),(15,7,'withdrawal','50000.00','120000.00','bank_transfer','','WTH20260528152121844',NULL,1,NULL,'completed','2026-05-28 15:21:21'),(16,4,'transfer_out','50000.00','550000.00','','SHR-3-1780060633','SP20260529161713357','Share purchase from savings account',1,NULL,'completed','2026-05-29 16:17:14'),(22,5,'transfer_out','20000.00','830000.00','internal','SHR-3-1780076050','SP20260529203410997','Share purchase from savings account',1,NULL,'completed','2026-05-29 20:34:10'),(23,8,'deposit','50000.00','50000.00','cash','','DEP20260617101400801','Initial savings account opening deposit',1,NULL,'completed','2026-06-17 10:14:00'),(24,9,'deposit','50000.00','50000.00','cash','','DEP20260617101421203','Initial savings account opening deposit',1,NULL,'completed','2026-06-17 10:14:21'),(25,8,'transfer_out','40000.00','10000.00','internal','SHR-7-1781680671','SP20260617101751690','Share purchase from savings account',1,NULL,'completed','2026-06-17 10:17:51'),(26,9,'transfer_out','30000.00','20000.00','internal','SHR-6-1781680695','SP20260617101815451','Share purchase from savings account',1,NULL,'completed','2026-06-17 10:18:15'),(27,8,'deposit','900000.00','910000.00','cash','','DEP20260617122436537','',1,NULL,'completed','2026-06-17 12:24:36'),(28,8,'withdrawal','80000.00','830000.00','cash','','WTH20260617122505145',NULL,1,NULL,'completed','2026-06-17 12:25:05'),(29,9,'deposit','1500000.00','1520000.00','cash','','DEP20260617122555146','',1,NULL,'completed','2026-06-17 12:25:55'),(30,9,'withdrawal','600000.00','920000.00','cash','','WTH20260617122632820',NULL,1,NULL,'completed','2026-06-17 12:26:32'),(31,10,'deposit','200000.00','200000.00','cash','','DEP20260617141445971','Initial savings account opening deposit',1,NULL,'completed','2026-06-17 14:14:45'),(32,11,'deposit','200000.00','200000.00','cash','','DEP20260617141535986','Initial savings account opening deposit',1,NULL,'completed','2026-06-17 14:15:35'),(33,10,'deposit','12000000.00','12200000.00','cash','','DEP20260617141607163','',1,NULL,'completed','2026-06-17 14:16:07'),(34,8,'deposit','9000000.00','9830000.00','cash','','DEP20260617141647948','',1,NULL,'completed','2026-06-17 14:16:47'),(35,10,'withdrawal','4000000.00','8200000.00','cash','','WTH20260617141714579',NULL,1,NULL,'completed','2026-06-17 14:17:14'),(36,10,'withdrawal','800000.00','7400000.00','cash','','WTH20260617141753206',NULL,1,NULL,'completed','2026-06-17 14:17:53'),(37,10,'transfer_out','120000.00','7280000.00','internal','SHR-8-1781711646','SP20260617185406193','Share purchase from savings account',1,NULL,'completed','2026-06-17 18:54:06'),(38,2,'deposit','400000.00','650000.00','cash','','DEP20260617200536364','',1,NULL,'completed','2026-06-17 20:05:36'),(39,9,'withdrawal','500000.00','420000.00','cash','','WTH20260617200601648',NULL,1,NULL,'completed','2026-06-17 20:06:01'),(41,4,'deposit','250000.00','800000.00','cash','DEP20260618190912292','DEP20260618190912537',NULL,1,NULL,'completed','2026-06-18 19:09:12'),(42,9,'withdrawal','150000.00','270000.00','cash','','WTH20260618191036525',NULL,1,NULL,'completed','2026-06-18 19:10:36'),(43,12,'deposit','180000.00','360000.00','cash','','DEP20260618191414530',NULL,1,NULL,'completed','2026-06-18 19:14:14'),(44,12,'transfer_out','60000.00','300000.00','internal','SHR-10-1781799290','SP20260618191450892','Share purchase from savings account',1,NULL,'completed','2026-06-18 19:14:50'),(45,12,'deposit','2000.00','302000.00','cash','DEP20260618201810581','DEP20260618201810966',NULL,1,NULL,'completed','2026-06-18 20:18:10'),(46,8,'withdrawal','4000.00','9826000.00','cash','','WTH20260618201840557',NULL,1,NULL,'completed','2026-06-18 20:18:40'),(47,12,'deposit','250000.00','552000.00','cheque','DEP20260621132200884','DEP20260621132200967',NULL,1,NULL,'completed','2026-06-21 13:22:00'),(48,11,'withdrawal','30000.00','170000.00','cash','','WTH20260621132339731',NULL,1,NULL,'completed','2026-06-21 13:23:39'),(49,6,'transfer_out','40000.00','55000.00','internal','SHR-4-1782037472','SP20260621132432595','Share purchase from savings account',1,NULL,'completed','2026-06-21 13:24:32'),(50,13,'deposit','100000.00','200000.00','mobile_money','','DEP20260621165049298',NULL,1,NULL,'completed','2026-06-21 16:50:49'),(51,13,'deposit','500000.00','700000.00','cash','DEP20260621165127649','DEP20260621165127886',NULL,1,NULL,'completed','2026-06-21 16:51:27'),(52,13,'withdrawal','300000.00','400000.00','cash','','WTH20260621165204960',NULL,1,NULL,'completed','2026-06-21 16:52:04'),(53,2,'withdrawal','20000.00','630000.00','cash','','WTH20260621171523103',NULL,1,NULL,'completed','2026-06-21 17:15:23'),(54,14,'deposit','50000.00','100000.00','cash','','DEP20260621173650958',NULL,1,NULL,'completed','2026-06-21 17:36:50'),(55,14,'deposit','60000.00','160000.00','cash','DEP20260621173722497','DEP20260621173722490',NULL,1,NULL,'completed','2026-06-21 17:37:22'),(56,9,'withdrawal','270000.00','0.00','cash','','WTH20260623202320511',NULL,1,NULL,'completed','2026-06-23 20:23:20'),(57,8,'transfer_out','10000.00','9816000.00','internal','SHR-7-1782246282','SP20260623232442798','Share purchase from savings account',5,NULL,'completed','2026-06-23 23:24:42'),(58,15,'deposit','90000.00','180000.00','cash','','DEP20260624121110161',NULL,1,NULL,'completed','2026-06-24 12:11:10');

/*Table structure for table `schema_migrations` */

DROP TABLE IF EXISTS `schema_migrations`;

CREATE TABLE `schema_migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `schema_migrations` */

insert  into `schema_migrations`(`id`,`filename`,`applied_at`) values (1,'001_create_sessions_and_standing_orders.sql','2026-05-18 19:58:57'),(2,'002_roles_permissions_settings.sql','2026-05-29 17:07:01'),(3,'003_add_auth_columns_to_users.sql','2026-05-29 17:10:45'),(4,'004_member_authentication.sql','2026-05-29 17:28:14'),(5,'005_member_shares.sql','2026-05-29 17:30:19'),(6,'006_shares_transfers_and_ledger.sql','2026-05-29 17:31:10'),(7,'007_member_share_sell_support.sql','2026-05-29 17:39:25');

/*Table structure for table `sessions` */

DROP TABLE IF EXISTS `sessions`;

CREATE TABLE `sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`session_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `sessions` */

/*Table structure for table `sms_queue` */

DROP TABLE IF EXISTS `sms_queue`;

CREATE TABLE `sms_queue` (
  `sms_id` int NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(50) DEFAULT NULL,
  `message_body` text,
  `message_type` varchar(50) DEFAULT NULL,
  `delivery_status` enum('pending','sent','failed') DEFAULT 'pending',
  `attempts` int DEFAULT '0',
  `max_attempts` int DEFAULT '3',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sms_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `sms_queue` */

/*Table structure for table `standing_order_runs` */

DROP TABLE IF EXISTS `standing_order_runs`;

CREATE TABLE `standing_order_runs` (
  `run_id` int NOT NULL AUTO_INCREMENT,
  `standing_order_id` int NOT NULL,
  `run_date` date NOT NULL,
  `status` enum('pending','processed','failed') DEFAULT 'pending',
  `amount` decimal(15,2) NOT NULL,
  `transaction_reference` varchar(255) DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  PRIMARY KEY (`run_id`),
  KEY `standing_order_id` (`standing_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `standing_order_runs` */

/*Table structure for table `standing_orders` */

DROP TABLE IF EXISTS `standing_orders`;

CREATE TABLE `standing_orders` (
  `standing_order_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `savings_account_id` int DEFAULT NULL,
  `loan_id` int DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `frequency` enum('weekly','monthly','fortnightly') DEFAULT 'monthly',
  `next_run_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`standing_order_id`),
  KEY `member_id` (`member_id`),
  KEY `next_run_date` (`next_run_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `standing_orders` */

/*Table structure for table `system_settings` */

DROP TABLE IF EXISTS `system_settings`;

CREATE TABLE `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `group` varchar(50) DEFAULT 'general',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `system_settings` */

/*Table structure for table `user_roles` */

DROP TABLE IF EXISTS `user_roles`;

CREATE TABLE `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `user_roles` */

/*Table structure for table `users` */

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `role` enum('admin','branch_manager','loan_officer','teller','accountant','viewer') NOT NULL,
  `branch_id` int DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `two_factor_enabled` tinyint(1) DEFAULT '0',
  `two_factor_method` varchar(20) DEFAULT 'sms',
  `two_factor_code` varchar(128) DEFAULT NULL,
  `two_factor_expires` datetime DEFAULT NULL,
  `login_attempts` int DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `last_failed_login` timestamp NULL DEFAULT NULL,
  `password_changed_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT '0',
  `password_expires_at` timestamp NULL DEFAULT NULL,
  `is_member` tinyint(1) DEFAULT '0',
  `linked_member_id` int DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_username` (`username`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

/*Data for the table `users` */

insert  into `users`(`user_id`,`username`,`password_hash`,`full_name`,`email`,`phone`,`role`,`branch_id`,`status`,`last_login`,`created_at`,`two_factor_enabled`,`two_factor_method`,`two_factor_code`,`two_factor_expires`,`login_attempts`,`locked_until`,`last_failed_login`,`password_changed_at`,`last_login_ip`,`must_change_password`,`password_expires_at`,`is_member`,`linked_member_id`) values (1,'admin','$2y$10$1YYpZS4uVjmDb0nMThtnxOtYrbLEO6i6lJl01VXQ51NXzhn4uxu1G','System Administrator','admin@sacco.local',NULL,'admin',NULL,'active','2026-06-24 11:45:59','2026-05-14 16:32:46',0,'sms',NULL,NULL,0,NULL,NULL,NULL,'127.0.0.1',0,NULL,0,NULL),(2,'james','2','System Administrator',NULL,NULL,'admin',NULL,'active',NULL,'2026-05-19 12:57:24',0,'sms',NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,0,NULL),(3,'peter','2','System Administrator',NULL,NULL,'admin',NULL,'active',NULL,'2026-05-19 14:41:42',0,'sms',NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,0,NULL),(4,'verifyuser8119','$2y$10$EJpjJQpYJulgF.8CW4bojeDi/LmEbKATicxQFX6Sa83ilPlft6HIC','Verify User','verify5885@example.com','0700000000','viewer',NULL,'active',NULL,'2026-06-23 23:06:17',0,'sms',NULL,NULL,0,NULL,NULL,NULL,NULL,0,NULL,0,NULL),(5,'jacob','$2y$10$koB6kbnJp.dp14lfZwVy.eoBJPJ3ylT2/g9FWCXL/T7dXIeVI3HqC','Jaco Tugume','test@gmail.com','','loan_officer',NULL,'active','2026-06-23 23:15:38','2026-06-23 23:07:23',0,'sms',NULL,NULL,0,NULL,NULL,NULL,'127.0.0.1',0,NULL,0,NULL);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
