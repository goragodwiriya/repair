<?php
/**
 * modules/repair/install/upgrade.php — พาฐานเดิมมาถึงสคีมาของโมดูล repair
 *
 * install/upgrade_core.php เรียกไฟล์นี้ให้เองสำหรับทุกโมดูลที่มี ตัวแปรที่ใช้ได้
 * คือชุดเดียวกับที่ upgrade_core ใช้ : $db, $db_config, $prefix, $content, $config
 *
 * ⚠️ ตารางของโมดูลต้องปรับรุ่นที่นี่ ไม่ใช่ใน install/upgrade2.php ของโปรเจ็ค
 * เพื่อให้ "นิยามตาราง + การปรับรุ่น" ของโมดูลอยู่ด้วยกันที่เดียว — โมดูลถูก
 * คัดลอกไปโปรเจ็คใหม่แล้วใช้ได้ทันทีโดยไม่ต้องตามไปแก้ตัวปรับรุ่นของโปรเจ็คนั้น
 *
 * กฎเดียวกับ upgrade_core : ทุกเงื่อนไขถามว่า "ต้องแก้ไหม" ไม่ใช่ "ตอนนี้เป็นอะไร"
 * และห้าม DROP / RENAME ข้อมูลธุรกิจ
 */
if (!defined('ROOT_PATH')) {
    exit;
}

// =========================================================
// repair_equipment (ของเดิมชื่อ {prefix}_inventory)
// =========================================================
$table_repair_equipment = $prefix.'_repair_equipment';
$table_old_inventory = $prefix.'_inventory';
// ระบบเดิมเก็บทะเบียนเครื่องที่รับซ่อมไว้ในตาราง inventory
// เปลี่ยนชื่อตามหลักการตั้งชื่อ {prefix}_<module>_<name> กันชนกับโมดูล inventory
if (!$db->tableExists($table_repair_equipment) && $db->tableExists($table_old_inventory) && $db->fieldExists($table_old_inventory, 'equipment')) {
    $_rows = $db->customQuery("SELECT COUNT(*) AS `c` FROM `$table_old_inventory`");
    $db->query("RENAME TABLE `$table_old_inventory` TO `$table_repair_equipment`");
    // ตารางเดิมหายไปจากการนับแถว ต้องแจ้งว่า "ย้าย" ไม่ใช่ "หาย"
    // ไม่งั้นตัวปรับรุ่นจะรายงานว่าข้อมูลหายแล้วตัดสินว่าล้มเหลว ทั้งที่อยู่ครบ
    noteRowsMoved($table_old_inventory, $table_repair_equipment, empty($_rows) ? 0 : (int) $_rows[0]->c);
    $content[] = '<li class="correct">repair_equipment: เปลี่ยนชื่อตารางจาก inventory</li>';
}
// ⚠️ นิยามตารางอยู่ที่ install/database.sql ของโมดูลที่เดียว
// ensureTable อ่านจากไฟล์นั้น จึงไม่มีนิยามชุดที่สองให้ค่อย ๆ ต่างกัน
if (ensureTable($db, $prefix, $table_repair_equipment)) {
    $content[] = '<li class="correct">repair_equipment: สร้างตารางใหม่</li>';
} else {
    // create_date เดิมเป็น int (unix timestamp) → created_at datetime
    if (!$db->fieldExists($table_repair_equipment, 'created_at')) {
        $db->query("ALTER TABLE `$table_repair_equipment` ADD `created_at` DATETIME NULL");
        if ($db->fieldExists($table_repair_equipment, 'create_date')) {
            $db->query("UPDATE `$table_repair_equipment` SET `created_at` = FROM_UNIXTIME(`create_date`) WHERE `create_date` > 0");
        }
        $db->query("UPDATE `$table_repair_equipment` SET `created_at` = NOW() WHERE `created_at` IS NULL");
        $db->query("ALTER TABLE `$table_repair_equipment` MODIFY `created_at` DATETIME NOT NULL");
        $content[] = '<li class="correct">repair_equipment: เพิ่ม created_at</li>';
    }
    if ($db->fieldExists($table_repair_equipment, 'create_date')) {
        $db->query("ALTER TABLE `$table_repair_equipment` DROP COLUMN `create_date`");
        $content[] = '<li class="correct">repair_equipment: ลบ create_date</li>';
    }
    // isColumnType() ดูแค่ชนิดคอลัมน์ ไม่ดู DEFAULT จึงต้องอ่าน columnInfo() เพิ่ม
    $_col = columnInfo($db, $table_repair_equipment, 'serial');
    if (!$db->isColumnType($table_repair_equipment, 'serial', 'varchar(20)') || !$_col || $_col->Null === 'YES' || $_col->Default !== '') {
        $db->query("UPDATE `$table_repair_equipment` SET `serial` = '' WHERE `serial` IS NULL");
        $db->query("ALTER TABLE `$table_repair_equipment` CHANGE `serial` `serial` VARCHAR(20) NOT NULL DEFAULT ''");
        $content[] = '<li class="correct">repair_equipment: แก้ไข serial เป็น VARCHAR(20) NOT NULL DEFAULT \'\'</li>';
    }
    if (!$db->indexExists($table_repair_equipment, 'PRIMARY')) {
        $db->query("ALTER TABLE `$table_repair_equipment` ADD PRIMARY KEY (`id`)");
        $content[] = '<li class="correct">repair_equipment: เพิ่ม PRIMARY KEY</li>';
    }
    if (!isAutoIncrement($db, $table_repair_equipment, 'id')) {
        $db->query("ALTER TABLE `$table_repair_equipment` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
        $content[] = '<li class="correct">repair_equipment: กำหนด id เป็น AUTO_INCREMENT</li>';
    }
    // ⚠️ ของเดิมที่นี่สั่ง DELETE แถวที่ซ้ำทิ้งเองเพื่อให้สร้าง UNIQUE ได้
    // นั่นคือตัวปรับรุ่น "ลบข้อมูลของผู้ใช้" บนไซต์ที่เรามองไม่เห็นและกู้ให้ไม่ได้
    // ทะเบียนเครื่องที่ซ้ำอาจเป็นเครื่องคนละตัวที่กรอกเลขเครื่องพลาด ไม่ใช่ขยะ
    // ต้องหยุดแล้วบอกให้ผู้ดูแลตัดสินใจเอง แบบเดียวกับที่ preflight ทำกับ
    // คอลัมน์ที่กำลังจะเป็น UNIQUE (ชุดทดสอบ F4b คือกรณีนี้)
    if (!$db->indexExists($table_repair_equipment, 'equipment_serial')) {
        $_dup = $db->customQuery(
            "SELECT COUNT(*) AS `c` FROM (SELECT 1 FROM `$table_repair_equipment`
             GROUP BY `equipment`, `serial` HAVING COUNT(*) > 1) `x`"
        );
        if (!empty($_dup) && (int) $_dup[0]->c > 0) {
            $content[] = '<li class="warning">repair_equipment: มีคู่ (equipment, serial) ซ้ำอยู่ '
                .number_format((int) $_dup[0]->c).' คู่ จึงยังสร้างดัชนี equipment_serial แบบ UNIQUE ไม่ได้ '
                .'กรุณาตรวจว่าเป็นเครื่องคนละตัวที่กรอกเลขเครื่องซ้ำกันหรือไม่ '
                .'แก้ให้เหลือชุดเดียวแล้วกดปรับรุ่นอีกครั้ง — ตัวปรับรุ่นจะไม่ลบให้เอง</li>';
        } else {
            $db->query("ALTER TABLE `$table_repair_equipment` ADD UNIQUE KEY `equipment_serial` (`equipment`, `serial`)");
            $content[] = '<li class="correct">repair_equipment: เพิ่ม unique index equipment_serial</li>';
        }
    }
    if (!$db->indexExists($table_repair_equipment, 'serial')) {
        $db->query("ALTER TABLE `$table_repair_equipment` ADD INDEX `serial` (`serial`)");
        $content[] = '<li class="correct">repair_equipment: เพิ่ม index serial</li>';
    }
    // ⚠️ ต้องแปลง engine ด้วย ไม่ใช่แค่ charset — ของเดิมแปลงแต่ charset
    // ไซต์รุ่นเก่าจึงยังเป็น MyISAM ตลอดไป (ไม่มี transaction ไม่มี foreign key)
    if (convertToInnoDB($db, $table_repair_equipment)) {
        $content[] = '<li class="correct">repair_equipment: แปลงเป็น InnoDB</li>';
    }
    if (convertToUtf8mb4($db, $table_repair_equipment)) {
        $content[] = '<li class="correct">repair_equipment: แปลงเป็น utf8mb4</li>';
    }
    $content[] = '<li class="correct">repair_equipment อัปเกรดสำเร็จ</li>';
}

// =========================================================
// repair
// =========================================================
$table_repair = $prefix.'_repair';
// ⚠️ นิยามตารางอยู่ที่ install/database.sql ของโมดูลที่เดียว
// ensureTable อ่านจากไฟล์นั้น จึงไม่มีนิยามชุดที่สองให้ค่อย ๆ ต่างกัน
if (ensureTable($db, $prefix, $table_repair)) {
    $content[] = '<li class="correct">repair: สร้างตารางใหม่</li>';
} else {
    // inventory_id → equipment_id (ชี้ไปที่ repair_equipment)
    if (!$db->fieldExists($table_repair, 'equipment_id') && $db->fieldExists($table_repair, 'inventory_id')) {
        $db->query("ALTER TABLE `$table_repair` CHANGE `inventory_id` `equipment_id` INT(11) NOT NULL DEFAULT 0");
        $content[] = '<li class="correct">repair: เปลี่ยนชื่อ inventory_id → equipment_id</li>';
    } elseif (!$db->fieldExists($table_repair, 'equipment_id')) {
        $db->query("ALTER TABLE `$table_repair` ADD `equipment_id` INT(11) NOT NULL DEFAULT 0");
        $content[] = '<li class="correct">repair: เพิ่ม equipment_id</li>';
    }
    if ($db->fieldExists($table_repair, 'inventory_id')) {
        $db->query("ALTER TABLE `$table_repair` DROP COLUMN `inventory_id`");
        $content[] = '<li class="correct">repair: ลบ inventory_id</li>';
    }
    if ($db->fieldExists($table_repair, 'create_date')) {
        $db->query("ALTER TABLE `$table_repair` CHANGE `create_date` `created_at` DATETIME NOT NULL");
        $content[] = '<li class="correct">repair: เปลี่ยนชื่อ create_date → created_at</li>';
    }
    // คอลัมน์ทั้งชุดของตาราง — ต้องครบตาม modules/repair/install/database.sql
    //
    // ⚠️ ของเดิมที่นี่สั่ง CHANGE `provinceID` ตรง ๆ โดยไม่เคยตรวจว่ามีคอลัมน์นั้นไหม
    // ไซต์รุ่นเก่าที่สุด (ยังใช้ customer_id และยังไม่มีที่อยู่ลูกค้าในใบซ่อม)
    // จะล้มทันทีด้วย "Unknown column 'provinceID'" แล้วปรับรุ่นไม่ได้เลย
    // ensureColumn เพิ่มให้ถ้าไม่มี และบังคับชนิดให้ตรงถ้ามีอยู่แล้ว จบในตัวเดียว
    foreach ([
        'equipment_id' => ['int(11)', false, '0', 'id'],
        'job_id' => ['varchar(20)', false, null, 'equipment_id'],
        'job_description' => ['varchar(1000)', false, '', 'job_id'],
        'created_at' => ['datetime', false, null, 'job_description'],
        'appointment_date' => ['date', true, null, 'created_at'],
        'name' => ['varchar(150)', true, null, 'appointment_date'],
        'phone' => ['varchar(32)', true, null, 'name'],
        'address' => ['varchar(150)', true, null, 'phone'],
        'provinceID' => ['smallint(3)', true, null, 'address'],
        'zipcode' => ['varchar(10)', true, null, 'provinceID'],
        'appraiser' => ['float', false, '0', 'zipcode']
    ] as $_col => $_def) {
        if (ensureColumn($db, $table_repair, $_col, $_def[0], $_def[1], $_def[2], '', $_def[3])) {
            $content[] = '<li class="correct">repair: ปรับคอลัมน์ '.$_col.'</li>';
        }
    }
    if (!$db->indexExists($table_repair, 'PRIMARY')) {
        $db->query("ALTER TABLE `$table_repair` ADD PRIMARY KEY (`id`)");
        $content[] = '<li class="correct">repair: เพิ่ม PRIMARY KEY</li>';
    }
    if (!isAutoIncrement($db, $table_repair, 'id')) {
        $db->query("ALTER TABLE `$table_repair` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
        $content[] = '<li class="correct">repair: กำหนด id เป็น AUTO_INCREMENT</li>';
    }
    if (!$db->indexExists($table_repair, 'job_id')) {
        try {
            $db->query("ALTER TABLE `$table_repair` ADD UNIQUE KEY `job_id` (`job_id`)");
            $content[] = '<li class="correct">repair: เพิ่ม unique index job_id</li>';
        } catch (\Exception $exc) {
            $db->query("ALTER TABLE `$table_repair` ADD INDEX `job_id` (`job_id`)");
            $content[] = '<li class="incorrect">repair: มีเลขที่ใบรับซ่อมซ้ำ ใช้ index ปกติแทน unique ('.$exc->getMessage().')</li>';
        }
    }
    if (ensureIndexes($db, $table_repair, [
        'equipment_id' => '`equipment_id`',
        'name' => '`name`',
        'phone' => '`phone`',
        'created_at' => '`created_at`'
    ])) {
        $content[] = '<li class="correct">repair: ปรับดัชนี</li>';
    }
    if (convertToInnoDB($db, $table_repair)) {
        $content[] = '<li class="correct">repair: แปลงเป็น InnoDB</li>';
    }
    if (convertToUtf8mb4($db, $table_repair)) {
        $content[] = '<li class="correct">repair: แปลงเป็น utf8mb4</li>';
    }
    $content[] = '<li class="correct">repair อัปเกรดสำเร็จ</li>';
}

// =========================================================
// repair_status
// =========================================================
$table_repair_status = $prefix.'_repair_status';
// ⚠️ นิยามตารางอยู่ที่ install/database.sql ของโมดูลที่เดียว
// ensureTable อ่านจากไฟล์นั้น จึงไม่มีนิยามชุดที่สองให้ค่อย ๆ ต่างกัน
if (ensureTable($db, $prefix, $table_repair_status)) {
    $content[] = '<li class="correct">repair_status: สร้างตารางใหม่</li>';
} else {
    if ($db->fieldExists($table_repair_status, 'create_date')) {
        $db->query("ALTER TABLE `$table_repair_status` CHANGE `create_date` `created_at` DATETIME NOT NULL");
        $content[] = '<li class="correct">repair_status: เปลี่ยนชื่อ create_date → created_at</li>';
    }
    $_col = columnInfo($db, $table_repair_status, 'cost');
    if ($_col && ($_col->Null === 'YES' || $_col->Default !== '0')) {
        $db->query("UPDATE `$table_repair_status` SET `cost` = 0 WHERE `cost` IS NULL");
        $db->query("ALTER TABLE `$table_repair_status` CHANGE `cost` `cost` FLOAT NOT NULL DEFAULT 0");
        $content[] = '<li class="correct">repair_status: แก้ไข cost เป็น NOT NULL DEFAULT 0</li>';
    }
    $_col = columnInfo($db, $table_repair_status, 'operator_id');
    if ($_col && $_col->Default !== '0') {
        $db->query("UPDATE `$table_repair_status` SET `operator_id` = 0 WHERE `operator_id` IS NULL");
        $db->query("ALTER TABLE `$table_repair_status` CHANGE `operator_id` `operator_id` INT(11) NOT NULL DEFAULT 0");
        $content[] = '<li class="correct">repair_status: แก้ไข operator_id เป็น NOT NULL DEFAULT 0</li>';
    }
    $_col = columnInfo($db, $table_repair_status, 'member_id');
    if ($_col && $_col->Default !== '0') {
        $db->query("UPDATE `$table_repair_status` SET `member_id` = 0 WHERE `member_id` IS NULL");
        $db->query("ALTER TABLE `$table_repair_status` CHANGE `member_id` `member_id` INT(11) NOT NULL DEFAULT 0");
        $content[] = '<li class="correct">repair_status: แก้ไข member_id เป็น NOT NULL DEFAULT 0</li>';
    }
    $_col = columnInfo($db, $table_repair_status, 'status');
    if ($_col && $_col->Default !== '0') {
        $db->query("UPDATE `$table_repair_status` SET `status` = 0 WHERE `status` IS NULL");
        $db->query("ALTER TABLE `$table_repair_status` CHANGE `status` `status` TINYINT(2) NOT NULL DEFAULT 0");
        $content[] = '<li class="correct">repair_status: แก้ไข status เป็น NOT NULL DEFAULT 0</li>';
    }
    if (!$db->indexExists($table_repair_status, 'PRIMARY')) {
        $db->query("ALTER TABLE `$table_repair_status` ADD PRIMARY KEY (`id`)");
        $content[] = '<li class="correct">repair_status: เพิ่ม PRIMARY KEY</li>';
    }
    if (!isAutoIncrement($db, $table_repair_status, 'id')) {
        $db->query("ALTER TABLE `$table_repair_status` MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT");
        $content[] = '<li class="correct">repair_status: กำหนด id เป็น AUTO_INCREMENT</li>';
    }
    // index เดิมชื่อ repair_id ถูกแทนด้วย idx_repair (repair_id, id)
    if ($db->indexExists($table_repair_status, 'repair_id')) {
        $db->query("ALTER TABLE `$table_repair_status` DROP INDEX `repair_id`");
        $content[] = '<li class="correct">repair_status: ลบ index repair_id</li>';
    }
    if (!$db->indexExists($table_repair_status, 'idx_repair')) {
        $db->query("ALTER TABLE `$table_repair_status` ADD INDEX `idx_repair` (`repair_id`, `id`)");
        $content[] = '<li class="correct">repair_status: เพิ่ม index idx_repair</li>';
    }
    if (!$db->indexExists($table_repair_status, 'idx_status')) {
        $db->query("ALTER TABLE `$table_repair_status` ADD INDEX `idx_status` (`status`, `created_at`)");
        $content[] = '<li class="correct">repair_status: เพิ่ม index idx_status</li>';
    }
    if (!$db->indexExists($table_repair_status, 'operator_id')) {
        $db->query("ALTER TABLE `$table_repair_status` ADD INDEX `operator_id` (`operator_id`)");
        $content[] = '<li class="correct">repair_status: เพิ่ม index operator_id</li>';
    }
    // ⚠️ ต้องแปลง engine ด้วย ไม่ใช่แค่ charset — ของเดิมแปลงแต่ charset
    // ไซต์รุ่นเก่าจึงยังเป็น MyISAM ตลอดไป (ไม่มี transaction ไม่มี foreign key)
    if (convertToInnoDB($db, $table_repair_status)) {
        $content[] = '<li class="correct">repair_status: แปลงเป็น InnoDB</li>';
    }
    if (convertToUtf8mb4($db, $table_repair_status)) {
        $content[] = '<li class="correct">repair_status: แปลงเป็น utf8mb4</li>';
    }
    $content[] = '<li class="correct">repair_status อัปเกรดสำเร็จ</li>';
}

// =========================================================
// category: สถานะการซ่อม (type = repairstatus)
// =========================================================
$_status = $db->customQuery("SELECT COUNT(*) AS `count` FROM `$table_category` WHERE `type` = 'repairstatus'");
if (empty($_status) || (int) $_status[0]->count === 0) {
    $_seed = [
        ['1', 'แจ้งซ่อม', '#660000'],
        ['2', 'กำลังดำเนินการ', '#120eeb'],
        ['3', 'รออะไหล่', '#d940ff'],
        ['4', 'ซ่อมสำเร็จ', '#06d628'],
        ['5', 'ซ่อมไม่สำเร็จ', '#FF0000'],
        ['6', 'เปลี่ยนสินค้าชิ้นใหม่', '#ff6969'],
        ['7', 'ยกเลิกการซ่อม', '#FF6600'],
        ['8', 'ชำระเงิน', '#006600'],
        ['9', 'ส่งมอบสินค้าคืนลูกค้าเรียบร้อย', '#2A2A2A']
    ];
    foreach ($_seed as $_item) {
        $db->query("INSERT INTO `$table_category` (`type`, `category_id`, `language`, `topic`, `color`, `is_active`) VALUES ('repairstatus', '".$_item[0]."', '', '".$_item[1]."', '".$_item[2]."', 1)");
    }
    $content[] = '<li class="correct">category: เพิ่มสถานะการซ่อมเริ่มต้น</li>';
}
