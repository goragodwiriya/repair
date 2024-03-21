<?php
/**
 * @filesource modules/repair/models/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Export;

/**
 * รับงานซ่อม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านรายละเอียดการทำรายการจาก $job_id
     * สำหรับการออกใบรับซ่อม
     *
     * @param string $job_id
     *
     * @return object
     */
    public static function get($job_id)
    {
        $sql = static::createQuery()
            ->select('R.*', 'V.equipment', 'V.serial', 'S.status', 'S.comment', 'S.operator_id')
            ->from('repair R')
            ->join('repair_status S', 'LEFT', array('S.repair_id', 'R.id'))
            ->join('inventory V', 'LEFT', array('V.id', 'R.inventory_id'))
            ->where(array('R.job_id', $job_id))
            ->order('S.id ASC');

        return static::createQuery()
            ->from(array($sql, 'Q'))
            ->groupBy('Q.id')
            ->first();
    }
}
