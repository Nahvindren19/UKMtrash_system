-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 27, 2025 at 02:53 PM
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
-- Database: `ukm_trash_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `bin`
--

CREATE TABLE `bin` (
  `binNo` varchar(10) NOT NULL,
  `binLocation` varchar(100) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `qrCode` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bin`
--

INSERT INTO `bin` (`binNo`, `binLocation`, `status`, `qrCode`) VALUES
('B001', 'Block F, Ground Floor', 'Active', 'qr_b001.png'),
('B002', 'Cafe KPZ', 'Maintenance', 'qr_b002.png'),
('B003', 'Block B, Near Lift', 'Active', 'qr_b003.png');

-- --------------------------------------------------------

--
-- Table structure for table `cleaningstaff`
--

CREATE TABLE `cleaningstaff` (
  `ID` varchar(10) NOT NULL,
  `status` varchar(50) DEFAULT 'Available',
  `change_password` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cleaningstaff`
--

INSERT INTO `cleaningstaff` (`ID`, `status`, `change_password`) VALUES
('C5003', 'Available', 1),
('S001', 'Available', 1),
('S002', 'Available', 1);

-- --------------------------------------------------------

--
-- Table structure for table `cleaningsupervisor`
--

CREATE TABLE `cleaningsupervisor` (
  `ID` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cleaningsupervisor`
--

INSERT INTO `cleaningsupervisor` (`ID`) VALUES
('S9002');

-- --------------------------------------------------------

--
-- Table structure for table `complaint`
--

CREATE TABLE `complaint` (
  `complaintID` varchar(10) NOT NULL,
  `type` varchar(100) NOT NULL,
  `date` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'Unresolved',
  `binNo` varchar(10) NOT NULL,
  `studentID` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaint`
--

INSERT INTO `complaint` (`complaintID`, `type`, `date`, `status`, `binNo`, `studentID`) VALUES
('C101', 'Bin full', '2025-11-11 14:39:46', 'Unresolved', 'B003', 'A202001'),
('C102', 'Bad odor', '2025-11-09 15:45:00', 'Resolved', 'B001', 'A202001');

-- --------------------------------------------------------

--
-- Table structure for table `mstaff`
--

CREATE TABLE `mstaff` (
  `ID` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mstaff`
--

INSERT INTO `mstaff` (`ID`) VALUES
('M1001');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `ID` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`ID`) VALUES
('A202001'),
('A202002'),
('A203406');

-- --------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE `task` (
  `taskID` varchar(10) NOT NULL,
  `staffID` varchar(10) DEFAULT NULL,
  `location` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `status` varchar(50) DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task`
--

INSERT INTO `task` (`taskID`, `staffID`, `location`, `date`, `status`) VALUES
('T201', NULL, 'Block B, Near Lift', '2025-11-11', 'Completed'),
('T202', NULL, 'Block F, Ground Floor', '2025-11-12', 'Scheduled'),
('T203', NULL, 'Block D, Main Hall', '2025-11-12', 'Scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `ID` varchar(10) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`ID`, `password`, `name`, `category`, `email`) VALUES
('A202001', 'hashed_student_pass', 'Test Student A', 'Student', 'student.a@ukm.edu.my'),
('A202002', 'hashed_student_pass', 'Student B', 'Student', 'student.b@ukm.edu.my'),
('A203406', '$2y$10$PaQTL.jKk1U9dRNKXh5lWeKja.QW1eqSufXmokll6Rvat.cXYMPSS', 'Nahvindren A/L Chandrasegaran', 'Student', 'a203406@siswa.ukm.edu.my'),
('C5001', 'hashed_staff_pass', 'Test Staff B', 'Cleaning Staff', 'staff.b@ukm.edu.my'),
('C5003', '$2y$10$tUQkP4CFVFoWefvsN0jYoOZg1q25BCGEOlFFfhTPkryyWWJyBcbMa', 'Test Cleaning Staff', 'Cleaning Staff', 'staff.test@ukm.edu.my'),
('M1001', '$2y$10$KMU1iwoAjEqQfFsju.vUJOmFQFqk4JqhNqm1tY0VsAyYPAKHeLBxC', 'Admin A', 'Maintenance and Infrastructure Department', 'admin@ukm.edu.my'),
('S001', '$2y$10$qpPHHurX33oU6vL06nKcSOPHPtSURifzA17Po3yQhxqMKvoD9KW5a', 'Hanisah', 'Cleaning Staff', 'mfn@ukm.edu.my'),
('S002', '$2y$10$fMKx.dhqPWq0hNJQapxKXu2mSeg1M7vI/lE0exUwb/q8de0z5KRsu', 'Alisya', 'Cleaning Staff', 'nahvindrennahvin@gmail.com'),
('S9002', 'hashed_super_pass', 'Supervisor C', 'Cleaning Supervisor', 'super.c@ukm.edu.my');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bin`
--
ALTER TABLE `bin`
  ADD PRIMARY KEY (`binNo`);

--
-- Indexes for table `cleaningstaff`
--
ALTER TABLE `cleaningstaff`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `cleaningsupervisor`
--
ALTER TABLE `cleaningsupervisor`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `complaint`
--
ALTER TABLE `complaint`
  ADD PRIMARY KEY (`complaintID`),
  ADD KEY `binNo` (`binNo`),
  ADD KEY `studentID` (`studentID`);

--
-- Indexes for table `mstaff`
--
ALTER TABLE `mstaff`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`taskID`),
  ADD KEY `staffID` (`staffID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cleaningstaff`
--
ALTER TABLE `cleaningstaff`
  ADD CONSTRAINT `cleaningstaff_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `user` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `cleaningsupervisor`
--
ALTER TABLE `cleaningsupervisor`
  ADD CONSTRAINT `cleaningsupervisor_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `user` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `complaint`
--
ALTER TABLE `complaint`
  ADD CONSTRAINT `complaint_ibfk_1` FOREIGN KEY (`binNo`) REFERENCES `bin` (`binNo`),
  ADD CONSTRAINT `complaint_ibfk_2` FOREIGN KEY (`studentID`) REFERENCES `student` (`ID`);

--
-- Constraints for table `mstaff`
--
ALTER TABLE `mstaff`
  ADD CONSTRAINT `mstaff_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `user` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`ID`) REFERENCES `user` (`ID`) ON DELETE CASCADE;

--
-- Constraints for table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `task_ibfk_1` FOREIGN KEY (`staffID`) REFERENCES `cleaningstaff` (`ID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
