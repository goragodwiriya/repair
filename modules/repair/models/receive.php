<?php
/**
 * @filesource modules/repair/models/receive.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Receive;

/**
 * ใบรับซ่อม (เพิ่ม/แก้ไข)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านใบรับซ่อมที่เลือก
     * $id = 0 คือรายการใหม่ ไม่พบคืนค่า null
     *
     * @param int   $id
     * @param array $defaults ค่าเริ่มต้นของรายการใหม่ (provinceID, zipcode)
     *
     * @return object|null
     */
    public static function get($id, $defaults = [])
    {
        $id = (int) $id;

        if ($id === 0) {
            return (object) [
                'id' => 0,
                'job_id' => '',
                'name' => '',
                'phone' => '',
                'address' => '',
                'provinceID' => isset($defaults['provinceID']) ? $defaults['provinceID'] : 10,
                'zipcode' => isset($defaults['zipcode']) ? $defaults['zipcode'] : 10000,
                'equipment' => '',
                'serial' => '',
                'job_description' => '',
                'created_at' => date('Y-m-d'),
                'appointment_date' => date('Y-m-d'),
                'appraiser' => 0,
                'comment' => '',
                'status_id' => 0
            ];
        }

        $result = \Repair\Detail\Model::get($id);
        if ($result === null) {
            return null;
        }

        return (object) [
            'id' => (int) $result->id,
            'job_id' => (string) $result->job_id,
            'name' => (string) $result->name,
            'phone' => (string) $result->phone,
            'address' => (string) $result->address,
            'provinceID' => (int) $result->provinceID,
            'zipcode' => (string) $result->zipcode,
            'equipment' => (string) $result->equipment,
            'serial' => (string) $result->serial,
            'job_description' => \Kotchasan\Text::untextarea((string) $result->job_description),
            'created_at' => \Kotchasan\Date::format($result->created_at, 'Y-m-d'),
            'appointment_date' => (string) $result->appointment_date,
            'appraiser' => (float) $result->appraiser,
            'comment' => \Kotchasan\Text::untextarea((string) $result->comment),
            'status_id' => (int) $result->status_id
        ];
    }

    /**
     * ออกเลขที่ใบรับซ่อม
     * ถ้าไม่ได้กำหนดรูปแบบไว้จะสุ่มเลขที่ให้ (ตรวจสอบซ้ำก่อนคืนค่า)
     *
     * @return string
     */
    public static function generateJobId()
    {
        $format = (string) self::$cfg->repair_job_no;
        if ($format !== '' && $format !== '%00d') {
            // running number ตามรูปแบบที่ตั้งค่าไว้
            return \Index\Number\Model::get(0, $format, 'repair', 'job_id', (string) self::$cfg->repair_prefix);
        }

        // สุ่มเลขที่ใบรับซ่อม
        $db = \Kotchasan\DB::create();
        do {
            $job_id = strtoupper(substr(uniqid(), 0, 10));
        } while ($db->first('repair', [['job_id', $job_id]]));

        return $job_id;
    }

    /**
     * บันทึกใบรับซ่อมใหม่ คืนค่า ID ของงานซ่อม
     * (ชื่อเมธอดต้องไม่ชนกับ Kotchasan\Model::create())
     *
     * @param array  $repair  ข้อมูลของ repair (ยังไม่มี job_id, created_at)
     * @param string $comment หมายเหตุของผู้รับซ่อม
     * @param int    $member_id
     *
     * @return array [id, job_id]
     */
    public static function createJob($repair, $comment, $member_id)
    {
        $db = \Kotchasan\DB::create();
        $repair['job_id'] = self::generateJobId();
        $repair['created_at'] = empty($repair['created_at']) ? date('Y-m-d H:i:s') : $repair['created_at'];

        $id = (int) $db->insert('repair', $repair);

        $db->insert('repair_status', [
            'repair_id' => $id,
            'member_id' => $member_id,
            'operator_id' => 0,
            'status' => (int) self::$cfg->repair_first_status,
            'comment' => $comment,
            'cost' => 0,
            'created_at' => $repair['created_at']
        ]);

        return [$id, $repair['job_id']];
    }

    /**
     * แก้ไขใบรับซ่อม พร้อมปรับหมายเหตุของสถานะล่าสุด
     * (ชื่อเมธอดต้องไม่ชนกับ Kotchasan\Model::update())
     *
     * @param object $index   ข้อมูลเดิม (ต้องมี id และ status_id)
     * @param array  $repair  ข้อมูลที่แก้ไข
     * @param string $comment หมายเหตุ
     * @param int    $member_id
     *
     * @return void
     */
    public static function updateJob($index, $repair, $comment, $member_id)
    {
        $db = \Kotchasan\DB::create();
        $db->update('repair', [['id', (int) $index->id]], $repair);

        $log = [
            'member_id' => $member_id,
            'comment' => $comment
        ];
        if (empty($index->status_id)) {
            // ยังไม่มีประวัติการดำเนินการ (ข้อมูลเก่าที่ไม่สมบูรณ์) สร้างให้ใหม่
            $log['repair_id'] = (int) $index->id;
            $log['operator_id'] = 0;
            $log['status'] = (int) self::$cfg->repair_first_status;
            $log['cost'] = 0;
            $log['created_at'] = date('Y-m-d H:i:s');
            $db->insert('repair_status', $log);
        } else {
            // แก้ไขหมายเหตุของสถานะล่าสุด (ไม่แตะ operator_id เพื่อไม่ให้ผู้รับผิดชอบหลุด)
            $db->update('repair_status', [['id', (int) $index->status_id]], $log);
        }
    }
}
