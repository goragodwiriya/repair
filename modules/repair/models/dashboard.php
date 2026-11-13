<?php
/**
 * @filesource modules/repair/models/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Dashboard;

use Gcms\Api as ApiController;
use Kotchasan\Database\Sql;

/**
 * ข้อมูลสรุปงานซ่อมสำหรับหน้าแรก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * สรุปงานซ่อม
     * ช่างซ่อมที่ไม่ใช่เจ้าหน้าที่รับซ่อม จะเห็นเฉพาะงานที่มอบหมายให้ตัวเอง
     *
     * @param object $login
     *
     * @return array
     */
    public static function summary($login)
    {
        $isManager = ApiController::hasPermission($login, 'can_received_repair');
        $first_status = (int) self::$cfg->repair_first_status;
        $operator_id = $isManager ? 0 : (int) $login->id;

        return [
            'is_manager' => $isManager ? 1 : 0,
            'first_status' => $first_status,
            'today' => self::countNew($first_status, $operator_id),
            'mine' => self::countByOperator((int) $login->id),
            'total' => self::countAll($operator_id),
            'url' => '/repair-jobs'
        ];
    }

    /**
     * จำนวนงานซ่อมใหม่ของวันนี้ (สถานะล่าสุดยังเป็นสถานะเริ่มต้น)
     *
     * @param int $status
     * @param int $operator_id 0 = ทุกคน
     *
     * @return int
     */
    protected static function countNew($status, $operator_id = 0)
    {
        $where = [
            ['S.status', $status],
            [Sql::DATE('S.created_at'), date('Y-m-d')]
        ];
        if ($operator_id > 0) {
            $where[] = ['S.operator_id', [0, $operator_id]];
        }

        $result = static::createQuery()
            ->selectCount()
            ->from('repair_status S')
            ->join([\Repair\Jobs\Model::latestStatusQuery(), 'T'], [['T.repair_id', 'S.repair_id'], ['T.max_id', 'S.id']], 'INNER')
            ->where($where)
            ->first();

        return $result ? (int) $result->count : 0;
    }

    /**
     * จำนวนงานซ่อมที่มอบหมายให้สมาชิกคนนี้ (นับจากสถานะล่าสุด)
     *
     * @param int $operator_id
     *
     * @return int
     */
    protected static function countByOperator($operator_id)
    {
        if ($operator_id < 1) {
            return 0;
        }

        $result = static::createQuery()
            ->selectCount()
            ->from('repair_status S')
            ->join([\Repair\Jobs\Model::latestStatusQuery(), 'T'], [['T.repair_id', 'S.repair_id'], ['T.max_id', 'S.id']], 'INNER')
            ->where([['S.operator_id', (int) $operator_id]])
            ->first();

        return $result ? (int) $result->count : 0;
    }

    /**
     * จำนวนงานซ่อมทั้งหมดที่มองเห็นได้
     *
     * @param int $operator_id 0 = ทุกคน
     *
     * @return int
     */
    protected static function countAll($operator_id = 0)
    {
        $query = static::createQuery()
            ->selectCount()
            ->from('repair R');

        if ($operator_id > 0) {
            $query
                ->join([\Repair\Jobs\Model::latestStatusQuery(), 'T'], [['T.repair_id', 'R.id']], 'LEFT')
                ->join('repair_status S', [['S.id', 'T.max_id']], 'LEFT')
                ->where([['S.operator_id', [0, $operator_id]]]);
        }

        $result = $query->first();

        return $result ? (int) $result->count : 0;
    }
}
