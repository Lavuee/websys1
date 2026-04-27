-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2026 at 07:41 AM
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
-- Database: `pines_ems`
--

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_contact` varchar(20) DEFAULT NULL,
  `grade_level` enum('Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12') NOT NULL,
  `strand` enum('N/A','STEM','HUMSS','ABM','GAS','TVL-ICT','TVL-HE','TVL-AFA') DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `status` enum('Pending','Assessed','Enrolled','Rejected') DEFAULT 'Pending',
  `lrn` varchar(50) DEFAULT NULL,
  `previous_school` varchar(255) DEFAULT NULL,
  `tuition_amount` decimal(10,2) DEFAULT 0.00,
  `misc_fees` decimal(10,2) DEFAULT 0.00,
  `total_assessment` decimal(10,2) DEFAULT 0.00,
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_email`, `first_name`, `middle_name`, `last_name`, `suffix`, `date_of_birth`, `gender`, `contact_number`, `address`, `guardian_name`, `guardian_contact`, `grade_level`, `strand`, `school_year`, `status`, `lrn`, `previous_school`, `tuition_amount`, `misc_fees`, `total_assessment`, `amount_paid`, `balance`, `admin_notes`, `created_at`) VALUES
('ENR-2026-0BAB0', 'leelav.viin@gmail.com', 'lav', 's', 'asgr', '', '2004-11-04', 'Female', '154632', '', 'elle', '', 'Grade 11', 'N/A', '2025-2026', 'Pending', '8521232', 'bnhs', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, '2026-04-24 05:26:57'),
('ENR-2026-A2645', 'leelav.viin@gmail.com', 'lav', 's', 'asgr', '', '2004-11-04', 'Female', '154632', '', 'elle', '', 'Grade 11', 'N/A', '2025-2026', 'Pending', '8521232', 'bnhs', 0.00, 0.00, 0.00, 0.00, 0.00, NULL, '2026-04-24 05:27:17');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `grade_id` int(11) NOT NULL,
  `enrollment_id` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `first_quarter` decimal(5,2) DEFAULT NULL,
  `second_quarter` decimal(5,2) DEFAULT NULL,
  `third_quarter` decimal(5,2) DEFAULT NULL,
  `fourth_quarter` decimal(5,2) DEFAULT NULL,
  `final_grade` decimal(5,2) DEFAULT NULL,
  `remarks` enum('Passed','Failed','Incomplete','Dropped') DEFAULT NULL,
  `school_year` varchar(20) DEFAULT NULL,
  `grade_level` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `enrollment_id` varchar(255) NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','GCash') NOT NULL,
  `gcash_reference` varchar(100) DEFAULT NULL,
  `receipt_url` text DEFAULT NULL,
  `status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `verified_by` varchar(255) DEFAULT NULL,
  `verified_date` datetime DEFAULT NULL,
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`enrollment_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `enrollment_id` (`enrollment_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments` (`enrollment_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


-- ── 1. Admins table ─────────────────────────────────────────
CREATE TABLE `admins` (
  `admin_id`      INT(11)       NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(100)  NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)  NOT NULL,
  `full_name`     VARCHAR(255)  DEFAULT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- Default admin account (username: admin | password: Admin@1234)
-- Change this password immediately after first login!
INSERT INTO `admins` (`username`, `password_hash`, `full_name`)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator');
 
 
-- ── 2. Student accounts table ────────────────────────────────
-- Linked to enrollments via enrollment_id
CREATE TABLE `student_accounts` (
  `account_id`    INT(11)       NOT NULL AUTO_INCREMENT,
  `enrollment_id` VARCHAR(255)  NOT NULL UNIQUE,
  `student_email` VARCHAR(255)  NOT NULL,
  `password_hash` VARCHAR(255)  NOT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`account_id`),
  CONSTRAINT `fk_student_account`
    FOREIGN KEY (`enrollment_id`)
    REFERENCES `enrollments` (`enrollment_id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
 
-- ── 3. Enrollment settings table ─────────────────────────────
CREATE TABLE `enrollment_settings` (
  `id`          INT(11)   NOT NULL DEFAULT 1,
  `is_open`     TINYINT(1) NOT NULL DEFAULT 0,
  `start_date`  DATE       DEFAULT NULL,
  `end_date`    DATE       DEFAULT NULL,
  `updated_by`  INT(11)   DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_settings_admin`
    FOREIGN KEY (`updated_by`)
    REFERENCES `admins` (`admin_id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
 
-- Default settings row (always one row with id = 1)
INSERT INTO `enrollment_settings` (`id`, `is_open`) VALUES (1, 0);
 
 
-- ── NOTE on student account creation ─────────────────────────
-- When Member 3 saves a new enrollment, also insert into student_accounts.
-- Example PHP (to be placed in Member 3's registration handler):
--
-- $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
-- $stmt = $pdo->prepare("
--   INSERT INTO student_accounts (enrollment_id, student_email, password_hash)
--   VALUES (?, ?, ?)
-- ");
-- $stmt->execute([$enrollment_id, $student_email, $hash]);