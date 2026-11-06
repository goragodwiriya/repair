-- ---------------------------------------------------------------------------
-- modules/repair/install/database.sql — ตารางที่โมดูล repair เป็นเจ้าของ
--
-- **ประกาศที่นี่ที่เดียว** ห้ามประกาศซ้ำใน install/database.sql ของโปรเจ็ค
-- ประกาศสองที่ = ติดตั้งใหม่ล้มด้วย "Table already exists" และนิยามสองชุด
-- จะค่อย ๆ ต่างกันจนไซต์ที่อัปเกรดคนละเส้นทางได้สคีมาไม่เหมือนกัน
--
-- ทั้งการติดตั้งใหม่ (common.php::schemaFiles) และการปรับรุ่น (ensureTable)
-- อ่านนิยามจากไฟล์นี้ไฟล์เดียว
-- ---------------------------------------------------------------------------

CREATE TABLE `{prefix}_repair_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment` varchar(64) NOT NULL,
  `serial` varchar(20) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_serial` (`equipment`,`serial`),
  KEY `serial` (`serial`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `{prefix}_repair` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL DEFAULT 0,
  `job_id` varchar(20) NOT NULL,
  `job_description` varchar(1000) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `appointment_date` date DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `provinceID` smallint(3) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `appraiser` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_id` (`job_id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `name` (`name`),
  KEY `phone` (`phone`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `{prefix}_repair_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL,
  `status` tinyint(2) NOT NULL DEFAULT 0,
  `operator_id` int(11) NOT NULL DEFAULT 0,
  `comment` varchar(1000) DEFAULT NULL,
  `member_id` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `cost` float NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_repair` (`repair_id`,`id`),
  KEY `idx_status` (`status`,`created_at`),
  KEY `operator_id` (`operator_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- ข้อมูลตั้งต้นของโมดูลนี้
--
-- ⚠️ ต้องอยู่ที่นี่ ไม่ใช่ใน install/database.sql ของโปรเจ็ค เพราะ schemaFiles()
-- รันไฟล์ของโปรเจ็ค **ก่อน** ไฟล์ของโมดูล — INSERT ที่อยู่ฝั่งโปรเจ็คจะวิ่งไปหา
-- ตารางที่ยังไม่ถูกสร้าง แล้วการติดตั้งใหม่ล้มทันที
-- ---------------------------------------------------------------------------

INSERT INTO `{prefix}_repair_equipment` (`id`, `equipment`, `serial`, `created_at`) VALUES
(1, 'คอมพิวเตอร์ตั้งโต๊ะ Dell OptiPlex 3070', 'DL3070-0012', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 18 DAY), '09:15:00')),
(2, 'โน๊ตบุ๊ค Lenovo ThinkPad E14', 'TP-E14-8891', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 15 DAY), '10:05:00')),
(3, 'เครื่องพิมพ์ Canon G2010', 'CN-G2010-4471', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 12 DAY), '13:20:00')),
(4, 'จอภาพ Samsung 24 นิ้ว', 'SM24-77120', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY), '11:00:00')),
(5, 'เครื่องสำรองไฟ APC BX700', 'APC-BX700-1180', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY), '09:45:00')),
(6, 'เครื่องปรับอากาศ Daikin 12000 BTU', 'DK-12K-0345', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '14:30:00')),
(7, 'กล้องวงจรปิด Hikvision DS-2CE', 'HK-2CE-9032', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 6 DAY), '10:20:00')),
(8, 'เครื่องถ่ายเอกสาร Ricoh MP2014', 'RC-MP2014-6620', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '15:10:00'));

INSERT INTO `{prefix}_repair` (`id`, `equipment_id`, `job_id`, `job_description`, `created_at`, `appointment_date`, `name`, `phone`, `address`, `provinceID`, `zipcode`, `appraiser`) VALUES
(1, 1, 'JOB0001', 'เปิดเครื่องไม่ติด มีเสียงร้องเป็นจังหวะ', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 18 DAY), '09:15:00'), DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'สมชาย ใจดี', '0812345671', '125/8 ถนนพระราม 9 แขวงห้วยขวาง', 10, '10310', 1500),
(2, 2, 'JOB0002', 'จอไม่ติด ต้องขยับฝาพับจึงจะเห็นภาพ', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 15 DAY), '10:05:00'), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'ปิยะ วงศ์สุวรรณ', '0898765432', '45 หมู่ 3 ถนนติวานนท์ ตำบลบางกระสอ', 12, '11000', 2800),
(3, 3, 'JOB0003', 'พิมพ์งานมีเส้น หมึกสีดำไม่ออก', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 12 DAY), '13:20:00'), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'มาลี ศรีสุข', '0865551234', '9/199 หมู่บ้านสวนธน ตำบลคลองหนึ่ง', 13, '12120', 900),
(4, 4, 'JOB0004', 'จอภาพมีเส้นแนวตั้งกลางจอ', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY), '11:00:00'), DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'อนันต์ พูนทรัพย์', '0819998877', '77 ซอยลาดพร้าว 101 แขวงคลองจั่น', 10, '10240', 1200),
(5, 5, 'JOB0005', 'เครื่องสำรองไฟมีเสียงร้องตลอดเวลา ใช้งานไม่ได้', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY), '09:45:00'), DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'กัลยา เรืองศรี', '0877776655', '302/17 ถนนสุขุมวิท ตำบลแสนสุข', 20, '20130', 3500),
(6, 6, 'JOB0006', 'แอร์ไม่เย็น มีน้ำหยดจากคอยล์เย็น', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '14:30:00'), DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'บริษัท ไทยรุ่งเรือง จำกัด', '021234567', '199 อาคารเอ็มไพร์ ชั้น 12 ถนนสาทรใต้', 10, '10120', 4500),
(7, 7, 'JOB0007', 'กล้องวงจรปิดตัวที่ 3 ภาพไม่ขึ้น', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 6 DAY), '10:20:00'), NULL, 'วีระ ทองแท้', '0834445566', '58/4 ถนนห้วยแก้ว ตำบลสุเทพ', 50, '50200', 2200),
(8, 8, 'JOB0008', 'เครื่องถ่ายเอกสารกระดาษติดบ่อย ดึงกระดาษไม่เข้า', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '15:10:00'), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'สุนีย์ แก้วมณี', '0856667788', '112 ถนนศรีจันทร์ ตำบลในเมือง', 40, '40000', 1800),
(9, 1, 'JOB0009', 'เครื่องรีสตาร์ทเองระหว่างใช้งาน', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '09:30:00'), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'ธนากร มั่นคง', '0843332211', '23/5 ถนนเพชรเกษม ตำบลห้วยจรเข้', 73, '73000', 750),
(10, 2, 'JOB0010', 'ชาร์จแบตไม่เข้า ไฟสถานะไม่ติด', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '16:40:00'), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'จิราพร แสงเดือน', '0827778899', '88 ซอยพหลโยธิน 24 แขวงจอมพล', 10, '10900', 0),
(11, 3, 'JOB0011', 'ไฟสถานะกระพริบสีส้ม เครื่องไม่ทำงาน', TIMESTAMP(CURDATE(), '09:10:00'), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'ประเสริฐ ดีงาม', '0891112233', '16/2 หมู่ 5 ตำบลบางรักน้อย', 12, '11110', 0),
(12, 5, 'JOB0012', 'UPS แจ้งเตือนแบตเตอรี่ ต้องการตรวจสอบด่วน', TIMESTAMP(CURDATE(), '10:35:00'), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'อารีย์ สมบูรณ์', '0862223344', '404 ถนนราชวิถี แขวงทุ่งพญาไท', 10, '10400', 0);

INSERT INTO `{prefix}_repair_status` (`id`, `repair_id`, `status`, `operator_id`, `comment`, `member_id`, `created_at`, `cost`) VALUES
(1, 1, 1, 0, 'รับเครื่องจากลูกค้า ตรวจสภาพภายนอกปกติ', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 18 DAY), '09:15:00'), 0),
(2, 1, 2, 3, 'มอบหมายให้ช่าง ตรวจสอบเบื้องต้นพบ Power Supply เสีย', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 17 DAY), '10:30:00'), 0),
(3, 1, 4, 3, 'เปลี่ยน Power Supply 500W ทดสอบใช้งานต่อเนื่อง 2 ชั่วโมง ปกติ', 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 15 DAY), '14:20:00'), 1500),
(4, 1, 8, 3, 'ลูกค้าชำระค่าซ่อมแล้ว ออกใบเสร็จเลขที่ RC-0001', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 14 DAY), '11:05:00'), 0),
(5, 1, 9, 3, 'ส่งมอบเครื่องคืนลูกค้าเรียบร้อย', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 14 DAY), '15:40:00'), 0),
(6, 2, 1, 0, 'รับเครื่องพร้อมอะแดปเตอร์ 1 ชิ้น', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 15 DAY), '10:05:00'), 0),
(7, 2, 2, 4, 'ถอดจอตรวจสอบ พบสายแพจอชำรุด', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 14 DAY), '09:40:00'), 0),
(8, 2, 3, 4, 'สั่งอะไหล่สายแพจอจากตัวแทนจำหน่าย รอของประมาณ 7 วัน', 4, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 12 DAY), '16:10:00'), 0),
(9, 3, 1, 0, 'รับเครื่องพิมพ์พร้อมสายไฟและสาย USB', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 12 DAY), '13:20:00'), 0),
(10, 3, 2, 5, 'ล้างหัวพิมพ์ด้วยน้ำยา กำลังทดสอบพิมพ์ต่อเนื่อง', 5, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 11 DAY), '10:15:00'), 0),
(11, 4, 1, 0, 'รับจอภาพ ตรวจสอบพบเส้นแนวตั้งกลางจอ', 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 10 DAY), '11:00:00'), 0),
(12, 4, 2, 3, 'ตรวจสอบพบพาเนลเสียหาย ค่าอะไหล่สูงกว่าราคาเครื่อง', 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY), '13:45:00'), 0),
(13, 4, 6, 3, 'เปลี่ยนจอภาพเครื่องใหม่ให้ลูกค้าตามเงื่อนไขประกัน', 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 8 DAY), '10:50:00'), 1200),
(14, 5, 1, 0, 'รับเครื่องสำรองไฟ ไม่มีอุปกรณ์อื่นแนบมา', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 9 DAY), '09:45:00'), 0),
(15, 5, 2, 4, 'ตรวจสอบแบตเตอรี่และบอร์ดควบคุม', 4, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 8 DAY), '11:30:00'), 0),
(16, 5, 5, 4, 'บอร์ดควบคุมเสียหาย ไม่มีอะไหล่ทดแทน แจ้งลูกค้าทราบแล้ว', 4, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 6 DAY), '14:00:00'), 0),
(17, 6, 1, 0, 'รับแจ้งซ่อมนอกสถานที่ นัดเข้าตรวจสอบหน้างาน', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '14:30:00'), 0),
(18, 6, 2, 5, 'เข้าหน้างาน ล้างแอร์และตรวจน้ำยา พบรอยรั่วที่ข้อต่อ', 5, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 6 DAY), '09:20:00'), 0),
(19, 6, 4, 5, 'เชื่อมข้อต่อใหม่ เติมน้ำยา R32 ทดสอบความเย็นปกติ', 5, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '16:00:00'), 4500),
(20, 6, 8, 5, 'ลูกค้าโอนชำระค่าบริการเรียบร้อย รอส่งมอบงาน', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 4 DAY), '10:10:00'), 0),
(21, 7, 1, 0, 'รับแจ้งกล้องวงจรปิดตัวที่ 3 ภาพไม่ขึ้น', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 6 DAY), '10:20:00'), 0),
(22, 7, 7, 0, 'ลูกค้าแจ้งยกเลิก ขอรับอุปกรณ์คืนก่อนดำเนินการซ่อม', 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '11:45:00'), 0),
(23, 8, 1, 0, 'รับเครื่องถ่ายเอกสารเข้าศูนย์', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '15:10:00'), 0),
(24, 8, 2, 3, 'ตรวจสอบพบชุดลูกยางดึงกระดาษสึกหรอ', 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 4 DAY), '09:50:00'), 0),
(25, 8, 3, 3, 'สั่งชุดลูกยางดึงกระดาษ รออะไหล่จากคลังกลาง', 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '14:35:00'), 0),
(26, 8, 2, 3, 'อะไหล่เข้าแล้ว กำลังเปลี่ยนชุดลูกยางและทำความสะอาดชุดป้อนกระดาษ', 3, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:25:00'), 0),
(27, 9, 1, 0, 'รับเครื่องพร้อมสายไฟ อาการรีสตาร์ทเอง', 1, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '09:30:00'), 0),
(28, 9, 2, 4, 'ทดสอบ RAM และฮาร์ดดิสก์ กำลังตรวจสอบระบบระบายความร้อนเพิ่มเติม', 4, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '13:15:00'), 0),
(29, 10, 1, 0, 'รับเครื่องพร้อมอะแดปเตอร์ ยังไม่ได้มอบหมายช่าง', 2, TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '16:40:00'), 0),
(30, 11, 1, 0, 'รับเครื่องพิมพ์เข้าระบบ รอมอบหมายช่าง', 2, TIMESTAMP(CURDATE(), '09:10:00'), 0),
(31, 12, 1, 4, 'รับแจ้งด่วน มอบหมายช่างเข้าตรวจสอบภายในวันนี้', 1, TIMESTAMP(CURDATE(), '10:35:00'), 0);

INSERT INTO `{prefix}_category` (`type`, `category_id`, `topic`, `color`, `is_active`) VALUES
('repairstatus', '1', 'แจ้งซ่อม', '#660000', 1),
('repairstatus', '2', 'กำลังดำเนินการ', '#120eeb', 1),
('repairstatus', '3', 'รออะไหล่', '#d940ff', 1),
('repairstatus', '4', 'ซ่อมสำเร็จ', '#06d628', 1),
('repairstatus', '5', 'ซ่อมไม่สำเร็จ', '#FF0000', 1),
('repairstatus', '6', 'เปลี่ยนสินค้าชิ้นใหม่', '#ff6969', 1),
('repairstatus', '7', 'ยกเลิกการซ่อม', '#FF6600', 1),
('repairstatus', '8', 'ชำระเงิน', '#006600', 1),
('repairstatus', '9', 'ส่งมอบสินค้าคืนลูกค้าเรียบร้อย', '#2A2A2A', 1);

INSERT INTO `{prefix}_number` (`type`, `prefix`, `auto_increment`, `updated_at`) VALUES
('JOB%04d', '', 12, CURDATE());
