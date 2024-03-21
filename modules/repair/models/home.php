<?php
/**
 * @filesource modules/repair/models/home.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Home;

use Kotchasan\Database\Sql;

/**
 * โมเดลสำหรับอ่านข้อมูลแสดงในหน้า  Home
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านจำนวนงานซ่อมใหม่วันนี้
     *
     * @return int
     */
    public static function getNew()
    {
        $status = isset(self::$cfg->repair_first_status) ? self::$cfg->repair_first_status : 1;
        $q1 = static::createQuery()
            ->select('repair_id', Sql::MAX('id', 'id'))
            ->from('repair_status')
            ->groupBy('repair_id');
        $search = static::createQuery()
            ->selectCount()
            ->from('repair_status S')
            ->join(array($q1, 'T'), 'INNER', array(array('T.repair_id', 'S.repair_id'), array('T.id', 'S.id')))
            ->where(array(
                array('S.status', $status),
                array(Sql::DATE('create_date'), date('Y-m-d'))
            ))
            ->toArray()
            ->execute();
        if (!empty($search)) {
            return $search[0]['count'];
        }
        return 0;
    }
}
