-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jun 12, 2026 at 10:26 AM
-- Server version: 8.0.31
-- PHP Version: 8.0.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sacco_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE IF NOT EXISTS `audit_logs` (
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
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`log_id`, `user_id`, `status`, `action`, `entity_type`, `entity_id`, `table_name`, `record_id`, `old_values`, `new_values`, `old_data`, `new_data`, `ip_address`, `user_agent`, `error_message`, `timestamp`, `created_at`) VALUES
(1, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-15 11:01:57'),
(2, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:148.0) Gecko/20100101 Firefox/148.0', NULL, '2026-05-29 14:31:02', '2026-05-15 11:06:01'),
(3, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.120.0 Chrome/142.0.7444.265 Electron/39.8.8 Safari/537.36', NULL, '2026-05-29 14:31:02', '2026-05-15 12:06:34'),
(4, 1, 'success', 'Create', NULL, NULL, 'members', 1, NULL, NULL, NULL, '{\"full_name\":\"James Komako\",\"national_id\":\"CM790102CF\",\"phone\":\"+256752965680\",\"email\":\"komakoj22@gmail.com\",\"gender\":\"Male\",\"date_of_birth\":\"1979-02-01\",\"occupation\":\"IT\",\"employer\":\"\",\"address\":\"Uganda\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-15 14:43:01'),
(5, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', NULL, '2026-05-29 14:31:02', '2026-05-18 08:46:25'),
(6, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-18 08:58:34'),
(7, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', NULL, '2026-05-29 14:31:02', '2026-05-18 09:58:04'),
(8, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', NULL, '2026-05-29 14:31:02', '2026-05-19 11:38:44'),
(9, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', NULL, '2026-05-29 14:31:02', '2026-05-19 11:44:01'),
(10, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', NULL, '2026-05-29 14:31:02', '2026-05-19 11:44:30'),
(11, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', NULL, '2026-05-29 14:31:02', '2026-05-19 11:56:04'),
(12, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', NULL, '2026-05-29 14:31:02', '2026-05-19 12:41:13'),
(13, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', NULL, '2026-05-29 14:31:02', '2026-05-19 13:28:36'),
(14, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:150.0) Gecko/20100101 Firefox/150.0', NULL, '2026-05-29 14:31:02', '2026-05-19 17:55:47'),
(15, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-26 01:53:34'),
(16, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-26 02:37:34'),
(17, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-26 04:31:15'),
(18, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-26 11:10:11'),
(19, 1, 'success', 'Create', NULL, NULL, 'members', 2, NULL, NULL, NULL, '{\"full_name\":\"Joseph Kamya\",\"national_id\":\"CM800162CF\",\"phone\":\"+256782880410\",\"email\":\"joseph@gmail.com\",\"gender\":\"Male\",\"date_of_birth\":\"2026-05-26\",\"occupation\":\"Teacher\",\"employer\":\"Government\",\"address\":\"Wandegeya\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-26 11:42:44'),
(20, 1, 'success', 'Create', NULL, NULL, 'loans', 1, NULL, NULL, NULL, '{\"member_id\":\"2\",\"product_id\":\"1\",\"amount\":\"500000\",\"period\":\"4\",\"purpose\":\"Sick child\",\"guarantors\":[\"1\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-26 11:48:58'),
(21, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-26 16:06:13'),
(22, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-26 16:42:08'),
(23, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-05-29 14:31:02', '2026-05-27 11:31:17'),
(24, 1, 'success', 'Create', NULL, NULL, 'members', 3, NULL, NULL, NULL, '{\"full_name\":\"Musoke Richard\",\"national_id\":\"CM8709890FM\",\"phone\":\"+256781236358\",\"email\":\"\",\"gender\":\"Male\",\"date_of_birth\":\"1984-05-11\",\"occupation\":\"IT\",\"employer\":\"Government\",\"address\":\"KAWEMPE\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-27 15:16:53'),
(25, 1, 'success', 'Create', NULL, NULL, 'loans', 2, NULL, NULL, NULL, '{\"member_id\":\"1\",\"product_id\":\"1\",\"amount\":\"450000\",\"period\":\"1\",\"purpose\":\"Personal\",\"guarantors\":[\"3\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-27 15:37:41'),
(26, 1, 'success', 'Create', NULL, NULL, 'loans', 3, NULL, NULL, NULL, '{\"member_id\":\"3\",\"product_id\":\"1\",\"amount\":\"150000\",\"period\":\"2\",\"purpose\":\"Personal\",\"guarantors\":[\"2\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-27 16:17:24'),
(27, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-27 17:04:32'),
(28, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-27 17:10:13'),
(29, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-27 17:52:15'),
(30, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-28 09:01:27'),
(31, 1, 'success', 'Create', NULL, NULL, 'members', 4, NULL, NULL, NULL, '{\"full_name\":\"Hellen Ekanu\",\"national_id\":\"CF8709860FM\",\"phone\":\"+256771458963\",\"email\":\"testing@test.com\",\"gender\":\"Female\",\"date_of_birth\":\"1987-08-19\",\"occupation\":\"Farmer\",\"employer\":\"Self Employed\",\"address\":\"Kyengera\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-28 09:26:26'),
(32, 1, 'success', 'Create', NULL, NULL, 'loans', 4, NULL, NULL, NULL, '{\"member_id\":\"4\",\"product_id\":\"4\",\"amount\":\"500000\",\"period\":\"12\",\"purpose\":\"personal development\",\"guarantors\":[\"3\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-28 09:39:46'),
(33, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-28 11:24:41'),
(34, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-28 11:59:34'),
(35, 1, 'success', 'Create', NULL, NULL, 'members', 5, NULL, NULL, NULL, '{\"full_name\":\"Mugumya John\",\"national_id\":\"CM6709890FM\",\"phone\":\"+256789236354\",\"email\":\"mugumya@gmail.com\",\"gender\":\"Male\",\"date_of_birth\":\"1976-05-25\",\"occupation\":\"DFO\",\"employer\":\"Government\",\"address\":\"Rakai\"}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-28 12:09:26'),
(36, 1, 'success', 'Create', NULL, NULL, 'loans', 5, NULL, NULL, NULL, '{\"member_id\":\"5\",\"product_id\":\"1\",\"amount\":\"60000\",\"period\":\"2\",\"purpose\":\"Personal\",\"guarantors\":[\"2\"]}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-28 12:27:23'),
(37, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-28 14:09:22'),
(38, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 14:31:02', '2026-05-28 19:48:21'),
(39, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 16:48:38', '2026-05-29 16:48:38'),
(40, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 16:56:34', '2026-05-29 16:56:34'),
(41, 1, 'success', 'share_purchase', NULL, NULL, 'member_share_transactions', 0, NULL, NULL, NULL, '{\"member_id\":3,\"shares\":2,\"amount\":20000,\"savings_account_id\":4}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 17:19:21', '2026-05-29 17:19:21'),
(42, 1, 'success', 'share_purchase', NULL, NULL, 'member_share_transactions', 0, NULL, NULL, NULL, '{\"member_id\":3,\"shares\":2,\"amount\":20000,\"savings_account_id\":4}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 17:24:20', '2026-05-29 17:24:20'),
(43, 1, 'success', 'share_purchase', NULL, NULL, 'member_share_transactions', 0, NULL, NULL, NULL, '{\"member_id\":3,\"shares\":2,\"amount\":20000,\"savings_account_id\":4}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 17:24:55', '2026-05-29 17:24:55'),
(44, 1, 'success', 'share_purchase', NULL, NULL, 'member_share_transactions', 0, NULL, NULL, NULL, '{\"member_id\":2,\"shares\":2,\"amount\":20000,\"savings_account_id\":3}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 17:25:34', '2026-05-29 17:25:34'),
(45, 1, 'success', 'share_purchase', NULL, NULL, 'member_share_transactions', 0, NULL, NULL, NULL, '{\"member_id\":4,\"shares\":2,\"amount\":20000,\"savings_account_id\":6}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 17:26:03', '2026-05-29 17:26:03'),
(46, 1, 'success', 'share_purchase', NULL, NULL, 'member_share_transactions', 2, NULL, NULL, NULL, '{\"member_id\":3,\"shares\":2,\"amount\":20000,\"savings_account_id\":5}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 17:34:13', '2026-05-29 17:34:13'),
(47, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 18:09:44', '2026-05-29 18:09:44'),
(48, 1, 'success', 'share_transfer', NULL, NULL, 'member_share_transfers', 3, NULL, NULL, NULL, '{\"source_member_id\":3,\"destination_member_id\":4,\"shares\":2,\"amount\":20000}', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:151.0) Gecko/20100101 Firefox/151.0', NULL, '2026-05-29 18:13:52', '2026-05-29 18:13:52'),
(49, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-06-05 07:23:14', '2026-06-05 07:23:14'),
(50, 1, 'success', 'Create', NULL, NULL, 'loans', 6, NULL, NULL, NULL, '{\"member_id\":\"5\",\"product_id\":\"1\",\"amount\":\"70000\",\"period\":\"2\",\"purpose\":\"School fees\",\"guarantors\":[\"3\"]}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-06-05 07:41:41', '2026-06-05 07:41:41'),
(51, 1, 'success', 'Logout', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-06-05 12:25:33', '2026-06-05 12:25:33'),
(52, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-06-05 12:25:36', '2026-06-05 12:25:36'),
(53, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-06-11 07:31:25', '2026-06-11 07:31:25'),
(54, 1, 'success', 'Login', NULL, NULL, 'users', 1, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', NULL, '2026-06-11 12:32:26', '2026-06-11 12:32:26');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
CREATE TABLE IF NOT EXISTS `branches` (
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

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`branch_id`, `branch_code`, `branch_name`, `location`, `phone`, `email`, `manager_id`, `status`) VALUES
(1, 'HQ', 'Head Office', 'Kampala', NULL, NULL, NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `ledger_entries`
--

DROP TABLE IF EXISTS `ledger_entries`;
CREATE TABLE IF NOT EXISTS `ledger_entries` (
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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ledger_entries`
--

INSERT INTO `ledger_entries` (`entry_id`, `ledger_code`, `ledger_name`, `entry_date`, `receipt_number`, `transaction_reference`, `transaction_type`, `debit`, `credit`, `description`, `payment_method`, `posted_by`, `approved_by`, `member_id`, `related_member_id`, `account_type`, `status`, `reversal_of_id`, `created_at`) VALUES
(1, '2020', 'Member Savings', '2026-05-29 17:34:12', 'SHR-3-1780076050', 'SHR-3-1780076050', 'SHARE_PURCHASE', '20000.00', '0.00', 'Transfer from savings to share capital', 'internal', 1, NULL, 3, NULL, 'savings', 'posted', NULL, '2026-05-29 17:34:12'),
(2, '2010', 'Member Share Capital', '2026-05-29 17:34:13', 'SHR-3-1780076050', 'SHR-3-1780076050', 'SHARE_PURCHASE', '0.00', '20000.00', 'Member share capital increase', 'internal', 1, NULL, 3, NULL, 'shares', 'posted', NULL, '2026-05-29 17:34:13'),
(3, '2010', 'Member Share Capital', '2026-05-29 18:13:51', 'STR-3-4-1780078429', 'STR-3-4-1780078429', 'SHARE_TRANSFER_OUT', '20000.00', '0.00', 'Share transfer out to member 4', NULL, 1, NULL, 3, 4, 'shares', 'posted', NULL, '2026-05-29 18:13:51'),
(4, '2010', 'Member Share Capital', '2026-05-29 18:13:51', 'STR-3-4-1780078429', 'STR-3-4-1780078429', 'SHARE_TRANSFER_IN', '0.00', '20000.00', 'Share transfer in from member 3', NULL, 1, NULL, 4, 3, 'shares', 'posted', NULL, '2026-05-29 18:13:51');

-- --------------------------------------------------------

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
CREATE TABLE IF NOT EXISTS `loans` (
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
  PRIMARY KEY (`loan_id`),
  UNIQUE KEY `loan_ref_no` (`loan_ref_no`),
  KEY `product_id` (`product_id`),
  KEY `idx_member` (`member_id`),
  KEY `idx_status` (`status`),
  KEY `idx_ref_no` (`loan_ref_no`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `loans`
--

INSERT INTO `loans` (`loan_id`, `loan_ref_no`, `member_id`, `product_id`, `amount_requested`, `amount_approved`, `interest_rate`, `repayment_period_months`, `processing_fee`, `purpose`, `application_date`, `approval_date`, `disbursement_date`, `first_payment_date`, `status`, `outstanding_balance`, `total_paid`, `applied_by`, `reviewed_by`, `approved_by`, `disbursed_by`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(1, 'LN202605266542', 2, 1, '500000.00', NULL, '12.00', 4, '0.00', 'Sick child', '2026-05-26', '2026-05-27', NULL, NULL, 'rejected', '0.00', '0.00', 1, NULL, 1, NULL, 'Low savings', '2026-05-26 11:48:58', '2026-05-27 15:57:57'),
(2, 'LN202605279169', 1, 1, '450000.00', NULL, '12.00', 1, '0.00', 'Personal', '2026-05-27', '2026-05-27', '2026-05-28', '2026-06-28', 'completed', '0.00', '0.00', 1, NULL, 1, 1, NULL, '2026-05-27 15:37:40', '2026-05-28 09:44:09'),
(3, 'LN202605275603', 3, 1, '150000.00', NULL, '12.00', 2, '0.00', 'Personal', '2026-05-27', '2026-05-27', '2026-05-27', '2026-06-27', 'disbursed', '100000.00', '0.00', 1, NULL, 1, 1, NULL, '2026-05-27 16:17:24', '2026-05-27 17:06:24'),
(4, 'LN202605284210', 4, 4, '500000.00', NULL, '18.00', 12, '0.00', 'personal development', '2026-05-28', '2026-05-28', '2026-05-28', '2026-06-28', 'disbursed', '475000.00', '0.00', 1, NULL, 1, 1, NULL, '2026-05-28 09:39:46', '2026-06-11 07:38:53'),
(5, 'LN202605285431', 5, 1, '60000.00', NULL, '12.00', 2, '0.00', 'Personal', '2026-05-28', '2026-05-28', '2026-05-28', '2026-06-28', 'completed', '0.00', '0.00', 1, NULL, 1, 1, NULL, '2026-05-28 12:27:23', '2026-05-28 12:37:47'),
(6, 'LN202606050702', 5, 1, '70000.00', NULL, '12.00', 2, '0.00', 'School fees', '2026-06-05', NULL, NULL, NULL, 'applied', '0.00', '0.00', 1, NULL, NULL, NULL, NULL, '2026-06-05 07:41:41', '2026-06-05 07:41:41');

-- --------------------------------------------------------

--
-- Table structure for table `loan_guarantors`
--

DROP TABLE IF EXISTS `loan_guarantors`;
CREATE TABLE IF NOT EXISTS `loan_guarantors` (
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `loan_guarantors`
--

INSERT INTO `loan_guarantors` (`guarantor_id`, `loan_id`, `guarantor_member_id`, `amount_guaranteed`, `percentage_guarantee`, `status`, `release_date`, `notes`) VALUES
(1, 1, 1, '160000.00', '32.00', 'active', NULL, NULL),
(2, 2, 3, '50000.00', '11.11', 'active', NULL, NULL),
(3, 3, 2, '150000.00', '100.00', 'active', NULL, NULL),
(4, 4, 3, '500000.00', '100.00', 'active', NULL, NULL),
(5, 5, 2, '60000.00', '100.00', 'active', NULL, NULL),
(6, 6, 3, '70000.00', '100.00', 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loan_products`
--

DROP TABLE IF EXISTS `loan_products`;
CREATE TABLE IF NOT EXISTS `loan_products` (
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

--
-- Dumping data for table `loan_products`
--

INSERT INTO `loan_products` (`product_id`, `product_name`, `description`, `min_amount`, `max_amount`, `default_interest_rate`, `min_repayment_months`, `max_repayment_months`, `processing_fee`, `late_penalty_rate`, `requires_guarantors`, `min_guarantors`, `status`) VALUES
(1, 'Emergency Loan', NULL, '50000.00', '500000.00', '12.00', 1, 6, '0.00', '0.00', 1, 1, 'active'),
(2, 'Development Loan', NULL, '100000.00', '5000000.00', '15.00', 6, 24, '0.00', '0.00', 1, 2, 'active'),
(3, 'School Fees Loan', NULL, '50000.00', '2000000.00', '10.00', 3, 12, '0.00', '0.00', 1, 1, 'active'),
(4, 'Business Loan', NULL, '200000.00', '10000000.00', '18.00', 12, 36, '0.00', '0.00', 1, 2, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayments`
--

DROP TABLE IF EXISTS `loan_repayments`;
CREATE TABLE IF NOT EXISTS `loan_repayments` (
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
  PRIMARY KEY (`repayment_id`),
  UNIQUE KEY `receipt_no` (`receipt_no`),
  KEY `schedule_id` (`schedule_id`),
  KEY `idx_loan` (`loan_id`),
  KEY `idx_receipt` (`receipt_no`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `loan_repayments`
--

INSERT INTO `loan_repayments` (`repayment_id`, `loan_id`, `schedule_id`, `amount_paid`, `principal_paid`, `interest_paid`, `penalty_paid`, `payment_method`, `reference_no`, `receipt_no`, `payment_date`, `posted_by`, `notes`) VALUES
(1, 3, NULL, '50000.00', NULL, NULL, '0.00', 'mobile_money', '', 'LRP20260527200624592', '2026-05-27 20:06:24', 1, NULL),
(2, 2, NULL, '450000.00', NULL, NULL, '0.00', 'mobile_money', '', 'LRP20260528124409772', '2026-05-28 12:44:09', 1, NULL),
(3, 5, NULL, '25000.00', NULL, NULL, '0.00', 'bank_transfer', '', 'LRP20260528153346226', '2026-05-28 15:33:46', 1, NULL),
(4, 5, NULL, '35000.00', NULL, NULL, '0.00', 'bank_transfer', '', 'LRP20260528153747156', '2026-05-28 15:37:47', 1, NULL),
(5, 4, NULL, '25000.00', NULL, NULL, '0.00', 'cash', '', 'LRP20260611103853405', '2026-06-11 10:38:53', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `loan_repayment_schedule`
--

DROP TABLE IF EXISTS `loan_repayment_schedule`;
CREATE TABLE IF NOT EXISTS `loan_repayment_schedule` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

DROP TABLE IF EXISTS `members`;
CREATE TABLE IF NOT EXISTS `members` (
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
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `membership_no`, `national_id`, `full_name`, `phone`, `email`, `address`, `date_of_birth`, `gender`, `occupation`, `employer`, `join_date`, `status`, `photo_path`, `signature_path`, `created_by`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'SAC202663690', 'CM790102CF', 'James Komako', '+256752965680', 'komakoj22@gmail.com', NULL, '1979-02-01', 'Male', 'IT', NULL, '2026-05-15', 'active', NULL, NULL, 1, NULL, '2026-05-15 14:43:00', '2026-05-15 14:43:00'),
(2, '001', 'CM800162CF', 'Joseph Kamya', '+256782880410', 'joseph@gmail.com', NULL, '2026-05-26', 'Male', 'Teacher', NULL, '2026-05-26', 'active', NULL, NULL, 1, NULL, '2026-05-26 11:42:44', '2026-05-26 11:42:44'),
(3, '002', 'CM8709890FM', 'Musoke Richard', '+256781236358', NULL, NULL, '1984-05-11', 'Male', 'IT', NULL, '2026-05-27', 'active', NULL, NULL, 1, NULL, '2026-05-27 15:16:52', '2026-05-27 15:16:52'),
(4, '003', 'CF8709860FM', 'Hellen Ekanu', '+256771458963', 'testing@test.com', NULL, '1987-08-19', 'Female', 'Farmer', NULL, '2026-05-28', 'active', NULL, NULL, 1, NULL, '2026-05-28 09:26:26', '2026-05-28 09:26:26'),
(5, '004', 'CM6709890FM', 'Mugumya John', '+256789236354', 'mugumya@gmail.com', NULL, '1976-05-25', 'Male', 'DFO', NULL, '2026-05-28', 'active', NULL, NULL, 1, NULL, '2026-05-28 12:09:26', '2026-05-28 12:09:26');

-- --------------------------------------------------------

--
-- Table structure for table `member_devices`
--

DROP TABLE IF EXISTS `member_devices`;
CREATE TABLE IF NOT EXISTS `member_devices` (
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

-- --------------------------------------------------------

--
-- Table structure for table `member_documents`
--

DROP TABLE IF EXISTS `member_documents`;
CREATE TABLE IF NOT EXISTS `member_documents` (
  `doc_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `document_type` enum('id_copy','passport_photo','membership_form','employment_letter','other') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`doc_id`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `member_login_audit`
--

DROP TABLE IF EXISTS `member_login_audit`;
CREATE TABLE IF NOT EXISTS `member_login_audit` (
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

-- --------------------------------------------------------

--
-- Table structure for table `member_login_credentials_history`
--

DROP TABLE IF EXISTS `member_login_credentials_history`;
CREATE TABLE IF NOT EXISTS `member_login_credentials_history` (
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

-- --------------------------------------------------------

--
-- Table structure for table `member_otp_tokens`
--

DROP TABLE IF EXISTS `member_otp_tokens`;
CREATE TABLE IF NOT EXISTS `member_otp_tokens` (
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

-- --------------------------------------------------------

--
-- Table structure for table `member_security_preferences`
--

DROP TABLE IF EXISTS `member_security_preferences`;
CREATE TABLE IF NOT EXISTS `member_security_preferences` (
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

-- --------------------------------------------------------

--
-- Table structure for table `member_sessions`
--

DROP TABLE IF EXISTS `member_sessions`;
CREATE TABLE IF NOT EXISTS `member_sessions` (
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

-- --------------------------------------------------------

--
-- Table structure for table `member_share_holdings`
--

DROP TABLE IF EXISTS `member_share_holdings`;
CREATE TABLE IF NOT EXISTS `member_share_holdings` (
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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `member_share_holdings`
--

INSERT INTO `member_share_holdings` (`share_id`, `member_id`, `shares_owned`, `share_price`, `total_invested`, `last_purchase_date`, `created_at`, `updated_at`) VALUES
(1, 3, 1, '10000.00', '10000.00', '2026-05-29 17:34:10', '2026-05-29 17:19:19', '2026-05-29 18:13:50'),
(2, 2, 2, '10000.00', '20000.00', '2026-05-29 17:25:34', '2026-05-29 17:25:34', '2026-05-29 17:25:34'),
(3, 4, 9, '10000.00', '90000.00', '2026-05-29 17:26:03', '2026-05-29 17:26:03', '2026-05-29 18:13:50');

-- --------------------------------------------------------

--
-- Table structure for table `member_share_transactions`
--

DROP TABLE IF EXISTS `member_share_transactions`;
CREATE TABLE IF NOT EXISTS `member_share_transactions` (
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
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `member_share_transactions`
--

INSERT INTO `member_share_transactions` (`transaction_id`, `member_id`, `related_member_id`, `transfer_id`, `share_id`, `account_id`, `transaction_type`, `shares`, `amount`, `reference_number`, `transaction_date`, `created_by`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, NULL, 1, 4, 'purchase', 2, '20000.00', 'SHR-3-1780075157', '2026-05-29 17:19:20', 1, 'Share purchase from savings account', 'completed', '2026-05-29 17:19:20', '2026-05-29 17:19:20'),
(2, 3, NULL, NULL, 1, 4, 'purchase', 2, '20000.00', 'SHR-3-1780075459', '2026-05-29 17:24:20', 1, 'Share purchase from savings account', 'completed', '2026-05-29 17:24:20', '2026-05-29 17:24:20'),
(3, 3, NULL, NULL, 1, 4, 'purchase', 2, '20000.00', 'SHR-3-1780075495', '2026-05-29 17:24:55', 1, 'Share purchase from savings account', 'completed', '2026-05-29 17:24:55', '2026-05-29 17:24:55'),
(4, 2, NULL, NULL, 2, 3, 'purchase', 2, '20000.00', 'SHR-2-1780075534', '2026-05-29 17:25:34', 1, 'Share purchase from savings account', 'completed', '2026-05-29 17:25:34', '2026-05-29 17:25:34'),
(5, 4, NULL, NULL, 3, 6, 'purchase', 2, '20000.00', 'SHR-4-1780075563', '2026-05-29 17:26:03', 1, 'Share purchase from savings account', 'completed', '2026-05-29 17:26:03', '2026-05-29 17:26:03'),
(6, 3, NULL, NULL, 1, 5, 'purchase', 2, '20000.00', 'SHR-3-1780076050', '2026-05-29 17:34:10', 1, 'Share purchase from savings account', 'completed', '2026-05-29 17:34:10', '2026-05-29 17:34:10'),
(7, 3, 4, 1, 1, NULL, 'transfer_out', 3, '30000.00', 'STR-3-4-1780077070', '2026-05-29 17:51:15', 1, 'Share transfer to Hellen Ekanu', 'completed', '2026-05-29 17:51:15', '2026-05-29 17:51:15'),
(8, 4, 3, 1, 3, NULL, 'transfer_in', 3, '30000.00', 'STR-3-4-1780077070', '2026-05-29 17:51:15', 1, 'Share transfer received from ', 'completed', '2026-05-29 17:51:15', '2026-05-29 17:51:15'),
(9, 3, 4, 2, 1, NULL, 'transfer_out', 2, '20000.00', 'STR-3-4-1780077242', '2026-05-29 17:54:02', 1, 'Share transfer to Hellen Ekanu', 'completed', '2026-05-29 17:54:02', '2026-05-29 17:54:02'),
(10, 4, 3, 2, 3, NULL, 'transfer_in', 2, '20000.00', 'STR-3-4-1780077242', '2026-05-29 17:54:02', 1, 'Share transfer received from ', 'completed', '2026-05-29 17:54:02', '2026-05-29 17:54:02'),
(11, 3, 4, 3, 1, NULL, 'transfer_out', 2, '20000.00', 'STR-3-4-1780078429', '2026-05-29 18:13:51', 1, 'Share transfer to Hellen Ekanu', 'completed', '2026-05-29 18:13:51', '2026-05-29 18:13:51'),
(12, 4, 3, 3, 3, NULL, 'transfer_in', 2, '20000.00', 'STR-3-4-1780078429', '2026-05-29 18:13:51', 1, 'Share transfer received from Musoke Richard', 'completed', '2026-05-29 18:13:51', '2026-05-29 18:13:51');

-- --------------------------------------------------------

--
-- Table structure for table `member_share_transfers`
--

DROP TABLE IF EXISTS `member_share_transfers`;
CREATE TABLE IF NOT EXISTS `member_share_transfers` (
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
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `member_share_transfers`
--

INSERT INTO `member_share_transfers` (`transfer_id`, `source_member_id`, `destination_member_id`, `source_share_id`, `destination_share_id`, `shares_transferred`, `amount`, `reference_number`, `status`, `posted_by`, `approved_by`, `reversed_by`, `notes`, `transfer_date`) VALUES
(1, 3, 4, 1, 3, 3, '30000.00', 'STR-3-4-1780077070', 'completed', 1, NULL, NULL, '', '2026-05-29 17:51:15'),
(2, 3, 4, 1, 3, 2, '20000.00', 'STR-3-4-1780077242', 'completed', 1, NULL, NULL, '', '2026-05-29 17:54:02'),
(3, 3, 4, 1, 3, 2, '20000.00', 'STR-3-4-1780078429', 'completed', 1, NULL, NULL, '', '2026-05-29 18:13:50');

-- --------------------------------------------------------

--
-- Table structure for table `next_of_kin`
--

DROP TABLE IF EXISTS `next_of_kin`;
CREATE TABLE IF NOT EXISTS `next_of_kin` (
  `kin_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `relationship` varchar(50) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text,
  PRIMARY KEY (`kin_id`),
  KEY `idx_member` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
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

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `reset_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reset_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
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

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `permission_id` int NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(100) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_key`, `label`, `description`, `created_at`) VALUES
(1, 'user.manage', 'Manage users', 'Create, update and deactivate system users', '2026-05-29 17:07:00'),
(2, 'loan.approve', 'Approve loans', 'Approve loan applications', '2026-05-29 17:07:00'),
(3, 'settings.manage', 'Manage settings', 'Create and edit system settings', '2026-05-29 17:07:00'),
(4, 'report.view', 'View reports', 'Access reports and dashboards', '2026-05-29 17:07:00'),
(5, 'audit.view', 'View audits', 'Access audit logs', '2026-05-29 17:07:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `label`, `description`, `created_at`) VALUES
(1, 'admin', 'Super Admin', 'Full access to all system functions', '2026-05-29 17:06:59'),
(2, 'manager', 'Manager', 'Manage business operations and approvals', '2026-05-29 17:06:59'),
(3, 'accountant', 'Accountant', 'Manage finance, reports and ledger entries', '2026-05-29 17:06:59'),
(4, 'loan_officer', 'Loan Officer', 'Manage loan applications and approvals', '2026-05-29 17:06:59'),
(5, 'cashier', 'Teller', 'Process savings deposits and withdrawals', '2026-05-29 17:06:59'),
(6, 'audit', 'Auditor', 'View audit logs and compliance reports', '2026-05-29 17:06:59'),
(7, 'viewer', 'Viewer', 'Read-only access', '2026-05-29 17:06:59');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int NOT NULL,
  `permission_id` int NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `savings_accounts`
--

DROP TABLE IF EXISTS `savings_accounts`;
CREATE TABLE IF NOT EXISTS `savings_accounts` (
  `account_id` int NOT NULL AUTO_INCREMENT,
  `member_id` int NOT NULL,
  `account_type` enum('monthly_savings','share_capital','voluntary','fixed_deposit') NOT NULL,
  `account_number` varchar(20) NOT NULL,
  `balance` decimal(12,2) DEFAULT '0.00',
  `interest_rate` decimal(5,2) DEFAULT '0.00',
  `opening_balance` decimal(12,2) DEFAULT '0.00',
  `status` enum('active','dormant','closed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `account_number` (`account_number`),
  KEY `idx_member` (`member_id`),
  KEY `idx_account_number` (`account_number`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `savings_accounts`
--

INSERT INTO `savings_accounts` (`account_id`, `member_id`, `account_type`, `account_number`, `balance`, `interest_rate`, `opening_balance`, `status`, `created_at`) VALUES
(1, 1, 'fixed_deposit', 'SV000001', '160000.00', '0.00', '100000.00', 'active', '2026-05-26 01:09:24'),
(2, 2, 'monthly_savings', 'SV000002', '250000.00', '0.00', '250000.00', 'active', '2026-05-26 11:45:46'),
(3, 2, 'monthly_savings', 'SV000003', '850000.00', '0.00', '900000.00', 'active', '2026-05-26 11:55:30'),
(4, 3, 'monthly_savings', 'SV000004', '550000.00', '0.00', '50000.00', 'active', '2026-05-27 15:17:58'),
(5, 3, 'monthly_savings', 'SV000005', '830000.00', '0.00', '850000.00', 'active', '2026-05-27 16:01:40'),
(6, 4, 'voluntary', 'SV000006', '95000.00', '0.00', '20000.00', 'active', '2026-05-28 09:27:42'),
(7, 5, 'monthly_savings', 'SV000007', '120000.00', '0.00', '20000.00', 'active', '2026-05-28 12:12:27');

-- --------------------------------------------------------

--
-- Table structure for table `savings_transactions`
--

DROP TABLE IF EXISTS `savings_transactions`;
CREATE TABLE IF NOT EXISTS `savings_transactions` (
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
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `savings_transactions`
--

INSERT INTO `savings_transactions` (`trans_id`, `account_id`, `transaction_type`, `amount`, `balance_after`, `payment_method`, `reference_no`, `receipt_no`, `description`, `posted_by`, `approved_by`, `status`, `transaction_date`) VALUES
(1, 1, 'deposit', '100000.00', '100000.00', 'cash', '', 'DEP20260526040924791', 'Initial savings account opening deposit', 1, NULL, 'completed', '2026-05-26 04:09:24'),
(2, 1, 'deposit', '60000.00', '160000.00', 'cash', 'try', 'DEP20260526041016733', '', 1, NULL, 'completed', '2026-05-26 04:10:16'),
(3, 2, 'deposit', '250000.00', '250000.00', 'cash', '', 'DEP20260526144546841', 'Initial savings account opening deposit', 1, NULL, 'completed', '2026-05-26 14:45:46'),
(4, 3, 'deposit', '900000.00', '900000.00', 'cash', '', 'DEP20260526145530917', 'Initial savings account opening deposit', 1, NULL, 'completed', '2026-05-26 14:55:30'),
(5, 3, 'withdrawal', '50000.00', '850000.00', 'mobile_money', 'made', 'WTH20260526150039742', NULL, 1, NULL, 'completed', '2026-05-26 15:00:39'),
(6, 4, 'deposit', '50000.00', '50000.00', 'cash', '', 'DEP20260527181758971', 'Initial savings account opening deposit', 1, NULL, 'completed', '2026-05-27 18:17:58'),
(7, 5, 'deposit', '850000.00', '850000.00', 'cash', '', 'DEP20260527190140541', 'Initial savings account opening deposit', 1, NULL, 'completed', '2026-05-27 19:01:40'),
(8, 4, 'deposit', '750000.00', '800000.00', 'cash', '', 'DEP20260527191549509', '', 1, NULL, 'completed', '2026-05-27 19:15:49'),
(9, 4, 'withdrawal', '200000.00', '600000.00', 'cash', '', 'WTH20260527191623632', NULL, 1, NULL, 'completed', '2026-05-27 19:16:24'),
(10, 6, 'deposit', '20000.00', '20000.00', 'cash', '', 'DEP20260528122742133', 'Initial savings account opening deposit', 1, NULL, 'completed', '2026-05-28 12:27:43'),
(11, 6, 'deposit', '100000.00', '120000.00', 'bank_transfer', '', 'DEP20260528123157977', '', 1, NULL, 'completed', '2026-05-28 12:31:57'),
(12, 6, 'withdrawal', '25000.00', '95000.00', 'bank_transfer', '', 'WTH20260528123525973', NULL, 1, NULL, 'completed', '2026-05-28 12:35:25'),
(13, 7, 'deposit', '20000.00', '20000.00', 'bank_transfer', '', 'DEP20260528151227929', 'Initial savings account opening deposit', 1, NULL, 'completed', '2026-05-28 15:12:27'),
(14, 7, 'deposit', '150000.00', '170000.00', 'bank_transfer', '', 'DEP20260528151824626', '', 1, NULL, 'completed', '2026-05-28 15:18:24'),
(15, 7, 'withdrawal', '50000.00', '120000.00', 'bank_transfer', '', 'WTH20260528152121844', NULL, 1, NULL, 'completed', '2026-05-28 15:21:21'),
(16, 4, 'transfer_out', '50000.00', '550000.00', '', 'SHR-3-1780060633', 'SP20260529161713357', 'Share purchase from savings account', 1, NULL, 'completed', '2026-05-29 16:17:14'),
(22, 5, 'transfer_out', '20000.00', '830000.00', 'internal', 'SHR-3-1780076050', 'SP20260529203410997', 'Share purchase from savings account', 1, NULL, 'completed', '2026-05-29 20:34:10');

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`id`, `filename`, `applied_at`) VALUES
(1, '001_create_sessions_and_standing_orders.sql', '2026-05-18 19:58:57'),
(2, '002_roles_permissions_settings.sql', '2026-05-29 17:07:01'),
(3, '003_add_auth_columns_to_users.sql', '2026-05-29 17:10:45'),
(4, '004_member_authentication.sql', '2026-05-29 17:28:14'),
(5, '005_member_shares.sql', '2026-05-29 17:30:19'),
(6, '006_shares_transfers_and_ledger.sql', '2026-05-29 17:31:10'),
(7, '007_member_share_sell_support.sql', '2026-05-29 17:39:25');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
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

-- --------------------------------------------------------

--
-- Table structure for table `sms_queue`
--

DROP TABLE IF EXISTS `sms_queue`;
CREATE TABLE IF NOT EXISTS `sms_queue` (
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

-- --------------------------------------------------------

--
-- Table structure for table `standing_orders`
--

DROP TABLE IF EXISTS `standing_orders`;
CREATE TABLE IF NOT EXISTS `standing_orders` (
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

-- --------------------------------------------------------

--
-- Table structure for table `standing_order_runs`
--

DROP TABLE IF EXISTS `standing_order_runs`;
CREATE TABLE IF NOT EXISTS `standing_order_runs` (
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

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `group` varchar(50) DEFAULT 'general',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role`, `branch_id`, `status`, `last_login`, `created_at`, `two_factor_enabled`, `two_factor_method`, `two_factor_code`, `two_factor_expires`, `login_attempts`, `locked_until`, `last_failed_login`, `password_changed_at`, `last_login_ip`, `must_change_password`, `password_expires_at`, `is_member`, `linked_member_id`) VALUES
(1, 'admin', '$2y$10$1YYpZS4uVjmDb0nMThtnxOtYrbLEO6i6lJl01VXQ51NXzhn4uxu1G', 'System Administrator', 'admin@sacco.local', NULL, 'admin', NULL, 'active', '2026-06-11 15:32:26', '2026-05-14 13:32:46', 0, 'sms', NULL, NULL, 0, NULL, NULL, NULL, '::1', 0, NULL, 0, NULL),
(2, 'james', '2', 'System Administrator', NULL, NULL, 'admin', NULL, 'active', NULL, '2026-05-19 09:57:24', 0, 'sms', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL),
(3, 'peter', '2', 'System Administrator', NULL, NULL, 'admin', NULL, 'active', NULL, '2026-05-19 11:41:42', 0, 'sms', NULL, NULL, 0, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

DROP TABLE IF EXISTS `user_roles`;
CREATE TABLE IF NOT EXISTS `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_5` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_6` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `members_ibfk_7` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
