<?php
/**
 * repair.php
 * ทางลัดของระบบเดิม (repair.php?id=<job_id>) ส่งต่อไปยังหน้าติดตามสถานะของ SPA
 *
 * @author Goragod Wiriya <admin@goragod.com>
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */
$id = isset($_GET['id']) ? preg_replace('/[^A-Za-z0-9\-_]/', '', $_GET['id']) : '';
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/').'/';

header('Location: '.$base.'repair-track'.($id === '' ? '' : '?id='.rawurlencode($id)), true, 301);
exit;
