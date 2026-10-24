<?php
/**
 * @filesource modules/repair/models/detail.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Detail;

/**
 * รายละเอียดงานซ่อมและประวัติการดำเนินการ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านรายละเอียดงานซ่อมพร้อมสถานะล่าสุด ไม่พบคืนค่า null
     *
     * @param int $id
     *
     * @return object|null
     */
    public static function get($id)
    {
        $result = static::createQuery()
            ->select(
                'R.*',
                'E.equipment',
                'E.serial',
                'S.status',
                'S.comment',
                'S.cost',
                'S.operator_id',
                'S.id status_id'
            )
            ->from('repair R')
            ->join([\Repair\Jobs\Model::latestStatusQuery(), 'T'], [['T.repair_id', 'R.id']], 'LEFT')
            ->join('repair_status S', [['S.id', 'T.max_id']], 'LEFT')
            ->join('repair_equipment E', [['E.id', 'R.equipment_id']], 'LEFT')
            ->where([['R.id', (int) $id]])
            ->first();

        return $result ?: null;
    }

    /**
     * อ่านรายละเอียดงานซ่อมจากเลขที่ใบรับซ่อม (สำหรับหน้าติดตามสถานะและใบรับซ่อม)
     * ไม่พบคืนค่า null
     *
     * @param string $job_id
     *
     * @return object|null
     */
    public static function getByJobId($job_id)
    {
        if ($job_id === '') {
            return null;
        }

        $result = static::createQuery()
            ->select(
                'R.*',
                'E.equipment',
                'E.serial',
                'S.status',
                'S.comment',
                'S.cost',
                'S.operator_id'
            )
            ->from('repair R')
            ->join([\Repair\Jobs\Model::latestStatusQuery(), 'T'], [['T.repair_id', 'R.id']], 'LEFT')
            ->join('repair_status S', [['S.id', 'T.max_id']], 'LEFT')
            ->join('repair_equipment E', [['E.id', 'R.equipment_id']], 'LEFT')
            ->where([['R.job_id', $job_id]])
            ->first();

        return $result ?: null;
    }

    /**
     * อ่านประวัติการดำเนินการทั้งหมดของงานซ่อม
     *
     * @param int $id
     *
     * @return array
     */
    public static function getAllStatus($id)
    {
        return static::createQuery()
            ->select('S.id', 'U.name', 'S.status', 'S.cost', 'S.created_at', 'S.comment')
            ->from('repair_status S')
            ->join('user U', [['U.id', 'S.operator_id']], 'LEFT')
            ->where([['S.repair_id', (int) $id]])
            ->orderBy('S.id')
            ->fetchAll(true);
    }

    /**
     * ประวัติการดำเนินการในรูปแบบที่เทมเพลตใช้ได้ทันที
     *
     * @param int $id
     * @param bool $canManage สิทธิ์ลบประวัติการดำเนินการ
     *
     * @return array
     */
    public static function timeline($id, $canManage = false)
    {
        $statuses = \Repair\Status\Model::map(false);
        $timeline = [];
        foreach (self::getAllStatus($id) as $item) {
            $timeline[] = [
                'id' => (int) $item['id'],
                'name' => (string) $item['name'],
                'status' => (int) $item['status'],
                'status_text' => \Repair\Status\Model::topicOf($statuses, $item['status']),
                'status_color' => \Repair\Status\Model::colorOf($statuses, $item['status']),
                'can_manage' => $canManage ? 1 : 0,
                'cost' => (float) $item['cost'],
                'cost_text' => empty($item['cost']) ? '' : \Kotchasan\Currency::format($item['cost']),
                'created_at' => $item['created_at'],
                'comment' => \Kotchasan\Text::untextarea((string) $item['comment'])
            ];
        }

        return $timeline;
    }

    /**
     * เพิ่มการดำเนินการใหม่ คืนค่า ID
     *
     * @param array $save
     *
     * @return int
     */
    public static function addStatus($save)
    {
        return \Kotchasan\DB::create()->insert('repair_status', $save);
    }

    /**
     * ลบการดำเนินการที่เลือก
     *
     * @param array $ids ID ของ repair_status
     *
     * @return int จำนวนรายการที่ลบ
     */
    public static function removeStatus(array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return 0;
        }

        return \Kotchasan\DB::create()->delete('repair_status', [['id', $ids]], 0);
    }
}
