<?php
/**
 * @filesource modules/repair/models/jobs.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Jobs;

use Kotchasan\Database\Sql;

/**
 * รายการงานซ่อม (เจ้าหน้าที่รับซ่อมและช่างซ่อม)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * Query หา id ของสถานะล่าสุดของแต่ละงานซ่อม
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function latestStatusQuery()
    {
        return static::createQuery()
            ->select('repair_id', Sql::MAX('id', 'max_id'))
            ->from('repair_status')
            ->groupBy('repair_id');
    }

    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param array $params
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    public static function toDataTable($params)
    {
        $where = [];
        // ช่างซ่อมส่งมาเป็น array [0, id ของตัวเอง] เจ้าหน้าที่ส่งมาเป็น id เดียว
        if (isset($params['operator_id']) && (is_array($params['operator_id']) ? !empty($params['operator_id']) : $params['operator_id'] > 0)) {
            $where[] = ['S.operator_id', $params['operator_id']];
        }
        if (isset($params['status']) && $params['status'] > -1) {
            $where[] = ['S.status', (int) $params['status']];
        }
        if (!empty($params['from'])) {
            $where[] = [Sql::DATE('R.created_at'), '>=', $params['from']];
        }
        if (!empty($params['to'])) {
            $where[] = [Sql::DATE('R.created_at'), '<=', $params['to']];
        }

        $query = static::createQuery()
            ->select(
                'R.id',
                'R.job_id',
                'R.name',
                'R.phone',
                'E.equipment',
                'R.created_at',
                'R.appointment_date',
                'S.operator_id',
                'S.status'
            )
            ->from('repair R')
            ->join([self::latestStatusQuery(), 'T'], [['T.repair_id', 'R.id']], 'LEFT')
            ->join('repair_status S', [['S.id', 'T.max_id']], 'LEFT')
            ->join('repair_equipment E', [['E.id', 'R.equipment_id']], 'LEFT')
            ->where($where);

        if (!empty($params['search'])) {
            $keyword = '%'.$params['search'].'%';
            $query->where([
                ['R.name', 'LIKE', $keyword],
                ['R.phone', 'LIKE', $keyword],
                ['R.job_id', 'LIKE', $keyword],
                ['E.equipment', 'LIKE', $keyword]
            ], 'OR');
        }

        return $query;
    }

    /**
     * ลบงานซ่อมพร้อมประวัติการดำเนินการ
     *
     * @param array $ids
     *
     * @return int จำนวนงานซ่อมที่ลบ
     */
    public static function remove(array $ids)
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return 0;
        }

        $db = \Kotchasan\DB::create();
        $count = $db->delete('repair', [['id', $ids]], 0);
        $db->delete('repair_status', [['repair_id', $ids]], 0);

        return $count;
    }
}
