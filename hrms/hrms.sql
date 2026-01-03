-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 12, 2025 at 02:44 PM
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
-- Database: `hrms`
--

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `created_by`, `priority`, `created_at`) VALUES
(1, 'System Maintenance', 'The HRMS system will undergo maintenance this Saturday from 10 PM to 2 AM.', 2, 'high', '2025-12-07 21:46:45'),
(2, 'System Maintenance', 'May gagawin lang po', 11, 'high', '2025-12-09 06:58:49'),
(3, 'Holiday Tomorrow', 'baka naman makalimutan niyo mga idol', 11, 'low', '2025-12-09 11:58:40');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('present','absent','late','half-day') DEFAULT 'absent',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `date`, `time_in`, `time_out`, `status`, `remarks`, `created_at`) VALUES
(10, 12, '2025-12-09', '14:31:31', '14:36:33', 'late', NULL, '2025-12-09 06:31:31'),
(11, 13, '2025-12-10', '00:43:03', NULL, 'present', NULL, '2025-12-09 16:43:03');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `leave_type` enum('sick','vacation','emergency','maternity','paternity') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_requests`
--

INSERT INTO `leave_requests` (`id`, `user_id`, `leave_type`, `start_date`, `end_date`, `reason`, `status`, `admin_remarks`, `created_at`, `updated_at`) VALUES
(2, 12, 'vacation', '2025-12-10', '2025-12-11', 'mag gagala ako boss, payagan mo na ko', 'rejected', 'puro ka gala', '2025-12-09 06:35:05', '2025-12-09 06:39:36'),
(3, 12, 'sick', '2025-12-11', '2025-12-11', 'masakit tiyan ko', 'approved', 'ge pagaling', '2025-12-09 11:49:19', '2025-12-09 11:50:42'),
(4, 13, 'sick', '2025-12-11', '2025-12-11', 'may sakit ako boss', 'rejected', 'pumasokn ka bawal absent', '2025-12-09 16:43:56', '2025-12-09 16:49:29');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(30, 2, 'New Leave Request', 'Andrew Timothy Jimenez has requested Sick leave.', 'warning', 0, '2025-12-08 21:57:12'),
(37, 11, 'Welcome to HRMS', 'Your account has been created successfully. You can now log in with your credentials.', 'success', 0, '2025-12-09 06:29:15'),
(38, 12, 'Attendance Recorded', 'You clocked in at 02:31 PM - Status: Late', 'success', 0, '2025-12-09 06:31:31'),
(39, 12, 'Profile Updated', 'Your profile picture has been updated successfully.', 'success', 0, '2025-12-09 06:34:20'),
(40, 12, 'Leave Request Submitted', 'Your Vacation leave request from Dec 10 to Dec 11 has been submitted.', 'info', 0, '2025-12-09 06:35:05'),
(41, 2, 'New Leave Request', 'Cedrick Opulencia has requested Vacation leave.', 'warning', 0, '2025-12-09 06:35:05'),
(42, 11, 'New Leave Request', 'Cedrick Opulencia has requested Vacation leave.', 'warning', 0, '2025-12-09 06:35:05'),
(43, 12, 'Clock Out Recorded', 'You clocked out at 02:36 PM', 'info', 0, '2025-12-09 06:36:33'),
(44, 12, 'Leave Request Rejected', 'Your Vacation leave request from Dec 10 to Dec 11 has been rejected. Reason: puro ka gala', 'error', 0, '2025-12-09 06:39:36'),
(45, 12, 'New Announcement: System Maintenance', 'May gagawin lang po', 'info', 0, '2025-12-09 06:58:49'),
(46, 12, 'Leave Request Submitted', 'Your Sick leave request from Dec 11 to Dec 11 has been submitted.', 'info', 0, '2025-12-09 11:49:19'),
(47, 2, 'New Leave Request', 'Cedrick Opulencia has requested Sick leave.', 'warning', 0, '2025-12-09 11:49:19'),
(48, 11, 'New Leave Request', 'Cedrick Opulencia has requested Sick leave.', 'warning', 0, '2025-12-09 11:49:19'),
(49, 12, 'Leave Request Approved', 'Your Sick leave request from Dec 11 to Dec 11 has been approved!', 'success', 0, '2025-12-09 11:50:42'),
(50, 12, 'New Announcement: Holiday Tomorrow', 'baka naman makalimutan niyo mga idol', 'info', 0, '2025-12-09 11:58:40'),
(51, 13, 'Attendance Recorded', 'You clocked in at 12:43 AM - Status: Present', 'success', 0, '2025-12-09 16:43:03'),
(52, 13, 'Leave Request Submitted', 'Your Sick leave request from Dec 11 to Dec 11 has been submitted.', 'info', 0, '2025-12-09 16:43:56'),
(53, 2, 'New Leave Request', 'Ken Clarence Saniel has requested Sick leave.', 'warning', 0, '2025-12-09 16:43:56'),
(54, 11, 'New Leave Request', 'Ken Clarence Saniel has requested Sick leave.', 'warning', 0, '2025-12-09 16:43:56'),
(55, 13, 'Profile Updated', 'Your profile picture has been updated successfully.', 'success', 0, '2025-12-09 16:44:43'),
(56, 13, 'Leave Request Rejected', 'Your Sick leave request from Dec 11 to Dec 11 has been rejected. Reason: pumasokn ka bawal absent', 'error', 1, '2025-12-09 16:49:29'),
(57, 14, 'Welcome to HRMS', 'Your account has been created successfully. You can now log in with your credentials.', 'success', 0, '2025-12-09 16:53:52');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int(11) NOT NULL,
  `basic_salary` decimal(10,2) DEFAULT 0.00,
  `allowances` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(10,2) DEFAULT 0.00,
  `status` enum('draft','processed','paid') DEFAULT 'draft',
  `payment_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `role` enum('admin','employee') NOT NULL DEFAULT 'employee',
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `contact_number`, `password`, `department`, `role`, `profile_picture`, `created_at`) VALUES
(2, 'ADM-002', 'System', 'Admin', 'admin2@hrms.com', '09123456789', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Human Resources', 'admin', NULL, '2025-12-07 19:38:30'),
(11, 'admin', 'Franz Andrei', 'Dayo', 'franzdayo@gmail.com', '09111111111', '$2y$10$HI4o8Dy/E6jeFAZmVRXEc.Jt3Qo/8pKACLeEjIVjBOm5.amJD4WUm', 'HR', 'admin', 'profile_11_1765265850.jpg', '2025-12-09 06:29:15'),
(12, 'EMP1234', 'Cedrick', 'Opulencia', 'cedrickopulencia@gmail.com', '09222222222', '$2y$10$0pB4MVhDkr0w2A7.tageQeJftlVmekr3NOj0jJ/GWxbfsCFDWNyt2', 'CCSE - College of Computer Studies and Engineering', 'employee', 'profile_12_1765262060.jpg', '2025-12-09 06:30:27'),
(13, 'EMP1233', 'Ken Clarence', 'Saniel', 'kensaniel@gmail.com', '09222222211', '$2y$10$qJ60A1ynrWF3jEMiO8KAw.Q94FOulj9JRYrj/iKmGMUdOrm6bK31m', 'CAS - College of Arts and Sciences', 'employee', 'profile_13_1765298683.jpg', '2025-12-09 16:42:15'),
(14, 'Admin1', 'Martin Lorns', 'Papa', 'martinpapa@gmail.com', '09222222244', '$2y$10$vtFUJpg4NiHAFkAtyT18iugYK.ylHJtJCabPPcuJZbl08c2adDxRy', 'CAS - College of Arts and Sciences', 'admin', 'profile_14_1765299552.jpg', '2025-12-09 16:53:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`date`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_payroll` (`user_id`,`month`,`year`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
