-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Mar 08, 2026 at 01:12 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `final_project1.0`
--

-- --------------------------------------------------------

--
-- Table structure for table `authors`
--

CREATE TABLE `authors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `authors`
--

INSERT INTO `authors` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Unknown Author', '2025-11-20 05:03:43', '2025-11-20 05:03:43'),
(2, 'Ruth A. Roth', '2025-11-20 06:01:47', '2025-11-20 06:01:47'),
(3, 'Kenn Makr', '2025-11-20 06:04:45', '2025-11-20 06:04:45'),
(4, 'John Smith', '2025-11-20 18:23:51', '2025-11-20 18:23:51'),
(5, 'Jhon Kent', '2025-11-25 20:44:38', '2025-11-25 20:44:38'),
(6, 'Eddie Mclaney', '2025-11-25 20:50:35', '2025-11-25 20:50:35'),
(7, 'titer', '2025-11-26 00:58:18', '2025-11-26 00:58:18'),
(8, 'Jane Doe', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(9, 'Robert Johnson', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(10, 'Sarah Wilson', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(11, 'Michael Brown', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(12, 'Emily Davis', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(13, 'Nell Dale', '2025-12-03 18:05:00', '2025-12-03 18:05:00'),
(14, 'Jason Gilmore', '2025-12-03 18:05:00', '2025-12-03 18:05:00'),
(15, 'Dr. Craig Hirst', '2025-12-03 18:05:00', '2025-12-03 18:05:00'),
(16, 'Sharon L. Lewis', '2025-12-14 00:01:14', '2025-12-14 00:01:14'),
(17, 'Ross', '2025-12-14 17:17:53', '2025-12-14 17:17:53');

-- --------------------------------------------------------

--
-- Table structure for table `authors_backup_before_normalization`
--

CREATE TABLE `authors_backup_before_normalization` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `authors_backup_before_normalization`
--

INSERT INTO `authors_backup_before_normalization` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Unknown Author', '2025-11-20 05:03:43', '2025-11-20 05:03:43'),
(2, 'Ruth A. Roth', '2025-11-20 06:01:47', '2025-11-20 06:01:47'),
(3, 'Kenn Makr', '2025-11-20 06:04:45', '2025-11-20 06:04:45'),
(4, 'John Smith', '2025-11-20 18:23:51', '2025-11-20 18:23:51'),
(5, 'Jhon Kent', '2025-11-25 20:44:38', '2025-11-25 20:44:38'),
(6, 'Eddie Mclaney', '2025-11-25 20:50:35', '2025-11-25 20:50:35'),
(7, 'titer', '2025-11-26 00:58:18', '2025-11-26 00:58:18'),
(8, 'Jane Doe', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(9, 'Robert Johnson', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(10, 'Sarah Wilson', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(11, 'Michael Brown', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(12, 'Emily Davis', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(13, 'Nell Dale', '2025-12-03 18:05:00', '2025-12-03 18:05:00'),
(14, 'Jason Gilmore', '2025-12-03 18:05:00', '2025-12-03 18:05:00'),
(15, 'Dr. Craig Hirst', '2025-12-03 18:05:00', '2025-12-03 18:05:00');

-- --------------------------------------------------------

--
-- Table structure for table `author_book`
--

CREATE TABLE `author_book` (
  `author_id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `author_book`
--

INSERT INTO `author_book` (`author_id`, `book_id`) VALUES
(1, 35),
(2, 2),
(6, 15),
(7, 15),
(13, 29),
(14, 30);

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `resource_type` varchar(20) NOT NULL DEFAULT 'book',
  `volume` varchar(50) DEFAULT NULL,
  `issue` varchar(50) DEFAULT NULL,
  `advisor` varchar(255) DEFAULT NULL,
  `defense_date` date DEFAULT NULL,
  `degree` varchar(100) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `pdf_file` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_checksum` varchar(64) DEFAULT NULL,
  `epub_file` varchar(255) DEFAULT NULL,
  `doc_file` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `availability_status` enum('available','borrowed','reserved','maintenance') NOT NULL DEFAULT 'available',
  `course` varchar(255) DEFAULT NULL,
  `program` varchar(50) DEFAULT NULL,
  `year_level` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 0.0,
  `language` varchar(255) NOT NULL DEFAULT 'English',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `publisher_id` bigint(20) UNSIGNED DEFAULT NULL,
  `published_year` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`id`, `title`, `resource_type`, `volume`, `issue`, `advisor`, `defense_date`, `degree`, `author`, `isbn`, `description`, `cover_photo`, `cover_image`, `pdf_file`, `file_path`, `file_checksum`, `epub_file`, `doc_file`, `file_type`, `content`, `availability_status`, `course`, `program`, `year_level`, `rating`, `language`, `created_at`, `updated_at`, `publisher_id`, `published_year`, `year`) VALUES
(2, 'Nutrition and Diet Theraphy', 'book', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Nutrition & Diet Therapy, 10th edition, provides sound nutritional information based upon fact.', 'storage/uploads/book-covers/1763647307_691f1f4b80f18.png', NULL, 'storage/books/pdfs/1763647307_691f1f4b823ed.pdf', NULL, NULL, NULL, NULL, 'pdf', NULL, 'available', 'Computer Science', NULL, NULL, 0.0, 'English', '2025-11-20 06:01:47', '2025-12-17 19:02:48', 2, NULL, NULL),
(15, 'Business Finance(Theory and Practice)', 'book', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Business Finance practices', 'storage/uploads/book-covers/1764132635_6926871b90d53.png', NULL, 'storage/books/pdfs/1764132635_6926871b919d1.pdf', NULL, NULL, NULL, NULL, 'pdf', NULL, 'available', 'BSBA', NULL, NULL, 0.0, 'English', '2025-11-25 20:50:35', '2025-11-27 14:05:43', 4, NULL, NULL),
(29, 'Object-Oriented Data Structures Using Java', 'book', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Object-Oriented Data Structures using JAVA', 'storage/uploads/book-covers/1764299869_6929145d5253f.png', NULL, 'storage/books/pdfs/1764299869_6929145d53fae.pdf', NULL, NULL, NULL, NULL, 'pdf', NULL, 'available', 'BSIT', NULL, NULL, 0.0, 'English', '2025-11-27 19:17:49', '2025-11-27 19:17:49', 1, NULL, NULL),
(30, 'Beginning PHP and MySQL, 4th Edition', 'book', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Learn how to build dynamic, database-driven web sites using two of the world’s most popular open source technologies', 'storage/uploads/book-covers/1764300202_692915aae2d4e.png', NULL, 'storage/books/pdfs/1764300202_692915aae3a74.pdf', NULL, NULL, NULL, NULL, 'pdf', NULL, 'available', 'BSIT', NULL, NULL, 0.0, 'English', '2025-11-27 19:23:22', '2025-11-27 19:23:22', 12, NULL, NULL),
(35, 'Surviving the Wild Outdoor', 'book', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Surviving Outdoors', 'storage/uploads/book-covers/1766064839_694402c752042.png', NULL, 'storage/books/pdfs/1766064839_694402c752ff3.pdf', NULL, NULL, NULL, NULL, NULL, NULL, 'available', NULL, NULL, NULL, 0.0, 'English', '2025-12-18 05:33:59', '2025-12-18 05:33:59', 1, 2021, NULL),
(36, 'Marketing in Food, Hospitality, Tourism, and Food-Goodfellow Publishers 2015- BSTourism', 'book', NULL, NULL, NULL, NULL, NULL, 'Unknown Author', NULL, NULL, 'covers/default-book.png', NULL, NULL, 'books/1772677442_Marketing_in_Food__Hospitality__Tourism__and_Food-Goodfellow_Publishers_2015-_BSTourism.pdf', NULL, NULL, NULL, NULL, NULL, 'available', NULL, 'General', NULL, 0.0, 'English', '2026-03-04 18:24:02', '2026-03-04 18:24:02', NULL, 2026, NULL),
(37, '1de-surviving-the-wild-outdoors-', 'book', NULL, NULL, NULL, NULL, NULL, 'Unknown Author', NULL, NULL, 'covers/default-book.png', 'covers/default-book.png', 'ebooks/1772941593_1de-surviving-the-wild-outdoors-.pdf', 'ebooks/1772941593_1de-surviving-the-wild-outdoors-.pdf', NULL, NULL, NULL, NULL, NULL, 'available', NULL, 'General', NULL, 0.0, 'English', '2026-03-07 19:46:35', '2026-03-07 19:46:35', NULL, 2026, NULL),
(38, 'Business Finance Theory and Practice by Eddie McLaney (z-lib.org)', 'book', NULL, NULL, NULL, NULL, NULL, 'Unknown Author', NULL, NULL, 'covers/default-book.png', 'covers/default-book.png', 'ebooks/1772941595_Business_Finance_Theory_and_Practice_by_Eddie_McLaney__z-lib_org_.pdf', 'ebooks/1772941595_Business_Finance_Theory_and_Practice_by_Eddie_McLaney__z-lib_org_.pdf', NULL, NULL, NULL, NULL, NULL, 'available', NULL, 'General', NULL, 0.0, 'English', '2026-03-07 19:46:35', '2026-03-07 19:46:35', NULL, 2026, NULL),
(39, 'Dale', 'book', NULL, NULL, NULL, NULL, NULL, 'Object-Oriented Data Structures Using Java (2016, Jones & Bartlett Learning)', NULL, NULL, 'covers/default-book.png', 'covers/default-book.png', 'ebooks/1772941595_Dale.pdf', 'ebooks/1772941595_Dale.pdf', NULL, NULL, NULL, NULL, NULL, 'available', NULL, 'General', NULL, 0.0, 'English', '2026-03-07 19:46:35', '2026-03-07 19:46:35', NULL, 2026, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `books_backup_before_normalization`
--

CREATE TABLE `books_backup_before_normalization` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_photo` varchar(255) DEFAULT NULL,
  `pdf_file` varchar(255) DEFAULT NULL,
  `epub_file` varchar(255) DEFAULT NULL,
  `doc_file` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `availability_status` enum('available','borrowed','reserved','maintenance') NOT NULL DEFAULT 'available',
  `course` varchar(255) DEFAULT NULL,
  `year_level` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 0.0,
  `language` varchar(255) NOT NULL DEFAULT 'English',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `publisher_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books_backup_before_normalization`
--

INSERT INTO `books_backup_before_normalization` (`id`, `title`, `description`, `cover_photo`, `pdf_file`, `epub_file`, `doc_file`, `file_type`, `content`, `availability_status`, `course`, `year_level`, `rating`, `language`, `created_at`, `updated_at`, `publisher_id`) VALUES
(2, 'Nutrition and Diet Theraphy', 'Nutrition & Diet Therapy, 10th edition, provides sound nutritional information based upon fact.', 'uploads/book-covers/1763647307_691f1f4b80f18.png', 'storage/books/pdfs/1763647307_691f1f4b823ed.pdf', NULL, NULL, 'pdf', NULL, 'available', 'BSN', NULL, 0.0, 'English', '2025-11-20 06:01:47', '2025-11-30 04:59:57', 2),
(3, 'Modern Physics', 'Modern Physics', 'uploads/book-covers/1763647485_691f1ffded520.png', 'storage/books/pdfs/1763647485_691f1ffdee991.pdf', NULL, NULL, 'pdf', NULL, 'available', NULL, NULL, 0.0, 'English', '2025-11-20 06:04:46', '2025-11-30 05:18:29', 11),
(14, 'Surviving The Wild Outdoors', 'Wild outdoor guides', 'uploads/book-covers/1764132278_692685b62402d.png', 'storage/books/pdfs/1764132278_692685b625de2.pdf', NULL, NULL, 'pdf', NULL, 'available', NULL, '1st Year', 0.0, 'English', '2025-11-25 20:44:38', '2025-11-25 20:44:38', 1),
(15, 'Business Finance(Theory and Practice)', 'Business Finance practices', 'uploads/book-covers/1764132635_6926871b90d53.png', 'storage/books/pdfs/1764132635_6926871b919d1.pdf', NULL, NULL, 'pdf', NULL, 'available', 'BSBA', NULL, 0.0, 'English', '2025-11-25 20:50:35', '2025-11-27 14:05:43', 4),
(29, 'Object-Oriented Data Structures Using Java', 'Object-Oriented Data Structures using JAVA', 'uploads/book-covers/1764299869_6929145d5253f.png', 'storage/books/pdfs/1764299869_6929145d53fae.pdf', NULL, NULL, 'pdf', NULL, 'available', 'BSIT', NULL, 0.0, 'English', '2025-11-27 19:17:49', '2025-11-27 19:17:49', 1),
(30, 'Beginning PHP and MySQL, 4th Edition', 'Learn how to build dynamic, database-driven web sites using two of the world’s most popular open source technologies', 'uploads/book-covers/1764300202_692915aae2d4e.png', 'storage/books/pdfs/1764300202_692915aae3a74.pdf', NULL, NULL, 'pdf', NULL, 'available', 'BSIT', NULL, 0.0, 'English', '2025-11-27 19:23:22', '2025-11-27 19:23:22', 12),
(31, 'Marketing in Food, Hospitality, Tourism, and Food', 'This book provides a critical introduction to customer-based marketing within the sector, and the structure and content of the chapters \r\nwill take you on an academic journey that builds towards a manifesto for the effective marketing of tourism, hospitality, events and food', 'uploads/book-covers/1764302025_69291cc9db151.png', 'storage/books/pdfs/1764302025_69291cc9dbedb.pdf', NULL, NULL, 'pdf', NULL, 'available', 'BSBA', NULL, 0.0, 'English', '2025-11-27 19:53:45', '2025-11-27 19:53:45', 13);

-- --------------------------------------------------------

--
-- Table structure for table `book_category`
--

CREATE TABLE `book_category` (
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `category_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `book_category`
--

INSERT INTO `book_category` (`book_id`, `category_id`) VALUES
(2, 1),
(15, 5),
(29, 6),
(30, 6),
(35, 4);

-- --------------------------------------------------------

--
-- Table structure for table `borrow_records`
--

CREATE TABLE `borrow_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `borrowed_date` datetime NOT NULL,
  `due_date` datetime NOT NULL,
  `returned_date` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
  `borrowing_duration` int(11) DEFAULT NULL,
  `renewal_count` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrow_records`
--

INSERT INTO `borrow_records` (`id`, `user_id`, `book_id`, `borrowed_date`, `due_date`, `returned_date`, `status`, `borrowing_duration`, `renewal_count`, `notes`, `created_at`, `updated_at`) VALUES
(19, 11, 2, '2025-11-20 14:05:33', '2025-11-25 14:05:33', '2025-11-25 07:59:00', 'returned', 5, 0, NULL, '2025-11-20 06:05:33', '2025-11-24 23:59:00'),
(21, 11, 2, '2025-11-25 07:56:16', '2025-11-30 07:56:16', '2025-11-25 08:00:53', 'returned', 5, 0, NULL, '2025-11-24 23:56:16', '2025-11-25 00:00:53'),
(22, 11, 2, '2025-11-25 07:56:16', '2025-11-30 07:56:16', '2025-11-25 07:59:06', 'returned', 5, 0, NULL, '2025-11-24 23:56:16', '2025-11-24 23:59:06'),
(25, 11, 2, '2025-11-25 08:01:13', '2025-11-30 08:01:13', '2025-11-25 14:38:52', 'returned', 5, 0, NULL, '2025-11-25 00:01:13', '2025-11-25 06:38:52'),
(26, 11, 2, '2025-11-25 08:01:14', '2025-11-30 08:01:14', '2025-11-25 08:07:31', 'returned', 5, 0, NULL, '2025-11-25 00:01:14', '2025-11-25 00:07:31'),
(27, 11, 2, '2025-11-25 08:08:04', '2025-11-30 08:08:04', '2025-11-25 13:59:49', 'returned', 5, 0, NULL, '2025-11-25 00:08:04', '2025-11-25 05:59:49'),
(28, 15, 2, '2025-11-25 13:35:51', '2025-11-30 13:35:51', '2025-12-09 08:02:11', 'returned', 5, 0, 'Auto-returned after 5-day limit', '2025-11-25 05:35:51', '2025-12-09 00:02:11'),
(30, 11, 2, '2025-11-25 14:39:16', '2025-11-30 14:39:16', '2025-11-25 14:39:38', 'returned', 5, 0, NULL, '2025-11-25 06:39:16', '2025-11-25 06:39:38'),
(31, 11, 2, '2025-11-25 14:39:57', '2025-11-30 14:39:57', '2025-11-27 08:14:59', 'returned', 5, 0, NULL, '2025-11-25 06:39:57', '2025-11-27 00:14:59'),
(32, 11, 2, '2025-11-26 01:10:21', '2025-12-01 01:10:21', '2025-11-26 01:11:55', 'returned', 5, 0, NULL, '2025-11-25 17:10:21', '2025-11-25 17:11:55'),
(33, 11, 15, '2025-11-26 08:47:10', '2025-12-10 08:53:08', '2025-11-26 22:54:56', 'returned', 5, 1, NULL, '2025-11-26 00:47:10', '2025-11-26 14:54:56'),
(34, 11, 30, '2025-11-28 07:24:07', '2025-12-03 07:24:07', '2025-12-01 02:24:44', 'returned', 5, 0, NULL, '2025-11-27 23:24:07', '2025-11-30 18:24:44'),
(35, 11, 2, '2025-11-30 14:04:15', '2025-12-05 14:04:15', '2025-11-30 14:05:02', 'returned', 5, 0, NULL, '2025-11-30 06:04:15', '2025-11-30 06:05:02'),
(36, 11, 2, '2025-11-30 14:04:34', '2025-12-05 14:04:34', '2025-11-30 14:05:13', 'returned', 5, 0, NULL, '2025-11-30 06:04:34', '2025-11-30 06:05:13'),
(37, 11, 2, '2025-11-30 14:17:18', '2025-12-05 14:17:18', '2025-12-05 13:36:27', 'returned', 5, 0, NULL, '2025-11-30 06:17:18', '2025-12-05 05:36:27'),
(39, 17, 30, '2025-12-01 03:03:22', '2025-12-06 03:03:22', '2025-12-09 08:02:11', 'returned', 5, 0, 'Auto-returned after 5-day limit', '2025-11-30 19:03:22', '2025-12-09 00:02:11'),
(40, 17, 29, '2025-12-01 03:04:21', '2025-12-06 03:04:21', '2025-12-01 03:05:07', 'returned', 5, 0, NULL, '2025-11-30 19:04:21', '2025-11-30 19:05:07'),
(41, 18, 30, '2025-12-01 03:09:16', '2025-12-06 03:09:16', '2025-12-09 08:02:11', 'returned', 5, 0, 'Auto-returned after 5-day limit', '2025-11-30 19:09:16', '2025-12-09 00:02:11'),
(42, 17, 29, '2025-12-01 05:36:18', '2025-12-06 05:36:18', '2025-12-09 08:02:11', 'returned', 5, 0, 'Auto-returned after 5-day limit', '2025-11-30 21:36:18', '2025-12-09 00:02:11'),
(44, 11, 29, '2025-12-05 13:30:33', '2025-12-10 13:30:33', '2025-12-14 08:33:55', 'returned', 5, 0, 'Auto-returned after 5-day limit', '2025-12-05 05:30:33', '2025-12-14 00:33:55'),
(45, 11, 30, '2025-12-05 13:33:19', '2025-12-10 13:33:19', '2025-12-14 08:33:55', 'returned', 5, 0, 'Auto-returned after 5-day limit', '2025-12-05 05:33:19', '2025-12-14 00:33:55'),
(46, 9, 29, '2025-12-09 07:43:50', '2025-12-14 07:43:50', '2025-12-14 08:33:55', 'returned', 5, 0, 'Auto-returned after 5-day limit', '2025-12-08 23:43:50', '2025-12-14 00:33:55'),
(52, 11, 2, '2025-12-15 02:39:48', '2025-12-20 02:39:48', '2025-12-15 06:25:28', 'returned', 5, 0, NULL, '2025-12-14 18:39:48', '2025-12-14 22:25:28'),
(53, 11, 30, '2025-12-15 06:24:58', '2025-12-20 06:24:58', '2025-12-15 06:25:18', 'returned', 5, 0, NULL, '2025-12-14 22:24:58', '2025-12-14 22:25:18'),
(54, 11, 2, '2025-12-15 06:33:23', '2025-12-20 06:33:23', '2025-12-15 06:34:18', 'returned', 5, 0, NULL, '2025-12-14 22:33:23', '2025-12-14 22:34:18'),
(55, 11, 2, '2025-12-15 06:34:26', '2025-12-20 06:34:26', '2025-12-15 06:35:24', 'returned', 5, 0, NULL, '2025-12-14 22:34:26', '2025-12-14 22:35:24'),
(56, 11, 2, '2025-12-15 06:35:31', '2025-12-16 06:35:31', '2025-12-18 02:32:02', 'returned', 1, 0, 'Auto-returned after due date', '2025-12-14 22:35:31', '2025-12-17 18:32:02'),
(57, 11, 29, '2025-12-15 06:35:51', '2025-12-16 06:35:51', '2025-12-18 02:32:02', 'returned', 1, 0, 'Auto-returned after due date', '2025-12-14 22:35:51', '2025-12-17 18:32:02'),
(61, 11, 35, '2025-12-18 13:42:22', '2026-01-01 13:42:22', '2025-12-18 14:13:56', 'returned', NULL, 0, NULL, '2025-12-18 05:42:22', '2025-12-18 06:13:56'),
(62, 11, 35, '2025-12-18 14:19:42', '2025-12-19 14:19:42', '2025-12-25 14:06:49', 'returned', NULL, 0, 'Auto-returned after due date', '2025-12-18 06:19:42', '2025-12-25 06:06:49'),
(63, 11, 35, '2025-12-25 14:36:06', '2025-12-26 14:36:06', '2025-12-25 14:36:32', 'returned', NULL, 0, NULL, '2025-12-25 06:36:06', '2025-12-25 06:36:32'),
(64, 11, 35, '2025-12-27 08:50:09', '2025-12-28 08:50:09', '2026-01-07 08:03:53', 'returned', NULL, 0, 'Auto-returned after due date', '2025-12-27 00:50:09', '2026-01-07 00:03:53'),
(65, 11, 35, '2026-01-07 08:06:07', '2026-01-08 08:06:07', '2026-01-08 22:13:30', 'returned', NULL, 0, 'Auto-returned after due date', '2026-01-07 00:06:07', '2026-01-08 14:13:30'),
(66, 11, 35, '2026-01-08 22:14:12', '2026-01-09 22:14:12', '2026-01-17 04:37:25', 'returned', NULL, 0, 'Auto-returned after due date', '2026-01-08 14:14:12', '2026-01-16 20:37:25'),
(67, 11, 30, '2026-02-23 03:12:17', '2026-02-24 03:12:17', '2026-03-01 05:43:12', 'returned', NULL, 0, 'Auto-returned after due date', '2026-02-22 19:12:17', '2026-02-28 21:43:12'),
(70, 11, 35, '2026-03-05 01:24:01', '2026-03-06 01:24:01', '2026-03-06 02:05:17', 'returned', NULL, 0, 'Auto-returned after due date', '2026-03-04 17:24:01', '2026-03-05 18:05:17'),
(71, 11, 36, '2026-03-06 03:52:06', '2026-03-07 03:52:06', '2026-03-08 02:29:10', 'returned', NULL, 0, 'Auto-returned after due date', '2026-03-05 19:52:06', '2026-03-07 18:29:10'),
(72, 11, 30, '2026-03-06 06:32:51', '2026-03-07 06:32:51', '2026-03-08 02:29:10', 'returned', NULL, 0, 'Auto-returned after due date', '2026-03-05 22:32:51', '2026-03-07 18:29:10');

-- --------------------------------------------------------

--
-- Table structure for table `borrow_records_backup_before_normalization`
--

CREATE TABLE `borrow_records_backup_before_normalization` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `borrowed_date` datetime NOT NULL,
  `due_date` datetime NOT NULL,
  `returned_date` datetime DEFAULT NULL,
  `status` enum('borrowed','returned','overdue') NOT NULL DEFAULT 'borrowed',
  `borrowing_duration` int(11) DEFAULT NULL,
  `renewal_count` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `borrow_records_backup_before_normalization`
--

INSERT INTO `borrow_records_backup_before_normalization` (`id`, `user_id`, `book_id`, `borrowed_date`, `due_date`, `returned_date`, `status`, `borrowing_duration`, `renewal_count`, `notes`, `created_at`, `updated_at`) VALUES
(19, 11, 2, '2025-11-20 14:05:33', '2025-11-25 14:05:33', '2025-11-25 07:59:00', 'returned', 5, 0, NULL, '2025-11-20 06:05:33', '2025-11-24 23:59:00'),
(20, 11, 3, '2025-11-21 05:03:51', '2025-11-26 05:03:51', '2025-11-22 07:08:45', 'returned', 5, 0, NULL, '2025-11-20 21:03:51', '2025-11-21 23:08:45'),
(21, 11, 2, '2025-11-25 07:56:16', '2025-11-30 07:56:16', '2025-11-25 08:00:53', 'returned', 5, 0, NULL, '2025-11-24 23:56:16', '2025-11-25 00:00:53'),
(22, 11, 2, '2025-11-25 07:56:16', '2025-11-30 07:56:16', '2025-11-25 07:59:06', 'returned', 5, 0, NULL, '2025-11-24 23:56:16', '2025-11-24 23:59:06'),
(23, 11, 3, '2025-11-25 07:58:11', '2025-11-30 07:58:11', '2025-11-25 07:59:11', 'returned', 5, 0, NULL, '2025-11-24 23:58:11', '2025-11-24 23:59:11'),
(24, 11, 3, '2025-11-25 07:58:12', '2025-11-30 07:58:12', '2025-11-25 08:07:46', 'returned', 5, 0, NULL, '2025-11-24 23:58:12', '2025-11-25 00:07:46'),
(25, 11, 2, '2025-11-25 08:01:13', '2025-11-30 08:01:13', '2025-11-25 14:38:52', 'returned', 5, 0, NULL, '2025-11-25 00:01:13', '2025-11-25 06:38:52'),
(26, 11, 2, '2025-11-25 08:01:14', '2025-11-30 08:01:14', '2025-11-25 08:07:31', 'returned', 5, 0, NULL, '2025-11-25 00:01:14', '2025-11-25 00:07:31'),
(27, 11, 2, '2025-11-25 08:08:04', '2025-11-30 08:08:04', '2025-11-25 13:59:49', 'returned', 5, 0, NULL, '2025-11-25 00:08:04', '2025-11-25 05:59:49'),
(28, 15, 2, '2025-11-25 13:35:51', '2025-11-30 13:35:51', NULL, 'borrowed', 5, 0, NULL, '2025-11-25 05:35:51', '2025-11-25 05:35:51'),
(29, 11, 3, '2025-11-25 13:44:12', '2025-11-30 13:44:12', '2025-11-27 08:15:03', 'returned', 5, 0, NULL, '2025-11-25 05:44:12', '2025-11-27 00:15:03'),
(30, 11, 2, '2025-11-25 14:39:16', '2025-11-30 14:39:16', '2025-11-25 14:39:38', 'returned', 5, 0, NULL, '2025-11-25 06:39:16', '2025-11-25 06:39:38'),
(31, 11, 2, '2025-11-25 14:39:57', '2025-11-30 14:39:57', '2025-11-27 08:14:59', 'returned', 5, 0, NULL, '2025-11-25 06:39:57', '2025-11-27 00:14:59'),
(32, 11, 2, '2025-11-26 01:10:21', '2025-12-01 01:10:21', '2025-11-26 01:11:55', 'returned', 5, 0, NULL, '2025-11-25 17:10:21', '2025-11-25 17:11:55'),
(33, 11, 15, '2025-11-26 08:47:10', '2025-12-10 08:53:08', '2025-11-26 22:54:56', 'returned', 5, 1, NULL, '2025-11-26 00:47:10', '2025-11-26 14:54:56'),
(34, 11, 30, '2025-11-28 07:24:07', '2025-12-03 07:24:07', '2025-12-01 02:24:44', 'returned', 5, 0, NULL, '2025-11-27 23:24:07', '2025-11-30 18:24:44'),
(35, 11, 2, '2025-11-30 14:04:15', '2025-12-05 14:04:15', '2025-11-30 14:05:02', 'returned', 5, 0, NULL, '2025-11-30 06:04:15', '2025-11-30 06:05:02'),
(36, 11, 2, '2025-11-30 14:04:34', '2025-12-05 14:04:34', '2025-11-30 14:05:13', 'returned', 5, 0, NULL, '2025-11-30 06:04:34', '2025-11-30 06:05:13'),
(37, 11, 2, '2025-11-30 14:17:18', '2025-12-05 14:17:18', NULL, 'borrowed', 5, 0, NULL, '2025-11-30 06:17:18', '2025-11-30 06:17:18'),
(38, 11, 31, '2025-12-01 02:30:03', '2025-12-06 02:30:03', NULL, 'borrowed', 5, 0, NULL, '2025-11-30 18:30:03', '2025-11-30 18:30:03'),
(39, 17, 30, '2025-12-01 03:03:22', '2025-12-06 03:03:22', NULL, 'borrowed', 5, 0, NULL, '2025-11-30 19:03:22', '2025-11-30 19:03:22'),
(40, 17, 29, '2025-12-01 03:04:21', '2025-12-06 03:04:21', '2025-12-01 03:05:07', 'returned', 5, 0, NULL, '2025-11-30 19:04:21', '2025-11-30 19:05:07'),
(41, 18, 30, '2025-12-01 03:09:16', '2025-12-06 03:09:16', NULL, 'borrowed', 5, 0, NULL, '2025-11-30 19:09:16', '2025-11-30 19:09:16'),
(42, 17, 29, '2025-12-01 05:36:18', '2025-12-06 05:36:18', NULL, 'borrowed', 5, 0, NULL, '2025-11-30 21:36:18', '2025-11-30 21:36:18');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Health', 'health', '2025-11-20 06:01:47', '2025-11-20 06:01:47'),
(2, 'Science', 'science', '2025-11-20 06:04:46', '2025-11-20 06:04:46'),
(3, 'Programming', 'programming', '2025-11-20 18:23:51', '2025-11-20 18:23:51'),
(4, 'Arts', 'arts', '2025-11-25 20:44:38', '2025-11-25 20:44:38'),
(5, 'Business', 'business', '2025-11-25 20:50:35', '2025-11-25 20:50:35'),
(6, 'Technology', 'technology', '2025-11-26 15:02:31', '2025-11-26 15:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `categories_backup_before_normalization`
--

CREATE TABLE `categories_backup_before_normalization` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories_backup_before_normalization`
--

INSERT INTO `categories_backup_before_normalization` (`id`, `name`, `slug`, `created_at`, `updated_at`) VALUES
(1, 'Health', 'health', '2025-11-20 06:01:47', '2025-11-20 06:01:47'),
(2, 'Science', 'science', '2025-11-20 06:04:46', '2025-11-20 06:04:46'),
(3, 'Programming', 'programming', '2025-11-20 18:23:51', '2025-11-20 18:23:51'),
(4, 'Arts', 'arts', '2025-11-25 20:44:38', '2025-11-25 20:44:38'),
(5, 'Business', 'business', '2025-11-25 20:50:35', '2025-11-25 20:50:35'),
(6, 'Technology', 'technology', '2025-11-26 15:02:31', '2025-11-26 15:02:31');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `name`, `code`, `department`, `created_at`, `updated_at`) VALUES
(1, 'Computer Science', 'CS', NULL, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(2, 'Information Technology', 'IT', NULL, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(3, 'Engineering', 'ENG', NULL, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(4, 'Business Administration', 'BA', NULL, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(5, 'Accountancy', 'ACC', NULL, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(6, 'Education', 'ED', NULL, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(7, 'Nursing', 'NUR', NULL, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(8, 'BSIT', 'BSI', NULL, '2025-12-03 18:17:45', '2025-12-03 18:17:45'),
(9, 'BSN', 'BSN', NULL, '2025-12-03 18:17:45', '2025-12-03 18:17:45'),
(10, 'BSTM', 'BST', NULL, '2025-12-03 18:17:45', '2025-12-03 18:17:45'),
(11, 'BS Hospitality Management', 'BSHM', NULL, '2025-12-05 05:52:15', '2025-12-05 05:52:15'),
(12, 'BS Education', 'BSED', NULL, '2025-12-05 05:52:15', '2025-12-05 05:52:15'),
(13, 'BS Entrepreneurship', 'BSEntrep', NULL, '2025-12-05 05:52:15', '2025-12-05 05:52:15');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `borrow_record_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `paid_date` datetime DEFAULT NULL,
  `issued_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `genders`
--

CREATE TABLE `genders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `genders`
--

INSERT INTO `genders` (`id`, `name`, `abbreviation`, `created_at`, `updated_at`) VALUES
(1, 'Male', 'M', '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(2, 'Female', 'F', '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(3, 'Other', 'O', '2025-12-03 18:17:14', '2025-12-03 18:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_10_01_045857_add_role_and_library_id_to_users_table', 1),
(5, '2025_10_02_033814_add_student_fields_to_users_table', 1),
(6, '2025_10_02_042925_update_user_roles_staff_student_only', 1),
(7, '2025_10_02_070817_create_books_table', 1),
(8, '2025_10_02_070849_create_borrow_records_table', 1),
(9, '2025_10_02_070916_create_reservations_table', 1),
(10, '2025_10_02_071009_create_favorites_table', 1),
(11, '2025_10_02_071037_create_fines_table', 1),
(12, '2025_10_02_072605_add_renewal_count_to_borrow_records_table', 1),
(13, '2025_10_02_085802_add_borrowing_duration_to_borrow_records_table', 1),
(14, '2025_10_02_090234_create_user_suspensions_table', 1),
(15, '2025_10_02_102042_add_cover_photo_to_books_table', 1),
(16, '2025_10_03_131449_remove_isbn_and_copies_from_books_table', 1),
(17, '2025_10_03_134456_drop_user_suspensions_table', 1),
(18, '2025_10_03_160531_add_pdf_file_to_books_table', 1),
(19, '2025_10_03_163842_add_ebook_files_to_books_table', 1),
(54, '2025_10_05_113001_add_columns_to_books_table', 2),
(55, '2025_10_05_113002_add_columns_to_borrow_records_table', 2),
(56, '2025_10_05_113003_add_columns_to_users_table', 2),
(57, '2025_10_05_114001_create_authors_table', 2),
(58, '2025_10_05_114002_create_categories_table', 2),
(59, '2025_10_05_114003_create_publishers_table', 2),
(60, '2025_10_05_114004_add_publisher_id_to_books_table', 2),
(61, '2025_10_05_114005_create_author_book_table', 2),
(62, '2025_10_05_114006_create_book_category_table', 2),
(63, '2025_10_05_120515_drop_legacy_columns_from_books_table', 2),
(64, '2025_10_08_020749_fix_borrowing_duration_values', 2),
(65, '2025_10_12_052530_remove_pages_column_from_books_table', 2),
(66, '2025_10_12_055716_add_gender_to_users_table', 2),
(67, '2025_10_12_112034_create_notifications_table', 2),
(68, '2025_11_15_124719_add_preferences_to_users_table', 2),
(69, '2025_11_17_072908_add_issued_date_to_fines_table', 2),
(70, '2025_11_17_074822_remove_overdue_from_borrow_records_status_enum', 2),
(71, '2025_11_25_113339_cleanup_duplicate_books', 3),
(72, '2025_11_25_114116_aggressive_cleanup_duplicate_books', 4),
(75, '2025_11_27_220500_add_legacy_columns_back_to_books_table', 5),
(76, '2025_12_04_100000_normalize_books_table', 5),
(77, '2025_12_04_101000_create_user_lookup_tables', 5),
(78, '2025_12_04_102000_normalize_users_table', 6),
(79, '2025_12_04_022250_backup_complete_database_before_normalization', 7),
(80, '2025_12_14_000000_add_published_year_back_to_books_table', 8),
(81, '2025_12_25_142631_add_resource_type_and_specialized_fields_to_books_table', 9),
(82, '2026_01_01_000000_add_bulk_upload_columns_to_books_table', 10),
(83, '2026_01_15_000000_add_cover_image_to_books_table', 11),
(84, '2026_03_08_000000_add_file_checksum_to_books_table', 12);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publishers`
--

CREATE TABLE `publishers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `publishers`
--

INSERT INTO `publishers` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Unknown Publisher', '2025-11-20 05:03:43', '2025-11-20 05:03:43'),
(2, 'Ruth A. Ruth', '2025-11-20 06:01:47', '2025-11-20 06:01:47'),
(3, 'Ralph a', '2025-11-20 06:04:46', '2025-11-20 06:04:46'),
(4, 'Eddie Mclaney', '2025-11-25 20:50:35', '2025-11-25 20:50:35'),
(5, 'Tech Books Publishing', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(6, 'Computer Science Press', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(7, 'Data Publishing House', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(8, 'Web Tech Books', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(9, 'Network Publications', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(10, 'Engineering Books Ltd', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(11, 'Kenn Makr', '2025-12-03 18:05:01', '2025-12-03 18:05:01'),
(12, 'Paul Manning', '2025-12-03 18:05:01', '2025-12-03 18:05:01'),
(13, 'Goodfellow', '2025-12-03 18:05:01', '2025-12-03 18:05:01');

-- --------------------------------------------------------

--
-- Table structure for table `publishers_backup_before_normalization`
--

CREATE TABLE `publishers_backup_before_normalization` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `publishers_backup_before_normalization`
--

INSERT INTO `publishers_backup_before_normalization` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Unknown Publisher', '2025-11-20 05:03:43', '2025-11-20 05:03:43'),
(2, 'Ruth A. Ruth', '2025-11-20 06:01:47', '2025-11-20 06:01:47'),
(3, 'Ralph a', '2025-11-20 06:04:46', '2025-11-20 06:04:46'),
(4, 'Eddie Mclaney', '2025-11-25 20:50:35', '2025-11-25 20:50:35'),
(5, 'Tech Books Publishing', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(6, 'Computer Science Press', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(7, 'Data Publishing House', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(8, 'Web Tech Books', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(9, 'Network Publications', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(10, 'Engineering Books Ltd', '2025-11-26 15:02:31', '2025-11-26 15:02:31'),
(11, 'Kenn Makr', '2025-12-03 18:05:01', '2025-12-03 18:05:01'),
(12, 'Paul Manning', '2025-12-03 18:05:01', '2025-12-03 18:05:01'),
(13, 'Goodfellow', '2025-12-03 18:05:01', '2025-12-03 18:05:01');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `book_id` bigint(20) UNSIGNED NOT NULL,
  `reserved_date` datetime NOT NULL,
  `expiry_date` datetime NOT NULL,
  `status` enum('active','fulfilled','cancelled','expired') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'student', 'Student', 'Regular student user', '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(3, 'admin', 'Administrator', 'System administrator', '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(4, 'librarian', 'Librarian', 'Custom role created during migration', '2025-12-03 18:17:45', '2025-12-03 18:17:45');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `mi` varchar(255) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `gender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `library_id` varchar(255) DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `year_level_id` bigint(20) UNSIGNED DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `firstname`, `mi`, `gender`, `gender_id`, `lastname`, `library_id`, `course_id`, `year_level_id`, `role_id`, `email`, `email_verified_at`, `preferences`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(9, 'Mark Talawan', NULL, NULL, NULL, NULL, NULL, NULL, 8, 2, 4, 'mark123@gmail.com', '2025-11-20 04:41:11', NULL, '$2y$12$rSceQS8W5W3FW72Pj1/5u..Qoo.ZJEEBjBGliVpXCfLmFSE.3hhR.', 'HI1rWEOSWuL4JUwi1eObggJt2KE4DhNVXF3xcjKgp3gsw1vrN2Y6Xyu8LU0T', '2025-11-15 04:15:05', '2025-11-26 15:29:40'),
(11, 'Jhon T Alaw', 'Jhon', 'T', 'male', 1, 'Ark', '133721', 9, 2, 1, 'Jhon123@gmail.com', '2025-11-20 04:20:09', NULL, '$2y$12$Cc92hzJzdOlLDKtGkkKoSetMklNns2wtjaqY4x4XbbI.ov1BIl2rG', 'YgnZy6nUsL1O0fv0zBgEPHjNm7rNNuqPb4hqfti72ZNadbFYgVLi3lVldRoC', '2025-11-20 04:20:09', '2025-12-03 18:09:52'),
(14, 'Mark R Rad', 'Mark', 'R', 'male', 1, 'Rad', 'LIB003', 10, 3, 1, 'radores8623@gmail.com', '2025-11-20 23:51:35', NULL, '$2y$12$.lgfNTRf0KEAP2J12qZCruSRFpj8cGGs/tsDOjlS3kM8iP3scxAeK', NULL, '2025-11-20 23:51:35', '2025-11-20 23:51:35'),
(15, 'Sab K Rian', 'Sab', 'K', 'female', 2, 'Rian', '70870', 9, 1, 1, 'Sab15@gmail.com', '2025-11-26 01:05:57', NULL, '$2y$12$pBcjC.v8nvD/MO/a2FW0e.vhD.cYiC3Hlpay.nqnZkO7JEJyV0IDm', NULL, '2025-11-25 04:45:57', '2025-11-26 01:05:57'),
(17, 'Carlito Dequito', 'Carlito', NULL, 'female', 2, 'Dequito', '395942', 8, 4, 1, 'scatter123@gmail.com', '2025-11-30 19:01:35', NULL, '$2y$12$SyvLMkjpFEBseHB78/jhLOGECBvnFEOLLAnOHQeQ05tCQKHQM5hEe', 'eF9l3gTb5DSSKs953L9qKIm30NkuB5XJxbbHdje7LywFWkIVto2fKH1Rcjn5', '2025-11-30 19:01:35', '2025-11-30 19:02:26'),
(18, 'Rey R Rado', 'Rey', 'R', 'male', 1, 'Rado', '809013', 9, 2, 1, 'markrt08@gmail.com', '2025-11-30 19:04:50', NULL, '$2y$12$vLsOIevtn1mw0wLARVBxN.EvJBq6unFoS7klvBgGb7R3knOPdBIgq', NULL, '2025-11-30 19:04:50', '2025-11-30 19:04:50'),
(19, 'sadasdsa asdsadsad', 'sadasdsa', NULL, 'male', 1, 'asdsadsad', '12321', 8, 2, 1, 'scatter@gmail.com', '2026-01-07 00:25:22', NULL, '$2y$12$xkIw.ki8Ufkp1S2JWvEW4etwezM4.ZIgVksrvd.tJMI9td9wlF4le', NULL, '2025-11-30 21:16:43', '2026-01-07 00:25:22');

-- --------------------------------------------------------

--
-- Table structure for table `users_backup_before_normalization`
--

CREATE TABLE `users_backup_before_normalization` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `firstname` varchar(255) DEFAULT NULL,
  `mi` varchar(255) DEFAULT NULL,
  `gender` enum('male','female') DEFAULT NULL,
  `gender_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lastname` varchar(255) DEFAULT NULL,
  `library_id` varchar(255) DEFAULT NULL,
  `course_id` bigint(20) UNSIGNED DEFAULT NULL,
  `year_level_id` bigint(20) UNSIGNED DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)),
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users_backup_before_normalization`
--

INSERT INTO `users_backup_before_normalization` (`id`, `name`, `firstname`, `mi`, `gender`, `gender_id`, `lastname`, `library_id`, `course_id`, `year_level_id`, `role_id`, `email`, `email_verified_at`, `preferences`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(9, 'Mark Talawan', NULL, NULL, NULL, NULL, NULL, NULL, 8, 2, 4, 'mark123@gmail.com', '2025-11-20 04:41:11', NULL, '$2y$12$rSceQS8W5W3FW72Pj1/5u..Qoo.ZJEEBjBGliVpXCfLmFSE.3hhR.', 'HvCzFJ3mLCZLv16yXOcj27ePS2TyhmVQ43FBvsAWJJGVKuCfpNH4cbIpjvcd', '2025-11-15 04:15:05', '2025-11-26 15:29:40'),
(11, 'Jhon T Alaw', 'Jhon', 'T', 'male', 1, 'Ark', '133721', 9, 2, 1, 'Jhon123@gmail.com', '2025-11-20 04:20:09', NULL, '$2y$12$Cc92hzJzdOlLDKtGkkKoSetMklNns2wtjaqY4x4XbbI.ov1BIl2rG', 'HGSfMtaGNCgntObdzLfxTgCCXX4szP0R5a2dcFlDpptz8TozPx6t4Z09o7jm', '2025-11-20 04:20:09', '2025-12-03 18:09:52'),
(14, 'Mark R Rad', 'Mark', 'R', 'male', 1, 'Rad', 'LIB003', 10, 3, 1, 'radores8623@gmail.com', '2025-11-20 23:51:35', NULL, '$2y$12$.lgfNTRf0KEAP2J12qZCruSRFpj8cGGs/tsDOjlS3kM8iP3scxAeK', NULL, '2025-11-20 23:51:35', '2025-11-20 23:51:35'),
(15, 'Sab K Rian', 'Sab', 'K', 'female', 2, 'Rian', '70870', 9, 1, 1, 'Sab15@gmail.com', '2025-11-26 01:05:57', NULL, '$2y$12$pBcjC.v8nvD/MO/a2FW0e.vhD.cYiC3Hlpay.nqnZkO7JEJyV0IDm', NULL, '2025-11-25 04:45:57', '2025-11-26 01:05:57'),
(17, 'Carlito Dequito', 'Carlito', NULL, 'female', 2, 'Dequito', '395942', 8, 4, 1, 'scatter123@gmail.com', '2025-11-30 19:01:35', NULL, '$2y$12$SyvLMkjpFEBseHB78/jhLOGECBvnFEOLLAnOHQeQ05tCQKHQM5hEe', 'eF9l3gTb5DSSKs953L9qKIm30NkuB5XJxbbHdje7LywFWkIVto2fKH1Rcjn5', '2025-11-30 19:01:35', '2025-11-30 19:02:26'),
(18, 'Rey R Rado', 'Rey', 'R', 'male', 1, 'Rado', '809013', 9, 2, 1, 'markrt08@gmail.com', '2025-11-30 19:04:50', NULL, '$2y$12$vLsOIevtn1mw0wLARVBxN.EvJBq6unFoS7klvBgGb7R3knOPdBIgq', NULL, '2025-11-30 19:04:50', '2025-11-30 19:04:50'),
(19, 'sadasdsa asdsadsad', 'sadasdsa', NULL, 'male', 1, 'asdsadsad', '12321', 8, 2, 1, 'scatter@gmail.com', '2025-11-30 21:16:43', NULL, '$2y$12$xkIw.ki8Ufkp1S2JWvEW4etwezM4.ZIgVksrvd.tJMI9td9wlF4le', NULL, '2025-11-30 21:16:43', '2025-11-30 21:16:43');

-- --------------------------------------------------------

--
-- Table structure for table `year_levels`
--

CREATE TABLE `year_levels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `level` varchar(255) NOT NULL,
  `numeric_level` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `year_levels`
--

INSERT INTO `year_levels` (`id`, `level`, `numeric_level`, `created_at`, `updated_at`) VALUES
(1, '1st Year', 1, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(2, '2nd Year', 2, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(3, '3rd Year', 3, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(4, '4th Year', 4, '2025-12-03 18:17:14', '2025-12-03 18:17:14'),
(5, '5th Year', 5, '2025-12-03 18:17:14', '2025-12-03 18:17:14');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `authors`
--
ALTER TABLE `authors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `authors_name_unique` (`name`);

--
-- Indexes for table `authors_backup_before_normalization`
--
ALTER TABLE `authors_backup_before_normalization`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `authors_name_unique` (`name`);

--
-- Indexes for table `author_book`
--
ALTER TABLE `author_book`
  ADD PRIMARY KEY (`author_id`,`book_id`),
  ADD KEY `author_book_book_id_foreign` (`book_id`);

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `books_isbn_unique` (`isbn`),
  ADD UNIQUE KEY `books_file_checksum_unique` (`file_checksum`),
  ADD KEY `books_title_author_index` (`title`),
  ADD KEY `books_course_year_level_index` (`course`,`year_level`),
  ADD KEY `books_availability_status_index` (`availability_status`),
  ADD KEY `books_publisher_id_foreign` (`publisher_id`),
  ADD KEY `books_resource_type_index` (`resource_type`),
  ADD KEY `books_resource_type_published_year_index` (`resource_type`,`published_year`),
  ADD KEY `books_cover_image_index` (`cover_image`);

--
-- Indexes for table `books_backup_before_normalization`
--
ALTER TABLE `books_backup_before_normalization`
  ADD PRIMARY KEY (`id`),
  ADD KEY `books_title_author_index` (`title`),
  ADD KEY `books_course_year_level_index` (`course`,`year_level`),
  ADD KEY `books_availability_status_index` (`availability_status`),
  ADD KEY `books_publisher_id_foreign` (`publisher_id`);

--
-- Indexes for table `book_category`
--
ALTER TABLE `book_category`
  ADD PRIMARY KEY (`book_id`,`category_id`),
  ADD KEY `book_category_category_id_foreign` (`category_id`);

--
-- Indexes for table `borrow_records`
--
ALTER TABLE `borrow_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrow_records_user_id_status_index` (`user_id`,`status`),
  ADD KEY `borrow_records_book_id_status_index` (`book_id`,`status`),
  ADD KEY `borrow_records_due_date_index` (`due_date`);

--
-- Indexes for table `borrow_records_backup_before_normalization`
--
ALTER TABLE `borrow_records_backup_before_normalization`
  ADD PRIMARY KEY (`id`),
  ADD KEY `borrow_records_user_id_status_index` (`user_id`,`status`),
  ADD KEY `borrow_records_book_id_status_index` (`book_id`,`status`),
  ADD KEY `borrow_records_due_date_index` (`due_date`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_name_unique` (`name`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`);

--
-- Indexes for table `categories_backup_before_normalization`
--
ALTER TABLE `categories_backup_before_normalization`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_name_unique` (`name`),
  ADD UNIQUE KEY `categories_slug_unique` (`slug`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `courses_name_unique` (`name`),
  ADD UNIQUE KEY `courses_code_unique` (`code`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `favorites_user_id_book_id_unique` (`user_id`,`book_id`),
  ADD KEY `favorites_book_id_foreign` (`book_id`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fines_borrow_record_id_foreign` (`borrow_record_id`),
  ADD KEY `fines_user_id_status_index` (`user_id`,`status`),
  ADD KEY `fines_status_index` (`status`);

--
-- Indexes for table `genders`
--
ALTER TABLE `genders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `genders_name_unique` (`name`),
  ADD UNIQUE KEY `genders_abbreviation_unique` (`abbreviation`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notifications_user_id_is_read_index` (`user_id`,`is_read`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `publishers`
--
ALTER TABLE `publishers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publishers_name_unique` (`name`);

--
-- Indexes for table `publishers_backup_before_normalization`
--
ALTER TABLE `publishers_backup_before_normalization`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publishers_name_unique` (`name`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservations_user_id_status_index` (`user_id`,`status`),
  ADD KEY `reservations_book_id_status_index` (`book_id`,`status`),
  ADD KEY `reservations_expiry_date_index` (`expiry_date`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_unique` (`name`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_course_id_foreign` (`course_id`),
  ADD KEY `users_year_level_id_foreign` (`year_level_id`),
  ADD KEY `users_role_id_foreign` (`role_id`),
  ADD KEY `users_gender_id_foreign` (`gender_id`);

--
-- Indexes for table `users_backup_before_normalization`
--
ALTER TABLE `users_backup_before_normalization`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_course_id_foreign` (`course_id`),
  ADD KEY `users_year_level_id_foreign` (`year_level_id`),
  ADD KEY `users_role_id_foreign` (`role_id`),
  ADD KEY `users_gender_id_foreign` (`gender_id`);

--
-- Indexes for table `year_levels`
--
ALTER TABLE `year_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year_levels_level_unique` (`level`),
  ADD UNIQUE KEY `year_levels_numeric_level_unique` (`numeric_level`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `authors`
--
ALTER TABLE `authors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `authors_backup_before_normalization`
--
ALTER TABLE `authors_backup_before_normalization`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `books_backup_before_normalization`
--
ALTER TABLE `books_backup_before_normalization`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `borrow_records`
--
ALTER TABLE `borrow_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `borrow_records_backup_before_normalization`
--
ALTER TABLE `borrow_records_backup_before_normalization`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categories_backup_before_normalization`
--
ALTER TABLE `categories_backup_before_normalization`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `genders`
--
ALTER TABLE `genders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `publishers`
--
ALTER TABLE `publishers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `publishers_backup_before_normalization`
--
ALTER TABLE `publishers_backup_before_normalization`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users_backup_before_normalization`
--
ALTER TABLE `users_backup_before_normalization`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `year_levels`
--
ALTER TABLE `year_levels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `author_book`
--
ALTER TABLE `author_book`
  ADD CONSTRAINT `author_book_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `author_book_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `books`
--
ALTER TABLE `books`
  ADD CONSTRAINT `books_publisher_id_foreign` FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `book_category`
--
ALTER TABLE `book_category`
  ADD CONSTRAINT `book_category_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `book_category_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `borrow_records`
--
ALTER TABLE `borrow_records`
  ADD CONSTRAINT `borrow_records_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `borrow_records_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fines`
--
ALTER TABLE `fines`
  ADD CONSTRAINT `fines_borrow_record_id_foreign` FOREIGN KEY (`borrow_record_id`) REFERENCES `borrow_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fines_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_book_id_foreign` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_course_id_foreign` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`),
  ADD CONSTRAINT `users_gender_id_foreign` FOREIGN KEY (`gender_id`) REFERENCES `genders` (`id`),
  ADD CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_year_level_id_foreign` FOREIGN KEY (`year_level_id`) REFERENCES `year_levels` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
