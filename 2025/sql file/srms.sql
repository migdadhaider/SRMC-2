-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 06:40 PM
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
-- Database: `srms`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '$2y$10$6PQvHlK5rue9W9ElDI3y4OKr2lYIWVXRMKVsr90RNq6xIQkthBwC2', '2025-08-31 10:59:56'),
(2, 'migdad', 'migdad123', '2025-09-10 06:08:25');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_at`) VALUES
(2, 'exam date', 'exam will start from 15 sep', '2025-09-06 15:57:09'),
(7, 'Vacation', 'Diwali Vacation start from 13-11-2025', '2025-10-03 05:07:10');

-- --------------------------------------------------------

--
-- Table structure for table `results`
--

CREATE TABLE `results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `theory_marks` int(11) NOT NULL,
  `practical_marks` int(11) NOT NULL,
  `total_marks` int(11) GENERATED ALWAYS AS (`theory_marks` + `practical_marks`) STORED,
  `grade` varchar(5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_headers`
--

CREATE TABLE `result_headers` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `semester` tinyint(3) UNSIGNED NOT NULL,
  `spi` decimal(6,2) DEFAULT NULL,
  `ppi` decimal(6,2) DEFAULT NULL,
  `cgpa` decimal(6,2) DEFAULT NULL,
  `result_class` enum('Internal','Remedial','External') DEFAULT 'Internal',
  `published_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `result_headers`
--

INSERT INTO `result_headers` (`id`, `student_id`, `semester`, `spi`, `ppi`, `cgpa`, `result_class`, `published_at`) VALUES
(8, 4, 3, 9.68, 10.00, 9.78, 'Remedial', '2025-09-04 10:55:33'),
(9, 4, 1, 10.00, 0.00, 10.00, 'Remedial', '2025-09-06 15:31:52'),
(10, 8, 3, 9.50, 0.00, 9.50, 'External', '2025-09-08 14:45:47'),
(11, 12, 7, 6.00, 0.00, 6.00, 'External', '2025-10-03 05:31:07'),
(12, 9, 5, 0.00, 0.00, 0.00, 'Internal', '2025-10-03 05:32:25');

-- --------------------------------------------------------

--
-- Table structure for table `result_items`
--

CREATE TABLE `result_items` (
  `id` int(11) NOT NULL,
  `header_id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `subject_name` varchar(120) NOT NULL,
  `course_credits` int(11) NOT NULL DEFAULT 3,
  `theory_marks` int(11) NOT NULL DEFAULT 0,
  `practical_marks` int(11) NOT NULL DEFAULT 0,
  `overall_marks` int(11) GENERATED ALWAYS AS (`theory_marks` + `practical_marks`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `result_items`
--

INSERT INTO `result_items` (`id`, `header_id`, `course_code`, `subject_name`, `course_credits`, `theory_marks`, `practical_marks`) VALUES
(19, 9, '2740', 'C++', 10, 20, 75),
(20, 8, '2740', 'C++', 10, 45, 77),
(21, 8, '2478', 'html', 5, 74, 44),
(22, 8, '44', '44', 7, 44, 44),
(23, 10, '1202', 'WDT-1', 10, 44, 44),
(24, 10, '1203', 'DBMS', 10, 58, 54),
(26, 12, '1202', 'WDT-1', 10, 1, 20),
(27, 11, '1204', 'C++', 10, 45, 12);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `enrollment_no` varchar(50) DEFAULT NULL,
  `seat_no` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `semester` tinyint(3) UNSIGNED DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `enrollment_no`, `seat_no`, `name`, `email`, `password`, `program`, `branch`, `semester`, `verified`, `created_at`) VALUES
(4, '24004500210153', '30', 'Migdadhaider', 'm@gmail.com', '$2y$10$NTsxhm9DifmoV59XfUENYOxGWTSSYAd3B9HtzzqFoleQwAt.sKTW.', 'bscit', 'ljmca', 3, 1, '2025-08-31 11:03:55'),
(6, '24004500210158', '80', 'umang', 'umang@gmail.com', '$2y$10$wFYcqmDBV65tgyuEQlYYMO7g1Exe5umBhv6yn.XhI.svAQpixfJHK', 'bscit', 'ljmca', 3, 1, '2025-09-01 06:46:24'),
(7, '8', '30', 'Migdadhaider', 'migdad@gmail.com', '$2y$10$6ASTHApqY5utrO4oU8nmJeOfjycRHyytxVaTJ4P1Err.msuDlQc5O', 'bscit', 'ljmca', 1, 1, '2025-09-03 05:49:27'),
(8, '120', '30', 'kasam', 'k@gmail.com', '$2y$10$qBVMmxaDhY0xJK0Y6H1c/uc.LUj9.kMVsh59OwTwfD5lU0FRuWdhK', 'bscit', 'ljmca', 3, 1, '2025-09-06 16:09:15'),
(9, '68', '23', 'Migdadhaider', 'MIH@GMAIL.COM', '$2y$10$B002BLHkObZQlvg/oAdVNupgdtq.IeLusct3Nw450DhhFn4J6GwpO', 'bscit', 'ljmca', 1, 1, '2025-09-10 05:29:17'),
(12, '24004500210152', '78', 'Jay Rathod', 'jay99@gmail.com', '$2y$10$69OQX3isql4RA7L2blq3L.5PH/XW6aZ5ysFH6ZhnMyc5H/5e7CiIu', 'BSCIT', 'LJ MSC', 8, 1, '2025-10-03 05:30:17');

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_performance`
-- (See below for the actual view)
--
CREATE TABLE `student_performance` (
`header_id` int(11)
,`student_id` int(11)
,`semester` tinyint(3) unsigned
,`spi` decimal(14,4)
,`ppi` decimal(14,4)
,`cgpa` decimal(14,4)
);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subject_code`, `subject_name`) VALUES
(1, '', 'ghjt'),
(2, '', 'fdrsf');

-- --------------------------------------------------------

--
-- Table structure for table `subjectss`
--

CREATE TABLE `subjectss` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(50) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `default_credits` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjectss`
--

INSERT INTO `subjectss` (`id`, `subject_code`, `subject_name`, `default_credits`, `is_active`) VALUES
(1, '1202', 'WDT-1', 10, 1),
(3, '1203', 'DBMS', 10, 1),
(4, '1204', 'C++', 10, 1);

-- --------------------------------------------------------

--
-- Structure for view `student_performance`
--
DROP TABLE IF EXISTS `student_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_performance`  AS SELECT `rh`.`id` AS `header_id`, `rh`.`student_id` AS `student_id`, `rh`.`semester` AS `semester`, avg(`ri`.`overall_marks`) AS `spi`, (select avg(`ri2`.`overall_marks`) from (`result_items` `ri2` join `result_headers` `rh2` on(`ri2`.`header_id` = `rh2`.`id`)) where `rh2`.`student_id` = `rh`.`student_id` and `rh2`.`semester` <= `rh`.`semester`) AS `ppi`, (select avg(`ri3`.`overall_marks`) from (`result_items` `ri3` join `result_headers` `rh3` on(`ri3`.`header_id` = `rh3`.`id`)) where `rh3`.`student_id` = `rh`.`student_id`) AS `cgpa` FROM (`result_headers` `rh` join `result_items` `ri` on(`rh`.`id` = `ri`.`header_id`)) GROUP BY `rh`.`id`, `rh`.`student_id`, `rh`.`semester` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `results`
--
ALTER TABLE `results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `result_headers`
--
ALTER TABLE `result_headers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `result_items`
--
ALTER TABLE `result_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `header_id` (`header_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enrollment_no` (`enrollment_no`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjectss`
--
ALTER TABLE `subjectss`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `results`
--
ALTER TABLE `results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `result_headers`
--
ALTER TABLE `result_headers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `result_items`
--
ALTER TABLE `result_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `subjectss`
--
ALTER TABLE `subjectss`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `results`
--
ALTER TABLE `results`
  ADD CONSTRAINT `results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `results_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`);

--
-- Constraints for table `result_headers`
--
ALTER TABLE `result_headers`
  ADD CONSTRAINT `result_headers_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `result_items`
--
ALTER TABLE `result_items`
  ADD CONSTRAINT `result_items_ibfk_1` FOREIGN KEY (`header_id`) REFERENCES `result_headers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
