-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 24, 2026 at 07:11 AM
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
-- Database: `school_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `att_date` date NOT NULL,
  `att_time` time DEFAULT NULL,
  `status` enum('present','absent') NOT NULL DEFAULT 'present',
  `method` varchar(30) NOT NULL DEFAULT 'manual',
  `confidence` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `student_id`, `att_date`, `att_time`, `status`, `method`, `confidence`) VALUES
(23, 12, '2026-05-26', NULL, 'absent', 'manual', NULL),
(24, 12, '2026-06-05', NULL, 'absent', 'manual', NULL),
(25, 13, '2026-06-05', NULL, 'present', 'manual', NULL),
(28, 12, '2026-06-08', NULL, 'absent', 'manual', NULL),
(29, 14, '2026-06-08', NULL, 'absent', 'manual', NULL),
(30, 13, '2026-06-08', NULL, 'present', 'manual', NULL),
(31, 12, '2026-06-11', NULL, 'present', 'manual', NULL),
(32, 14, '2026-06-11', NULL, 'present', 'manual', NULL),
(33, 13, '2026-06-11', NULL, 'present', 'manual', NULL),
(34, 15, '2026-06-11', NULL, 'absent', 'manual', NULL),
(35, 12, '2026-08-10', NULL, 'present', 'manual', NULL),
(36, 14, '2026-08-10', NULL, 'absent', 'manual', NULL),
(37, 13, '2026-08-10', NULL, 'absent', 'manual', NULL),
(38, 15, '2026-08-10', NULL, 'present', 'manual', NULL),
(39, 12, '2026-08-24', NULL, 'present', 'manual', NULL),
(40, 14, '2026-08-24', NULL, 'absent', 'manual', NULL),
(41, 13, '2026-08-24', NULL, 'present', 'manual', NULL),
(42, 15, '2026-08-24', NULL, 'present', 'manual', NULL),
(43, 20, '2026-09-12', NULL, 'present', 'manual', NULL),
(44, 19, '2026-09-12', NULL, 'absent', 'manual', NULL),
(45, 21, '2026-09-12', NULL, 'present', 'manual', NULL),
(46, 16, '2026-09-12', NULL, 'present', 'manual', NULL),
(47, 17, '2026-09-12', NULL, 'absent', 'manual', NULL),
(48, 18, '2026-09-12', NULL, 'present', 'manual', NULL),
(49, 20, '2026-10-20', NULL, 'absent', 'manual', NULL),
(50, 19, '2026-10-20', NULL, 'present', 'manual', NULL),
(51, 21, '2026-10-20', NULL, 'present', 'manual', NULL),
(52, 16, '2026-10-20', NULL, 'absent', 'manual', NULL),
(53, 17, '2026-10-20', NULL, 'present', 'manual', NULL),
(54, 18, '2026-10-20', NULL, 'present', 'manual', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `name`) VALUES
(1, 'Nursery'),
(14, 'KG'),
(16, 'Class 2nd'),
(17, 'Class 3rd'),
(18, 'Class 4th'),
(19, 'Class 5th'),
(20, 'Class 6th'),
(21, 'Class 7th'),
(22, 'Class 8th'),
(23, 'Class 9th'),
(24, 'Class 10th'),
(25, 'Class 1st');

-- --------------------------------------------------------

--
-- Table structure for table `fees`
--

CREATE TABLE `fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int(4) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_on` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fees`
--

INSERT INTO `fees` (`id`, `student_id`, `month`, `year`, `amount`, `paid_on`) VALUES
(6, 12, 'August', 2026, 6000.00, '2026-08-30 14:30:56'),
(7, 19, 'August', 2026, 2500.00, '2026-08-30 15:16:58'),
(8, 19, 'August', 2026, 2500.00, '2026-08-30 15:25:20');

-- --------------------------------------------------------

--
-- Table structure for table `id_card_settings`
--

CREATE TABLE `id_card_settings` (
  `id` int(11) NOT NULL,
  `school_name` varchar(150) NOT NULL DEFAULT 'ASLAM PUBLIC HIGH SCHOOL',
  `tagline` varchar(200) NOT NULL DEFAULT 'Excellence in Knowledge & Character',
  `campus` varchar(100) NOT NULL DEFAULT 'Main Campus',
  `phone` varchar(50) NOT NULL DEFAULT '+92 300 1234567',
  `email` varchar(100) NOT NULL DEFAULT 'info@aslamschool.edu',
  `address` varchar(255) NOT NULL DEFAULT 'College Road, Model Town',
  `session_year` varchar(50) NOT NULL DEFAULT '2026 - 2027',
  `principal_title` varchar(50) NOT NULL DEFAULT 'Principal',
  `signature_text` varchar(100) NOT NULL DEFAULT 'Authorized Signatory',
  `theme` varchar(50) NOT NULL DEFAULT 'royal_navy',
  `logo_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `id_card_settings`
--

INSERT INTO `id_card_settings` (`id`, `school_name`, `tagline`, `campus`, `phone`, `email`, `address`, `session_year`, `principal_title`, `signature_text`, `theme`, `logo_path`, `updated_at`) VALUES
(1, 'ASLAM PUBLIC HIGH SCHOOL', 'Excellence in Knowledge & Character', '', '+92 300 1234567', '', 'College Road, Model Town', '2026 - 2027', 'Principal', '', 'royal_navy', NULL, '2026-10-21 06:44:44');

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `exam_term` varchar(50) NOT NULL,
  `total_marks` int(11) NOT NULL DEFAULT 100,
  `obtained_marks` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `marks`
--

INSERT INTO `marks` (`id`, `student_id`, `subject_id`, `exam_term`, `total_marks`, `obtained_marks`) VALUES
(23, 12, 11, 'First Term', 100, 100),
(25, 12, 10, 'First Term', 100, 98),
(26, 12, 14, 'First Term', 100, 45),
(27, 12, 9, 'First Term', 100, 90),
(28, 12, 12, 'First Term', 100, 100),
(29, 12, 21, 'First Term', 50, 44),
(30, 13, 11, 'First Term', 100, 99),
(31, 13, 21, 'First Term', 50, 45),
(32, 13, 10, 'First Term', 100, 97),
(33, 13, 14, 'First Term', 100, 46),
(34, 13, 9, 'First Term', 100, 90),
(35, 13, 12, 'First Term', 100, 100),
(36, 12, 22, 'First Term', 75, 56),
(37, 13, 22, 'First Term', 75, 40),
(38, 14, 11, 'First Term', 100, 100),
(39, 14, 21, 'First Term', 50, 45),
(40, 14, 10, 'First Term', 100, 100),
(41, 14, 14, 'First Term', 100, 45),
(42, 14, 22, 'First Term', 75, 70),
(43, 14, 9, 'First Term', 100, 100),
(44, 14, 12, 'First Term', 100, 100),
(45, 15, 11, 'First Term', 100, 88),
(46, 15, 21, 'First Term', 50, 45),
(47, 15, 10, 'First Term', 100, 60),
(48, 15, 14, 'First Term', 100, 43),
(49, 15, 22, 'First Term', 75, 42),
(50, 15, 9, 'First Term', 100, 90),
(51, 15, 12, 'First Term', 100, 100),
(52, 16, 30, 'First Term', 75, 70),
(53, 16, 29, 'First Term', 100, 72),
(54, 16, 32, 'First Term', 75, 66),
(55, 16, 26, 'First Term', 100, 98),
(56, 16, 25, 'First Term', 100, 98),
(57, 16, 23, 'First Term', 100, 99),
(58, 16, 31, 'First Term', 75, 71),
(59, 16, 24, 'First Term', 100, 98),
(60, 17, 30, 'First Term', 75, 55),
(61, 17, 29, 'First Term', 100, 71),
(62, 17, 32, 'First Term', 75, 72),
(63, 17, 26, 'First Term', 100, 76),
(64, 17, 25, 'First Term', 100, 77),
(65, 17, 23, 'First Term', 100, 99),
(66, 17, 31, 'First Term', 75, 70),
(67, 17, 24, 'First Term', 100, 99),
(68, 18, 30, 'First Term', 75, 71),
(69, 18, 29, 'First Term', 100, 72),
(70, 18, 32, 'First Term', 75, 71),
(71, 18, 26, 'First Term', 100, 78),
(72, 18, 25, 'First Term', 100, 66),
(73, 18, 23, 'First Term', 100, 52),
(74, 18, 31, 'First Term', 75, 71),
(75, 18, 24, 'First Term', 100, 98),
(76, 20, 30, 'First Term', 75, 71),
(77, 20, 29, 'First Term', 100, 72),
(78, 20, 32, 'First Term', 75, 71),
(79, 20, 26, 'First Term', 100, 71),
(80, 20, 25, 'First Term', 100, 100),
(81, 20, 23, 'First Term', 100, 100),
(82, 20, 31, 'First Term', 75, 71),
(83, 20, 24, 'First Term', 100, 100),
(84, 19, 32, 'First Term', 75, 23),
(85, 19, 30, 'First Term', 75, 43),
(86, 21, 30, 'First Term', 75, 30),
(87, 21, 29, 'First Term', 100, 30),
(88, 21, 32, 'First Term', 75, 30),
(89, 21, 26, 'First Term', 100, 30),
(90, 21, 25, 'First Term', 100, 30),
(91, 21, 23, 'First Term', 100, 30),
(92, 21, 31, 'First Term', 75, 30),
(93, 21, 24, 'First Term', 100, 30);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `admission_no` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `father_name` varchar(100) NOT NULL,
  `dob` date NOT NULL,
  `gender` varchar(10) NOT NULL,
  `blood_group` varchar(10) DEFAULT 'O+',
  `class_id` int(11) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `admission_date` date NOT NULL,
  `monthly_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `previous_dues` decimal(10,2) NOT NULL DEFAULT 0.00,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `admission_no`, `name`, `father_name`, `dob`, `gender`, `blood_group`, `class_id`, `phone`, `address`, `admission_date`, `monthly_fee`, `previous_dues`, `photo`) VALUES
(12, '1001', 'Aslam Malik', 'Mohammad Jamal', '2007-03-10', 'Male', 'O+', 17, '03437727490', 'Pirkalay Matta Swat', '2026-05-26', 2000.00, 0.00, NULL),
(13, '1002', 'Hammad', 'Haroon', '2007-10-26', 'Male', 'O+', 17, '03456758999', 'Duresh Khela Matta Swat', '2026-05-26', 3000.00, 6000.00, NULL),
(14, '1003', 'Basit', 'Ahmad', '2022-02-08', 'Male', 'O+', 17, '03442866133', 'Matta', '2026-06-08', 2500.00, 2500.00, NULL),
(15, '1004', 'Usman Khan', 'Ayaz Khan', '2018-06-11', 'Male', 'O+', 17, '03437727490', 'Prkly Matta Swat\r\n', '2026-06-11', 1500.00, 1500.00, NULL),
(16, '1005', 'Ibrar', 'Mohammad Jamal', '2020-06-01', 'Male', 'O+', 24, '03437727490', 'pirkly', '2026-08-30', 2500.00, 0.00, NULL),
(17, '1006', 'Numan', 'Ghani', '2006-12-02', 'Male', 'O+', 24, '03437727490', 'pirkly', '2026-08-30', 2500.00, 0.00, NULL),
(18, '1007', 'Wajid', 'Ghulam', '2007-10-07', 'Male', 'O+', 24, '03437727490', 'pirkly', '2026-08-30', 2500.00, 0.00, NULL),
(19, '1100', 'Aslam Malik', 'Mohammad Jamal', '2007-03-10', 'Male', 'O+', 24, '03437727490', 'pirkly', '2026-08-30', 2500.00, -2500.00, NULL),
(20, '1102', 'Aslam Khan', 'Mohammad Jamal', '2007-03-10', 'Male', 'O+', 24, '03437727490', 'pirkly', '2026-08-30', 2500.00, 2500.00, 'stu_6a944bcc3b0d9.jpeg'),
(21, '1103', 'Aslam Malik', 'Mohammad Jamal', '2026-09-02', 'Male', 'O+', 24, '03437727490', 'pirkly', '2026-09-02', 2500.00, 0.00, NULL),
(22, '1104', 'Aslam', 'Mohammad Jamal', '2026-09-27', 'Male', 'O+', 17, '03437727490', 'pirkly', '2026-09-02', 2500.00, 0.00, 'stu_6a983d687e95f.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `student_faces`
--

CREATE TABLE `student_faces` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `face_descriptor` longtext NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `sample_count` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `total_marks` int(11) NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `class_id`, `name`, `total_marks`) VALUES
(9, 16, 'Science', 100),
(10, 16, 'Maths', 100),
(11, 16, 'English', 100),
(12, 16, 'Urdu', 100),
(14, 16, 'Pak-study', 100),
(15, 17, 'English', 100),
(16, 17, 'Science', 100),
(17, 17, 'Maths', 100),
(18, 17, 'Urdu', 100),
(19, 17, 'Islamiat', 100),
(20, 17, 'Pak-study', 100),
(21, 16, 'Islamiat', 50),
(22, 16, 'Physics', 75),
(23, 24, 'Maths', 100),
(24, 24, 'Urdu', 100),
(25, 24, 'Islamiat', 100),
(26, 24, 'English', 100),
(29, 24, 'Biolo', 100),
(30, 24, ' Biology', 75),
(31, 24, 'Physics', 75),
(32, 24, 'Chemistry', 75);

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `qualification` varchar(100) NOT NULL,
  `join_date` date NOT NULL,
  `salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `phone`, `email`, `qualification`, `join_date`, `salary`, `status`) VALUES
(1, 'Aslam Malik', '03437727490', 'malikpc405@gmail.com', 'BS Computer Science', '2026-09-02', 20000.00, 'Active'),
(2, 'Noman', '03437727490', 'malikpc405@gmail.com', 'BS Maths', '2026-09-02', 25000.00, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_assignments`
--

CREATE TABLE `teacher_assignments` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `duration_mins` int(11) NOT NULL DEFAULT 30,
  `start_time` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_assignments`
--

INSERT INTO `teacher_assignments` (`id`, `teacher_id`, `class_id`, `subject_id`, `duration_mins`, `start_time`) VALUES
(1, 1, 16, 11, 30, ''),
(2, 1, 24, 31, 30, '9:00');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_salaries`
--

CREATE TABLE `teacher_salaries` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `month` varchar(20) NOT NULL,
  `year` int(4) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `paid_on` timestamp NOT NULL DEFAULT current_timestamp(),
  `base_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allowance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deduction` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_salaries`
--

INSERT INTO `teacher_salaries` (`id`, `teacher_id`, `month`, `year`, `amount`, `paid_on`, `base_salary`, `allowance`, `deduction`, `net_salary`, `payment_method`, `remarks`) VALUES
(1, 2, 'September', 2026, 0.00, '2026-09-01 19:00:00', 25000.00, 0.00, 0.00, 25000.00, 'Cash', '');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `period_no` int(11) NOT NULL,
  `start_time` varchar(20) NOT NULL,
  `end_time` varchar(20) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `day_name` varchar(20) DEFAULT 'Daily'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `class_id`, `period_no`, `start_time`, `end_time`, `subject_id`, `teacher_id`, `day_name`) VALUES
(1, 1, 1, '9:00', '9:30', NULL, NULL, 'Daily'),
(2, 24, 1, '9:00', '9:30', 31, 1, 'Daily');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_date` (`student_id`,`att_date`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fees`
--
ALTER TABLE `fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `id_card_settings`
--
ALTER TABLE `id_card_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_no` (`admission_no`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `student_faces`
--
ALTER TABLE `student_faces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_student_face` (`student_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `teacher_salaries`
--
ALTER TABLE `teacher_salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `fees`
--
ALTER TABLE `fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `id_card_settings`
--
ALTER TABLE `id_card_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `student_faces`
--
ALTER TABLE `student_faces`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_salaries`
--
ALTER TABLE `teacher_salaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fees`
--
ALTER TABLE `fees`
  ADD CONSTRAINT `fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marks`
--
ALTER TABLE `marks`
  ADD CONSTRAINT `marks_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marks_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`);

--
-- Constraints for table `student_faces`
--
ALTER TABLE `student_faces`
  ADD CONSTRAINT `student_faces_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_assignments`
--
ALTER TABLE `teacher_assignments`
  ADD CONSTRAINT `teacher_assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_assignments_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teacher_salaries`
--
ALTER TABLE `teacher_salaries`
  ADD CONSTRAINT `teacher_salaries_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
