-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 02, 2024 at 08:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 7.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_category`
--

CREATE TABLE `{prefix}_category` (
  `type` varchar(20) NOT NULL,
  `category_id` varchar(10) DEFAULT '0',
  `topic` varchar(150) NOT NULL,
  `color` varchar(16) DEFAULT NULL,
  `published` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `{prefix}_category`
--

INSERT INTO `{prefix}_category` (`type`, `category_id`, `topic`, `color`, `published`) VALUES
('repairstatus', 1, 'แจ้งซ่อม', '#660000', 1),
('repairstatus', 2, 'กำลังดำเนินการ', '#120eeb', 1),
('repairstatus', 3, 'รออะไหล่', '#d940ff', 1),
('repairstatus', 4, 'ซ่อมสำเร็จ', '#06d628', 1),
('repairstatus', 5, 'ซ่อมไม่สำเร็จ', '#FF0000', 1),
('repairstatus', 6, 'เปลี่ยนสินค้าชิ้นใหม่', '#ff6969', 1),
('repairstatus', 7, 'ยกเลิกการซ่อม', '#FF6600', 1),
('repairstatus', 8, 'ชำระเงิน', '#006600', 1),
('repairstatus', 9, 'ส่งมอบสินค้าคืนลูกค้าเรียบร้อย', '#2A2A2A', 1);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_inventory`
--

CREATE TABLE `{prefix}_inventory` (
  `id` int(11) NOT NULL,
  `equipment` varchar(64) NOT NULL,
  `serial` varchar(20) NOT NULL,
  `create_date` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `{prefix}_inventory`
--

INSERT INTO `{prefix}_inventory` (`id`, `equipment`, `serial`, `create_date`) VALUES
(1, 'คอมพิวเตอร์', '0123456789', 1629274808),
(2, 'ไม่มี', '12345', 1660886207),
(3, 'qeweqqwww', 'qeweqqwww', 1661002628),
(4, 'qwrrrtyu', 'qeeftttyy', 1661002830),
(5, 'เครื่องคอมพิวเตอร์', '01212123', 1662813324),
(6, 'ทดสอบ', '012345', 1663031691);

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_language`
--

CREATE TABLE `{prefix}_language` (
  `id` int(11) NOT NULL,
  `key` text NOT NULL,
  `type` varchar(5) NOT NULL,
  `owner` varchar(20) NOT NULL,
  `js` tinyint(1) NOT NULL,
  `th` text DEFAULT NULL,
  `en` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_logs`
--

CREATE TABLE `{prefix}_logs` (
  `id` int(11) NOT NULL,
  `src_id` int(11) NOT NULL,
  `module` varchar(20) NOT NULL,
  `action` varchar(20) NOT NULL,
  `create_date` datetime NOT NULL,
  `reason` text DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `topic` text NOT NULL,
  `datas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_number`
--

CREATE TABLE `{prefix}_number` (
  `type` varchar(20) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `auto_increment` int(11) NOT NULL,
  `last_update` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_repair`
--

CREATE TABLE `{prefix}_repair` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `job_id` varchar(20) NOT NULL,
  `job_description` varchar(1000) NOT NULL,
  `create_date` datetime NOT NULL,
  `appointment_date` date DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `provinceID` varchar(3) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `appraiser` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_repair_status`
--

CREATE TABLE `{prefix}_repair_status` (
  `id` int(11) NOT NULL,
  `repair_id` int(11) NOT NULL,
  `status` tinyint(2) NOT NULL,
  `operator_id` int(11) NOT NULL,
  `comment` varchar(1000) DEFAULT NULL,
  `member_id` int(11) NOT NULL,
  `create_date` datetime NOT NULL,
  `cost` float DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user`
--

CREATE TABLE `{prefix}_user` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `salt` varchar(32) NOT NULL,
  `password` varchar(50) NOT NULL,
  `token` varchar(50) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `permission` text NOT NULL,
  `name` varchar(150) NOT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `id_card` varchar(13) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `provinceID` varchar(3) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `country` varchar(2) DEFAULT 'TH',
  `create_date` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `social` tinyint(1) DEFAULT 0,
  `line_uid` varchar(33) DEFAULT NULL,
  `activatecode` varchar(32) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `{prefix}_user_meta`
--

CREATE TABLE `{prefix}_user_meta` (
  `value` varchar(10) NOT NULL,
  `name` varchar(10) NOT NULL,
  `member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Indexes for table `{prefix}_category`
--
ALTER TABLE `{prefix}_category`
  ADD KEY `type` (`type`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `{prefix}_inventory`
--
ALTER TABLE `{prefix}_inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `src_id` (`src_id`),
  ADD KEY `module` (`module`),
  ADD KEY `action` (`action`);

--
-- Indexes for table `{prefix}_number`
--
ALTER TABLE `{prefix}_number`
  ADD PRIMARY KEY (`type`,`prefix`);

--
-- Indexes for table `{prefix}_repair`
--
ALTER TABLE `{prefix}_repair`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_id` (`job_id`),
  ADD KEY `name` (`name`),
  ADD KEY `phone` (`phone`);

--
-- Indexes for table `{prefix}_repair_status`
--
ALTER TABLE `{prefix}_repair_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `operator_id` (`operator_id`),
  ADD KEY `repair_id` (`repair_id`);

--
-- Indexes for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  ADD PRIMARY KEY (`id`),
  ADD KEY `line_uid` (`line_uid`),
  ADD KEY `username` (`username`),
  ADD KEY `token` (`token`),
  ADD KEY `phone` (`phone`),
  ADD KEY `id_card` (`id_card`),
  ADD KEY `activatecode` (`activatecode`);

--
-- Indexes for table `{prefix}_user_meta`
--
ALTER TABLE `{prefix}_user_meta`
  ADD KEY `member_id` (`member_id`,`name`);

--
-- AUTO_INCREMENT for table `{prefix}_inventory`
--
ALTER TABLE `{prefix}_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_language`
--
ALTER TABLE `{prefix}_language`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_logs`
--
ALTER TABLE `{prefix}_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_repair`
--
ALTER TABLE `{prefix}_repair`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_repair_status`
--
ALTER TABLE `{prefix}_repair_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `{prefix}_user`
--
ALTER TABLE `{prefix}_user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
